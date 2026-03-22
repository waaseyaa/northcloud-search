<?php

declare(strict_types=1);

namespace App\Provider;

use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;
use Waaseyaa\Routing\RouteBuilder;
use Waaseyaa\Routing\WaaseyaaRouter;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(\App\Support\NorthCloudClient::class, \App\Support\NorthCloudClient::class);
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
}
