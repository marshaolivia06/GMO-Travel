@php
    use Modules\MasterManagement\Models\Section;
    use Modules\MasterManagement\Models\Department;
    use Modules\MasterManagement\Models\MasterDivision;
    use Modules\UserManagement\Models\User;
    use Spatie\Activitylog\Models\Activity;

    $val = fn ($v) => ($v === null || $v === '') ? '-' : $v;
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y') : '-';
    $dateTime = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d M Y H:i') : '-';
    $currency = $report->currency ?? 'IDR';
    $money = fn ($v) => $currency . ' ' . number_format((float) ($v ?? 0), 0, ',', '.');

    $ticket = $report->annualTicket;
    $employeeId = $report->employee_id;

    $section = Section::with('department')->where('section_head_id', $employeeId)->first();
    $department = $section?->department ?? Department::where('dept_head_id', $employeeId)->first() ?? Department::where('dept_admin_id', $employeeId)->first();
    $division = MasterDivision::where('division_head_id', $employeeId)->first();

    $employee = $ticket?->employee ?? User::find($employeeId);
    $officeLocation = $employee?->employee?->office_location;
    $officeLabel = $officeLocation ? "{$officeLocation} Plant" : config('company.plant');

    $isReimbursement = $report->claim_type === 'ticket_reimbursement';
    $claimTypeLabel = $isReimbursement ? 'Ticket Reimbursement (Direct Payment)' : 'Employee Excess Settlement (Booked by Company)';
    $purpose = ($ticket?->route_condition ?? '-') . ' - ' . ($ticket?->ticket_arrangement ?? '-') . ' ' . ($isReimbursement ? 'Reimbursable' : 'Excess Settlement');

    // Region mengikuti negara destinasi eligible - sama dengan logika di frontend
    $country = strtolower(trim($employee?->eligibleDestination?->country ?? ''));
    $region = $country === 'indonesia' ? 'Domestic' : ($country === 'singapore' ? 'Singapore' : ($country !== '' ? 'Overseas' : null));

    // Route: leg departure pertama / rute return yang berbeda dari standar (kebalikan departure)
    // - Semua return sama:          "Batam - Medan / Medan - Batam"
    // - Ada return berubah rute:    "Batam - Medan / Medan - Bandung"
    $legs = $ticket?->legs ?? collect();
    $departureLeg = $legs->firstWhere('direction', 'departure');
    $departureRoute = $departureLeg ? ($departureLeg->travel_from . ' - ' . $departureLeg->travel_to) : null;

    $expectedReturnRoute = $departureLeg ? ($departureLeg->travel_to . ' - ' . $departureLeg->travel_from) : null;
    $returnLegs = $legs->where('direction', 'return')->values();
    $returnLeg = $returnLegs->first(fn ($leg) => ($leg->travel_from . ' - ' . $leg->travel_to) !== $expectedReturnRoute)
        ?? $returnLegs->first();
    $returnRoute = $returnLeg ? ($returnLeg->travel_from . ' - ' . $returnLeg->travel_to) : null;
    $route = collect([$departureRoute, $returnRoute])->filter()->implode(' / ');

    $dates = $legs->map(fn ($leg) => $leg->reschedule_date ?? $leg->travel_date)->filter()->sort()->values();
    $travelPeriod = $dates->isEmpty()
        ? '-'
        : ($dates->count() > 1 ? $dates->first()->format('d M Y') . ' - ' . $dates->last()->format('d M Y') : $dates->first()->format('d M Y'));

    $sourceLabel = $report->source_type === 'travel_order' ? 'Travel Order' : 'Annual Ticket';
    $tripTypeLabel = $report->source_type === 'travel_order' ? 'Travel Order' : 'Annual Ticket';

    // Transfer purpose mengikuti jenis claim - reimbursement & excess beda keterangan
    $transferPurpose = $isReimbursement ? 'Travel expense reimbursement' : 'Travel expense excess settlement';

    // Approval: alur authority matrix TER belum ada - tampilkan baris Requested (submitter) saja
    $requestedLog = Activity::with('causer')
        ->where('log_name', 'travel-expense-report')
        ->where('subject_type', $report->getMorphClass())
        ->where('subject_id', $report->id)
        ->where('event', 'submitted')
        ->latest('id')
        ->first();
    $requestedBy = $requestedLog?->causer?->name;
    $requestedRole = str_replace(' ', '.', $requestedLog?->properties->get('role') ?? 'Employee');
    $requestedAt = $requestedLog?->created_at;

    $attachments = collect($report->attachments ?? []);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Travel Expense Report #{{ $report->id }}</title>
    <style>
        @page { margin: 40px 75px 70px 75px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right { text-align: right; }
        .doc-head { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 22px; }
        .doc-title { font-size: 20px; font-weight: bold; letter-spacing: 3px; }
        .section { margin-bottom: 22px; }
        .section-title { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
        .grid td, .grid th { border: 1px solid #000; padding: 7px 8px; vertical-align: top; }
        .grid th { text-align: center; font-size: 10px; background: #f2f2f2; }
        .grid td.lbl { background: #f2f2f2; white-space: nowrap; font-size: 10px; }
        .info-table td { padding: 4px 6px; vertical-align: top; font-size: 11px; }
        .info-table td.lbl { width: 22%; white-space: nowrap; }
        .info-table td.val { font-weight: bold; }
        .split-table td { border: 1px solid #000; padding: 0; vertical-align: top; width: 50%; }
        .split-title { background: #d9d9d9; text-align: center; font-weight: bold; font-size: 10px; padding: 6px 8px; border-bottom: 1px solid #000; }
        .footer { position: fixed; bottom: -48px; left: 0; right: 0; border-top: 1px solid #000; padding-top: 5px; font-size: 9px; }
    </style>
</head>

<body>
    <div class="doc-head">
        <table>
            <tr>
                <td style="text-align: left; font-size: 11px; font-weight: bold;">{{ config('company.name') }}<br>{{ $officeLabel }}</td>
                <td class="right" style="vertical-align: middle; width: 1%;"><img src="{{ public_path('img/logo-eco.png') }}" style="height: 80px;" alt="Logo"></td>
            </tr>
        </table>
        <div class="doc-title">TRAVEL EXPENSE REPORT</div>
    </div>

    <div class="section">
        <div class="section-title">Employee &amp; Travel Information</div>
        <table class="grid">
            <tr>
                <td class="lbl" style="width: 16%">TER No</td>
                <td style="width: 34%; font-weight: bold;">{{ $val($report->document_number) }}</td>
                <td class="lbl" style="width: 16%">Employee</td>
                <td style="width: 34%; font-weight: bold;">{{ $val($employee?->name) }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ $sourceLabel }}</td>
                <td style="font-weight: bold;">{{ $val($ticket?->document_number) }}</td>
                <td class="lbl">Department</td>
                <td style="font-weight: bold;">{{ $val($department?->name) }}</td>
            </tr>
            <tr>
                <td class="lbl">Trip Type</td>
                <td style="font-weight: bold;">{{ $tripTypeLabel }}</td>
                <td class="lbl">Region</td>
                <td style="font-weight: bold;">{{ $val($region) }}</td>
            </tr>
            <tr>
                <td class="lbl">Travel Period</td>
                <td style="font-weight: bold;">{{ $travelPeriod }}</td>
                <td class="lbl">Route</td>
                <td style="font-weight: bold;">{{ $val($route) }}</td>
            </tr>
            <tr>
                <td class="lbl">Purpose</td>
                <td colspan="3" style="font-weight: bold;">{{ $val($purpose) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">{{ $isReimbursement ? 'Employee Bank Account' : 'Finance Bank Account' }}</div>
        <table class="grid">
            <tr>
                <td class="lbl" style="width: 16%">Bank</td>
                <td style="width: 34%">{{ $isReimbursement ? $val($employee?->employee?->bank_name) : '-' }}</td>
                <td class="lbl" style="width: 16%">Account Holder</td>
                <td style="width: 34%">{{ $isReimbursement ? $val($employee?->name) : '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Account Number</td>
                <td>{{ $isReimbursement ? $val($employee?->employee?->bank_account_number) : '-' }}</td>
                <td class="lbl">Transfer Purpose</td>
                <td>{{ $transferPurpose }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Expense Summary - Category x Currency</div>
        <table class="grid">
            <tr>
                <td style="width: 50%">Annual Ticket Settlement</td>
                <td>{{ $isReimbursement ? '-' : ($money($report->employee_excess) . ' Excess') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Reviewed &amp; Approved By</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 6%">No</th>
                    <th style="width: 24%">Role</th>
                    <th style="width: 23%">Name</th>
                    <th class="center" style="width: 22%">Date &amp; Time</th>
                    <th class="center" style="width: 25%">Status</th>
                </tr>
            </thead>
            <tbody>
                @if ($requestedBy)
                    <tr>
                        <td class="center">1</td>
                        <td>{{ $requestedRole }}</td>
                        <td>{{ $requestedBy }}</td>
                        <td class="center">{{ $dateTime($requestedAt) }}</td>
                        <td class="center">
                            <span style="display: inline; padding: 3px 6px; border-radius: 4px; font-weight: bold; background-color: #DBEAFE; color: #1D4ED8;">Requested</span>
                        </td>
                    </tr>
                @else
                    <tr><td colspan="5" class="center">-</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    @php
        // Halaman lampiran per jenis claim:
        // - Reimbursement: file yang di-upload employee (e-ticket + boarding pass + invoice)
        // - Excess: e-ticket dari GMO (booking) + bukti pembayaran excess ke Finance
        $excessProof = (! $isReimbursement && $report->proof_of_payment_path) ? $report->proof_of_payment_path : null;
        $gmoETicket = (! $isReimbursement && $ticket?->booking?->e_ticket_path) ? $ticket?->booking?->e_ticket_path : null;

        $attachmentPages = collect();
        if ($isReimbursement) {
            foreach ($attachments as $attachment) {
                $attachmentPages->push(['label' => 'E-Ticket + Boarding Pass + Invoice', 'path' => $attachment->file_path]);
            }
        } else {
            if ($gmoETicket) {
                $attachmentPages->push(['label' => 'E-Ticket from GMO', 'path' => $gmoETicket]);
            }

            if ($excessProof) {
                $attachmentPages->push(['label' => 'Proof of Excess Payment', 'path' => $excessProof]);
            }
        }
        $totalAttachments = $attachmentPages->count();
    @endphp

    @foreach ($attachmentPages as $page)
        @php
            $ext = strtolower(pathinfo($page['path'], PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
        @endphp
        <div style="page-break-before: always;">
            <div class="section-title" style="color: #808080;">Attachment {{ $loop->iteration }} of {{ $totalAttachments }} - {{ $page['label'] }}</div>
            @if ($isImage)
                <img src="{{ storage_path('app/public/' . $page['path']) }}" style="width: 100%;" alt="{{ $page['label'] }}">
            @else
                <div style="font-style: italic;">({{ strtoupper($ext) }} file - refer to the attached original file)</div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>
