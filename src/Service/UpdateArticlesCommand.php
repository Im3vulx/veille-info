<?php

namespace App\Command;

use App\Entity\Article;
use App\Service\ArticleContentExtractorService;
use App\Service\AIClassifierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateArticlesCommand extends Command
{
    protected static $defaultName = 'app:update-articles';
    protected static $defaultDescription = 'Met à jour le contenu et l’image des anciens articles';

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
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $articles = $this->em->getRepository(Article::class)->findAll();

        foreach ($articles as $article) {
            /** @var Article $article */
            $output->writeln("Updating article: {$article->getTitle()}");

            // 1️⃣ Contenu complet
            $fullContent = null;
            if (!empty($article->getLink())) {
                $fullContent = $this->contentExtractor->extract($article->getLink());
                if ($fullContent) {
                    $article->setContent($fullContent);
                }
            }

            // 2️⃣ Classification IA avec le contenu complet
            $classification = $this->aiClassifier->classify(
                $article->getTitle(),
                $fullContent ?: $article->getSummary()
            );

            $slug = $classification['category'] ?? null;
            $category = $article->getCategory();

            if ($slug) {
                $slug = strtolower(trim($slug));
                $slug = str_replace(' ', '-', $slug);
                if (!in_array($slug, $this->slugs)) {
                    $slug = $this->slugMapping[$slug] ?? null;
                }
                if ($slug) {
                    $cat = $this->em->getRepository('App:Category')->findOneBy(['slug' => $slug]);
                    if ($cat) $category = $cat;
                }
            }

            if (!$category) {
                $category = $this->em->getRepository('App:Category')->findOneBy(['slug' => 'autres']);
            }

            $article->setCategory($category);

            // 3️⃣ Image fallback
            if (!$article->getImageUrl()) {
                $article->setImageUrl(null);
            }

            $article->setUpdatedAt(new \DateTimeImmutable());

            $this->em->persist($article);
        }

        $this->em->flush();

        $output->writeln("All articles updated ✅");

        return Command::SUCCESS;
    }
}
