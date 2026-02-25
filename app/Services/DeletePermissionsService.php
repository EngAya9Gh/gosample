<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class DeletePermissionsService
{
    private const STORAGE_FILE = 'delete_permissions.json';

    /**
     * Get the list of user IDs allowed to perform delete actions.
     */
    public function getAllowedUserIds(): array
    {
        if (!Storage::exists(self::STORAGE_FILE)) {
            $ids = config('delete_permissions.allowed_user_ids', [1]);
            $this->save($ids);
            return $ids;
        }

        $content = Storage::get(self::STORAGE_FILE);
        $data = json_decode($content, true);

        return is_array($data['allowed_user_ids'] ?? null) ? $data['allowed_user_ids'] : [1];
    }

    /**
     * Add a user ID to the allowed list.
     */
    public function addUser(int $userId): bool
    {
        $ids = $this->getAllowedUserIds();
        if (in_array($userId, $ids)) {
            return true;
        }
        $ids[] = $userId;
        sort($ids);
        return $this->save($ids);
    }

    /**
     * Remove a user ID from the allowed list.
     */
    public function removeUser(int $userId): bool
    {
        $ids = array_values(array_filter(
            $this->getAllowedUserIds(),
            fn($id) => (int) $id !== $userId
        ));
        return $this->save($ids);
    }

    /**
     * Check if the current user (ID 1) can manage delete permissions.
     */
    public function canManage(): bool
    {
        return auth()->check() && auth()->id() === 1;
    }

    private function save(array $ids): bool
    {
        return Storage::put(self::STORAGE_FILE, json_encode([
            'allowed_user_ids' => array_values(array_map('intval', $ids)),
        ], JSON_PRETTY_PRINT));
    }
}
