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
            'visit_session_id' => ['nullable', 'integer', 'exists:visit_sessions,id'],
            'doctor_id' => ['required_without:visit_session_id', 'integer', 'exists:master_customers,id'],
            'visit_date' => ['required_without:visit_session_id', 'date_format:Y-m-d'],
            'check_in' => ['required_without:visit_session_id', 'regex:/^\\d{2}:\\d{2}(:\\d{2})?$/'],
            'check_out' => ['required_without:visit_session_id', 'regex:/^\\d{2}:\\d{2}(:\\d{2})?$/'],
            'photo_url' => ['nullable', 'string', 'max:65535'],
            'signature_url' => ['nullable', 'string', 'max:65535'],
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
            'products.*.distinct' => 'Produk yang dibahas tidak boleh dipilih lebih dari sekali.',
            'products.*.exists' => 'Produk yang dipilih tidak valid.',
        ];
    }
}
