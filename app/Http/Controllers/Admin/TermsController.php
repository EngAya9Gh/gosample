<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyTermRequest;
use App\Http\Requests\StoreTermRequest;
use App\Http\Requests\UpdateTermRequest;
use App\Models\Term;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TermsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('term_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $terms = Term::all();

        return inertia('Terms/TermsList', [
            'terms' => $terms,
        ]);
    }

    public function create()
    {
        abort_if(Gate::denies('term_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.terms.create');
    }

    public function store(StoreTermRequest $request)
    {
        $term = Term::create($request->all());
            return redirect()->back();
        return redirect()->route('admin.terms.index');
    }

    public function edit(Term $term)
    {
        abort_if(Gate::denies('term_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.terms.edit', compact('term'));
    }

    public function update(UpdateTermRequest $request, Term $term)
    {
        $term->update($request->all());
            return redirect()->back();
        return redirect()->route('admin.terms.index');
    }

    public function show(Term $term)
    {
        abort_if(Gate::denies('term_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.terms.show', compact('term'));
    }

    public function destroy(Term $term, Request $request)
    {
        abort_if(Gate::denies('term_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $term->delete();
            return redirect()->back();
        return back();
    }

    public function massDestroy(MassDestroyTermRequest $request)
    {
        abort_if(Gate::denies('term_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        Term::whereIn('id', request('ids'))->delete();
            return redirect()->back();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
