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

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    protected WhatsAppInstance $instance;
    protected string $number;
    protected string $message;
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(WhatsAppInstance $instance, string $number, string $message, array $options = [])
    {
        $this->instance = $instance;
        $this->number = $number;
        $this->message = $message;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(EvolutionService $evolutionService): void
    {
        try {
            $result = $evolutionService->sendText(
                $this->instance->instance_name,
                $this->number,
                $this->message,
                $this->options
            );

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to send message');
            }

            Log::info('WhatsApp message sent successfully', [
                'instance' => $this->instance->instance_name,
                'number' => $this->number,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'instance' => $this->instance->instance_name,
                'number' => $this->number,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp message job failed permanently', [
            'instance' => $this->instance->instance_name,
            'number' => $this->number,
            'error' => $exception->getMessage(),
        ]);
    }
}
