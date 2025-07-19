<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PaymentMade implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;
    public $parent_id;

    public function __construct($payment, $parentId)
    {
        $this->payment = $payment;
        $this->parent_id = $parentId;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('paiement.' . $this->parent_id);
    }

    public function broadcastAs()
    {
        return 'paiement.effectue';
    }
}
