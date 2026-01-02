<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveRouteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'geojson' => ['nullable', 'array'],
            'geojson.type' => ['required_with:geojson', 'in:LineString'],
            'geojson.coordinates' => ['required_with:geojson', 'array', 'min:2'],
            'geojson.coordinates.*' => ['array', 'size:2'],

            'points' => ['nullable', 'array', 'min:2'],
            'points.*.lat' => ['required_with:points', 'numeric'],
            'points.*.lng' => ['required_with:points', 'numeric'],
        ];
    }
}
