<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShiftTemplate;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShiftTemplatesController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $shiftTemplates = ShiftTemplate::all();

        return view('admin.shiftTemplates.index', compact('shiftTemplates'));
    }

    public function create()
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.shiftTemplates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        ShiftTemplate::create($request->all());

        return redirect()->route('admin.shift-templates.index');
    }

    public function edit(ShiftTemplate $shiftTemplate)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.shiftTemplates.edit', compact('shiftTemplate'));
    }

    public function update(Request $request, ShiftTemplate $shiftTemplate)
    {
        $request->validate([
            'name' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $shiftTemplate->update($request->all());

        return redirect()->route('admin.shift-templates.index');
    }

    public function show(ShiftTemplate $shiftTemplate)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.shiftTemplates.show', compact('shiftTemplate'));
    }

    public function destroy(ShiftTemplate $shiftTemplate)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $shiftTemplate->delete();

        return back();
    }

    public function massDestroy(Request $request)
    {
        ShiftTemplate::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
