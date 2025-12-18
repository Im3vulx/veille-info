<?php

namespace App\Service;

use App\Entity\Feed;
use App\Entity\Article;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ArticleFetcherService
{
    public function __construct(private EntityManagerInterface $em, private AIClassifierService $aiclassifier, private LoggerInterface $logger, private HttpClientInterface $httpClient) {}

    public function fetchFromFeed(Feed $feed): void
    {
        $this->logger->info('Starting fetch for feed', ['url' => $feed->getUrl()]);

        $fallbackCategory = null;
        if ($feed->getCategorySlug()) {
            $fallbackCategory = $this->getOrCreateCategory($feed->getCategorySlug());
        }

        try {
            if ($feed->getType() === 'rss') {
                $this->fetchFromRss($feed->getUrl(), $fallbackCategory);
            } elseif ($feed->getType() === 'json') {
                $this->fetchFromJson($feed->getUrl(), $fallbackCategory);
            } else {
                $this->logger->warning('Unknown feed type', ['type' => $feed->getType(), 'url' => $feed->getUrl()]);
            }

            if ($this->em->isOpen()) {
                $this->em->flush();
            } else {
                $this->logger->error('EntityManager closed before flush');
            }
        } catch (\Throwable $e) {
            $this->logger->error('fetchFromFeed failed', ['exception' => $e->getMessage(), 'url' => $feed->getUrl()]);
        }
    }

    public function fetchFromRss(string $url, ?Category $fallbackCategory): void
    {
        $this->logger->info('Fetching RSS', ['url' => $url]);

        $context = stream_context_create([
            'http' => ['header' => "User-Agent: veille-info-bot/1.0\r\n"]
        ]);

        $rssContent = @file_get_contents($url, false, $context);
        if (!$rssContent) {
            $this->logger->error('RSS content empty or cannot be fetched', ['url' => $url]);
            return;
        }

        $rss = @simplexml_load_string($rssContent);
        if (!$rss) {
            $this->logger->error('Cannot parse RSS', ['url' => $url]);
            return;
        }

        $items = $rss->channel->item ?? $rss->item ?? [];
        foreach ($items as $item) {
            $this->handleRssItem($item, $fallbackCategory, $url);
        }
    }

    public function fetchFromJson(string $url, ?Category $fallbackCategory): void
    {
        $this->logger->info('Fetching JSON feed', ['url' => $url]);

        $json = @file_get_contents($url);
        if (!$json) {
            $this->logger->error('Impossible de charger le flux JSON', ['url' => $url]);
            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->logger->error('Impossible de parser le flux JSON', ['url' => $url]);
            return;
        }

        foreach ($data['articles'] ?? [] as $item) {
            try {
                $payload = [
                    'guid' => $item['guid'] ?? $item['link'] ?? null,
                    'link' => $item['link'] ?? null,
                    'title' => $item['title'] ?? '',
                    'summary' => $item['summary'] ?? '',
                    'content' => $item['content'] ?? ($item['summary'] ?? ''),
                    'pubDateStr' => $item['createdAt'] ?? null,
                    'imageUrl' => $item['image'] ?? null,
                    'fallbackCategory' => $fallbackCategory,
                ];
                $this->processArticleData($payload);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to process JSON item', [
                    'exception' => $e->getMessage(),
                    'item' => $item['title'] ?? $item['link'] ?? null
                ]);
            }
        }
    }

    private function handleRssItem(\SimpleXMLElement $item, ?Category $fallbackCategory, string $feedUrl): void
    {
        try {
            $payload = [
                'guid' => (string)($item->guid ?? $item->link ?? ''),
                'link' => (string)($item->link ?? ''),
                'title' => (string)($item->title ?? ''),
                'summary' => isset($item->description) ? strip_tags((string)$item->description) : '',
                'content' => isset($item->{'content:encoded'}) ? (string)$item->{'content:encoded'} : (isset($item->description) ? strip_tags((string)$item->description) : ''),
                'pubDateStr' => (string)($item->pubDate ?? null),
                'imageUrl' => $this->extractImageUrl((string)($item->{'content:encoded'} ?? ''), $item),
                'fallbackCategory' => $fallbackCategory,
            ];
            $this->processArticleData($payload);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to handle RSS item', ['exception' => $e->getMessage(), 'feed' => $feedUrl]);
        }
    }

    private function processArticleData(array $payload): void
    {
        $identifier = $payload['guid'] ?? $payload['link'] ?? null;
        if (!$identifier) return;

        if ($this->isBannedArticle($payload)) return;

        $existing = $this->em->getRepository(Article::class)->findOneBy(['guid' => $identifier]);
        if ($existing) return;

        $summary = $payload['summary'] ?? '';
        $content = $payload['content'] ?? $summary;
        if ($content === $summary || strlen(strip_tags($content)) < 100) {
            $content = $this->extractFullContent($summary, $payload['link'] ?? null);
        }

        try {
            $classifyResult = $this->aiclassifier->classify($payload['title'] ?? '', $summary);
            if (!is_array($classifyResult)) {
                $classifyResult = ['category' => null, 'subcategory' => null, 'is_promo' => false];
            }
        } catch (\Throwable $e) {
            $classifyResult = ['category' => null, 'subcategory' => null, 'is_promo' => false];
        }

        if (!empty($classifyResult['is_promo'])) return;

        // --- Auto category with priority: slug of feed -> IA -> "autres" ---
        $category = null;

        if (!empty($payload['fallbackCategory'])) {
            $category = $payload['fallbackCategory'];
        }

        if (!$category && !empty($classifyResult['category'])) {
            $slug = strtolower($classifyResult['category']);
            $category = $this->getOrCreateCategory($slug);
        }

        if (!$category) {
            $category = $this->getOrCreateCategory('autres');
        }

        if (!$category) return;

        $article = new Article();
        $article->setGuid($identifier);
        $article->setTitle($this->sanitizeText($payload['title'] ?? ''));
        $article->setSummary($this->sanitizeText($summary));
        $article->setContent($this->sanitizeText($content));
        $article->setCreatedAt($this->parseDate($payload['pubDateStr'] ?? null));
        $article->setUpdatedAt($this->parseDate($payload['pubDateStr'] ?? null));
        $article->setCategory($category);
        $article->setPublished(true);

        if (!empty($payload['imageUrl']) && is_string($payload['imageUrl'])) {
            try {
                $article->setImageUrl((string)$payload['imageUrl']);
            } catch (\TypeError $e) {
                $this->logger->warning('setImageUrl TypeError, skipping image', ['exception' => $e->getMessage()]);
            }
        }

        $this->em->persist($article);
        $this->logger->info('Article persisted', ['title' => $article->getTitle(), 'guid' => $identifier]);
    }

    private function getOrCreateCategory(string $slug, ?Category $parent = null): ?Category
    {
        $slug = strtolower($slug);
        $category = $this->em->getRepository(Category::class)->findOneBy(['slug' => $slug]);
        if ($category) return $category;

        try {
            $cat = new Category();
            $cat->setName($this->slugToReadableName($slug));
            $cat->setSlug($slug);
            $cat->setIconName('default');

            if ($parent) {
                $cat->setParent($parent);
            }

            $this->em->persist($cat);
            return $cat;
        } catch (\Throwable $e) {
            $this->logger->error('getOrCreateCategory failed', ['slug' => $slug, 'exception' => $e->getMessage()]);
            return null;
        }
    }

    private function isBannedArticle(array $payload): bool
    {
        $title = strtolower($payload['title'] ?? '');
        $summary = strtolower($payload['summary'] ?? '');
        $bannedKeywords = ['black friday', 'promotion', 'soldes', 'promo', 'discount', 'offre', 'deal'];

        foreach ($bannedKeywords as $kw) {
            if (str_contains($title, $kw) || str_contains($summary, $kw)) {
                return true;
            }
        }

        return false;
    }

    private function extractFullContent(string $summary, ?string $link): string
    {
        if (!$link) return $summary;
        try {
            $html = @file_get_contents($link);
            if (!$html) return $summary;

            preg_match_all('#<p[^>]*>(.*?)</p>#is', $html, $matches);
            if (!empty($matches[1])) {
                $extracted = implode("\n\n", array_map('strip_tags', $matches[1]));
                if (strlen($extracted) > strlen($summary)) {
                    return $extracted;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->debug('extractFullContent exception', ['exception' => $e->getMessage()]);
        }
        return $summary;
    }

    private function extractImageUrl(string $content, $item): ?string
    {
        if (isset($item->enclosure) && isset($item->enclosure['type']) && strpos((string)$item->enclosure['type'], 'image/') === 0) {
            return (string)$item->enclosure['url'];
        }
        if (isset($item->image)) return (string)$item->image;
        if (preg_match('/<img[^>]+src=[\"\\\']([^\"\\\']+)[\"\\\']/i', $content, $m)) return $m[1];
        return null;
    }

    private function sanitizeText(?string $text): string
    {
        if (!$text) return '';
        $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $clean = preg_replace('/[\\x00-\\x1F\\x7F]/u', '', $clean);
        return trim($clean);
    }

    private function parseDate(?string $dateStr): \DateTimeImmutable
    {
        if (!$dateStr) return new \DateTimeImmutable();
        try {
            return new \DateTimeImmutable($dateStr);
        } catch (\Throwable $e) {
            $this->logger->warning('parseDate failed, using now', ['dateStr' => $dateStr, 'exception' => $e->getMessage()]);
            return new \DateTimeImmutable();
        }
    }

    private function slugToReadableName(string $slug): string
    {
        $name = str_replace(['-', '_'], ' ', $slug);
        return ucwords($name);
    }
}
