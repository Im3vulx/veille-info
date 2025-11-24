<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIClassifierService
{
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(private HttpClientInterface $client, private string $groqApiKey, private LoggerInterface $logger) {}

    /**
     * Classify an article into one of the allowed categories.
     */
    public function classify(string $title, string $summary): string
    {
        $allowedCategories = [
            'programmation',
            'ai',
            'jeux-video',
            'espace',
            'handball',
            'sante',
            'plante',
            'automobile',
            'autres', // default
        ];

        $prompt = "
        Tu es un modèle de classification.
        Classe cet article dans UNE des catégories suivantes : " . implode(", ", $allowedCategories) . ".
        Renvoie uniquement le slug de la catégorie. Si tu n'es pas sûr, renvoie 'autres'.

        Titre : $title
        Résumé : $summary
    ";

        try {
            $response = $this->client->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'openai/gpt-oss-safeguard-20b',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es un classificateur d’articles, renvoie uniquement le slug.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.0
                ]
            ]);

            $data = json_decode($response->getContent(), true);
            $category = trim(strtolower($data['choices'][0]['message']['content'] ?? ''));

            return in_array($category, $allowedCategories) ? $category : 'autres';
        } catch (\Throwable $e) {
            $this->logger->warning('Groq AI classify failed, using fallback', [
                'title' => $title,
                'summary' => $summary,
                'exception' => $e->getMessage(),
            ]);
            return 'autres';
        }
    }
}
