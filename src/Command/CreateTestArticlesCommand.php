<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-articles',
    description: 'Crée des articles de test pour le développement',
)]
class CreateTestArticlesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Créer des catégories si elles n'existent pas
        $categories = $this->createCategories();

        // Données d'articles de test
        $articlesData = [
            [
                'title' => 'Les tendances de l\'IA en 2024',
                'summary' => 'Découvrez les dernières avancées en intelligence artificielle et leurs impacts sur le développement logiciel.',
                'content' => '<h2>Introduction</h2><p>L\'intelligence artificielle continue d\'évoluer à un rythme rapide en 2024. Les nouvelles technologies comme GPT-4, Claude et d\'autres modèles de langage transforment la façon dont nous développons des applications.</p><h2>Les principales tendances</h2><ul><li>IA générative pour le code</li><li>Automatisation des tests</li><li>Optimisation des performances</li><li>Développement assisté par IA</li></ul><h2>Impact sur le développement</h2><p>Ces technologies permettent aux développeurs de se concentrer sur des tâches plus créatives et complexes, tout en automatisant les tâches répétitives.</p>',
                'category' => 'Intelligence Artificielle',
                'authorName' => 'Marie Dubois',
                'imageUrl' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&h=400&fit=crop'
            ],
            [
                'title' => 'Symfony 7 : Les nouveautés',
                'summary' => 'Explorez les nouvelles fonctionnalités de Symfony 7 et comment migrer vos projets existants.',
                'content' => '<h2>Symfony 7 est arrivé !</h2><p>La nouvelle version majeure de Symfony apporte de nombreuses améliorations en termes de performance et de sécurité.</p><h2>Nouvelles fonctionnalités</h2><ul><li>Support PHP 8.2+</li><li>Nouveau système de cache</li><li>Amélioration du debug</li><li>Nouvelles annotations</li></ul><h2>Migration</h2><p>La migration depuis Symfony 6 est relativement simple grâce aux outils fournis par l\'équipe Symfony.</p>',
                'category' => 'Développement Web',
                'authorName' => 'Thomas Martin',
                'imageUrl' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=800&h=400&fit=crop'
            ],
            [
                'title' => 'Cybersécurité : Bonnes pratiques',
                'summary' => 'Apprenez les bonnes pratiques essentielles pour sécuriser vos applications web modernes.',
                'content' => '<h2>La sécurité avant tout</h2><p>Dans un monde numérique en constante évolution, la sécurité des applications est plus importante que jamais.</p><h2>Bonnes pratiques</h2><ul><li>Validation des entrées utilisateur</li><li>Chiffrement des données sensibles</li><li>Authentification multi-facteurs</li><li>Audits de sécurité réguliers</li></ul><h2>Outils recommandés</h2><p>Utilisez des outils comme OWASP ZAP, SonarQube et des scanners de vulnérabilités pour maintenir un niveau de sécurité élevé.</p>',
                'category' => 'Sécurité',
                'authorName' => 'Sophie Bernard',
                'imageUrl' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800&h=400&fit=crop'
            ],
            [
                'title' => 'Docker pour les développeurs',
                'summary' => 'Maîtrisez Docker pour simplifier le déploiement et le développement de vos applications.',
                'content' => '<h2>Qu\'est-ce que Docker ?</h2><p>Docker est une plateforme de conteneurisation qui permet de packager une application avec toutes ses dépendances.</p><h2>Avantages</h2><ul><li>Environnements reproductibles</li><li>Déploiement simplifié</li><li>Isolation des applications</li><li>Scalabilité</li></ul><h2>Exemples pratiques</h2><p>Nous verrons comment conteneuriser une application Symfony et la déployer avec Docker Compose.</p>',
                'category' => 'DevOps',
                'authorName' => 'Lucas Moreau',
                'imageUrl' => 'https://images.unsplash.com/photo-1605745341112-85968b19335b?w=800&h=400&fit=crop'
            ],
            [
                'title' => 'React vs Vue.js : Comparaison',
                'summary' => 'Découvrez les différences entre React et Vue.js pour choisir le framework frontend adapté à vos besoins.',
                'content' => '<h2>React</h2><p>Développé par Facebook, React est un framework JavaScript populaire pour créer des interfaces utilisateur interactives.</p><h2>Vue.js</h2><p>Vue.js est un framework progressif qui peut être adopté de manière incrémentale.</p><h2>Comparaison</h2><ul><li>Courbe d\'apprentissage</li><li>Performance</li><li>Écosystème</li><li>Communauté</li></ul><h2>Recommandation</h2><p>Le choix dépend de vos besoins spécifiques et de l\'expérience de votre équipe.</p>',
                'category' => 'Frontend',
                'authorName' => 'Emma Rousseau',
                'imageUrl' => 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?w=800&h=400&fit=crop'
            ],
            [
                'title' => 'API REST avec Symfony',
                'summary' => 'Créez des APIs REST robustes et performantes avec Symfony et les bonnes pratiques.',
                'content' => '<h2>Qu\'est-ce qu\'une API REST ?</h2><p>Une API REST est un style d\'architecture pour les systèmes distribués, particulièrement adapté aux applications web.</p><h2>Bonnes pratiques</h2><ul><li>Utilisation des codes de statut HTTP appropriés</li><li>Versioning des APIs</li><li>Documentation avec OpenAPI</li><li>Tests automatisés</li></ul><h2>Implémentation avec Symfony</h2><p>Symfony offre des outils puissants pour créer des APIs REST, notamment avec le composant Serializer et API Platform.</p>',
                'category' => 'Backend',
                'authorName' => 'Pierre Durand',
                'imageUrl' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800&h=400&fit=crop'
            ]
        ];

        $createdCount = 0;
        foreach ($articlesData as $articleData) {
            $category = $categories[$articleData['category']] ?? $categories['Développement Web'];

            $article = new Article();
            $article->setTitle($articleData['title']);
            $article->setSummary($articleData['summary']);
            $article->setContent($articleData['content']);
            $article->setCategory($category);
            $article->setAuthorName($articleData['authorName']);
            $article->setImageUrl($articleData['imageUrl']);
            $article->setPublished(true);
            $article->setCreatedAt(new \DateTimeImmutable());
            $article->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($article);
            $createdCount++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d articles de test ont été créés avec succès !', $createdCount));

        return Command::SUCCESS;
    }

    private function createCategories(): array
    {
        $categoriesData = [
            'Intelligence Artificielle' => 'ai',
            'Développement Web' => 'web',
            'Sécurité' => 'security',
            'DevOps' => 'devops',
            'Frontend' => 'frontend',
            'Backend' => 'backend',
            'Mobile' => 'mobile'
        ];

        $categories = [];
        foreach ($categoriesData as $name => $slug) {
            $existingCategory = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);

            if (!$existingCategory) {
                $category = new Category();
                $category->setName($name);
                $category->setSlug($slug);
                $category->setIconName('default');

                $this->entityManager->persist($category);
                $categories[$name] = $category;
            } else {
                $categories[$name] = $existingCategory;
            }
        }

        $this->entityManager->flush();
        return $categories;
    }
}
