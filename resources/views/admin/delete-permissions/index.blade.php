@extends('layouts.master')
@section('title')
    Delete Permissions
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            Delete Permissions
        @endslot
    @endcomponent

    @if(session('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Users Allowed to Delete</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">These users can see delete buttons and perform delete actions across the application.</p>
                    <ul class="list-group list-group-flush">
                        @forelse($allowedUserIds as $userId)
                            @php $user = $allowedUsers[$userId] ?? null; @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    @if($user)
                                        {{ $user->name }} ({{ $user->email }})
                                        @if($user->trashed())
                                            <span class="badge bg-secondary">Deleted</span>
                                        @endif
                                    @else
                                        User #{{ $userId }}
                                    @endif
                                </span>
                                @if($userId !== 1)
                                    <form action="{{ route('admin.delete-permissions.destroy', $userId) }}" method="POST"
                                        onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                    </form>
                                @else
                                    <span class="badge bg-secondary">Owner</span>
                                @endif
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No users configured.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add User</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.delete-permissions.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Select user to allow delete actions</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="">-- Choose a user --</option>
                                @foreach($usersForSelect as $user)
                                    @if(!in_array($user->id, $allowedUserIds))
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endif
                                @endforeach
                            </select>
                            @if($usersForSelect->whereNotIn('id', $allowedUserIds)->isEmpty())
                                <small class="text-muted">All users are already in the list.</small>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-success">Add User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
