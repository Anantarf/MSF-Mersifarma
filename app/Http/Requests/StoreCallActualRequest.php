<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallActualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['nullable', 'integer', 'exists:call_plans,id'],
            'doctor_id' => ['required', 'integer', 'exists:master_customers,id'],
            'visit_date' => ['required', 'date_format:Y-m-d'],
            'check_in' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'check_out' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'photo_url' => ['nullable', 'string', 'max:2048'],
            'signature_url' => ['nullable', 'string', 'max:2048'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'distinct', 'exists:products,id'],
            'visit_reason' => ['nullable', 'string', 'max:1000'],
            'deviation_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_id.required' => 'Dokter wajib dipilih.',
            'doctor_id.exists' => 'Dokter tidak ditemukan dalam MCL.',
            'visit_date.required' => 'Tanggal kunjungan wajib diisi.',
            'visit_date.date_format' => 'Format tanggal kunjungan harus YYYY-MM-DD.',
            'check_in.required' => 'Jam check-in wajib diisi.',
            'check_in.regex' => 'Format jam check-in harus HH:MM atau HH:MM:SS.',
            'check_out.required' => 'Jam check-out wajib diisi.',
            'check_out.regex' => 'Format jam check-out harus HH:MM atau HH:MM:SS.',
            'products.*.distinct' => 'Produk detailing tidak boleh duplikat.',
            'products.*.exists' => 'Produk detailing tidak valid.',
        ];
    }
}