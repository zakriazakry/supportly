<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportingPagesController extends Controller
{
    public function sendTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'issueType' => 'required',
            'priority' => 'required',
            'subject' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 400);
        }

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'email' => $request->email,
            'issueType' => $request->issueType,
            'priority' => $request->priority,
            'subject' => $request->subject,
            'description' => $request->description,
        ]);

        if (!$ticket) {
            return responseFormat('Ticket created failed', 500);
        }

        return responseFormat('Ticket created successfully', 200);
    }
}
