<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhatsappDocsController extends Controller
{
    public function sendMessage(Request $request)
    {
        return responseFormat('send message');
    }
}
