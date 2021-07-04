<?php


namespace App\Providers;


use App\Exceptions\Google\GoogleConfigInvalidException;
use App\GoogleAPI;
use App\Models\GoogleSettings;
use App\Services\Clients\GoogleClient;
use Illuminate\Support\ServiceProvider;

class GoogleClientProvider extends ServiceProvider
{
    public function boot(){
        $this->app->singleton(GoogleClient::class, function(){
            return new GoogleClient($this->app->get('google.api.client'));
        });
        $this->app->singleton('google.api.client', function(){
            /** @var GoogleSettings $config */
            $config = $this->app->get(GoogleSettings::class);

            if(!$config->isValid()){
                throw new GoogleConfigInvalidException();
            }

            $client = new \Google\Client();
            $client->setApplicationName(\config('app.name'));
            $client->setAuthConfig($config->googleConfig);

            $client->setIncludeGrantedScopes(true);
            $client->addScope('https://www.googleapis.com/auth/webmasters');
            $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
            $client->addScope('https://www.googleapis.com/auth/analytics.readonly');

            return $client;
        });

    }
}
