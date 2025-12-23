<?php

namespace App\Service;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIClassifierService
{
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(
        private HttpClientInterface $client,
        private string $groqApiKey,
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private string $googleApiKey,
    ) {}

    /**
     * Retourne UNIQUEMENT un slug existant
     * @param string $title
     * @param string $content Le contenu complet de l'article (ou résumé si pas dispo)
     */
    public function classify(string $title, string $content): array
    {
        $rows = $this->em->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->select('c.slug')
            ->getQuery()
            ->getScalarResult();

        $allowed = array_map(fn($r) => strtolower($r['slug']), $rows);

        if (empty($allowed)) {
            return ['category' => null, 'subcategory' => null, 'is_promo' => false];
        }

        $prompt = <<<TXT
Choisis UNE catégorie parmi cette liste (slugs uniquement) :
{$this->list($allowed)}

Renvoie uniquement le slug exact ou "autres".

Titre : $title
Contenu : $content
TXT;

        try {
            $response = $this->client->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->googleApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'openai/gpt-oss-safeguard-20b',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Classificateur strict'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.0,
                    'max_tokens' => 64
                ]
            ]);

            $data = json_decode($response->getContent(false), true);
            $raw = strtolower(trim($data['choices'][0]['message']['content'] ?? ''));

            $raw = str_replace(' ', '-', $raw);

            $category = in_array($raw, $allowed, true) ? $raw : null;

            $isPromo = preg_match(
                '#\b(black friday|promo|soldes|discount|offre|deal)\b#i',
                $title . ' ' . $content
            );

            return [
                'category' => $category,
                'subcategory' => null,
                'is_promo' => (bool) $isPromo
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('AI classify failed', [
                'exception' => $e->getMessage()
            ]);

            return ['category' => null, 'subcategory' => null, 'is_promo' => false];
        }
    }

    private function list(array $items): string
    {
        return implode(', ', $items);
    }
}
