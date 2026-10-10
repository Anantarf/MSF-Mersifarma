<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewCallListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'required_if:status,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Keputusan status (approved atau rejected) wajib ditentukan.',
            'status.in' => 'Status keputusan harus salah satu dari: approved, rejected.',
            'reason.required_if' => 'Alasan penolakan wajib diisi jika status ditolak (rejected).',
        ];
    }
}
