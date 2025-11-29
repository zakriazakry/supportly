<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return responseFormat('User not found', 404);
        }
        if (!Hash::check($request->password, $user->password)) {
            return responseFormat('Invalid password', 401);
        }
        $token = $user->createToken('auth-token')->plainTextToken;
        return responseFormat([
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);
        $token = $user->createToken('auth-token')->plainTextToken;
        return responseFormat([
            'token' => $token,
            'user' => $user,
        ], 200);
    }
}
