<?php

namespace Cerpus\REContentClient;

use Cerpus\LaravelAuth\Service\CerpusAuthService;
use Cerpus\REContentClient\Exceptions\NoTokenException;
use GuzzleHttp\Client;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
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
            $ccToken = null;
            try {
                $auth = app(CerpusAuthService::class);
                $token = $auth->getClientCredentialsTokenRequest()->execute();
                if (is_object($token)) {
                    $ccToken = $token->access_token ?? null;
                }
            } catch (\Exception $e) {
                throw new NoTokenException($e->getMessage(), $e->getCode(), $e);
            }

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
