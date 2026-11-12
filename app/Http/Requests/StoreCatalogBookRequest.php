<?php

namespace App\Http\Requests;

use App\Services\Isbn;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCatalogBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('isbn')) {
            return;
        }

        $isbn = $this->input('isbn');

        $this->merge([
            'isbn' => filled($isbn) ? Isbn::normalize((string) $isbn) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value !== null && ! Isbn::isValid((string) $value)) {
                        $fail(__('ui.catalog_isbn_invalid'));
                    }
                },
                Rule::unique('catalog_books', 'isbn'),
            ],
            'publisher' => ['nullable', 'string', 'max:150'],
            'published_year' => ['nullable', 'integer', 'min:0', 'max:2100'],
            'shelf_id' => ['required', 'integer', 'exists:shelves,id'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
