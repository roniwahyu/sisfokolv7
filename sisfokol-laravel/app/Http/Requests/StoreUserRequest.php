<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($userId)],
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($userId)],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password',
            'role' => 'Role',
        ];
    }
}
