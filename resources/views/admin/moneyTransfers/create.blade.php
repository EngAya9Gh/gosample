@extends('layouts.master')
@section('content')
    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('global.create') }} {{ trans('cruds.moneyTransfer.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.money-transfers.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="driver_id">{{ trans('cruds.moneyTransfer.fields.driver') }}</label>
                        <select class="form-control select2 {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_id" id="driver_id" required>
                            @foreach ($drivers as $id => $entry)
                                <option value="{{ $id }}" {{ old('driver_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.moneyTransfer.fields.driver_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="client_id">{{ trans('cruds.moneyTransfer.fields.client') }}</label>
                        <select class="form-control select2 {{ $errors->has('client') ? 'is-invalid' : '' }}"
                            name="client_id" id="client_id" required>
                            @foreach ($clients as $id => $entry)
                                <option value="{{ $id }}" {{ old('client_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('client'))
                            <div class="invalid-feedback">{{ $errors->first('client') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.moneyTransfer.fields.client_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="from_location_id">{{ trans('cruds.moneyTransfer.fields.from_location') }}</label>
                        <select class="form-control select2 {{ $errors->has('from_location') ? 'is-invalid' : '' }}"
                            name="from_location_id" id="from_location_id" required>
                            @foreach ($from_locations as $id => $entry)
                                <option value="{{ $id }}" {{ old('from_location_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('from_location'))
                            <div class="invalid-feedback">{{ $errors->first('from_location') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.moneyTransfer.fields.from_location_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="to_location_id">{{ trans('cruds.moneyTransfer.fields.to_location') }}</label>
                        <select class="form-control select2 {{ $errors->has('to_location') ? 'is-invalid' : '' }}"
                            name="to_location_id" id="to_location_id">
                            @foreach ($to_locations as $id => $entry)
                                <option value="{{ $id }}" {{ old('to_location_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('to_location'))
                            <div class="invalid-feedback">{{ $errors->first('to_location') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.moneyTransfer.fields.to_location_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="amount">{{ trans('cruds.moneyTransfer.fields.amount') }}</label>
                        <input class="form-control {{ $errors->has('amount') ? 'is-invalid' : '' }}" type="number"
                            name="amount" id="amount" value="{{ old('amount', '') }}" step="0.01"
                            placeholder="e.g. 1500.00" required>
                        @if ($errors->has('amount'))
                            <div class="invalid-feedback">{{ $errors->first('amount') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.moneyTransfer.fields.amount_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.money-transfers.index') }}" class="btn btn-reset mb-1">
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
