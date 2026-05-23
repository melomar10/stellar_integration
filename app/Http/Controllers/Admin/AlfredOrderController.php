<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlfredAccount;
use App\Models\AlfredOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlfredOrderController extends Controller
{
    public function index(Request $request): View
    {
        $baseQuery = $this->filteredQuery($request);

        $statsQuery = clone $baseQuery;

        $stats = [
            'total_count'           => (clone $statsQuery)->count(),
            'completed_count'       => (clone $statsQuery)->where('status', AlfredOrder::STATUS_COMPLETED)->count(),
            'not_completed_count'   => (clone $statsQuery)->where('status', '!=', AlfredOrder::STATUS_COMPLETED)->count(),
            'total_amount'          => (float) ((clone $statsQuery)->sum('total_amount_value') ?? 0),
            'completed_amount'      => (float) ((clone $statsQuery)->where('status', AlfredOrder::STATUS_COMPLETED)->sum('total_amount_value') ?? 0),
            'not_completed_amount'  => (float) ((clone $statsQuery)->where('status', '!=', AlfredOrder::STATUS_COMPLETED)->sum('total_amount_value') ?? 0),
        ];

        $orders = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'stats'  => $stats,
            'filters' => [
                'date_from' => $request->input('date_from', ''),
                'date_to'   => $request->input('date_to', ''),
                'search'    => $request->input('search', ''),
            ],
        ]);
    }

    public function show(AlfredOrder $order): View
    {
        $order->load(['senderAccount', 'receiverAccount', 'alfredQuote']);

        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }

    public static function accountDisplayName(?AlfredAccount $account): string
    {
        if ($account === null) {
            return '—';
        }

        $full = trim((string) ($account->full_name ?? ''));
        if ($full !== '') {
            return $full;
        }

        $name = trim(trim((string) ($account->first_name ?? '')).' '.trim((string) ($account->last_name ?? '')));

        return $name !== '' ? $name : '—';
    }

    public static function statusLabel(string $status): string
    {
        return match (strtolower($status)) {
            AlfredOrder::STATUS_PROCESSED  => 'Procesada',
            AlfredOrder::STATUS_CONFIRMED  => 'Confirmada',
            AlfredOrder::STATUS_COMPLETED  => 'Completada',
            'refunded'                     => 'Reembolsada',
            'reversed'                     => 'Revertida',
            'failed'                       => 'Fallida',
            default                        => ucfirst($status),
        };
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = AlfredOrder::query()->with(['senderAccount', 'receiverAccount']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $outer) use ($like) {
                $outer->whereHas('senderAccount', function (Builder $account) use ($like) {
                    $this->applyAccountNameSearch($account, $like);
                })->orWhereHas('receiverAccount', function (Builder $account) use ($like) {
                    $this->applyAccountNameSearch($account, $like);
                });
            });
        }

        return $query;
    }

    private function applyAccountNameSearch(Builder $query, string $like): void
    {
        $query->where(function (Builder $q) use ($like) {
            $q->where('full_name', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", [$like]);
        });
    }
}
