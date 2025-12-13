<?php

namespace App\Http\Controllers\Webhook;

use App\Helpers\WebhookHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Whatsapp\AutoReplyController;
use App\Jobs\WhatsappHandlerJob;
use App\Services\EvolutionService;
use App\Models\WhatsAppInstance;
use App\Services\AiManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class WebhookController extends Controller
{
    protected $evolutionService;

    public function __construct(EvolutionService $service)
    {
        $this->evolutionService = $service;
    }

    /**
     * Handle incoming webhooks from Evolution API
     */
    public function handle(Request $request)
    {
        WhatsappHandlerJob::dispatch($request->all());
        return responseFormat('ok');
    }

    /**
     * Get handler method name from event type
     */
}
