<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use App\Services\EvolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsappDocsController extends Controller
{
    protected EvolutionService $evolutionService;
    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|max:255',
            'text' => 'required|string|max:255',
        ]);
        $instance = $request->instance;
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        $this->evolutionService->sendChatPresence($instance->instance_name, $request->number);
        $this->evolutionService->sendText($instance->instance_name, $request->number, $request->text);
        return responseFormat("تم إرسال الرسالة");
    }

    public function sendChatPresence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|max:255',
            'time' => 'required|numeric',
        ]);
        $instance = $request->instance;
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        $this->evolutionService->sendChatPresence($instance->instance_name, $request->number, $request->time);
        return responseFormat('send chat presence');
    }
}
