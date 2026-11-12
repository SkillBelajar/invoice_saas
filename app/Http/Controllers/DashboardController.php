<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $today = Carbon::today();

        // 1. Ringkasan Finansial Utama
        $paidInvoices = $user->invoices()->where('status', 'paid');
        $unpaidInvoices = $user->invoices()->where('status', 'unpaid');

        $totalRevenue = (float) (clone $paidInvoices)->sum('grand_total');
        $totalReceivables = (float) (clone $unpaidInvoices)->sum('grand_total');
        $totalInvoicesCount = $user->invoices()->count();
        $paidCount = (clone $paidInvoices)->count();
        $unpaidCount = (clone $unpaidInvoices)->count();
        $draftCount = $user->invoices()->where('status', 'draft')->count();
        $totalClientsCount = $user->clients()->count();

        // 2. Laporan Aging Piutang (Accounts Receivable Aging)
        // Dihitung dari seluruh invoice yang berstatus belum dibayar ('unpaid')
        $allUnpaid = $user->invoices()
            ->where('status', 'unpaid')
            ->select(['id', 'invoice_number', 'client_id', 'grand_total', 'due_date', 'issue_date'])
            ->get();

        $aging = [
            'current' => ['label' => 'Belum Jatuh Tempo', 'amount' => 0.0, 'count' => 0],
            'overdue_1_30' => ['label' => '1 - 30 Hari', 'amount' => 0.0, 'count' => 0],
            'overdue_31_60' => ['label' => '31 - 60 Hari', 'amount' => 0.0, 'count' => 0],
            'overdue_60_plus' => ['label' => '> 60 Hari', 'amount' => 0.0, 'count' => 0],
            'total' => 0.0,
        ];

        foreach ($allUnpaid as $inv) {
            $dueDate = Carbon::parse($inv->due_date)->startOfDay();
            $amount = (float) $inv->grand_total;
            $aging['total'] += $amount;

            if ($dueDate->gte($today)) {
                $aging['current']['amount'] += $amount;
                $aging['current']['count']++;
            } else {
                $daysOverdue = abs((int) $today->diffInDays($dueDate));
                if ($daysOverdue <= 30) {
                    $aging['overdue_1_30']['amount'] += $amount;
                    $aging['overdue_1_30']['count']++;
                } elseif ($daysOverdue <= 60) {
                    $aging['overdue_31_60']['amount'] += $amount;
                    $aging['overdue_31_60']['count']++;
                } else {
                    $aging['overdue_60_plus']['amount'] += $amount;
                    $aging['overdue_60_plus']['count']++;
                }
            }
        }

        // 3. Faktur Jatuh Tempo & Perlu Ditindaklanjuti (Maksimal 6 transaksi)
        $attentionInvoices = $user->invoices()
            ->where('status', 'unpaid')
            ->with('client:id,name,phone')
            ->orderBy('due_date', 'asc')
            ->limit(6)
            ->get()
            ->map(function ($inv) use ($today) {
                $dueDate = Carbon::parse($inv->due_date)->startOfDay();
                $isOverdue = $dueDate->lt($today);
                $daysDiff = (int) abs($today->diffInDays($dueDate));

                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'client_name' => $inv->client->name ?? 'Tanpa Nama',
                    'client_phone' => $inv->client->phone ?? null,
                    'due_date' => $inv->due_date->format('d M Y'),
                    'raw_due_date' => $inv->due_date->toDateString(),
                    'grand_total' => (float) $inv->grand_total,
                    'status' => $inv->status,
                    'is_overdue' => $isOverdue,
                    'days_text' => $isOverdue 
                        ? "Terlambat {$daysDiff} hari" 
                        : ($daysDiff === 0 ? "Jatuh tempo hari ini" : "Sisa {$daysDiff} hari"),
                ];
            });

        // 4. Tren Pendapatan 6 Bulan Terakhir
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::today()->subMonths($i);
            $yearMonth = $monthDate->format('Y-m');
            $label = $monthDate->translatedFormat('M Y');

            $monthPaid = (float) $user->invoices()
                ->where('status', 'paid')
                ->whereYear('issue_date', $monthDate->year)
                ->whereMonth('issue_date', $monthDate->month)
                ->sum('grand_total');

            $monthBilled = (float) $user->invoices()
                ->whereYear('issue_date', $monthDate->year)
                ->whereMonth('issue_date', $monthDate->month)
                ->sum('grand_total');

            $monthCount = $user->invoices()
                ->whereYear('issue_date', $monthDate->year)
                ->whereMonth('issue_date', $monthDate->month)
                ->count();

            $monthlyTrend[] = [
                'period' => $yearMonth,
                'label' => $label,
                'paid' => $monthPaid,
                'billed' => $monthBilled,
                'count' => $monthCount,
            ];
        }

        // 5. Faktur Terbaru (5 transaksi terakhir)
        $recentInvoices = $user->invoices()
            ->with('client:id,name')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'client_name' => $inv->client->name ?? '-',
                    'issue_date' => $inv->issue_date->format('d M Y'),
                    'grand_total' => (float) $inv->grand_total,
                    'status' => $inv->status,
                ];
            });

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'total_receivables' => $totalReceivables,
                'total_invoices_count' => $totalInvoicesCount,
                'paid_count' => $paidCount,
                'unpaid_count' => $unpaidCount,
                'draft_count' => $draftCount,
                'total_clients_count' => $totalClientsCount,
            ],
            'aging' => $aging,
            'monthlyTrend' => $monthlyTrend,
            'attentionInvoices' => $attentionInvoices,
            'recentInvoices' => $recentInvoices,
        ]);
    }
}
