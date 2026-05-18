@extends('layouts.master')

@section('content')
    @php
        $isWeb       = Auth::guard('web')->check();
        $createRoute = $isWeb ? route('admin.roles.create') : route('admin.client-roles.create');

        // Stats for the header strip
        $totalRoles       = $roles->count();
        $totalAssignedPerms = $roles->sum(fn ($r) => $r->permissions->count());
        $maxPerms         = max(1, $roles->max(fn ($r) => $r->permissions->count()) ?? 1);

        // Color palette for role avatars — deterministic per role name
        $palette = [
            ['#0ea5a4', '#0d9488'],   // teal
            ['#6366f1', '#4f46e5'],   // indigo
            ['#3b82f6', '#2563eb'],   // blue
            ['#22c55e', '#16a34a'],   // green
            ['#f59e0b', '#d97706'],   // amber
            ['#ef4444', '#dc2626'],   // red
            ['#a855f7', '#9333ea'],   // purple
            ['#0891b2', '#0e7490'],   // cyan
            ['#ec4899', '#db2777'],   // pink
            ['#84cc16', '#65a30d'],   // lime
        ];
        $superAdminGradient = ['#fbbf24', '#f59e0b']; // gold — for SuperAdmin / Admin
    @endphp

    <style>
        /* ===== Role cards page (scoped) ===== */
        .roles-toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }
        .roles-stats {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .roles-stat {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.85rem;
            color: #475569;
            font-weight: 500;
        }
        .roles-stat strong { color: #0f172a; font-weight: 700; font-size: 0.95rem; }
        .roles-stat .stat-icon {
            width: 28px; height: 28px;
            border-radius: 7px;
            display: inline-flex; align-items: center; justify-content: center;
            color: #ffffff; font-size: 0.95rem;
        }
        .roles-stat--teal   .stat-icon { background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%); }
        .roles-stat--indigo .stat-icon { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }

        .roles-search {
            position: relative;
            margin-left: auto;
            min-width: 240px;
            max-width: 320px;
            flex: 1;
        }
        .roles-search input {
            width: 100%;
            height: 40px;
            padding: 0 12px 0 38px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
            font-size: 0.88rem;
            color: #0f172a;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .roles-search input:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12);
        }
        .roles-search input::placeholder { color: #94a3b8; }
        .roles-search i {
            position: absolute;
            left: 13px; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
        }

        /* ===== Role card ===== */
        .role-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px;
            transition: transform .15s ease, box-shadow .2s ease, border-color .15s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 200px;
            position: relative;
            overflow: hidden;
        }
        .role-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            border-color: rgba(13, 148, 136, 0.35);
        }
        .role-card__header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .role-avatar {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 0.5px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.20);
        }
        .role-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 2px 0;
            line-height: 1.25;
            word-break: break-word;
        }
        .role-id-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 500;
            letter-spacing: 0.02em;
        }
        .role-id-badge i { font-size: 0.75rem; }
        .role-superadmin-tag {
            position: absolute;
            top: 12px; right: 12px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            letter-spacing: 0.04em;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.30);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .role-superadmin-tag i { font-size: 0.78rem; }

        .role-meta {
            flex: 1;
            margin-bottom: 14px;
        }
        .role-meta__label {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 6px;
        }
        .role-meta__label-text {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
            letter-spacing: 0.01em;
        }
        .role-meta__label-count {
            font-size: 0.95rem;
            color: #0d9488;
            font-weight: 700;
        }
        .role-progress {
            height: 6px;
            background: #f1f5f9;
            border-radius: 999px;
            overflow: hidden;
        }
        .role-progress__fill {
            height: 100%;
            background: linear-gradient(90deg, #0ea5a4 0%, #0d9488 100%);
            border-radius: 999px;
            transition: width .3s ease;
        }

        .role-card__actions {
            display: flex;
            gap: 6px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
        }
        .role-card__actions .btn { flex: 1; }

        /* Empty state */
        .role-empty {
            text-align: center;
            padding: 60px 30px;
            background: #ffffff;
            border: 2px dashed #e2e8f0;
            border-radius: 14px;
            color: #94a3b8;
        }
        .role-empty i { font-size: 3rem; opacity: 0.45; display: block; margin-bottom: 10px; color: #94a3b8; }
        .role-empty .role-empty__title { color: #475569; font-weight: 600; margin-bottom: 4px; }

        /* Hidden by search filter */
        .role-card-wrap.is-hidden { display: none; }

        /* "No results" for search */
        .role-search-empty {
            display: none;
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .role-search-empty.is-active { display: block; }
    </style>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">
                <i class="ri-shield-user-line"></i> {{ trans('cruds.role.title') }}
            </h5>
            @can('role_create')
                <a class="btn btn-create mb-1" href="{{ $createRoute }}">
                    <i class="ri-add-line"></i> {{ trans('global.add') }} {{ trans('cruds.role.title_singular') }}
                </a>
            @endcan
        </div>

        <div class="card-body">
            <div class="roles-toolbar">
                <div class="roles-stats">
                    <div class="roles-stat roles-stat--teal">
                        <span class="stat-icon"><i class="ri-shield-user-line"></i></span>
                        <span><strong>{{ $totalRoles }}</strong> {{ $totalRoles === 1 ? 'role' : 'roles' }}</span>
                    </div>
                    <div class="roles-stat roles-stat--indigo">
                        <span class="stat-icon"><i class="ri-key-2-line"></i></span>
                        <span><strong>{{ $totalAssignedPerms }}</strong> permission assignments</span>
                    </div>
                </div>

                <div class="roles-search">
                    <i class="ri-search-line"></i>
                    <input type="text" id="role-filter" placeholder="Search roles..." autocomplete="off">
                </div>
            </div>

            @if ($roles->count() === 0)
                <div class="role-empty">
                    <i class="ri-shield-user-line"></i>
                    <div class="role-empty__title">No roles defined yet</div>
                    <div>Create your first role to start assigning permissions.</div>
                </div>
            @else
                <div class="row">
                    @foreach ($roles as $role)
                        @php
                            // Pick a gradient deterministically from the palette based on role name
                            $nameLower = strtolower($role->name);
                            $isPrivileged = in_array($nameLower, ['admin', 'super admin', 'superadmin']);
                            $gradient = $isPrivileged
                                ? $superAdminGradient
                                : $palette[crc32($role->name) % count($palette)];
                            $initials = collect(preg_split('/[\s_-]+/', $role->name))
                                ->filter()
                                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                ->take(2)
                                ->implode('');
                            if ($initials === '') { $initials = mb_strtoupper(mb_substr($role->name, 0, 2)); }
                            $permCount = $role->permissions->count();
                            $progressPct = $maxPerms > 0 ? min(100, ($permCount / $maxPerms) * 100) : 0;
                        @endphp

                        <div class="col-lg-4 col-md-6 mb-3 role-card-wrap" data-role-name="{{ strtolower($role->name) }}">
                            <div class="role-card">
                                @if ($isPrivileged)
                                    <span class="role-superadmin-tag"><i class="ri-vip-crown-line"></i> Admin</span>
                                @endif

                                <div class="role-card__header">
                                    <div class="role-avatar" style="background: linear-gradient(135deg, {{ $gradient[0] }} 0%, {{ $gradient[1] }} 100%); box-shadow: 0 4px 12px {{ $gradient[1] }}40;">
                                        {{ $initials }}
                                    </div>
                                    <div style="min-width: 0; flex: 1;">
                                        <h6 class="role-name">{{ $role->name }}</h6>
                                        <span class="role-id-badge"><i class="ri-hashtag"></i>{{ $role->id }}</span>
                                    </div>
                                </div>

                                <div class="role-meta">
                                    <div class="role-meta__label">
                                        <span class="role-meta__label-text">
                                            <i class="ri-key-2-line"></i> Permissions
                                        </span>
                                        <span class="role-meta__label-count">{{ $permCount }}</span>
                                    </div>
                                    <div class="role-progress">
                                        <div class="role-progress__fill" style="width: {{ $progressPct }}%;"></div>
                                    </div>
                                </div>

                                <div class="role-card__actions">
                                    @can('role_show')
                                        <a href="{{ $isWeb ? route('admin.roles.show', $role->id) : route('admin.client-roles.show', $role->id) }}"
                                            class="btn btn-soft-info btn-sm" title="{{ trans('global.view') }}">
                                            <i class="ri-eye-fill"></i>
                                        </a>
                                    @endcan
                                    @can('role_edit')
                                        <a href="{{ $isWeb ? route('admin.roles.edit', $role->id) : route('admin.client-roles.edit', $role->id) }}"
                                            class="btn btn-soft-primary btn-sm" title="{{ trans('global.edit') }}">
                                            <i class="ri-edit-2-fill"></i>
                                        </a>
                                    @endcan
                                    @can('can-delete')
                                        <form action="{{ $isWeb ? route('admin.roles.destroy', $role->id) : route('admin.client-roles.destroy', $role->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                            style="display: inline-flex; flex: 1;">
                                            @method('DELETE')
                                            @csrf
                                            <button type="submit" class="btn btn-soft-danger btn-sm w-100" title="{{ trans('global.delete') }}">
                                                <i class="ri-delete-bin-fill"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="role-search-empty" id="role-search-empty">
                    <i class="ri-search-line" style="font-size: 2rem; opacity: 0.4; display: block; margin-bottom: 8px;"></i>
                    No roles match your search.
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            var $cards = $('.role-card-wrap');
            var $empty = $('#role-search-empty');

            $('#role-filter').on('input', function () {
                var q = $(this).val().trim().toLowerCase();
                var visible = 0;
                $cards.each(function () {
                    var name = $(this).data('role-name') || '';
                    var match = q === '' || name.indexOf(q) !== -1;
                    $(this).toggleClass('is-hidden', !match);
                    if (match) visible++;
                });
                $empty.toggleClass('is-active', visible === 0 && q !== '');
            });
        });
    </script>
@endsection
