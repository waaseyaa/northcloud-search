<?php

declare(strict_types=1);

namespace App\Provider;

use Waaseyaa\Database\DatabaseInterface;
use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;
use Waaseyaa\Routing\RouteBuilder;
use Waaseyaa\Routing\WaaseyaaRouter;
use Waaseyaa\Search\Fts5\Fts5SearchIndexer;
use Waaseyaa\Search\Fts5\Fts5SearchProvider;
use Waaseyaa\Search\SearchIndexerInterface;
use Waaseyaa\Search\SearchProviderInterface;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(SearchIndexerInterface::class, function () {
            $database = $this->resolve(DatabaseInterface::class);
            return new Fts5SearchIndexer($database);
        });

        $this->singleton(SearchProviderInterface::class, function () {
            $database = $this->resolve(DatabaseInterface::class);
            $indexer = $this->resolve(SearchIndexerInterface::class);
            return new Fts5SearchProvider($database, $indexer);
        });
    }

    public function boot(): void
    {
        $indexer = $this->resolve(SearchIndexerInterface::class);
        if ($indexer instanceof Fts5SearchIndexer) {
            $indexer->ensureSchema();
        }
    }

    public function routes(WaaseyaaRouter $router, ?\Waaseyaa\Entity\EntityTypeManager $entityTypeManager = null): void
    {
        $router->addRoute('home', RouteBuilder::create('/')
            ->controller('App\\Controller\\HomeController::index')
            ->methods('GET')
            ->render()
            ->allowAll()
            ->build());

        $router->addRoute('search', RouteBuilder::create('/search')
            ->controller('App\\Controller\\SearchController::results')
            ->methods('GET')
            ->render()
            ->allowAll()
            ->build());

        $router->addRoute('content.show', RouteBuilder::create('/content/{id}')
            ->controller('App\\Controller\\ContentController::show')
            ->methods('GET')
            ->render()
            ->allowAll()
            ->build());

        $router->addRoute('suggest', RouteBuilder::create('/api/suggest')
            ->controller('App\\Controller\\SuggestController::suggest')
            ->methods('GET')
            ->allowAll()
            ->build());

        $router->addRoute('health', RouteBuilder::create('/health')
            ->controller('App\\Controller\\HealthController::check')
            ->methods('GET')
            ->allowAll()
            ->build());
    }

    public function commands(
        \Waaseyaa\Entity\EntityTypeManager $entityTypeManager,
        \Waaseyaa\Database\DatabaseInterface $database,
        \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher,
    ): array {
        $indexer = new Fts5SearchIndexer($database);
        $indexer->ensureSchema();

        return [
            new \App\Command\SubscribeCommand($indexer),
        ];
    }
}
