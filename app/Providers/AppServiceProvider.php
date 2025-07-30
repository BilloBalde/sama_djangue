<?php

namespace App\Providers;

use Paydunya\Setup;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Auth\Notifications\ResetPassword;
use Paydunya\Checkout\Store;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Configurer les clés API PayDunya
        Setup::setMasterKey(env('PAYDUNYA_MASTER_KEY'));
        Setup::setPublicKey(env('PAYDUNYA_PUBLIC_KEY'));
        Setup::setPrivateKey(env('PAYDUNYA_PRIVATE_KEY'));
        Setup::setToken(env('PAYDUNYA_TOKEN'));
        Setup::setMode(env('PAYDUNYA_MODE', 'test'));

        // Configurer les informations de la boutique (optionnel)
        Store::setName("Votre Nom de Boutique");
        Store::setTagline("Votre Slogan");
        Store::setPhoneNumber("Votre Numéro de Téléphone");
        Store::setPostalAddress("Votre Adresse Postale");
        Store::setWebsiteUrl("https://votre-site.com");
        Store::setLogoUrl("https://votre-site.com/logo.png");
        Store::setCallbackUrl("http://localhost:8000/payment/callback");
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Broadcast::routes(); // important

        require base_path('routes/channels.php');
    }

}
