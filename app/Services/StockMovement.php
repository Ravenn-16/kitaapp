<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockMovement
{
    public static function record(int $productId, int $before, int $after, string $type, string $referenceId, ?User $user = null): void
    {
        DB::table('stock_movements')->insert([
            'productId' => $productId, 'quantityChange' => $after - $before,
            'quantityBefore' => $before, 'quantityAfter' => $after,
            'referenceType' => $type, 'referenceId' => $referenceId,
            'userId' => $user?->id, 'userRole' => $user?->role, 'created_at' => now(),
        ]);
    }
}
