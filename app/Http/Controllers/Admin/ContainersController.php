<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyContainerRequest;
use App\Http\Requests\StoreContainerRequest;
use App\Http\Requests\UpdateContainerRequest;
use App\Models\Car;
use App\Models\Container;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContainersController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('container_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // SPA (/app) list + its JSON reloads. The classic Blade page below is untouched.
        if ($request->wantsJson() || true) {
            $query = Container::with(['car']);

            if ($request->filled('keyword')) {
                $kw = $request->keyword;
                $query->where(function ($q) use ($kw) {
                    $q->where('id', 'LIKE', "%{$kw}%")
                      ->orWhere('imei', 'LIKE', "%{$kw}%")
                      ->orWhere('model', 'LIKE', "%{$kw}%")
                      ->orWhere('description', 'LIKE', "%{$kw}%")
                      ->orWhereHas('car', function ($q2) use ($kw) {
                          $q2->withoutGlobalScope('enabled')->where('plate_number', 'LIKE', "%{$kw}%");
                      });
                });
            }
            if ($request->filled('car_id')) {
                $query->where('car_id', $request->car_id);
            }
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $sortBy = in_array($request->input('sort_by'), ['id', 'created_at']) ? $request->sort_by : 'id';
            $sortOrder = $request->input('sort_order') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $pageSize = max(1, min((int) $request->input('pageSize', 25), 100));
            $page = max(1, (int) $request->input('page', 1));
            $total = (clone $query)->count();
            $offset = ($page - 1) * $pageSize;

            $seq = $offset;
            $rows = $query->offset($offset)->limit($pageSize)->get()->map(function ($c) use (&$seq) {
                $seq++;
                return [
                    'sequence'    => $seq,
                    'id'          => $c->id,
                    'car_id'      => $c->car_id,
                    // classic index reads car->plate_number through the same
                    // relation (Car's 'enabled' scope applies there too)
                    'car_name'    => $c->car ? $c->car->plate_number : null,
                    'imei'        => $c->imei,
                    'type'        => $c->type,
                    'model'       => $c->model,
                    'description' => $c->description,
                    'status'      => $c->status, // 1: enabled, 2: disabled
                    'created_at'  => $c->created_at ? $c->created_at->format('Y-m-d H:i') : null,
                ];
            });

            if ($request->wantsJson()) {
                return response()->json(['rows' => $rows, 'total' => $total]);
            }

            // Same list the classic create/edit forms show (enabled cars only).
            $cars = Car::select('id', 'plate_number')->get()
                ->map(fn ($car) => ['value' => $car->id, 'label' => $car->plate_number])->values();

            return \Inertia\Inertia::render('Containers/ContainersList', [
                'initialRows'  => $rows,
                'initialTotal' => $total,
                'filters'      => ['cars' => $cars],
            ]);
        }

        $containers = Container::with(['car'])->get();

        return view('admin.containers.index', compact('containers'));
    }

    /**
     * Printable barcode page for a container — same content the classic index
     * prints (logo, type, car plate, Code128 of "{id}-container"); opens in a
     * new tab and triggers the print dialog itself.
     */
    public function barcode(Container $container)
    {
        abort_if(Gate::denies('container_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $container->load('car');
        $svg = \DNS1D::getBarcodeSVG($container->id . '-container', 'C128', 5, 100);
        $logo = asset('assets/img/logo_excel_2.jpg');
        $plate = e($container->car->plate_number ?? '');

        return response(
            "<html><head><title>Container #{$container->id}</title>" .
            '<style>@page{margin:0}body{padding-top:10rem;margin:0 auto;text-align:center;font-family:sans-serif}svg{margin-top:20px}</style>' .
            "</head><body onload=\"window.print()\">" .
            "<img src=\"{$logo}\" alt=\"\" style=\"height:200px\">" .
            "<h1>Type: {$container->type}</h1>" .
            "<h1>Car Number: {$plate}</h1>" .
            $svg .
            '</body></html>'
        );
    }

    public function create()
    {
        abort_if(Gate::denies('container_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $cars = Car::pluck('plate_number', 'id')->prepend(trans('translation.pleaseSelect'), '');

        return view('admin.containers.create', compact('cars'));
    }

    public function store(StoreContainerRequest $request)
    {
        $container = Container::create($request->all());

        return redirect()->route('admin.containers.index');
    }

    /**
     * Create a container from the SPA modal (Car details page). Mirrors the
     * classic create form 1:1 — car optional, sensor IMEI/model/type/status
     * required; redirects back so the Inertia page refreshes its containers
     * list.
     */
    public function storePopup(Request $request)
    {
        abort_if(Gate::denies('container_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'car_id'      => ['nullable', 'integer'],
            'imei'        => ['required', 'string'],
            'type'        => ['required', 'in:' . implode(',', array_keys(Container::TYPE_SELECT))],
            'model'       => ['required', 'string'],
            'status'      => ['required', 'in:' . implode(',', array_keys(Container::STATUS_SELECT))],
            'description' => ['nullable', 'string'],
        ]);

        Container::create($data);

        return redirect()->back()->with('success', 'Container created successfully');
    }

    /**
     * Update a container from the SPA modal (Car details page). Same field
     * set as the classic edit form / UpdateContainerRequest; redirects back
     * so the Inertia page refreshes its containers list.
     */
    public function updatePopup(Request $request, Container $container)
    {
        abort_if(Gate::denies('container_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'car_id'      => ['nullable', 'integer'],
            'imei'        => ['required', 'string'],
            'type'        => ['required', 'in:' . implode(',', array_keys(Container::TYPE_SELECT))],
            'model'       => ['required', 'string'],
            'status'      => ['required', 'in:' . implode(',', array_keys(Container::STATUS_SELECT))],
            'description' => ['nullable', 'string'],
        ]);

        $container->update($data);

        return redirect()->back()->with('success', 'Container updated successfully');
    }

    public function edit(Container $container)
    {
        abort_if(Gate::denies('container_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $cars = Car::pluck('plate_number', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $container->load('car');

        return view('admin.containers.edit', compact('cars', 'container'));
    }

    public function update(UpdateContainerRequest $request, Container $container)
    {
        $container->update($request->all());

        return redirect()->route('admin.containers.index');
    }

    public function show(Request $request, Container $container)
    {
        abort_if(Gate::denies('container_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $container->load('car');

        $bags = collect();
        if (Gate::allows('view_bag_container_details')) {
            $samples = \App\Models\Sample::where('container_id', $container->id)->get();
            $bags = $samples->groupBy('bag_code');
        }

        // SPA (/app) details page — same data the classic show page renders:
        // the details table, the on-page barcode SVG and the bags-per-container
        // breakdown (first sample's type/temperature per bag, like the Blade).
        $bagRows = $bags->map(function ($bag, $code) {
            return [
                'bag_code'         => $code,
                'total'            => count($bag),
                'sample_type'      => $bag[0]->sample_type ?? null,
                'temperature_type' => $bag[0]->temperature_type ?? null,
            ];
        })->values();

        // Same enabled-cars list the classic create/edit forms show (for the edit popup).
        $cars = Car::select('id', 'plate_number')->get()
            ->map(fn ($c) => ['value' => $c->id, 'label' => $c->plate_number])->values();

        return \Inertia\Inertia::render('Containers/ContainerView', [
            'container' => [
                'id'          => $container->id,
                'car_id'      => $container->car_id,
                'car_plate'   => $container->car->plate_number ?? null,
                'car_imei'    => $container->car->imei ?? null,
                'imei'        => $container->imei,
                'type'        => $container->type,
                'model'       => $container->model,
                'description' => $container->description,
                'status'      => $container->status,
                'created_at'  => $container->created_at ? $container->created_at->format('Y-m-d H:i') : null,
            ],
            'barcodeSvg'  => \DNS1D::getBarcodeSVG($container->id . '-container', 'C128', 3, 55),
            'bags'        => $bagRows,
            'canViewBags' => Gate::allows('view_bag_container_details'),
            'cars'        => $cars,
        ]);
    }

    public function destroy(Container $container)
    {
        $this->authorize('can-delete');

        $container->delete();

        return back();
    }

    public function massDestroy(MassDestroyContainerRequest $request)
    {
        $this->authorize('can-delete');
        Container::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
