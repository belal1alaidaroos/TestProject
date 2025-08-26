<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\WorkerReserved;
use App\Events\ContractCreated;
use App\Events\ProposalSubmitted;
use App\Events\PaymentConfirmed;
use App\Listeners\SendReservationNotification;
use App\Listeners\SendContractNotification;
use App\Listeners\SendProposalNotification;
use App\Listeners\SendPaymentNotification;
use App\Listeners\UpdateWorkerStatus;
use App\Listeners\LogActivity;

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
        
        // Worker events
        WorkerReserved::class => [
            SendReservationNotification::class,
            UpdateWorkerStatus::class,
            LogActivity::class,
        ],
        
        // Contract events
        ContractCreated::class => [
            SendContractNotification::class,
            LogActivity::class,
        ],
        
        // Proposal events
        ProposalSubmitted::class => [
            SendProposalNotification::class,
            LogActivity::class,
        ],
        
        // Payment events
        PaymentConfirmed::class => [
            SendPaymentNotification::class,
            UpdateWorkerStatus::class,
            LogActivity::class,
        ],
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