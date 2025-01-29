<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NytRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'author' => 'nullable|string|max:255',
            'title'  => 'nullable|string|max:255',
            'isbn'   => [
                'nullable',
                'string',
                'regex:/^(\d{9}[\dXx]|\d{13})(;\d{9}[\dXx]|\;\d{13})*$/'
            ],
            'offset' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value % 20 !== 0) {
                        $fail("$attribute must be a multiple of 20.");
                    }
                }
            ],
        ];
    }
}
