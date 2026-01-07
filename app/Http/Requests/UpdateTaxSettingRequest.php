<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow admins; user must be logged in
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'tax_rate' => 'required|numeric|min:0|max:100',
        ];
    }
}
