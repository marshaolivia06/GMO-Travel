<?php

namespace Modules\ExternalFormRequest\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\ExternalFormRequest\Models\ExternalFormRequest;
use Modules\ExternalFormRequest\Repositories\ExternalFormRequestRepository;
use Modules\UserManagement\Models\User;

class ExternalFormRequestService
{
    public function __construct(private ExternalFormRequestRepository $repository) {}

    public function listFor(User $user)
    {
        return $this->repository->allFor($user);
    }

    public function create(User $user, array $data, array $travelers): ExternalFormRequest
    {
        return $this->repository->create($user, $data, $travelers);
    }

    public function update(User $user, int $id, array $data, array $travelers): ExternalFormRequest
    {
        return $this->repository->update($user, $id, $data, $travelers);
    }

    public function approve(User $user, int $id): ExternalFormRequest
    {
        return $this->repository->decide($user, $id, 'approve');
    }

    public function revision(User $user, int $id, ?string $remark = null): ExternalFormRequest
    {
        return $this->repository->revision($user, $id, $remark);
    }

    public function reject(User $user, int $id, ?string $remark = null): ExternalFormRequest
    {
        return $this->repository->reject($user, $id, $remark);
    }

    public function history(User $user, int $id): array
    {
        return $this->repository->historyFor($this->repository->findFor($user, $id));
    }

    public function pdf(int $id, User $user)
    {
        $form = $this->repository->findFor($user, $id);

        $view = match (true) {
            $form->type === ExternalFormRequest::TYPE_HOTEL_RESERVATION => 'externalformrequest::pdf.external_form_hotel',
            $form->transport_mode === 'Airplane' => 'externalformrequest::pdf.external_form_airplane',
            $form->transport_mode === 'Ferry' => 'externalformrequest::pdf.external_form_ferry',
            default => 'externalformrequest::pdf.external_form_ticket',
        };

        // Nama approver hanya tampil setelah dia approve/review (baris pending tetap '-');
        // PDF menampilkan rantai approval round terakhir saja
        $round = $form->approvals->max('round') ?? 1;

        return Pdf::loadView($view, [
            'form' => $form,
            'approvals' => $form->approvals->where('round', $round)->sortBy('step')->values(),
        ])->setPaper('a4');
    }
}
