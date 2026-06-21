@extends('layouts.master')
@section('content')
    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('global.edit') }} {{ trans('cruds.user.title_singular') }}</h4>
        </div>

        <div class="card-body">
            @if(count($errors) > 0)
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.users.update', [$user->id]) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="name">{{ trans('cruds.user.fields.name') }}</label>
                        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                            name="name" id="name" value="{{ old('name', $user->name) }}"
                            placeholder="Full name" required>
                        @if ($errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.user.fields.name_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="email">{{ trans('cruds.user.fields.email') }}</label>
                        <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email"
                            name="email" id="email" value="{{ old('email', $user->email) }}"
                            placeholder="name@example.com" required>
                        @if ($errors->has('email'))
                            <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.user.fields.email_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="password">{{ trans('cruds.user.fields.password') }}</label>
                        <div class="input-group">
                            <input class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password"
                                name="password" id="password" placeholder="Leave blank to keep current password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="ri-eye-off-line" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        @if ($errors->has('password'))
                            <div class="invalid-feedback d-block">{{ $errors->first('password') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.user.fields.password_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="clients">{{ trans('translation.task.fields.billing_client') }}</label>
                        <select class="form-control select2 {{ $errors->has('clients') ? 'is-invalid' : '' }}"
                            name="clients[]" id="clients" data-placeholder="Select client(s) (optional)" multiple>
                            @php
                                $assignedClients = old('clients', $user->assigned_client_ids ?? []);
                            @endphp
                            @foreach ($clients as $id => $entry)
                                <option value="{{ $entry->id }}"
                                    {{ in_array($entry->id, $assignedClients) ? 'selected' : '' }}>
                                    {{ $entry->english_name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('clients'))
                            <div class="invalid-feedback">{{ $errors->first('clients') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-12 mb-3">
                        <label class="required" for="roles">{{ trans('cruds.user.fields.roles') }}</label>
                        <div class="d-flex flex-wrap mb-2" style="gap: 6px;">
                            <button type="button" class="btn btn-soft-info btn-sm select-all">
                                <i class="ri-checkbox-multiple-line"></i> {{ trans('global.select_all') }}
                            </button>
                            <button type="button" class="btn btn-soft-secondary btn-sm deselect-all">
                                <i class="ri-checkbox-multiple-blank-line"></i> {{ trans('global.deselect_all') }}
                            </button>
                        </div>
                        <select class="form-control select2 {{ $errors->has('roles') ? 'is-invalid' : '' }}"
                            name="roles[]" id="roles" data-placeholder="Select one or more roles" multiple required>
                            @foreach ($roles as $id => $role)
                                <option value="{{ $id }}"
                                    {{ in_array($id, old('roles', [])) || $user->roles->contains($id) ? 'selected' : '' }}>
                                    {{ $role }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('roles'))
                            <div class="invalid-feedback">{{ $errors->first('roles') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.user.fields.roles_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-reset mb-1">
                        {{ trans('global.cancel') }}
                    </a>
                    <button class="btn btn-save mb-1" type="submit">
                        <i class="fas fa-save"></i> {{ trans('global.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            $('.select2').each(function () {
                $(this).select2({
                    placeholder: $(this).data('placeholder') || 'Please select',
                    allowClear: false
                });
            });

            $('.select-all').on('click', function () {
                $('#roles option').prop('selected', true);
                $('#roles').trigger('change');
            });
            $('.deselect-all').on('click', function () {
                $('#roles option').prop('selected', false);
                $('#roles').trigger('change');
            });
        });
        
        document.getElementById('togglePassword')?.addEventListener('click', function (e) {
            const password = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('ri-eye-off-line');
                icon.classList.add('ri-eye-line');
            } else {
                password.type = 'password';
                icon.classList.remove('ri-eye-line');
                icon.classList.add('ri-eye-off-line');
            }
        });
    </script>
@endsection
