<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\InstanceConnected;
use Illuminate\Support\Facades\Log;

class LogInstanceConnection
{
    /**
     * Handle the event.
     */
    public function handle(InstanceConnected $event): void
    {
        Log::info('WhatsApp Instance Connected', [
            'instance_id' => $event->instance->id,
            'instance_name' => $event->instance->instance_name,
            'user_id' => $event->instance->user_id,
            'phone_number' => $event->data['phone_number'] ?? null,
        ]);
    }
}
