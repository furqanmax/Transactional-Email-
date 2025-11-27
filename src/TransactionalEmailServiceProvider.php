<?php

namespace Furqanmax\TransactionalEmail;

use Illuminate\Support\ServiceProvider;

class TransactionalEmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/transactional-email.php', 'transactional-email');

        $this->app->singleton(TransactionalEmailClient::class, function ($app) {
            $config = $app['config']->get('transactional-email', []);

            $baseUrl = (string)($config['base_url'] ?? 'http://127.0.0.1:8000/api');
            $endpoints = (array)($config['endpoints'] ?? []);
            $http = (array)($config['http'] ?? []);
            $appId = isset($config['app_id']) ? (string)$config['app_id'] : null;
            $credentials = isset($config['credentials']) && is_array($config['credentials'])
                ? $config['credentials']
                : null;

            return new TransactionalEmailClient(
                baseUrl: $baseUrl,
                endpoints: $endpoints,
                httpConfig: $http,
                credentials: $credentials,
                appId: $appId
            );
        });

        // Backwards-compatible string accessor
        $this->app->alias(TransactionalEmailClient::class, 'transactional-email');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/transactional-email.php' => config_path('transactional-email.php'),
        ], 'config');
    }
}
