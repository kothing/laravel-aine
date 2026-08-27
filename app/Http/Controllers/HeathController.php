<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function health()
    {
        $db = 'fail';
        try {
            DB::connection()->getPdo();
            $db = 'ok';
        } catch (\Throwable $e) {
        }

        return response()->json([
            'status' => 'ok',
            'db' => $db,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function up()
    {
        return response('', 200);
    }
}