<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AutoReplyController extends Controller
{
    static public function whenReceiveTextMessage($data)
    {
        Log::info('AutoReplyController :📝 Text Message', $data);
    }
}
