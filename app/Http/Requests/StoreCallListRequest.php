<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'period_month' => ['required', 'date_format:Y-m'],
            'doctor_ids' => ['required', 'array', 'min:1'],
            'doctor_ids.*' => ['integer', 'distinct', 'exists:master_customers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_month.required' => 'Periode bulan wajib diisi.',
            'period_month.date_format' => 'Format periode bulan harus YYYY-MM (contoh: 2026-11).',
            'doctor_ids.required' => 'Daftar dokter target wajib dipilih minimal 1 dokter.',
            'doctor_ids.min' => 'Pilih minimal 1 dokter target.',
            'doctor_ids.*.exists' => 'Dokter yang dipilih tidak terdaftar di Master Customer List (MCL).',
            'doctor_ids.*.distinct' => 'Terdapat dokter ganda dalam daftar pilihan.',
        ];
    }
}
