<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * Generate next invoice number for the month: INV-YYYYMM-XXX
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ym') . '-';
        $latest = Invoice::where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->first();

        $seq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->invoice_number, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function index(Request $request): Response
    {
        $query = $request->user()->invoices()->with('client:id,name,email');

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status') && in_array($request->status, ['draft', 'unpaid', 'paid'])) {
            $query->where('status', $request->status);
        }

        $invoices = $query->latest('issue_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        $clients = $user->clients()
            ->select('id', 'name', 'email', 'phone', 'address', 'tax_id')
            ->orderBy('name')
            ->get();

        $products = $user->products()
            ->select('id', 'name', 'description', 'price', 'unit')
            ->orderBy('name')
            ->get();

        return Inertia::render('Invoices/Create', [
            'clients' => $clients,
            'products' => $products,
            'nextInvoiceNumber' => self::generateInvoiceNumber(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where('user_id', $user->id),
            ],
            'invoice_number' => ['nullable', 'string', 'max:50', 'unique:invoices,invoice_number'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'discount_type' => ['nullable', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:draft,unpaid,paid'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('user_id', $user->id),
            ],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            // Kalkulasi subtotal dari line items
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $lineTotal = round($qty * $price, 2);
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $lineTotal,
                ];
            }

            $discountType = $validated['discount_type'] ?? null;
            $discountValue = (float) ($validated['discount_value'] ?? 0);
            $discountAmount = 0;

            if ($discountType === 'percentage' && $discountValue > 0) {
                $discountAmount = round($subtotal * ($discountValue / 100), 2);
            } elseif ($discountType === 'fixed' && $discountValue > 0) {
                $discountAmount = min($subtotal, $discountValue);
            } else {
                $discountType = null;
                $discountValue = 0;
            }

            $afterDiscount = max(0, $subtotal - $discountAmount);
            $taxRate = (float) ($validated['tax_rate'] ?? 0);
            $taxAmount = ($taxRate > 0) ? round($afterDiscount * ($taxRate / 100), 2) : 0;
            $grandTotal = round($afterDiscount + $taxAmount, 2);

            // Invoice number fallback jika tidak diisi atau terjadi bentrok
            $invoiceNumber = $validated['invoice_number'] ?? null;
            if (! $invoiceNumber || Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = self::generateInvoiceNumber();
                while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                    $prefix = 'INV-' . now()->format('Ym') . '-';
                    $invoiceNumber = $prefix . str_pad((string) (Invoice::count() + 1), 3, '0', STR_PAD_LEFT);
                }
            }

            $invoice = $user->invoices()->create([
                'client_id' => $validated['client_id'],
                'invoice_number' => $invoiceNumber,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->items()->createMany($itemsData);
        });

        return redirect()->route('invoices.index')->with('success', 'Faktur berhasil dibuat.');
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        abort_unless($invoice->user_id === $request->user()->id, 404);

        $invoice->load(['client', 'items.product']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 404);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Faktur berhasil dihapus.');
    }
}
