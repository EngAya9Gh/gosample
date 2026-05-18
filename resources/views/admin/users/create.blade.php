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
                        <input class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password"
                            name="password" id="password" placeholder="Minimum 8 characters" required>
                        @if ($errors->has('password'))
                            <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="client_id">{{ trans('translation.task.fields.billing_client') }}</label>
                        <select class="form-control select2 {{ $errors->has('client_id') ? 'is-invalid' : '' }}"
                            name="client_id" id="client_id" data-placeholder="Select client (optional)">
                            <option value="">Select Client</option>
                            @foreach ($clients as $id => $entry)
                                <option value="{{ $entry->id }}" {{ old('client_id') == $entry->id ? 'selected' : '' }}>
                                    {{ $entry->english_name }}
                                </option>
                            @endforeach
                        </select>
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
    </script>
@endsection
