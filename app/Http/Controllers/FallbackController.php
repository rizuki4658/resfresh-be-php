<?php

namespace App\Http\Controllers;

class FallbackController extends Controller
{
    public function handler() {
        return response()->json([
            'message'   =>  'Routes not Found!'
        ], 404);
    }


    public function handlerMiddleware() {
        return response()->json([
            'message'   =>  'Unauthorized!'
        ], 401);
    }
}
