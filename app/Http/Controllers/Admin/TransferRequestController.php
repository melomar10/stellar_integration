<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransferRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferRequestController extends Controller
{
    public function index(Request $request): View
    {
        $baseQuery = $this->filteredQuery($request);

        $stats = [
            'total'       => (clone $baseQuery)->count(),
            'solicitada'  => (clone $baseQuery)->where('status', TransferRequest::STATUS_SOLICITADA)->count(),
            'aprobado'    => (clone $baseQuery)->where('status', TransferRequest::STATUS_APROBADO)->count(),
            'completada'  => (clone $baseQuery)->where('status', TransferRequest::STATUS_COMPLETADA)->count(),
            'cancelada'   => (clone $baseQuery)->where('status', TransferRequest::STATUS_CANCELADA)->count(),
            'rechazada'   => (clone $baseQuery)->where('status', TransferRequest::STATUS_RECHAZADA)->count(),
        ];

        $requests = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.transfer-requests.index', [
            'requests' => $requests,
            'stats'    => $stats,
            'filters'  => [
                'date_from' => $request->input('date_from', ''),
                'date_to'   => $request->input('date_to', ''),
                'status'    => $request->input('status', ''),
                'search'    => $request->input('search', ''),
            ],
        ]);
    }

    public function cancel(TransferRequest $transferRequest): RedirectResponse
    {
        if (! $transferRequest->canBeCancelled()) {
            return redirect()
                ->route('admin.transfer-requests.index')
                ->with('error', 'Esta solicitud no puede cancelarse en su estado actual.');
        }

        $transferRequest->update(['status' => TransferRequest::STATUS_CANCELADA]);

        return redirect()
            ->route('admin.transfer-requests.index')
            ->with('success', 'Solicitud cancelada correctamente.');
    }

    public static function statusLabel(string $status): string
    {
        return match (strtolower($status)) {
            TransferRequest::STATUS_SOLICITADA => 'Solicitada',
            TransferRequest::STATUS_RECHAZADA => 'Rechazada',
            TransferRequest::STATUS_APROBADO   => 'Aprobada',
            TransferRequest::STATUS_COMPLETADA => 'Completada',
            TransferRequest::STATUS_CANCELADA  => 'Cancelada',
            default                            => ucfirst($status),
        };
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = TransferRequest::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $term = '%'.trim((string) $request->input('search')).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('sender_phone', 'like', $term)
                    ->orWhere('receiver_phone', 'like', $term)
                    ->orWhere('uuid', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        return $query;
    }
}
