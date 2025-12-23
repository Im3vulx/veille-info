<?php

namespace App\Service;

use App\Entity\Feed;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AtlasFeedImporterService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ArticleFetcherService $articleFetcher
    ) {}

    public function importFeedsFromAtlas(): void
    {
        $url = 'https://atlasflux.saynete.net/atlasdesflux_blog.xml';

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            foreach ($data['feeds'] ?? [] as $feedData) {
                $this->processFeed($feedData);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch Atlas feeds', [
                'exception' => $e->getMessage()
            ]);
        }

        $this->em->flush();
    }

    private function processFeed(array $feedData): void
    {
        $feedUrl = $feedData['url'] ?? null;
        if (!$feedUrl) {
            return;
        }

        $existingFeed = $this->em
            ->getRepository(Feed::class)
            ->findOneBy(['url' => $feedUrl]);

        if ($existingFeed) {
            $this->logger->info('Feed already exists', ['url' => $feedUrl]);
            return;
        }

        $feed = new Feed();
        $feed->setUrl($feedUrl);
        $feed->setType($feedData['type'] ?? 'rss');
        $feed->setCategorySlug($feedData['category_slug'] ?? null);

        $this->em->persist($feed);

        $this->logger->info('Feed imported', [
            'url' => $feedUrl,
            'category' => $feed->getCategorySlug()
        ]);

        // Fetch immédiat des articles
        $this->articleFetcher->fetchFromFeed($feed);
    }
}
