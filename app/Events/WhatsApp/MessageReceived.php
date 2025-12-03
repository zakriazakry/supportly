<?php

namespace App\Events\WhatsApp;

use App\Models\WhatsAppInstance;
use App\Models\WhatsAppMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WhatsAppInstance $instance;
    public array $messageData;

    /**
     * Create a new event instance.
     */
    public function __construct(WhatsAppInstance $instance, array $messageData)
    {
        $this->instance = $instance;
        $this->messageData = $messageData;
    }
}
