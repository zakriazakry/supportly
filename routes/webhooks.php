<?php

use App\Http\Controllers\Webhook\FacebookWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;



$pageId = '702602156278377';
$pageAccessToken = "EAAL6cDIxiFcBQIHYFAqCI77jnqf8eS1XxO60ZAwflZBYBOx6BJBZA2iyEJvV4iJMJ9gBl9EjpsMZBHf6MRfiZBcxkH0ytnz8slrionT3TkiJwSgEzGildjB9rooLSK6SBcmZCpQ1sHHLHZC8RzAHxQSFZAHDXgU52MBpWM2XAe7kjj7TrgVHGjAezOM4KL0csIq8BBqtqMblebZCPyZC8xvx9yZBW5QDsII3teZCNVm4teUZD";

// Webhook Verification
Route::get('facebook/webhook', [FacebookWebhookController::class, 'verify']);

// Webhook Receiver
Route::post('facebook/webhook', [FacebookWebhookController::class, 'receive']);


// 492c0ee5a47b60eb24cdfec83b11e29e90b0165f2370cebeb10e119f76d9460c