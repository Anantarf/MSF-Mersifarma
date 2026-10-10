<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'call_list_id' => ['required', 'integer', 'exists:call_lists,id'],
            'doctor_id' => ['required', 'integer', 'exists:master_customers,id'],
            'plan_date' => ['required', 'date_format:Y-m-d'],
            'planned_time' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'call_list_id.required' => 'Call List wajib dipilih.',
            'call_list_id.exists' => 'Call List tidak valid.',
            'doctor_id.required' => 'Dokter sasaran wajib dipilih.',
            'doctor_id.exists' => 'Dokter tidak ditemukan dalam master MCL.',
            'plan_date.required' => 'Tanggal rencana kunjungan wajib diisi.',
            'plan_date.date_format' => 'Format tanggal rencana harus YYYY-MM-DD.',
            'planned_time.required' => 'Waktu rencana kunjungan wajib diisi.',
        ];
    }
}
