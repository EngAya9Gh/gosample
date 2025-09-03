<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Car;
use App\Models\Notifications;
use App\Models\Driver;
use App\Models\Sample;
use App\Models\Task;
use App\Models\Client;
use App\Models\Afaqi;
use App\Models\Location;
use DB;
use Illuminate\Support\Carbon;

use Akaunting\Apexcharts\Chart;

use Dompdf\Dompdf;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Http;

use Gate;
use Symfony\Component\HttpFoundation\Response;

class CarDashboardController extends Controller
{
    //

    public function index()
    {
        abort_if(Gate::denies('car-dashboard'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $imeis = DB::table('cars')
            ->where('afaqi', 1)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->whereNotNull('imei')
            ->pluck('imei')
            ->toArray();
        \Log::info($imeis);
        $cars = [];

        try {
            $token = $this->generateAndSaveToken();
            $data = $this->getVehicleDataCustom($token, $imeis);
            \Log::info($data['pagination']);
            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $vehicle) {
                    $car = [
                        'id' => $vehicle['id'],
                        'name' => $vehicle['n'],
                        'i' => $vehicle['i'],
                        'profile' => $vehicle['profile'],
                        'sensors' => []
                    ];

                    $tempSensors = array_filter($vehicle['sensors'], function ($sensor) {
                        return $sensor['t'] === 'temperature';
                    });

                    $car['sensors'] = $tempSensors;
                    $cars[] = $car;
                }
            } else {
                \Log::info("Empty or invalid response from Afaqi.");
                session()->flash('error', 'Afaqi service is not available now.');
            }
            return view('car-dashboard', [
                'cars' => $cars,
            ]);
        } catch (\Exception $e) {
            \Log::error("Afaqi error: " . $e->getMessage());
            session()->flash('error', 'Afaqi service is not available now.');
            return view('error.error-afaqy');
        }
    }



    public function getVehicleData($token, $afaqiVehicleId)
    {
        $url = 'http://api.afaqy.sa/units/lists?token=' . $token;

        $response = Http::post($url, [
            'data' => [
                'simplify' => 1,
                'filters' => [
                    'imei' => [
                        'value' => $afaqiVehicleId
                    ]
                ],
                'projection' => [
                    'basic',
                    'last_update',
                    'sensors_last_val',
                    'counters',
                    'sensors'
                ]
            ]
        ]);

        if ($response->ok()) {
            return $response->json();
        } else {
            return null; // handle error here
        }
    }

    public function getVehicleDataCustom($token, $afaqiVehicles)
    {
        $url = 'https://api.afaqy.sa/units/lists?token=' . $token;
        $response = Http::withoutVerifying()
            // ->retry(3, 1000)
            // ->timeout(30)
            ->post($url, [
                'data' => [
                    'simplify' => 1,
                    'filters' => [
                        'imei' => [
                            'value' => $afaqiVehicles,
                            'op' => 'in'
                        ],
                    ],
                    'projection' => [
                        'basic',
                        'last_update',
                        'sensors_last_val',
                        'counters',
                        'sensors'
                    ],
                    'offset' => 0,
                    'limit' => 1000,
                    'simplify' => 1
                ]
            ]);
        if ($response->status() == 200) {
            return $response->json();
        } else {
            // dd([
            //     'status' => $response->status(),
            //     'error' => $response->body(),
            // ]);
            return null; // handle error here
        }
    }

    public function generateAndSaveToken()
    {


        // Check if a token already exists for today
        // $existingToken = Afaqi::where('created_at', today())->first();
        $lastRecord = Afaqi::latest()->first();

        if (!$lastRecord || Carbon::parse($lastRecord->created_at)->isToday()) {

            // get from database
            return $lastRecord->token;
        }

        // generate from API
        $url = "https://api.afaqy.sa/auth/login";
        $payload = [
            "data" => [
                "username" => "mtc-adm",
                "password" => "Mtc-adm@12345",
                "lang" => "en",
                "expire" => 24,
            ],
        ];

        $response = Http::post($url, $payload);

        if ($response->status() == 200) {
            $data = $response->json();
            $token = $data["data"]["token"];

            $tokenModel = new Afaqi();
            $tokenModel->token = $token;
            $tokenModel->save();
            return  $token;
        }
    }
}
