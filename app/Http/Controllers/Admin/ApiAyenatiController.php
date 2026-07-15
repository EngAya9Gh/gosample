<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiAyenati;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class ApiAyenatiController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('api_ayenati_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = ApiAyenati::query()
            ->when($request->search, fn ($q, $s) =>
                $q->where('api_url', 'like', "%{$s}%")
                  ->orWhere('response', 'like', "%{$s}%")
                  ->orWhere('response_flag', 'like', "%{$s}%")
            )
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString();

        return inertia('ApiAyenati/ApiAyenatiList', [
            'logs' => $query,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(ApiAyenati $apiAyenati)
    {
        abort_if(Gate::denies('api_ayenati_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.apiAyenatis.show', compact('apiAyenati'));
    }
}
