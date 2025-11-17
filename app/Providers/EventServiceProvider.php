<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Telegram Events
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Events\TicketAssigned;
use App\Listeners\SendTicketCreatedTelegramNotification;
use App\Listeners\SendTicketStatusChangedTelegramNotification;
use App\Listeners\SendTicketCreatedNotificationToExecutors;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Telegram уведомления о тикетах
        TicketCreated::class => [
            SendTicketCreatedTelegramNotification::class,
            SendTicketCreatedNotificationToExecutors::class,
        ],

        TicketStatusChanged::class => [
            SendTicketStatusChangedTelegramNotification::class,
        ],

        // Можно добавить больше listeners
        // TicketAssigned::class => [
        //     SendTicketAssignedTelegramNotification::class,
        // ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
