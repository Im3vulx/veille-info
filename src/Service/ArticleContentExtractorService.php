<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ArticleContentExtractorService
{
    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger
    ) {}

    public function extract(string $url): ?string
    {
        try {
            $response = $this->client->request('GET', $url, [
                'headers' => ['User-Agent' => 'Mozilla/5.0 (compatible; VeilleInfoBot/1.0)'],
                'timeout' => 10,
            ]);

            $html = $response->getContent(false);

            return $this->extractMainContent($html);
        } catch (\Throwable $e) {
            $this->logger->warning('Scraping article failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    private function extractMainContent(string $html): ?string
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);

        $xpath = new \DOMXPath($dom);

        $queries = [
            '//article',
            '//*[@role="main"]',
            '//div[contains(@class,"article-content")]',
            '//div[contains(@class,"post-content")]',
            '//div[contains(@class,"entry-content")]',
            '//div[contains(@class,"content")]',
        ];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes->length > 0) {
                return trim(strip_tags(
                    $dom->saveHTML($nodes->item(0)),
                    '<p><h1><h2><h3><ul><li><strong><em><img><a>'
                ));
            }
        }

        return null;
    }
}
