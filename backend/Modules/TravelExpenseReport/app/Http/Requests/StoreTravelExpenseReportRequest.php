<?php

namespace Modules\TravelExpenseReport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelExpenseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // draft = simpan sementara, submit = langsung finalisasi nominal
            'action' => ['sometimes', 'in:draft,submit'],
            'annual_ticket_id' => ['required', 'integer', 'exists:annual_tickets,id'],
            // Hanya relevan untuk claim reimbursement (Direct Payment)
            'total_actual_amount' => ['nullable', 'numeric', 'min:0'],
            // Lampiran reimbursement: e-ticket, boarding pass, invoice - image only agar bisa ditempel ke dokumen PDF
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png', 'max:5120'],
            // Bukti pembayaran excess ke Finance (Booked by Company + Different Route)
            'proof_of_payment' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
