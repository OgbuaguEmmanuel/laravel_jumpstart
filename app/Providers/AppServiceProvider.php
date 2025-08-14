<?php

namespace App\Providers;

use App\Enums\PaymentGatewayEnum;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Policies\NotificationPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingsPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\UserPolicy;
use App\Services\PaypalService;
use App\Services\PaystackService;
use App\Services\StripeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Twitter\Provider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return match (config('payment.driver')) {
                PaymentGatewayEnum::PAYSTACK => new PaystackService(),
                PaymentGatewayEnum::STRIPE => new StripeService(),
                PaymentGatewayEnum::PAYPAL => new PaypalService(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(!app()->isProduction());

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('twitter', Provider::class);
        });

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(DatabaseNotification::class, NotificationPolicy::class);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
        Gate::policy(Setting::class, SettingsPolicy::class);
    }
}
