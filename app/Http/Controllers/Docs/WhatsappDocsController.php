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
            'delay' => 'nullable|numeric',
        ]);
        $instance = $request->instance;
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        if (isset($request->delay) && $request->delay > 500) {
            $this->evolutionService->sendChatPresence($instance->instance_name, $request->number, 'composing', $request->delay);
        }
        $this->evolutionService->sendText($instance->instance_name, $request->number, $request->text);
        return responseFormat("تم إرسال الرسالة");
    }
    public function sendMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|max:255',
            'media_url' => 'required|string|url',
        ]);
        $instance = $request->instance;
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        $this->evolutionService->sendMedia($instance->instance_name, $request->number, $request->media_url);
        return responseFormat("تم إرسال الملف");
    }
    public function showWirte(Request $request)
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

    public function seen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'messageKey' => 'required|string|max:255',
        ]);
        $instance = $request->instance;
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        $tst = $this->evolutionService->markAsRead($instance->instance_name, [$request->messageKey]);
        return responseFormat($tst['success'] == true ? 'تم التحقق من الرسالة' : 'فشل التحقق من الرسالة');
    }
}
