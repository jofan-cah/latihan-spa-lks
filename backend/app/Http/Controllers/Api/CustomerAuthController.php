<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nama'  => 'required|string|max:100',
            'no_hp' => 'required|string|max:20|unique:customers,no_hp',
        ]);

        $customer = Customer::create([
            'nama'     => $request->nama,
            'no_hp'    => $request->no_hp,
            'password' => Hash::make($request->no_hp),
        ]);

        $token = $customer->createToken('customer')->plainTextToken;

        return response()->json(['token' => $token, 'customer' => $customer], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'no_hp'    => 'required|string',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('no_hp', $request->no_hp)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return response()->json(['message' => 'No HP atau password salah'], 401);
        }

        $token = $customer->createToken('customer')->plainTextToken;

        return response()->json(['token' => $token, 'customer' => $customer]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
