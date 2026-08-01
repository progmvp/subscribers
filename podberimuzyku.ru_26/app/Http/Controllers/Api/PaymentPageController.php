<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentPageController extends Controller
{
    public function show(Request $request)
    {
        if (
            empty($request->name) ||
            empty($request->email) ||
            empty($request->plan)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Missing parameters'
            ], 400);
        }


        $name  = $request->name;
        $email = $request->email;
        $plan  = $request->plan;


        $plans = [

            'start' => [
                'name' => 'Стартовый',
                'amount' => 250,
            ],

            'base' => [
                'name' => 'Базовый',
                'amount' => 450,
            ],

            'full' => [
                'name' => 'Полный',
                'amount' => 750,
            ],

        ];


        if (!isset($plans[$plan])) {

            return response()->json([
                'success' => false,
                'message' => 'Unknown plan'
            ], 400);

        }


        $paymentId = (string) Str::uuid();


        Payment::create([

            'name' => $name,
            'email' => $email,
            'plan' => $plan,
            'amount' => $plans[$plan]['amount'],
            'status' => 'pending',
            'payment_id' => $paymentId,

        ]);


        return response()->json([

            'success' => true,

            'planName' => $plans[$plan]['name'],

            'amount' => $plans[$plan]['amount'],

            'email' => $email,

            'paymentId' => $paymentId,

        ]);
    }
}