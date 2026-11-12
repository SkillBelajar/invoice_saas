<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Faktur - {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 28px 32px 32px 32px;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 20px;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 14px;
        }
        .logo-img {
            max-height: 52px;
            max-width: 180px;
        }
        .company-title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 10px;
            color: #64748b;
        }
        .invoice-title-td {
            text-align: right;
            vertical-align: top;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: 800;
            color: #0284c7;
            letter-spacing: 1px;
            margin: 0 0 4px 0;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        .badge-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .badge-unpaid {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
        }
        .badge-draft {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .info-table {
            margin-bottom: 16px;
        }
        .party-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            vertical-align: top;
        }
        .party-heading {
            font-size: 9px;
            font-weight: bold;
            color: #0284c7;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }
        .party-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .party-detail {
            font-size: 10px;
            color: #475569;
            line-height: 1.4;
        }
        .meta-table {
            margin-bottom: 18px;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .meta-td {
            padding: 6px 12px;
            width: 25%;
            border-right: 1px solid #e2e8f0;
        }
        .meta-td:last-child {
            border-right: none;
        }
        .meta-label {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .meta-value {
            font-size: 11px;
            color: #0f172a;
            font-weight: bold;
            margin-top: 1px;
        }
        .items-table {
            margin-bottom: 18px;
            border: 1px solid #cbd5e1;
        }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border: 1px solid #0f172a;
        }
        .items-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            font-size: 10.5px;
        }
        .items-table td:last-child {
            border-right: none;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .summary-wrapper {
            margin-bottom: 18px;
        }
        .summary-table {
            width: 100%;
        }
        .summary-table td {
            padding: 4px 8px;
            font-size: 10.5px;
        }
        .summary-label {
            color: #475569;
            text-align: right;
            width: 60%;
        }
        .summary-value {
            color: #0f172a;
            text-align: right;
            font-weight: 600;
            width: 40%;
        }
        .total-row td {
            background-color: #0284c7;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 12.5px !important;
            padding: 7px 10px !important;
        }
        .total-row .summary-label {
            color: #ffffff !important;
        }
        .total-row .summary-value {
            color: #ffffff !important;
        }
        .bank-box {
            background-color: #f8fafc;
            border-left: 3px solid #0284c7;
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 12px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }
        .bank-title {
            font-size: 9px;
            font-weight: bold;
            color: #0284c7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .bank-desc {
            font-size: 10px;
            color: #334155;
            line-height: 1.4;
        }
        .notes-box {
            font-size: 9.5px;
            color: #64748b;
            padding: 6px 10px;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
        }
        .footer-table {
            margin-top: 10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
        }
        .qr-wrap {
            text-align: left;
            vertical-align: bottom;
            width: 45%;
        }
        .qr-img {
            width: 82px;
            height: 82px;
            border: 1px solid #e2e8f0;
            padding: 2px;
            background: #ffffff;
        }
        .qr-caption {
            font-size: 8px;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.25;
        }
        .signature-wrap {
            text-align: center;
            vertical-align: bottom;
            width: 45%;
        }
        .signature-date {
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 4px;
        }
        .signature-img {
            max-height: 60px;
            max-width: 140px;
            margin: 4px auto;
        }
        .signature-space {
            height: 55px;
        }
        .signature-name {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
        }
        .signature-role {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }
        .compliance-note {
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            margin-top: 14px;
            border-top: 1px dotted #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    {{-- 1. HEADER --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo" class="logo-img">
                @else
                    <h1 class="company-title">{{ $businessProfile?->company_name ?? ($invoice->user->name ?? 'PERUSAHAAN') }}</h1>
                    <div class="company-sub">Faktur Penjualan & Penagihan Resmi</div>
                @endif
            </td>
            <td class="invoice-title-td" style="width: 45%;">
                <div class="invoice-title">FAKTUR / INVOICE</div>
                <div>
                    @if($invoice->status === 'paid')
                        <span class="badge badge-paid">LUNAS / PAID</span>
                    @elseif($invoice->status === 'unpaid')
                        <span class="badge badge-unpaid">BELUM LUNAS / UNPAID</span>
                    @else
                        <span class="badge badge-draft">DRAFT</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- 2. METADATA FAKTUR --}}
    <table class="meta-table">
        <tr>
            <td class="meta-td">
                <div class="meta-label">Nomor Faktur</div>
                <div class="meta-value">{{ $invoice->invoice_number }}</div>
            </td>
            <td class="meta-td">
                <div class="meta-label">Tanggal Terbit</div>
                <div class="meta-value">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</div>
            </td>
            <td class="meta-td">
                <div class="meta-label">Jatuh Tempo</div>
                <div class="meta-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</div>
            </td>
            <td class="meta-td">
                <div class="meta-label">Mata Uang</div>
                <div class="meta-value">IDR (Rupiah)</div>
            </td>
        </tr>
    </table>

    {{-- 3. PIHAK PENJUAL & PEMBELI --}}
    <table class="info-table">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="party-card">
                    <div class="party-heading">Diterbitkan Oleh (Penjual)</div>
                    <div class="party-name">{{ $businessProfile?->company_name ?? $invoice->user->name }}</div>
                    <div class="party-detail">
                        {!! nl2br(e($businessProfile?->address ?? 'Alamat belum diatur')) !!}<br>
                        <strong>NPWP / NIK:</strong> {{ $businessProfile?->tax_id ?: '-' }}<br>
                        <strong>Email:</strong> {{ $invoice->user->email ?? '-' }}
                    </div>
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div class="party-card">
                    <div class="party-heading">Ditujukan Kepada (Pembeli)</div>
                    <div class="party-name">{{ $invoice->client->name ?? '-' }}</div>
                    <div class="party-detail">
                        {!! nl2br(e($invoice->client->address ?? '-')) !!}<br>
                        <strong>NPWP / NIK:</strong> {{ $invoice->client->tax_id ?: '-' }}<br>
                        <strong>Telepon:</strong> {{ $invoice->client->phone ?: '-' }}<br>
                        <strong>Email:</strong> {{ $invoice->client->email ?: '-' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- 4. TABEL BARIS ITEM (LINE ITEMS) --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No</th>
                <th class="text-left" style="width: 45%;">Deskripsi Produk / Layanan</th>
                <th class="text-center" style="width: 12%;">Qty</th>
                <th class="text-right" style="width: 18%;">Harga Satuan</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">
                        <strong>{{ $item->description }}</strong>
                    </td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 16px; color: #94a3b8;">Tidak ada item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- 5. RINGKASAN FINANSIAL & INSTRUKSI PEMBAYARAN --}}
    @php
        $subtotal = (float) $invoice->subtotal;
        $discountAmount = (float) $invoice->discount_amount;
        $dpp = max(0, $subtotal - $discountAmount);
        $taxRate = (float) $invoice->tax_rate;
        $taxAmount = (float) $invoice->tax_amount;
        $grandTotal = (float) $invoice->grand_total;
    @endphp
    <table class="summary-wrapper">
        <tr>
            {{-- Kolom Kiri: Rekening & Catatan --}}
            <td style="width: 50%; vertical-align: top; padding-right: 14px;">
                @if(!empty($businessProfile?->bank_name) || !empty($businessProfile?->bank_account_number))
                    <div class="bank-box">
                        <div class="bank-title">Instruksi Pembayaran (Transfer Bank)</div>
                        <div class="bank-desc">
                            <strong>Bank:</strong> {{ $businessProfile?->bank_name ?? '-' }}<br>
                            <strong>No. Rekening:</strong> {{ $businessProfile?->bank_account_number ?? '-' }}<br>
                            <strong>Atas Nama:</strong> {{ $businessProfile?->bank_account_name ?? '-' }}
                        </div>
                    </div>
                @else
                    <div class="bank-box" style="border-left-color: #f59e0b; background-color: #fffbeb;">
                        <div class="bank-title" style="color: #b45309;">Instruksi Pembayaran</div>
                        <div class="bank-desc" style="color: #78350f;">
                            Rekening pembayaran resmi belum dicantumkan di sistem.<br>
                            Silakan hubungi <strong>{{ $businessProfile?->company_name ?? ($invoice->user->name ?? 'Penerbit Faktur') }}</strong>
                            @if(!empty($invoice->user->email))
                                ({{ $invoice->user->email }})
                            @endif
                            untuk konfirmasi nomor rekening transfer resmi.
                        </div>
                    </div>
                @endif

                @if(!empty($invoice->notes))
                    <div class="notes-box">
                        <strong>Catatan:</strong><br>
                        {!! nl2br(e($invoice->notes)) !!}
                    </div>
                @endif
            </td>

            {{-- Kolom Kanan: Rincian Finansial Standar UU HPP & SAK EMKM --}}
            <td style="width: 50%; vertical-align: top;">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Subtotal</td>
                        <td class="summary-value">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($discountAmount > 0)
                        <tr>
                            <td class="summary-label">
                                Diskon
                                @if($invoice->discount_type === 'percentage')
                                    ({{ rtrim(rtrim(number_format($invoice->discount_value, 2, ',', '.'), '0'), ',') }}%)
                                @endif
                            </td>
                            <td class="summary-label" style="color: #dc2626; text-align: right; font-weight: 600;">
                                - Rp {{ number_format($discountAmount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="summary-label">Dasar Pengenaan Pajak (DPP)</td>
                        <td class="summary-value">Rp {{ number_format($dpp, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">
                            PPN 
                            @if($taxRate > 0)
                                ({{ rtrim(rtrim(number_format($taxRate, 2, ',', '.'), '0'), ',') }}%)
                            @else
                                (0%)
                            @endif
                        </td>
                        <td class="summary-value">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td class="summary-label">GRAND TOTAL</td>
                        <td class="summary-value">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- 6. PENGESAHAN (FOOTER): QR CODE & TANDA TANGAN / STEMPEL --}}
    <table class="footer-table">
        <tr>
            {{-- QR Code Verifikasi Keaslian Dokumen --}}
            <td class="qr-wrap">
                @if(!empty($qrCodeBase64))
                    <img src="{{ $qrCodeBase64 }}" alt="QR Verifikasi" class="qr-img">
                @endif
                <div class="qr-caption">
                    <strong>Pindai QR Code</strong> untuk memvalidasi keaslian faktur ini secara elektronik pada sistem kami.
                </div>
            </td>

            <td style="width: 10%;"></td>

            {{-- Stempel & Tanda Tangan Digital --}}
            <td class="signature-wrap">
                <div class="signature-date">
                    Diterbitkan: {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d F Y') }}
                </div>

                @if(!empty($signatureBase64))
                    <img src="{{ $signatureBase64 }}" alt="Tanda Tangan & Stempel" class="signature-img">
                @else
                    <div class="signature-space"></div>
                @endif

                <div class="signature-name">
                    {{ $businessProfile?->company_name ?? $invoice->user->name }}
                </div>
                <div class="signature-role">
                    Penjual / Pihak Berwenang
                </div>
            </td>
        </tr>
    </table>

    {{-- Catatan Kepatuhan UU HPP & SAK EMKM --}}
    <div class="compliance-note">
        Dokumen faktur ini sah dan diterbitkan secara elektronik sesuai standar UU Harmonisasi Peraturan Perpajakan (UU HPP) dan Standar Akuntansi Keuangan Entitas Mikro, Kecil, dan Menengah (SAK EMKM).
    </div>

</body>
</html>
