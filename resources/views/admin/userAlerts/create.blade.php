@extends('layouts.admin')
@section('content')
    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.create') }} {{ trans('cruds.userAlert.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.user-alerts.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="alert_text">{{ trans('cruds.userAlert.fields.alert_text') }}</label>
                        <input class="form-control {{ $errors->has('alert_text') ? 'is-invalid' : '' }}" type="text"
                            name="alert_text" id="alert_text" value="{{ old('alert_text', '') }}"
                            placeholder="Message shown to users" required>
                        @if ($errors->has('alert_text'))
                            <div class="invalid-feedback">{{ $errors->first('alert_text') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.userAlert.fields.alert_text_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="alert_link">{{ trans('cruds.userAlert.fields.alert_link') }}</label>
                        <input class="form-control {{ $errors->has('alert_link') ? 'is-invalid' : '' }}" type="text"
                            name="alert_link" id="alert_link" value="{{ old('alert_link', '') }}"
                            placeholder="Optional URL">
                        @if ($errors->has('alert_link'))
                            <div class="invalid-feedback">{{ $errors->first('alert_link') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.userAlert.fields.alert_link_helper') }}</small>
                    </div>

                    <div class="col-lg-12 mb-3" data-multi="users">
                        <label for="users">{{ trans('cruds.userAlert.fields.user') }}</label>
                        <div class="d-flex flex-wrap mb-2" style="gap: 6px;">
                            <button type="button" class="btn btn-soft-info btn-sm select-all">
                                <i class="ri-checkbox-multiple-line"></i> {{ trans('translation.select_all') }}
                            </button>
                            <button type="button" class="btn btn-soft-secondary btn-sm deselect-all">
                                <i class="ri-checkbox-multiple-blank-line"></i> {{ trans('translation.deselect_all') }}
                            </button>
                        </div>
                        <select class="form-control select2 {{ $errors->has('users') ? 'is-invalid' : '' }}"
                            name="users[]" id="users" data-placeholder="Select users" multiple>
                            @foreach ($users as $id => $user)
                                <option value="{{ $id }}" {{ in_array($id, old('users', [])) ? 'selected' : '' }}>
                                    {{ $user }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('users'))
                            <div class="invalid-feedback">{{ $errors->first('users') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.userAlert.fields.user_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.user-alerts.index') }}" class="btn btn-reset mb-1">
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
