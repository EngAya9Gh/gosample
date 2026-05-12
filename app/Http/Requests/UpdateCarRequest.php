<?php

namespace App\Http\Requests;

use App\Models\Car;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateCarRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('car_edit');
    }

    public function rules()
    {
        return [
            'imei' => [
                'string',
                'required',
                Rule::unique('cars', 'imei')
                ->ignore(request()->route('car'))
                ->whereNull('deleted_at'),
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
            'status' => [
                'required',
                'in:1,2',
            ],
        ];
    }
}
