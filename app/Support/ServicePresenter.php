<?php

namespace App\Support;

use App\Models\Service;

/**
 * Maps internal Service/quotation state into the customer-facing labels used by
 * the customer portal (Indonesian), matching the prototype design.
 */
class ServicePresenter
{
    /**
     * Customer-facing quotation status.
     * Prototype badges: Menunggu (warning), Disetujui (success), PO Diupload (info), Ditolak (danger).
     */
    public static function quotationStatus(Service $s): array
    {
        $status = (string) $s->quotation_status;

        if (in_array($status, ['Rejected', 'Cancelled'], true)) {
            return ['code' => 'ditolak', 'label' => 'Ditolak', 'color' => 'danger'];
        }

        if (! empty($s->po_number)) {
            return ['code' => 'po_diupload', 'label' => 'PO Diupload', 'color' => 'info'];
        }

        if ($status === 'Accepted') {
            return ['code' => 'disetujui', 'label' => 'Disetujui', 'color' => 'success'];
        }

        // Draft / Sent / Revised / empty => waiting on the customer
        return ['code' => 'menunggu', 'label' => 'Menunggu', 'color' => 'warning'];
    }

    /**
     * Service repair progress as a 4-step flow (matches prototype):
     * 1 Kendaraan Diterima -> 2 Sedang Dikerjakan -> 3 Sudah Diperbaiki -> 4 Serah Terima.
     */
    public static function serviceProgress(Service $s): array
    {
        $status = (string) $s->status;
        $hasHandover = ! empty($s->handover_date) || ! empty($s->invoice_handover_date);

        $step = 1;
        $label = 'Kendaraan Diterima';
        $badge = ['label' => 'Baru Masuk', 'color' => 'gray'];

        if ($hasHandover) {
            $step = 4;
            $label = 'Serah Terima';
            $badge = ['label' => 'Selesai', 'color' => 'success'];
        } elseif ($status === 'Completed') {
            $step = 3;
            $label = 'Sudah Diperbaiki';
            $badge = ['label' => 'Siap Diambil', 'color' => 'info'];
        } elseif (in_array($status, ['In Progress', 'Pending Parts', 'On Hold'], true)) {
            $step = 2;
            $label = 'Sedang Dikerjakan';
            $badge = ['label' => 'Dikerjakan', 'color' => 'warning'];
        }

        return [
            'step' => $step,
            'step_label' => $label,
            'total_steps' => 4,
            'steps' => [
                ['no' => 1, 'label' => 'Kendaraan Diterima', 'done' => $step >= 1, 'active' => $step === 1],
                ['no' => 2, 'label' => 'Sedang Dikerjakan', 'done' => $step >= 2, 'active' => $step === 2],
                ['no' => 3, 'label' => 'Sudah Diperbaiki', 'done' => $step >= 3, 'active' => $step === 3],
                ['no' => 4, 'label' => 'Serah Terima', 'done' => $step >= 4, 'active' => $step === 4],
            ],
            'badge' => $badge,
        ];
    }

    public static function vehicleLabel(Service $s): array
    {
        $v = $s->vehicle;

        return [
            'name' => $v ? trim(($v->brand ?? '') . ' ' . ($v->model ?? '')) : '-',
            'license_plate' => $v->license_plate ?? '-',
        ];
    }
}
