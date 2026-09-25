<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class WorkflowNotifications
{
    public static function reviewers(): array
    {
        return User::query()->get()->filter(fn (User $user) => $user->isActive() && in_array($user->normalizedRole(), ['admin', 'super_admin']))->all();
    }

    public static function owner(?int $id, ?string $role): ?User
    {
        return $id && $role ? User::where('id', $id)->where('role', $role)->first() : null;
    }

    public static function send(array $recipients, User $actor, string $type, string $message, string $area, ?string $recordId = null): void
    {
        $recipients = collect($recipients)->filter()->unique(fn (User $user) => $user->role.':'.$user->id);
        if ($recipients->isEmpty()) {
            return;
        }
        // Reuses the existing notification table and manual integer ID convention.
        DB::transaction(function () use ($recipients, $actor, $type, $message, $area, $recordId) {
            $sequence = DB::table('inventory_sequences')->where('name', 'notifications')->lockForUpdate()->first();
            $id = max((int) $sequence->lastValue, (int) DB::table('notifications')->max('id'));
            foreach ($recipients as $recipient) {
                $role = $recipient->normalizedRole();
                $screen = match ($area) {
                    'purchase' => $role === 'manager' ? 'mgrRequestView' : 'admPurchasedOrders',
                    'receiving' => $role === 'manager' ? 'mgrPurchaseHistory' : 'admReceivingApprovals',
                    'adjustment' => $role === 'manager' ? 'mgrInventory' : 'admInventoryApprovals',
                    'account' => match ($role) { 'super_admin' => 'saAdmins', 'admin' => 'admManagers', 'manager' => 'mgrCashiers', default => null },
                    default => null,
                };
                DB::table('notifications')->insert([
                    'id' => ++$id, 'recipientId' => $recipient->id, 'recipientRole' => $recipient->role,
                    'actorId' => $actor->id, 'actorRole' => $actor->role, 'type' => $type,
                    'message' => $message, 'priority' => 'Standard', 'screen' => $screen,
                    'recordId' => $recordId, 'created_at' => now(),
                ]);
            }
            DB::table('inventory_sequences')->where('name', 'notifications')->update(['lastValue' => $id]);
        });
    }
}
