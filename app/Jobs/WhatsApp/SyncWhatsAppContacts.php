<?php

namespace App\Jobs\WhatsApp;

use App\Models\WhatsAppInstance;
use App\Services\EvolutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWhatsAppContacts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 120;

    protected WhatsAppInstance $instance;

    /**
     * Create a new job instance.
     */
    public function __construct(WhatsAppInstance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Execute the job.
     */
    public function handle(EvolutionService $evolutionService): void
    {
        try {
            $result = $evolutionService->findContacts($this->instance->instance_name);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to fetch contacts');
            }

            $contacts = $result['data'] ?? [];

            foreach ($contacts as $contact) {
                $this->instance->contacts()->updateOrCreate(
                    [
                        'jid' => $contact['id'] ?? $contact['jid'],
                    ],
                    [
                        'phone_number' => $contact['number'] ?? null,
                        'name' => $contact['name'] ?? null,
                        'push_name' => $contact['pushName'] ?? null,
                        'profile_picture_url' => $contact['profilePictureUrl'] ?? null,
                    ]
                );
            }

            Log::info('WhatsApp contacts synced successfully', [
                'instance' => $this->instance->instance_name,
                'count' => count($contacts),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync WhatsApp contacts', [
                'instance' => $this->instance->instance_name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
