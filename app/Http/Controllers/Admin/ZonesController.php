<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyZoneRequest;
use App\Http\Requests\StoreZoneRequest;
use App\Http\Requests\UpdateZoneRequest;
use App\Models\Zone;
use Gate;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ZonesController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('zone_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // SPA (/app) list + its JSON reloads. The classic Blade page below is untouched.
        if ($request->wantsJson() || true) {
            $query = Zone::query();

            if ($request->filled('keyword')) {
                $kw = $request->keyword;
                $query->where(function ($q) use ($kw) {
                    $q->where('id', 'LIKE', "%{$kw}%")->orWhere('name', 'LIKE', "%{$kw}%");
                });
            }

            $sortBy = in_array($request->input('sort_by'), ['id', 'name', 'created_at']) ? $request->sort_by : 'id';
            $sortOrder = $request->input('sort_order') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $pageSize = max(1, min((int) $request->input('pageSize', 25), 100));
            $page = max(1, (int) $request->input('page', 1));
            $total = (clone $query)->count();
            $offset = ($page - 1) * $pageSize;

            $seq = $offset;
            $rows = $query->offset($offset)->limit($pageSize)->get()->map(function ($z) use (&$seq) {
                $seq++;
                // GeoJSON ring [[lng,lat],…] — same source the classic show page
                // reads ($zone->area->toJson()); flipped to {lat,lng} for the UI.
                $ring = [];
                try {
                    $geo = json_decode($z->area ? $z->area->toJson() : '', true);
                    foreach (($geo['coordinates'][0] ?? []) as $c) {
                        $ring[] = ['lat' => (float) $c[1], 'lng' => (float) $c[0]];
                    }
                } catch (\Throwable $e) {
                    $ring = [];
                }
                return [
                    'sequence'   => $seq,
                    'id'         => $z->id,
                    'name'       => $z->name,
                    'points'     => $ring,
                    'created_at' => $z->created_at ? $z->created_at->format('Y-m-d H:i') : null,
                ];
            });

            if ($request->wantsJson()) {
                return response()->json(['rows' => $rows, 'total' => $total]);
            }

            return \Inertia\Inertia::render('Zones/ZonesList', [
                'initialRows'  => $rows,
                'initialTotal' => $total,
            ]);
        }

        $zones = Zone::all();

        return view('admin.zones.index', compact('zones'));
    }

    /**
     * Create a zone from the SPA modal. Same polygon-building logic as the
     * classic store(): Point(lat, lng) per vertex + the first vertex appended
     * again to close the ring.
     */
    public function storePopup(Request $request)
    {
        abort_if(Gate::denies('zone_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $this->validatePopup($request);

        $zone = new Zone();
        $zone->name = $data['name'];
        $zone->area = $this->buildPolygon($data['area']);
        $zone->save();

        return redirect()->back()->with('success', 'Zone created successfully');
    }

    /**
     * Update a zone from the SPA modal. Parses the polygon the same way as
     * storePopup (the classic edit page also expects a redrawn polygon).
     */
    public function updatePopup(Request $request, Zone $zone)
    {
        abort_if(Gate::denies('zone_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $this->validatePopup($request);

        $zone->name = $data['name'];
        $zone->area = $this->buildPolygon($data['area']);
        $zone->save();

        return redirect()->back()->with('success', 'Zone updated successfully');
    }

    private function validatePopup(Request $request): array
    {
        return $request->validate([
            'name'       => ['required', 'string'],
            'area'       => ['required', 'array', 'min:3'],
            'area.*.lat' => ['required', 'numeric'],
            'area.*.lng' => ['required', 'numeric'],
        ], [
            'area.required' => 'Draw the zone area on the map (at least 3 points).',
            'area.min'      => 'The zone area needs at least 3 points.',
        ]);
    }

    private function buildPolygon(array $points): Polygon
    {
        $ring = [];
        foreach ($points as $p) {
            $ring[] = new Point((float) $p['lat'], (float) $p['lng']);
        }
        // close the ring — same as the classic store()
        $ring[] = new Point((float) $points[0]['lat'], (float) $points[0]['lng']);

        return new Polygon([new LineString($ring)]);
    }

    public function create()
    {
        abort_if(Gate::denies('zone_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.zones.create');
    }

    public function store(StoreZoneRequest $request)
    {
        // \Log::info($request->all());
        // $zone = Zone::create($request->all());
        $zone = new Zone();

        $polygon = json_decode($request->area, true);

        $area=array();
        foreach ($polygon as $point) {
            $area[] = new Point($point['lat'], $point['lng']);
        }
        $area[]= new Point($polygon[0]['lat'], $polygon[0]['lng']);
        $zone->name = $request->name;
        $zone->area =  new Polygon([new LineString( $area)]);
        $zone->save();
        return redirect()->route('admin.zones.index');
    }

    public function edit(Zone $zone)
    {
        abort_if(Gate::denies('zone_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.zones.edit', compact('zone'));
    }

    public function update(UpdateZoneRequest $request, Zone $zone)
    {
        $zone->update($request->all());

        return redirect()->route('admin.zones.index');
    }

    public function show(Zone $zone)
    {
        abort_if(Gate::denies('zone_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.zones.show', compact('zone'));
    }

    public function destroy(Zone $zone)
    {
        $this->authorize('can-delete');

        $zone->delete();

        return back();
    }

    public function massDestroy(MassDestroyZoneRequest $request)
    {
        $this->authorize('can-delete');
        Zone::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
