<?php

namespace App\Http\Requests;

use App\Models\Driver;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreDriverRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('driver_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'min:1',
                'max:50',
                'required',
            ],
            'password' => [
                'required',
            ],
            'working_hours_start' => [
                'required',
                // 'date_format:H:i',

            ],
            'working_hours_end' => [
                'required',
                // 'date_format:H:i',
            ],
            'status' => [
                'required',
            ],
            'username' => [
                'string',
                'required',
                'unique:drivers',
            ],
            'mobile' => [
                'string',
                'required',
                'unique:drivers',
            ],
            'email' => [
                'string',
                'required',
                'unique:drivers',
            ],
            'shift_count' => [
                'integer',
                'nullable',
            ],
            'employment_type' => [
                'string',
                'nullable',
            ],
            'total_working_hours' => [
                'integer',
                'nullable',
            ],
            'second_shift_working_hours_start' => [
                'nullable',
            ],
            'second_shift_working_hours_end' => [
                'nullable',
            ],
        ];
    }
}
