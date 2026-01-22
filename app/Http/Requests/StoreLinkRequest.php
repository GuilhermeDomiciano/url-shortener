<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'original_url' => ['required', 'url', 'max:2048'],
            'custom_slug' => ['nullable', 'regex:/^[A-Za-z0-9_-]{3,64}$/', 'unique:links,slug'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
