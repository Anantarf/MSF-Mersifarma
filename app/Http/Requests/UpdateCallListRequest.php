<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCallListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'doctor_ids' => ['required', 'array', 'min:1'],
            'doctor_ids.*' => ['integer', 'distinct', 'exists:master_customers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_ids.required' => 'Daftar dokter target wajib dipilih minimal 1 dokter.',
            'doctor_ids.min' => 'Pilih minimal 1 dokter target.',
            'doctor_ids.*.exists' => 'Dokter yang dipilih tidak terdaftar di Master Customer List (MCL).',
            'doctor_ids.*.distinct' => 'Terdapat dokter ganda dalam daftar pilihan.',
        ];
    }
}
