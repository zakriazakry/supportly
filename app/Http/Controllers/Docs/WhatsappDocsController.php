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
        $send =  $this->evolutionService->sendText($instance->instance_name, $request->number, $request->text);
        return responseFormat($send);
    }

    public function sendList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'buttonText' => 'required|string|max:255',
            'sections' => 'required|array',
        ]);
        $instance = $request->instance;
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $send =  $this->evolutionService->sendList($instance->name, $request->number, $request->title, $request->description, $request->buttonText, $request->sections);
        return responseFormat($send);
    }
}
