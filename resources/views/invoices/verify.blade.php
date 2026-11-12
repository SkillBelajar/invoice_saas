<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Faktur {{ $invoice->invoice_number }} - Invoice SaaS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            padding: 24px 16px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            width: 100%;
            max-width: 640px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            padding: 28px 24px;
            color: #ffffff;
            text-align: center;
        }
        .icon-check {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 28px;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: -0.01em;
        }
        .subtitle {
            font-size: 13px;
            opacity: 0.9;
        }
        .content {
            padding: 24px;
        }
        .meta-card {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 16px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }
        .meta-item-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .meta-item-val {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-paid { background: #dcfce7; color: #15803d; }
        .status-unpaid { background: #fef3c7; color: #b45309; }
        .status-draft { background: #e2e8f0; color: #475569; }
        .section {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #0284c7;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .row-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 13px;
        }
        .row-info .label { color: #64748b; }
        .row-info .val { font-weight: 600; color: #0f172a; text-align: right; }
        .grand-total-row {
            background: #0284c7;
            color: #ffffff;
            padding: 12px 16px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            font-weight: 700;
            margin-top: 14px;
        }
        .grand-total-row .val {
            font-size: 18px;
        }
        .footer {
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
        .btn-home {
            display: inline-block;
            margin-top: 10px;
            font-size: 12px;
            color: #0284c7;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-home:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="icon-check">✓</div>
        <div class="title">Faktur Terverifikasi Sah</div>
        <div class="subtitle">Dokumen ini resmi terdaftar pada platform Invoice SaaS</div>
    </div>

    <div class="content">
        <div class="meta-card">
            <div>
                <div class="meta-item-label">Nomor Faktur</div>
                <div class="meta-item-val">{{ $invoice->invoice_number }}</div>
            </div>
            <div>
                <div class="meta-item-label">Status Pembayaran</div>
                <div style="margin-top: 3px;">
                    @if($invoice->status === 'paid')
                        <span class="status-badge status-paid">Lunas</span>
                    @elseif($invoice->status === 'unpaid')
                        <span class="status-badge status-unpaid">Belum Lunas</span>
                    @else
                        <span class="status-badge status-draft">Draft</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="meta-item-label">Tanggal Terbit</div>
                <div class="meta-item-val">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d M Y') }}</div>
            </div>
            <div>
                <div class="meta-item-label">Jatuh Tempo</div>
                <div class="meta-item-val">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Pihak Penerbit (Penjual)</div>
            <div class="row-info">
                <span class="label">Nama Usaha / Perusahaan</span>
                <span class="val">{{ $businessProfile->company_name ?? $invoice->user->name }}</span>
            </div>
            @if(!empty($businessProfile->tax_id))
            <div class="row-info">
                <span class="label">NPWP / NIK Penjual</span>
                <span class="val">{{ $businessProfile->tax_id }}</span>
            </div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">Pihak Ditagih (Pembeli)</div>
            <div class="row-info">
                <span class="label">Nama Klien</span>
                <span class="val">{{ $invoice->client->name }}</span>
            </div>
            @if(!empty($invoice->client->tax_id))
            <div class="row-info">
                <span class="label">NPWP / NIK Pembeli</span>
                <span class="val">{{ $invoice->client->tax_id }}</span>
            </div>
            @endif
        </div>

        <div class="section" style="border-bottom: none; margin-bottom: 0;">
            <div class="section-title">Ringkasan Nilai Transaksi</div>
            <div class="row-info">
                <span class="label">Subtotal</span>
                <span class="val">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="row-info">
                <span class="label">Diskon</span>
                <span class="val" style="color: #dc2626;">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="row-info">
                <span class="label">Dasar Pengenaan Pajak (DPP)</span>
                <span class="val">Rp {{ number_format(max(0, $invoice->subtotal - $invoice->discount_amount), 0, ',', '.') }}</span>
            </div>
            <div class="row-info">
                <span class="label">PPN ({{ (float)$invoice->tax_rate }}%)</span>
                <span class="val">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</span>
            </div>

            <div class="grand-total-row">
                <span>Grand Total</span>
                <span class="val">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <div>Sesuai dengan standar ketentuan perundang-undangan perpajakan (UU HPP & SAK EMKM).</div>
        <a href="/" class="btn-home">← Kembali ke Halaman Utama</a>
    </div>
</div>

</body>
</html>
