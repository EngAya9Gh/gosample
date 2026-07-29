<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\Client;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = User::with(['roles', 'clients']);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('id', $request->role);
            });
        }

        if ($request->filled('client')) {
            $query->whereHas('clients', function($q) use ($request) {
                $q->where('id', $request->client);
            });
        }

        $pageSize = $request->get('pageSize', 25);
        $paginator = $query->orderBy('id', 'desc')->paginate($pageSize);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'rows' => $paginator->items(),
                'total' => $paginator->total(),
            ]);
        }

        $roles = Role::pluck('name', 'id');
        $logged_id_user = auth()->user();
        if ($logged_id_user->client_id !== null) {
            $clients = Client::whereIn('id', $logged_id_user->assigned_client_ids)->get();
        } else {
            $clients = Client::all();
        }

        return \Inertia\Inertia::render('Users/UsersList', [
            'initialRows' => $paginator->items(),
            'initialTotal' => $paginator->total(),
            'roles' => $roles,
            'clients' => $clients,
        ]);
    }

    public function create()
    {
        abort_if(Gate::denies('user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $clients = Client::all();

        $roles = Role::pluck('name', 'id');

        return view('admin.users.create', compact('roles','clients'));
    }

    public function store(StoreUserRequest $request)
    {
        // Extract clients logic
        $data = $request->all();
        $clients = $request->input('clients', []);
        
        // As per the gradual migration strategy, we move all selected clients to the pivot table
        // and leave client_id as null.
        $data['client_id'] = null;

        $user = User::create($data);
        $user->roles()->sync($request->input('roles', []));
        $user->clients()->sync($clients);
            return redirect()->route('admin.users.index');
    }

    public function edit(User $user)
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $roles = Role::pluck('name', 'id');
        $clients = Client::all();

        $user->load(['roles', 'clients']);

        return view('admin.users.edit', compact('roles', 'user', 'clients'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->all();
        $clients = $request->input('clients', []);

        // Gradual migration: clear client_id and put all clients in the pivot table
        $data['client_id'] = null;

        // If password is empty/null, remove it from $data so we don't update it to empty
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        $user->roles()->sync($request->input('roles', []));
        $user->clients()->sync($clients);
            return redirect()->route('admin.users.index');
    }

    public function show(User $user)
    {
        abort_if(Gate::denies('user_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user->load(['roles', 'clients']);

        return view('admin.users.show', compact('user'));
    }

    public function destroy(User $user)
    {
        $this->authorize('can-delete');

        $user->delete();

        return back();
    }

    public function massDestroy(MassDestroyUserRequest $request)
    {
        $this->authorize('can-delete');
        User::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
