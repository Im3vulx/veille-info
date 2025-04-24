<?php

namespace App\Command;

use App\Service\ArticleFetcherService;
use App\Repository\FeedRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:sync-articles')]
class SyncArticlesCommand extends Command
{
    public function __construct(
        private ArticleFetcherService $fetcher,
        private FeedRepository $feedRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('📡 Synchronisation des articles...');

        $feeds = $this->feedRepository->findAll();

        foreach ($feeds as $feed) {
            $this->fetcher->fetchFromFeed($feed);
        }

        $output->writeln('✅ Articles importés avec succès.');
        return Command::SUCCESS;
    }
}
