<?php

namespace App\Service;

use App\Entity\Feed;
use App\Entity\Article;
use App\Entity\Category;
use App\Service\AIClassifierService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ArticleFetcherService
{
    public function __construct(private EntityManagerInterface $em, private AIClassifierService $aiclassifier, private LoggerInterface $logger) {}

    public function fetchFromFeed(Feed $feed): void
    {
        $url = $feed->getUrl();
        $categorySlug = $feed->getCategorySlug();
        $category = $this->em->getRepository(Category::class)->findOneBy(['slug' => $categorySlug]);

        if ($feed->getType() === 'rss') {
            $this->fetchFromRss($url, $category);
        } elseif ($feed->getType() === 'json') {
            $this->fetchFromJson($url, $category);
        }

        $this->em->flush();
    }

    public function fetchFromRss(string $url, ?Category $category): void
    {
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: veille-info-bot/1.0\r\n"
            ]
        ]);

        $rssContent = @file_get_contents($url, false, $context);
        if (!$rssContent) {
            $this->logger->error("RSS content empty for URL: $url");
            return;
        }

        $rss = @simplexml_load_string($rssContent);
        if (!$rss) {
            $this->logger->error("Cannot parse RSS feed: $url");
            return;
        }

        foreach ($rss->channel->item as $item) {
            try {
                $guid = isset($item->guid) ? (string) $item->guid : null;
                $link = isset($item->link) ? (string) $item->link : null;
                $title = isset($item->title) ? (string) $item->title : '';
                $summary = isset($item->description) ? strip_tags((string) $item->description) : '';
                $content = isset($item->{'content:encoded'}) ? (string) $item->{'content:encoded'} : $summary;
                $pubDateStr = isset($item->pubDate) ? (string) $item->pubDate : null;

                $imageUrl = $this->extractImageUrl($content, $item);

                $payload = [
                    'guid' => $guid,
                    'link' => $link,
                    'title' => $title,
                    'summary' => $summary,
                    'content' => $content,
                    'pubDateStr' => $pubDateStr,
                    'imageUrl' => $imageUrl,
                    'fallbackCategory' => $category,
                ];

                $this->processArticleData($payload);
            } catch (\Throwable $e) {
                $this->logger->error("Failed to process RSS item", [
                    'title' => $item->title ?? null,
                    'link' => $item->link ?? null,
                    'exception' => $e->getMessage(),
                ]);
                continue;
            }
        }
    }

    public function fetchFromJson(string $url, ?Category $category): void
    {
        $json = @file_get_contents($url);
        if (!$json) {
            $this->logger->error("Impossible de charger le flux JSON : $url");
            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->logger->error("Impossible de parser le flux JSON : $url");
            return;
        }

        foreach ($data['articles'] ?? [] as $item) {
            try {
                $identifier = $item['guid'] ?? $item['link'] ?? null;
                $title = $item['title'] ?? '';
                $summary = $item['summary'] ?? '';
                $content = $item['content'] ?? $summary;
                $pubDateStr = $item['createdAt'] ?? 'now';
                $imageUrl = $item['image'] ?? null;

                $payload = [
                    'guid' => $identifier,
                    'link' => $item['link'] ?? null,
                    'title' => $title,
                    'summary' => $summary,
                    'content' => $content,
                    'pubDateStr' => $pubDateStr,
                    'imageUrl' => $imageUrl,
                    'fallbackCategory' => $category,
                ];

                $this->processArticleData($payload);
            } catch (\Throwable $e) {
                $this->logger->error("Failed to process JSON item", [
                    'title' => $item['title'] ?? null,
                    'link' => $item['link'] ?? null,
                    'exception' => $e->getMessage(),
                ]);
                continue;
            }
        }
    }

    private function extractFullContent(string $summary, ?string $link): string
    {
        if (!$link) return $summary;

        try {
            $html = @file_get_contents($link);
            if (!$html) return $summary;

            preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $matches);
            if (!empty($matches[1])) {
                $extracted = implode("\n\n", array_map('strip_tags', $matches[1]));
                if (strlen($extracted) > strlen($summary)) {
                    return $extracted;
                }
            }
        } catch (\Throwable $e) {
            return $summary;
        }

        return $summary;
    }

    private function extractImageUrl(string $content, $item): ?string
    {
        if (isset($item->enclosure) && isset($item->enclosure['type']) && strpos((string)$item->enclosure['type'], 'image/') === 0) {
            return (string) $item->enclosure['url'];
        } elseif (isset($item->image)) {
            return (string) $item->image;
        } else {
            preg_match('/<img.*?src=["\'](.*?)["\']/', $content, $matches);
            return $matches[1] ?? null;
        }
    }

    private function resolveCategory(string $title, string $summary, ?Category $fallbackCategory): ?Category
    {
        $promoKeywords = ['black friday', 'promotion', 'soldes', 'promo', 'discount', 'offre'];
        $titleLower = strtolower($title);
        foreach ($promoKeywords as $kw) {
            if (str_contains($titleLower, $kw)) {
                return null;
            }
        }

        $predictedSlug = $this->aiclassifier->classify($title, $summary);

        // Si l'IA ne renvoie rien, utiliser la catégorie "autres"
        if (!$predictedSlug) {
            $predictedSlug = 'autres';
        }

        $predictedSlug = substr(trim(strtolower($predictedSlug)), 0, 100);

        return $this->getOrCreateCategory($predictedSlug, $fallbackCategory);
    }

    private function getOrCreateCategory(string $slug, ?Category $fallbackCategory): ?Category
    {
        $category = $this->em->getRepository(Category::class)->findOneBy(['slug' => $slug]);
        if (!$category) {
            $category = new Category();
            $name = ucwords(str_replace(['-', '_'], ' ', $slug));
            if (strlen($name) > 50) $name = substr($name, 0, 50);

            $category->setName($name);
            $category->setSlug($slug);
            $category->setIconName('default');

            // Optional: fallback parent
            if ($fallbackCategory) {
                $category->setParent($fallbackCategory);
            }

            $this->em->persist($category);
        }

        return $category ?? $fallbackCategory;
    }

    private function processArticleData(array $payload): void
    {
        $identifier = $payload['guid'] ?? $payload['link'] ?? null;
        if (!$identifier) return;

        if ($this->isBannedArticle($payload)) {
            return;
        }

        $existingArticle = $this->em->getRepository(Article::class)->findOneBy(['guid' => $identifier]);
        if ($existingArticle) return;

        $summary = $payload['summary'] ?? '';
        $content = $payload['content'] ?? $summary;
        if ($content === $summary) {
            $content = $this->extractFullContent($summary, $payload['link'] ?? null);
        }

        $category = $this->resolveCategory($payload['title'] ?? '', $summary, $payload['fallbackCategory'] ?? null);
        if (!$category) return;

        $pubDate = isset($payload['pubDateStr']) && $payload['pubDateStr'] ? new \DateTimeImmutable($payload['pubDateStr']) : new \DateTimeImmutable();

        $article = new Article();
        $article->setGuid($identifier);
        $article->setTitle($this->sanitizeText($payload['title'] ?? ''));
        $article->setSummary($this->sanitizeText($summary));
        $article->setContent($this->sanitizeText($content));

        // Vérification imageUrl
        if (!empty($payload['imageUrl']) && is_string($payload['imageUrl'])) {
            $article->setImageUrl($payload['imageUrl']);
        }

        $article->setCreatedAt($pubDate);
        $article->setUpdatedAt($pubDate);
        $article->setCategory($category);
        $article->setPublished(true);

        $this->em->persist($article);
    }

    private function isBannedArticle(array $payload): bool
    {
        $title = strtolower($payload['title'] ?? '');
        $summary = strtolower($payload['summary'] ?? '');
        $bannedKeywords = ['black friday', 'promotion', 'soldes', 'promo', 'discount', 'offre'];

        foreach ($bannedKeywords as $kw) {
            if (str_contains($title, $kw) || str_contains($summary, $kw)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeText(?string $text): string
    {
        if (!$text) return '';
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }
}
