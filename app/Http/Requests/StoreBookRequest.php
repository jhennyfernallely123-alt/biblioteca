<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'publisher' => ['nullable', 'string', 'max:150'],
            'published_year' => ['nullable', 'integer', 'min:0', 'max:2100'],
            'shelf_id' => ['required', 'integer', 'exists:shelves,id'],
            'rating' => ['nullable', 'integer', 'min:0', 'max:5'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
