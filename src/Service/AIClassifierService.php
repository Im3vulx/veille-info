<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Category;

class AIClassifierService
{
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(
        private HttpClientInterface $client,
        private string $groqApiKey,
        private LoggerInterface $logger,
        private EntityManagerInterface $em
    ) {}

    /**
     * Classify an article into one of the existing categories.
     * IMPORTANT: this method will only return slugs that already exist in the DB.
     * Return format: ['category' => string|null, 'subcategory' => string|null, 'is_promo' => bool]
     */
    public function classify(string $title, string $summary): array
    {
        // build allowed slugs dynamically from DB (white list)
        $rows = $this->em->getRepository(Category::class)->createQueryBuilder('c')
            ->select('c.slug')
            ->getQuery()
            ->getScalarResult();

        $allowed = array_map(fn($r) => strtolower($r['slug']), $rows);
        if (empty($allowed)) {
            // fallback minimal list
            $allowed = ['programmation', 'ai', 'jeux-video', 'espace', 'handball', 'autres'];
        }

        $prompt = "Tu es un modèle de classification.\n" .
            "Choisis une seule catégorie parmi la liste suivante (slugs) : " . implode(', ', $allowed) . ".\n" .
            "Renvoie uniquement le slug exact si tu es sûr, sinon renvoie 'autres'.\n\n" .
            "Titre : $title\nRésumé : $summary\n";

        try {
            $response = $this->client->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'openai/gpt-oss-safeguard-20b',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es un classificateur d’articles, renvoie uniquement le slug (ou "autres").'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.0,
                    'max_tokens' => 64
                ]
            ]);

            $data = json_decode($response->getContent(false), true);
            $raw = trim(strtolower($data['choices'][0]['message']['content'] ?? ''));

            // sometimes the model returns JSON or additional text — try to extract slug-like token
            if (preg_match('/([a-z0-9\-_ ]+)/i', $raw, $m)) {
                $candidate = strtolower(trim($m[1]));
            } else {
                $candidate = $raw;
            }

            // normalize candidate (replace spaces by '-')
            $candidate = str_replace(' ', '-', $candidate);

            $category = in_array($candidate, $allowed) ? $candidate : null;

            // simple promo detection
            $lower = strtolower($title . ' ' . $summary);
            $isPromo = (bool) preg_match('#\b(black ?friday|promotion|soldes|promo|discount|deal|offre)\b#i', $lower);

            return ['category' => $category, 'subcategory' => null, 'is_promo' => $isPromo];
        } catch (\Throwable $e) {
            $this->logger->warning('Groq AI classify failed, using fallback', [
                'title' => $title,
                'summary' => $summary,
                'exception' => $e->getMessage(),
            ]);
            return ['category' => null, 'subcategory' => null, 'is_promo' => false];
        }
    }
}
