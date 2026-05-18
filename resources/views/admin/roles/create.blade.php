@extends('layouts.master')

@section('content')
    @php
        $isWeb      = Auth::guard('web')->check();
        $storeRoute = $isWeb ? route('admin.roles.store') : route('admin.client-roles.store');
        $indexRoute = $isWeb ? route('admin.roles.index') : route('admin.client-roles.index');
        $guardName  = $isWeb ? 'web' : 'client_users';
    @endphp

    <style>
        /* ===== Permission grid + toggle switches (page-scoped) ===== */
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 10px 14px;
        }
        .permission-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
            cursor: pointer;
            min-height: 48px;
        }
        .permission-item:hover {
            border-color: rgba(13, 148, 136, 0.40);
            background: rgba(13, 148, 136, 0.03);
        }
        .permission-item.is-checked {
            border-color: rgba(13, 148, 136, 0.45);
            background: rgba(13, 148, 136, 0.07);
            box-shadow: 0 1px 3px rgba(13, 148, 136, 0.10);
        }
        /* Native checkbox hidden but still functional (clicks delegated via the parent <label>) */
        .permission-item input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }
        /* Toggle switch — iOS/Material style, teal when on */
        .permission-toggle {
            position: relative;
            display: inline-block;
            width: 38px;
            height: 22px;
            background: #cbd5e1;
            border-radius: 12px;
            flex-shrink: 0;
            transition: background .2s ease;
        }
        .permission-toggle::before {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 16px;
            height: 16px;
            background: #ffffff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.20);
            transition: transform .2s ease;
        }
        .permission-item.is-checked .permission-toggle {
            background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
            box-shadow: 0 0 0 1px rgba(13, 148, 136, 0.10), 0 2px 6px rgba(13, 148, 136, 0.18);
        }
        .permission-item.is-checked .permission-toggle::before {
            transform: translateX(16px);
        }
        .permission-item .permission-name {
            margin: 0;
            font-size: 0.86rem;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
            user-select: none;
            line-height: 1.35;
            flex: 1;
            min-width: 0; /* allows shrink for wrapping */
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .permission-item.is-checked .permission-name { color: #0f172a; font-weight: 600; }
        /* Focus ring for keyboard navigation */
        .permission-item input[type="checkbox"]:focus-visible + .permission-toggle {
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.18);
        }
        .permission-toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin: 4px 0 12px;
        }
        .permission-counter {
            margin-left: auto;
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
        }
        .permission-counter strong { color: #0d9488; font-weight: 700; }

        .perm-toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            color: #475569;
            font-weight: 500;
            font-size: 0.82rem;
            padding: 6px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease, color .15s ease;
        }
        .perm-toolbar-btn:hover {
            border-color: rgba(13, 148, 136, 0.40);
            color: #0d9488;
            background: rgba(13, 148, 136, 0.05);
        }
        .perm-toolbar-btn i { font-size: 0.95em; }

        /* Search box for filtering long permission lists */
        .permission-search {
            position: relative;
            flex: 1;
            min-width: 200px;
            max-width: 320px;
        }
        .permission-search input {
            width: 100%;
            height: 36px;
            padding: 0 12px 0 34px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 0.85rem;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }
        .permission-search input:focus {
            outline: none;
            border-color: #0d9488;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12);
        }
        .permission-search i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
            pointer-events: none;
        }
    </style>

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('global.create') }} {{ trans('cruds.role.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="guard_name" value="{{ $guardName }}">

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="name">{{ trans('cruds.role.fields.name') }}</label>
                        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                            name="name" id="name" value="{{ old('name', '') }}"
                            placeholder="e.g. Manager, Driver, SuperAdmin" required>
                        @if ($errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.role.fields.name_helper') }}</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12 mb-3">
                        <label class="required d-block">{{ trans('cruds.role.fields.permissions') }}</label>

                        <div class="permission-toolbar">
                            <button type="button" class="perm-toolbar-btn select-all">
                                <i class="ri-checkbox-multiple-line"></i> {{ trans('global.select_all') }}
                            </button>
                            <button type="button" class="perm-toolbar-btn deselect-all">
                                <i class="ri-checkbox-multiple-blank-line"></i> {{ trans('global.deselect_all') }}
                            </button>
                            <div class="permission-search">
                                <i class="ri-search-line"></i>
                                <input type="text" id="permission-filter" placeholder="Filter permissions..." autocomplete="off">
                            </div>
                            <span class="permission-counter">
                                <strong id="perm-selected-count">0</strong> / {{ count($permissions) }} selected
                            </span>
                        </div>

                        <div class="permission-grid">
                            @foreach ($permissions as $id => $permission)
                                @php
                                    $isChecked = in_array($id, old('permissions', []));
                                @endphp
                                <label class="permission-item {{ $isChecked ? 'is-checked' : '' }}"
                                    for="permission_{{ $id }}"
                                    data-permission-name="{{ strtolower($permission) }}">
                                    <input type="checkbox" name="permissions[]"
                                        id="permission_{{ $id }}" value="{{ $id }}"
                                        {{ $isChecked ? 'checked' : '' }}>
                                    <span class="permission-toggle" aria-hidden="true"></span>
                                    <span class="permission-name">{{ $permission }}</span>
                                </label>
                            @endforeach
                        </div>

                        @if ($errors->has('permissions'))
                            <div class="invalid-feedback d-block mt-2">{{ $errors->first('permissions') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.role.fields.permissions_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ $indexRoute }}" class="btn btn-reset mb-1">
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
            var $items   = $('.permission-item');
            var $boxes   = $('input[name="permissions[]"]');
            var $counter = $('#perm-selected-count');

            function updateCount() {
                $counter.text($boxes.filter(':checked').length);
            }

            // Reflect checked state visually on the parent .permission-item
            $boxes.on('change', function () {
                $(this).closest('.permission-item').toggleClass('is-checked', this.checked);
                updateCount();
            });

            $('.select-all').on('click', function () {
                $boxes.prop('checked', true);
                $items.addClass('is-checked');
                updateCount();
            });
            $('.deselect-all').on('click', function () {
                $boxes.prop('checked', false);
                $items.removeClass('is-checked');
                updateCount();
            });

            // Filter permissions by name
            $('#permission-filter').on('input', function () {
                var q = $(this).val().trim().toLowerCase();
                $items.each(function () {
                    var name = $(this).data('permission-name') || '';
                    $(this).toggle(q === '' || name.indexOf(q) !== -1);
                });
            });

            updateCount();
        });
    </script>
@endsection

