<?php

use App\Http\Controllers\Docs\WhatsappDocsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1/whatsapp-developer')->group(function () {
    Route::controller(WhatsappDocsController::class)->prefix('{instanceId}')->group(function () {
       
    });
});
