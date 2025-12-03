<?php

namespace App\Events\WhatsApp;

use App\Models\WhatsAppInstance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstanceConnected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WhatsAppInstance $instance;
    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(WhatsAppInstance $instance, array $data = [])
    {
        $this->instance = $instance;
        $this->data = $data;
    }
}
