<?php

namespace App\Events\WhatsApp;

use App\Models\WhatsAppInstance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstanceDisconnected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WhatsAppInstance $instance;

    /**
     * Create a new event instance.
     */
    public function __construct(WhatsAppInstance $instance)
    {
        $this->instance = $instance;
    }
}
