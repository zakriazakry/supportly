<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{
    public function index()
    {
        return responseFormat(Package::all(), 200);
    }
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id',
        ]);
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        $package = Package::find($request->package_id);
        $user = $request->user();
        if ($user->hasActiveSubscription()) {
            return responseFormat('لديك اشتراك نشط بالفعل', 403);
        }
        $user->package_id = $package->id;
        $user->save();
        return responseFormat('تم الاشتراك بنجاح', 200);
    }
}
