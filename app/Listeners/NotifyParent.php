<?php

namespace App\Listeners;

use App\Events\PaymentMade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaymentNotification;

class NotifyParent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentMade $event)
    {
        $parent = $event->payment->student->parent;
        Notification::send($parent, new PaymentNotification($event->payment));
    }
}
