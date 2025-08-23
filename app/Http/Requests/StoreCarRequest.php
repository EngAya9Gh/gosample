<?php

namespace App\Http\Requests;

use App\Models\Car;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;


class StoreCarRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('car_create');
    }

    public function rules()
    {
        return [
            'imei' => [
                'string',
                'required',
                //'unique:cars',
	        Rule::unique('cars')->whereNull('deleted_at'), // Ignore soft-deleted records
            ],
            'plate_number' => [
                'string',
                'required',
            ],
            'afaqi' => [
                'boolean',
                'required',
            ],
            'model' => [
                'string',
                'nullable',
            ],
            'color' => [
                'string',
                'nullable',
            ],
            'contact_person' => [
                'string',
                'required',
            ],
        ];
    }
}
