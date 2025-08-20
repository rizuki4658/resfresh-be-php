<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|unique:users',
        'password' => 'required|string|min:6',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    return response()->json([
        'email' => $user->email,
        'name' => $user->name
    ]);
});

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid Email'], 404);
    }
    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid password'], 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'email' => $user->email,
        'name' => $user->name,
        'access_token' => $token,
        'token_type' => 'Bearer',
    ]);
});

Route::post('/codes/verify', function (Request $request) {
    $request->validate([
        'code' => 'required'
    ]);

    $code = $request->code;

    if ($code === 'BXCZ6518') {
        return response()->json([
            'valid' => true,
            'box_type' => 'Black Box',
            'metadata' => [
                'allowed_items' => ['electronics', 'smartphones', 'tablets', 'laptops'],
                'size_limit' => 'Medium box capacity',
                'value_range' => '$50-$500'
            ]
        ]);
    }

    if ($code === 'BXCZ6528') {
        return response()->json([
            'valid' => false,
            'message' => "Code not found"
        ], 200);
    }

    if ($code === 'BXCZ6538') {
        return response()->json([
            'valid' => false,
            'message' => "Code already used"
        ], 200);
    }

    return response()->json([
        'error' => 'Invalid code format', 
        'details' => 'Code must be 6-20 alphanumeric characters'
    ], 400);
});

Route::post('/codes/submit', function (Request $request) {
    $request->validate([
        'code' => 'required',
        'item_info.item_type' => 'required|string',
        'item_info.brand' => 'required|string',
        'item_info.model' => 'required|string',
        'item_info.condition' => 'required|string',
        'item_info.estimated_value' => 'required'
    ]);

    $code = $request->code;

    if ($code === 'BXCZ6518') {
        return response()->json([
            'success' => true, 
            'message' => "Submission recorded successfully", 
            'submission_id' => "SUB_2025_001001", 
            'next_steps' => "Your item will be processed within 2-3 business days" 
        ]);
    }

    if ($code === 'BXCZ6528') {
        return response()->json([
            'success' => false,
            'message' => "Item type '{$request->item_info['item_type']}' not allowed for Black Box. Allowed: electronics, smartphones, tablets, laptops"
        ], 200);
    }

    if ($code === 'BXCZ6538B') {
        return response()->json([
            'success' => false,
            'message' => "Code already used"
        ], 200);
    }

    return response()->json([
        'error' => 'Invalid code format', 
        'details' => 'Code must be 6-20 alphanumeric characters'
    ], 400);
});

Route::get('/', function () {
    return response()->json([
        'message' => "You don't have access for this"
    ], 403);
});

Route::get('', function () {
    return response()->json([
        'message' => "You don't have access for this"
    ], 403);
});

Route::fallback(function () {
    return response()->json([
        'message' => 'API route not found.'
    ], 404);
});
