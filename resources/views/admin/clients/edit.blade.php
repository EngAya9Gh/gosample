@extends('layouts.master')
@section('title')
    @lang('translation.clients')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.clients')
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.edit') }} {{ trans('translation.client') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.clients.update', [$client->id]) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="arabic_name">{{ trans('translation.client.fields.arabic_name') }}</label>
                        <input class="form-control {{ $errors->has('arabic_name') ? 'is-invalid' : '' }}" type="text"
                            name="arabic_name" id="arabic_name" value="{{ old('arabic_name', $client->arabic_name) }}"
                            placeholder="الاسم العربي">
                        @if ($errors->has('arabic_name'))
                            <div class="invalid-feedback">{{ $errors->first('arabic_name') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="english_name">{{ trans('translation.client.fields.english_name') }}</label>
                        <input class="form-control {{ $errors->has('english_name') ? 'is-invalid' : '' }}" type="text"
                            name="english_name" id="english_name"
                            value="{{ old('english_name', $client->english_name) }}" placeholder="English name">
                        @if ($errors->has('english_name'))
                            <div class="invalid-feedback">{{ $errors->first('english_name') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="email">{{ trans('translation.client.fields.email') }}</label>
                        <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="text"
                            name="email" id="email" value="{{ old('email', $client->email) }}"
                            placeholder="name@example.com">
                        @if ($errors->has('email'))
                            <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="address">{{ trans('translation.client.fields.address') }}</label>
                        <input class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}" type="text"
                            name="address" id="address" value="{{ old('address', $client->address) }}"
                            placeholder="Street, city...">
                        @if ($errors->has('address'))
                            <div class="invalid-feedback">{{ $errors->first('address') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="statuss">{{ trans('translation.client.fields.status') }}</label>
                        <select class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status"
                            id="statuss" required>
                            <option value disabled {{ old('status', null) === null ? 'selected' : '' }}>
                                {{ trans('translation.pleaseSelect') }}
                            </option>
                            @foreach (App\Models\Client::STATUS_SELECT as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('status', $client->status) === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('status'))
                            <div class="invalid-feedback">{{ $errors->first('status') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="logo">{{ trans('translation.client.fields.logo') }}</label>
                        @if ($client->logo)
                            <div class="mb-2">
                                <a href="{{ asset(ltrim($client->logo, '/')) }}" target="_blank" style="display: inline-block;">
                                    <img src="{{ asset(ltrim($client->logo, '/')) }}" alt="logo"
                                        style="max-height:80px; border-radius:8px; border:1px solid #e2e8f0;">
                                </a>
                            </div>
                        @endif
                        <input type="file" name="logo" id="inputImage" accept="image/*"
                            class="form-control @error('logo') is-invalid @enderror">
                        @if ($errors->has('logo'))
                            <div class="invalid-feedback">{{ $errors->first('logo') }}</div>
                        @endif
                        <small class="help-block text-muted">Leave empty to keep the current logo. Allowed: JPG, PNG, GIF.</small>
                    </div>

                    <div class="col-lg-6 mb-3" data-multi="drivers">
                        <label class="required" for="drivers">{{ trans('cruds.driver.title_singular') }}</label>
                        <div class="d-flex flex-wrap mb-2" style="gap: 6px;">
                            <button type="button" class="btn btn-soft-info btn-sm select-all">
                                <i class="ri-checkbox-multiple-line"></i> {{ trans('translation.select_all') }}
                            </button>
                            <button type="button" class="btn btn-soft-secondary btn-sm deselect-all">
                                <i class="ri-checkbox-multiple-blank-line"></i> {{ trans('translation.deselect_all') }}
                            </button>
                        </div>
                        <select class="form-control select2 {{ $errors->has('drivers') ? 'is-invalid' : '' }}"
                            name="drivers[]" id="drivers" data-placeholder="Select drivers" multiple required>
                            @foreach ($drivers as $id => $driver)
                                <option value="{{ $id }}"
                                    {{ in_array($id, old('drivers', [])) || $client->drivers->contains($id) ? 'selected' : '' }}>
                                    {{ $driver }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('drivers'))
                            <div class="invalid-feedback">{{ $errors->first('drivers') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3" data-multi="locations">
                        <label class="required" for="locations">{{ trans('cruds.sample.fields.location') }}</label>
                        <div class="d-flex flex-wrap mb-2" style="gap: 6px;">
                            <button type="button" class="btn btn-soft-info btn-sm select-all">
                                <i class="ri-checkbox-multiple-line"></i> {{ trans('translation.select_all') }}
                            </button>
                            <button type="button" class="btn btn-soft-secondary btn-sm deselect-all">
                                <i class="ri-checkbox-multiple-blank-line"></i> {{ trans('translation.deselect_all') }}
                            </button>
                        </div>
                        <select class="form-control select2 {{ $errors->has('locations') ? 'is-invalid' : '' }}"
                            name="locations[]" id="locations" data-placeholder="Select locations" multiple required>
                            @foreach ($locations as $id => $location)
                                <option value="{{ $id }}"
                                    {{ in_array($id, old('locations', [])) || $client->locations->contains($id) ? 'selected' : '' }}>
                                    {{ $location }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('locations'))
                            <div class="invalid-feedback">{{ $errors->first('locations') }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-reset mb-1">
                        {{ trans('global.cancel') }}
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

            // Scope select-all / deselect-all to the wrapper of each multi-select
            $('[data-multi]').each(function () {
                var $wrap   = $(this);
                var $select = $wrap.find('select');
                $wrap.find('.select-all').on('click', function () {
                    $select.find('option').prop('selected', true);
                    $select.trigger('change');
                });
                $wrap.find('.deselect-all').on('click', function () {
                    $select.find('option').prop('selected', false);
                    $select.trigger('change');
                });
            });
        });
    </script>
@endsection
