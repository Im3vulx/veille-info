<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Feed;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ArticleFetcherService
{
    private array $slugs = [
        'ai',
        'web',
        'security',
        'devops',
        'frontend',
        'backend',
        'mobile',
        'programmation',
        'espace',
        'handball',
        'jeux-video',
        'sante',
        'plante',
        'automobile',
        'autres'
    ];

    private array $slugMapping = [
        'auto' => 'automobile',
        'car' => 'automobile',
        'renault' => 'automobile',
        'peugeot' => 'automobile',
        'bmw' => 'automobile',
        'mercedes' => 'automobile',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private AIClassifierService $aiClassifier,
        private ArticleContentExtractorService $contentExtractor,
        private LoggerInterface $logger
    ) {}

    public function fetchFromFeed(Feed $feed): void
    {
        $fallbackCategory = null;

        if ($feed->getCategorySlug()) {
            $fallbackCategory = $this->em
                ->getRepository(Category::class)
                ->findOneBy(['slug' => strtolower($feed->getCategorySlug())]);
        }

        if ($feed->getType() === 'rss') {
            $this->fetchFromRss($feed->getUrl(), $fallbackCategory);
        }
    }

    private function fetchFromRss(string $url, ?Category $fallbackCategory): void
    {
        $rss = @simplexml_load_file($url, null, LIBXML_NOCDATA);
        if (!$rss) {
            $this->logger->warning('RSS illisible', ['url' => $url]);
            return;
        }

        foreach ($rss->channel->item ?? [] as $item) {
            $this->processArticleData([
                'guid' => (string) ($item->guid ?? $item->link),
                'link' => (string) ($item->link),
                'title' => (string) ($item->title),
                'summary' => strip_tags((string) ($item->description ?? '')),
                'image' => $this->extractImageFromItem($item),
                'fallbackCategory' => $fallbackCategory,
            ]);
        }

        $this->em->flush();
    }

    private function extractImageFromItem(\SimpleXMLElement $item): ?string
    {
        if (isset($item->enclosure)) {
            $attrs = $item->enclosure->attributes();
            if (isset($attrs['url'])) {
                return (string) $attrs['url'];
            }
        }

        $namespaces = $item->getNamespaces(true);
        if (isset($namespaces['media'])) {
            $media = $item->children($namespaces['media']);
            if (isset($media->content)) {
                $attrs = $media->content->attributes();
                if (isset($attrs['url'])) return (string) $attrs['url'];
            }
            if (isset($media->thumbnail)) {
                $attrs = $media->thumbnail->attributes();
                if (isset($attrs['url'])) return (string) $attrs['url'];
            }
        }

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $item->description, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function processArticleData(array $data): void
    {
        if (empty($data['guid'])) return;

        if ($this->em->getRepository(Article::class)->findOneBy(['guid' => $data['guid']])) {
            return;
        }

        $classification = $this->aiClassifier->classify($data['title'], $data['summary']);

        if ($classification['is_promo']) return;

        // catégorie
        $category = $data['fallbackCategory'] ?? null;
        $slug = $classification['category'] ?? null;

        if ($slug) {
            $slug = strtolower(trim($slug));
            $slug = str_replace(' ', '-', $slug);
            if (!in_array($slug, $this->slugs)) {
                $slug = $this->slugMapping[$slug] ?? null;
            }
            if ($slug) {
                $category = $this->em->getRepository(Category::class)->findOneBy(['slug' => $slug]);
            }
        }

        if (!$category) {
            $category = $this->em->getRepository(Category::class)->findOneBy(['slug' => 'autres']);
        }

        if (!$category) return;

        $now = new \DateTimeImmutable();

        $fullContent = null;
        if (!empty($data['link'])) {
            $fullContent = $this->contentExtractor->extract($data['link']);
        }

        $article = new Article();
        $article->setGuid($data['guid']);
        $article->setTitle($data['title']);
        $article->setSummary($data['summary']);
        $article->setContent($fullContent ?: $data['summary']);
        $article->setCategory($category);
        $article->setImageUrl($data['image'] ?? null);
        $article->setPublished(true);
        $article->setCreatedAt($now);
        $article->setUpdatedAt($now);

        $this->em->persist($article);
    }
}
