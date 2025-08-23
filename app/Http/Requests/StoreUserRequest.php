<?php

namespace App\Http\Requests;

use App\Models\User;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('user_create');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'email' => [
                'required',
                'unique:users',
            ],
            'password' => [
                'required',
            ],
            'roles.*' => [
                'integer',
            ],
            'roles' => [
                'required',
                'array',
            ],
            'client_id' => [
                Rule::requiredIf(function () {
                  $roles = array_map('intval', $this->input('roles', []));
                  return !in_array(1, $roles);
                }),
                'nullable',
                'exists:clients,id',
            ],
        ];
    }
}
