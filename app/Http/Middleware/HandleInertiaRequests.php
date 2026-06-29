<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /** The root Blade template loaded on the first page visit. */
    protected $rootView = 'app';

    /**
     * Prevent browser caching of the initial HTML payload and Inertia JSON responses.
     * This guarantees that clients always receive the latest Vite asset hashes
     * after a new deployment, preventing 404 errors for deleted JS chunks.
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = parent::handle($request, $next);

        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }

    /**
     * Props shared with every Inertia response (replaces the old boot payload).
     * Permissions are exposed for render-gating only — the backend Gates remain
     * the real security boundary on every route/action.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // Lazy closure → only evaluated when an Inertia response is built, so
            // classic Blade pages don't pay for getAllPermissions() on every request.
            'auth' => function () use ($request) {
                $user = $request->user();
                try {
                    $permissions = $user ? $user->getAllPermissions()->pluck('name')->values()->all() : [];
                } catch (\Throwable $e) {
                    $permissions = [];
                }
                try {
                    $canDelete = \Gate::allows('can-delete');
                } catch (\Throwable $e) {
                    $canDelete = false;
                }
                return [
                    'user' => $user ? ['name' => $user->name ?? '', 'email' => $user->email ?? ''] : null,
                    'permissions' => $permissions,
                    'canDelete' => $canDelete,
                ];
            },
            'locale' => app()->getLocale(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
