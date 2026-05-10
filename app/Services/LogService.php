<?php

namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
class LogService
{
    protected $headers = null;
    protected $client = null;

    public function __construct()
    {
        $this->client = new Client($this->attachHeader());
    }

    public function hasIntegration($task)
    {
        if (isset($task->client) && isset($task->client->id)) {
            if ($task->client->is_blazma == 1) {
                return true;
            }
        }
        return false;
    }

    public function attachHeader()
    {
        return  [
            'headers' => $this->headers ?? [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->getSecretKey()
            ]
        ];
    }

    public function headers(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function getSecretKey(){
        // return env('LOG_SECRETE_KEY',"as_das#DA3AWR2313%432^3essd#@4_#$=");
        return config('services.blazma.LOG_SECRETE_KEY');
    }

    public function do($method, $url, $payload = null)
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->getSecretKey()
            ])->timeout(6)->post($this->baseUrl($url), ['samples'=>$payload]);
            //$response =  Http::withHeaders($this->attachHeader())->{$method}($this->baseUrl($url), $payload);
            //$response =  $this->client->request("$method",$this->baseUrl($url), $payload);
            return $this->handleResponse($response);
        }
        catch (\Exception $exception){
            return (object)(['success' => false, 'status' => 500, 'payload' => [], 'errors' => $exception->getMessage() ?? []]);
        }
    }

    public function handleRequest($method,$payload)
    {
        if ($method == 'post'){
            return ['form_params' => $payload];
        }

        return ['query' =>  (array) $payload];
    }

    public function handleResponse($res = null)
    {
        try {
            $response = json_decode($res->getBody());
            $statusCode = $res->getStatusCode();
            return (object)(
            ($response->success && ($statusCode >= 200 && $statusCode < 300)) ?
                ['success' => true, 'status' => $statusCode, 'payload' => $response->payload ?? [], 'errors' => null] :
                ['success' => false, 'status' => $statusCode, 'payload' => [], 'errors' => $response->error ?? []]
            );
        }catch (\Exception $exception){
            return (object)(['success' => false, 'status' => 500, 'payload' => [], 'errors' => $exception->getMessage() ?? []]);
        }
    }

    public function addLog($request)
    {
        $payload = $this->handlerPayload($request);
        return $this->do('post','/api/samples/log',$payload);
    }

    public function handlerPayload($samples)
    {

        $allSamples = [];
        foreach ($samples as $payload) {
            $payload = (object) $payload;
            $data = [
                'PROFILE_ID' => $payload->PROFILE_ID ?? null,
                'END_USER_LAB_ORDER_PACKAGE_ID' => $payload->END_USER_LAB_ORDER_PACKAGE_ID ?? null,
                'END_USER_LAB_ORDER_PACKAGE_SAMPLE_ID' => $payload->END_USER_LAB_ORDER_PACKAGE_SAMPLE_ID ?? null,
                'END_USER_LAB_ORDER_ID' => $payload->END_USER_LAB_ORDER_ID ?? null,
                'SYSTEM_USER_ID' => $payload->SYSTEM_USER_ID ?? null,
                'SYSTEM_USER_NAME' => $payload->SYSTEM_USER_NAME ?? null,
                'UPDATED_COLUMN' => $payload->UPDATED_COLUMN ?? null,
                'PREUPDATE' => $payload->PREUPDATE ?? null,
                'POSTUPDATE' => $payload->POSTUPDATE ?? null,
                'UPDATE_TIME' => $payload->UPDATE_TIME ?? null,
                'STATUS_ID' => $payload->STATUS_ID ?? null,
                'STATUS_NAME' => $payload->STATUS_NAME ?? null,
                'CLIENT_ID' => $payload->CLIENT_ID ?? null,
                'CLIENT_NAME' => $payload->CLIENT_NAME ?? null,
                'HOSPITAL_ID' => $payload->HOSPITAL_ID ?? null,
                'HOSPITAL_NAME' => $payload->HOSPITAL_NAME ?? null,//registration branch
                'PROFILE_UNIT_ID' => $payload->PROFILE_UNIT_ID ?? null,
                'PROFILE_UNIT_NAME' => $payload->PROFILE_UNIT_NAME ?? null,
                'END_USER_IS_COMPANY' => $payload->END_USER_IS_COMPANY ?? 0,
                'LAB_CATEGORY_PACKAGE_TEST_CATEGORY' => $payload->LAB_CATEGORY_PACKAGE_TEST_CATEGORY ?? null,
                'LAB_CATEGORY_PACKAGE_ID' => $payload->LAB_CATEGORY_PACKAGE_ID ?? -1,
                'LAB_CATEGORY_PACKAGE_NAME' => $payload->LAB_CATEGORY_PACKAGE_NAME ?? null,
                'IDENTIFICATION' => $payload->IDENTIFICATION ?? null,
                'PATIENT_NAME' => $payload->PATIENT_NAME ?? null,
                'PROCESSING_HOSPITAL_ID' => $payload->PROCESSING_HOSPITAL_ID ?? null,
                'PROCESSING_HOSPITAL_NAME' => $payload->PROCESSING_HOSPITAL_NAME ?? null,
                'ANALYZER_ID' => $payload->ANALYZER_ID ?? null,
                'ANALYZER_NAME' => $payload->ANALYZER_NAME ?? null,
                'HOSPITAL_BRANCH_ID' => $payload->HOSPITAL_BRANCH_ID ?? null,
                'HOSPITAL_BRANCH_NAME' => $payload->HOSPITAL_BRANCH_NAME ?? null,
                'IS_MTC' => true,
                'ICON' => $payload->ICON ?? null
            ];
            $allSamples [] = $data;
        }
        return $allSamples;
    }

    public function baseUrl(string $url)
    {
        return config('services.blazma.LOG_HOSTS', 'http://158.101.243.250') . $url;
    }
}
