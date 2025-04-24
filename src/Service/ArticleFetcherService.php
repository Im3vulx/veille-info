<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Feed;
use Doctrine\ORM\EntityManagerInterface;

class ArticleFetcherService
{
    public function __construct(private EntityManagerInterface $em) {}

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
    }

    public function fetchFromRss(string $url, ?Category $category): void
    {
        $rss = @simplexml_load_file($url);

        if (!$rss) {
            throw new \RuntimeException("Impossible de charger le flux RSS : $url");
        }

        foreach ($rss->channel->item as $item) {
            $title = (string) $item->title;
            $summary = strip_tags((string) $item->description);
            $pubDate = new \DateTimeImmutable((string) $item->pubDate);

            if (!$this->em->getRepository(Article::class)->findOneBy(['title' => $title])) {
                $article = new Article();
                $article->setTitle($title);
                $article->setSummary($summary);
                $article->setContent($summary);
                $article->setImageUrl((string) $item->image ?? null);
                $article->setCreatedAt($pubDate);
                $article->setUpdatedAt($pubDate);
                $article->setCategory($category);
                $article->setPublished(true);

                $this->em->persist($article);
            }
        }

        $this->em->flush();
    }

    public function fetchFromJson(string $url, ?Category $category): void
    {
        $json = file_get_contents($url);
        $data = json_decode($json, true);

        foreach ($data['articles'] ?? [] as $item) {
            $title = $item['title'] ?? '';
            if (!$this->em->getRepository(Article::class)->findOneBy(['title' => $title])) {
                $article = new Article();
                $article->setTitle($title);
                $article->setSummary($item['summary'] ?? '');
                $article->setContent($item['content'] ?? '');
                $article->setImageUrl($item['image'] ?? null);
                $article->setCreatedAt(new \DateTimeImmutable($item['createdAt'] ?? 'now'));
                $article->setUpdatedAt(new \DateTimeImmutable($item['updatedAt'] ?? 'now'));
                $article->setCategory($category);
                $article->setPublished(true);

                $this->em->persist($article);
            }
        }

        $this->em->flush();
    }
}
