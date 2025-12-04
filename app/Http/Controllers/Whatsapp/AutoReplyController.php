<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppInstance;
use App\Services\EvolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AutoReplyController extends Controller
{
    protected $evolutionService;

    public function __construct(EvolutionService $service)
    {
        $this->evolutionService = $service;
    }

    static public function whenReceiveTextMessage($data)
    {
        $instance_name = $data['instance'] ?? null;
        $instance = WhatsAppInstance::where('instance_name', $instance_name)->first();
        if (!$instance) {
            Log::error('Instance not found', ['instance_name' => $instance_name]);
            return;
        }
        $user = User::where('id', $instance->user_id)->first();
        if (!$user) {
            Log::error('User not found', ['user_id' => $instance->user_id]);
            return;
        }
        $autoReply = $user->whasAppReply;
        if (!$autoReply) {
            Log::error('user is Expider', ['user_id' => $user->id]);
            return;
        }
        $message = $data['message'] ?? null;
        // process message
        // send message to user
        $autoReply = $user->whasAppReply;
        self::$evolutionService->sendText($instance_name, $data['formNumber'], $message);
        if ($autoReply->welcome) {
            self::welcomeReply($message);
        }
        if ($autoReply->ai) {
            self::aiReply($message);
        }
        if ($autoReply->number) {
            self::normalReply($message);
        }
    }
    private function welcomeReply($msg) {}
    private function aiReply($msg) {}
    private function normalReply($msg) {}
}
