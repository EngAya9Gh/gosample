@extends('layouts.master')
@section('title')
    @lang('translation.widgets')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            Users
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.create') }} {{ trans('translation.user') }}</h4>
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
            <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="name">{{ trans('translation.user.fields.name') }}</label>
                        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                            name="name" id="name" value="{{ old('name', '') }}"
                            placeholder="Full name" required>
                        @if ($errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="email">{{ trans('translation.user.fields.email') }}</label>
                        <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email"
                            name="email" id="email" value="{{ old('email') }}"
                            placeholder="name@example.com" required>
                        @if ($errors->has('email'))
                            <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="password">{{ trans('translation.user.fields.password') }}</label>
                        <div class="input-group">
                            <input class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password"
                                name="password" id="password" placeholder="Minimum 8 characters" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="ri-eye-off-line" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        @if ($errors->has('password'))
                            <div class="invalid-feedback d-block">{{ $errors->first('password') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="clients">{{ trans('translation.task.fields.billing_client') }}</label>
                        <select class="form-control select2 {{ $errors->has('clients') ? 'is-invalid' : '' }}"
                            name="clients[]" id="clients" data-placeholder="Select client(s) (optional)" multiple>
                            @foreach ($clients as $id => $entry)
                                <option value="{{ $entry->id }}" {{ in_array($entry->id, old('clients', [])) ? 'selected' : '' }}>
                                    {{ $entry->english_name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('clients'))
                            <div class="invalid-feedback">{{ $errors->first('clients') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-12 mb-3">
                        <label class="required" for="roles">{{ trans('translation.user.fields.roles') }}</label>
                        <div class="d-flex flex-wrap mb-2" style="gap: 6px;">
                            <button type="button" class="btn btn-soft-info btn-sm select-all">
                                <i class="ri-checkbox-multiple-line"></i> {{ trans('translation.select_all') }}
                            </button>
                            <button type="button" class="btn btn-soft-secondary btn-sm deselect-all">
                                <i class="ri-checkbox-multiple-blank-line"></i> {{ trans('translation.deselect_all') }}
                            </button>
                        </div>
                        <select class="form-control select2 {{ $errors->has('roles') ? 'is-invalid' : '' }}"
                            name="roles[]" id="roles" data-placeholder="Select one or more roles" multiple required>
                            @foreach ($roles as $id => $role)
                                <option value="{{ $id }}" {{ in_array($id, old('roles', [])) ? 'selected' : '' }}>
                                    {{ $role }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('roles'))
                            <div class="invalid-feedback">{{ $errors->first('roles') }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-reset mb-1">
                        {{ trans('translation.back_to_list') }}
                    </a>
                    <button class="btn btn-save mb-1" type="submit">
                        <i class="fas fa-save"></i> {{ trans('translation.save') }}
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
