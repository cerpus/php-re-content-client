<?php

namespace Cerpus\REContentClient;

use Cerpus\Helper\Clients\CachingAuthClient;
use GuzzleHttp\Client;
use Illuminate\Log\Logger;
use Illuminate\Support\ServiceProvider;


class REContentClientProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__."/../config/re-content-index.php", "re-content-index");
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__."/../config/re-content-index.php" => config_path("re-content-index.php"),
        ]);

        $this->app->singleton(ContentClient::class, function () {
            /** @var CachingAuthClient $ccToken */
            $ccToken = app(CachingAuthClient::class)->fetchCCToken();

            $httpClient = new Client([
                "base_uri" => config("re-content-index.content-index-url"),
                "headers" => [
                    "Authorization" => "Bearer $ccToken",
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                ],
            ]);

            try {
                $logger = app(Logger::class);
            } catch (\Throwable $t) {
                $logger = null;
            }

            return new ContentClient($httpClient, $logger);
        });
    }
}
