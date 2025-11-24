<?php

namespace App\Command;

use App\Entity\Feed;
use App\Service\ArticleFetcherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fetch-articles',
    description: 'Importe les articles depuis tous les flux RSS/JSON enregistrés.',
)]
class FetchArticlesCommand extends Command
{
    public function __construct(
        private ArticleFetcherService $fetcher,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $feeds = $this->em->getRepository(Feed::class)->findAll();

        if (!$feeds) {
            $io->warning('Aucun flux trouvé dans la table Feed.');
            return Command::SUCCESS;
        }

        foreach ($feeds as $feed) {
            try {
                $this->fetcher->fetchFromFeed($feed);
                $io->success('Articles importés depuis : ' . $feed->getUrl());
            } catch (\Throwable $e) {
                $io->error('Erreur pour le flux ' . $feed->getUrl());
                $io->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
