<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeletePermissionsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeletePermissionsController extends Controller
{
    public function __construct(
        private DeletePermissionsService $deletePermissionsService
    ) {}

    public function index()
    {
        if (!$this->deletePermissionsService->canManage()) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        $allowedUserIds = $this->deletePermissionsService->getAllowedUserIds();
        $allowedUsers = User::withTrashed()
            ->whereIn('id', $allowedUserIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $usersForSelect = User::orderBy('name')->get();

        return view('admin.delete-permissions.index', compact('allowedUserIds', 'allowedUsers', 'usersForSelect'));
    }

    public function store(Request $request)
    {
        if (!$this->deletePermissionsService->canManage()) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $this->deletePermissionsService->addUser((int) $request->user_id);

        return redirect()
            ->route('admin.delete-permissions.index')
            ->with('message', __('User added to delete permissions.'));
    }

    public function destroy(int $userId)
    {
        if (!$this->deletePermissionsService->canManage()) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        if ($userId === 1) {
            return redirect()
                ->route('admin.delete-permissions.index')
                ->with('error', __('User ID 1 cannot be removed from delete permissions.'));
        }

        $this->deletePermissionsService->removeUser($userId);

        return redirect()
            ->route('admin.delete-permissions.index')
            ->with('message', __('User removed from delete permissions.'));
    }
}
