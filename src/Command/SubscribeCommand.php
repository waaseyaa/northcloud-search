<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\ContentDocument;
use Predis\Client as RedisClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Waaseyaa\Search\Fts5\Fts5SearchIndexer;
use Waaseyaa\Search\SearchIndexerInterface;

final class SubscribeCommand extends Command
{
    private int $indexed = 0;
    private int $errors = 0;

    public function __construct(
        private readonly SearchIndexerInterface $indexer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:subscribe')
            ->setDescription('Subscribe to North Cloud Redis pub/sub and index content into FTS5')
            ->addOption('redis-url', null, InputOption::VALUE_REQUIRED, 'Redis URL', 'tcp://127.0.0.1:6379')
            ->addOption('channels', null, InputOption::VALUE_REQUIRED, 'Channel pattern (comma-separated)', 'content:*');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $redisUrl = $input->getOption('redis-url') ?: getenv('REDIS_URL') ?: 'tcp://127.0.0.1:6379';
        $channelPatterns = explode(',', $input->getOption('channels'));

        if ($this->indexer instanceof Fts5SearchIndexer) {
            $this->indexer->ensureSchema();
        }

        $output->writeln(sprintf('<info>Connecting to Redis at %s...</info>', $redisUrl));
        $output->writeln(sprintf('<info>Subscribing to: %s</info>', implode(', ', $channelPatterns)));

        $redis = new RedisClient($redisUrl);

        $pubsub = $redis->pubSubLoop();
        foreach ($channelPatterns as $pattern) {
            $pubsub->psubscribe(trim($pattern));
        }

        $output->writeln('<info>Listening for content...</info>');

        /** @var object $message */
        foreach ($pubsub as $message) {
            if ($message->kind !== 'pmessage') {
                continue;
            }

            $this->processMessage($message->payload, $message->channel, $output);

            if (($this->indexed + $this->errors) % 100 === 0 && ($this->indexed + $this->errors) > 0) {
                $output->writeln(sprintf(
                    '<comment>Stats: %d indexed, %d errors</comment>',
                    $this->indexed,
                    $this->errors,
                ));
            }
        }

        return Command::SUCCESS;
    }

    private function processMessage(string $payload, string $channel, OutputInterface $output): void
    {
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            $this->errors++;
            $output->writeln('<error>Invalid JSON received</error>');
            return;
        }

        $id = $data['id'] ?? null;
        if ($id === null) {
            $this->errors++;
            return;
        }

        try {
            $doc = ContentDocument::fromRedisMessage($data);
            $this->indexer->index($doc);
            $this->indexed++;

            $output->writeln(sprintf(
                '  <info>[%s]</info> %s — %s',
                $data['content_type'] ?? 'unknown',
                mb_substr($data['title'] ?? '(no title)', 0, 60),
                $channel,
            ), OutputInterface::VERBOSITY_VERBOSE);
        } catch (\Throwable $e) {
            $this->errors++;
            $output->writeln(sprintf('<error>Index error for %s: %s</error>', $id, $e->getMessage()));
        }
    }
}
