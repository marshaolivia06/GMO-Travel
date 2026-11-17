{{-- Partial tabel approval - dipakai ulang untuk tabel alur request & tabel pengajuan pembatalan --}}
{{-- $title = judul section, $rows = koleksi baris approval (object: sequence, role, name, acted_at, status, action_type) --}}
{{-- $pageBreak = paksa mulai di halaman baru --}}
@if ($rows->isNotEmpty())
    <div class="section" @if ($pageBreak ?? false) style="page-break-before: always;" @endif>
        <div class="section-title">{{ $title }}</div>
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
                @foreach ($rows->sortBy('sequence') as $step)
                    @php
                        $role = $step->role instanceof \Spatie\Permission\Models\Role ? $step->role->name : $step->role;

                        $organization = match ($role) {
                            'Sect Head' => $department?->name,
                            'Dept Head' => $department?->name,
                            'Div Head' => $division?->division_name,
                            default => null,
                        };

                        $displayRole = str_replace(' ', '.', $role);
                        $roleLabel = $organization
                            ? "{$organization} ({$displayRole})"
                            : $displayRole;

                        $statusStyle = match ($step->status) {
                            'submitted' => 'background-color: #DBEAFE; color: #1D4ED8;',
                            'approved', 'checked' => 'background-color: #DCFCE7; color: #15803D;',
                            'revision', 'pending' => 'background-color: #FEF9C3; color: #A16207;',
                            default => '',
                        };

                        $statusLabel = match ($step->status) {
                            'submitted' => 'Requested',
                            'checked' => 'Reviewed',
                            'approved' => 'Approved',
                            'revision' => 'Revision',
                            'finalized' => 'Finalized',
                            'rejected' => 'Rejected',
                            'pending' => match ($step->action_type) {
                                'check' => 'Awaiting Review',
                                'finalization' => 'Awaiting Finalization',
                                default => 'Awaiting Approval',
                            },
                            default => $step->status,
                        };
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $val($roleLabel) }}</td>
                        <td>{{ $val($step->name) }}</td>
                        <td class="center">{{ $dateTime($step->acted_at) }}</td>
                        <td class="center">
                            <span style="display: inline; padding: 3px 6px; border-radius: 4px; font-weight: bold; {{ $statusStyle }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
