<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const formatDate = (val) => {
    if (!val) return '-';
    const d = new Date(val);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const statusBadge = (s) => {
    switch (s) {
        case 'paid':
            return { label: 'Lunas', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'draft':
            return { label: 'Draf', class: 'bg-slate-100 text-slate-700 border-slate-200' };
        default:
            return { label: 'Belum Dibayar', class: 'bg-amber-50 text-amber-700 border-amber-200' };
    }
};
</script>

<template>
    <Head :title="`Faktur ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                        <Link :href="route('invoices.index')" class="hover:text-indigo-600">Faktur</Link>
                        <span>/</span>
                        <span class="font-mono text-slate-800">{{ invoice.invoice_number }}</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                        <span>Faktur {{ invoice.invoice_number }}</span>
                        <span :class="statusBadge(invoice.status).class" class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold">
                            {{ statusBadge(invoice.status).label }}
                        </span>
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('invoices.index')"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                    >
                        &larr; Kembali
                    </Link>
                    <Link
                        :href="route('invoices.create')"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                    >
                        + Buat Faktur Lain
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <!-- Invoice Header Banner -->
                    <div class="border-b border-slate-100 bg-slate-50/50 p-6 sm:p-8">
                        <div class="flex flex-col justify-between gap-6 sm:flex-row">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Faktur Penjualan</span>
                                <h2 class="mt-1 font-mono text-2xl font-black text-slate-900">{{ invoice.invoice_number }}</h2>
                                <div class="mt-2 text-xs text-slate-500 space-y-1">
                                    <div>Tanggal Terbit: <strong class="text-slate-700">{{ formatDate(invoice.issue_date) }}</strong></div>
                                    <div>Jatuh Tempo: <strong class="text-slate-700">{{ formatDate(invoice.due_date) }}</strong></div>
                                </div>
                            </div>

                            <!-- Info Klien -->
                            <div class="sm:text-right">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Ditujukan Kepada</span>
                                <div class="mt-1 text-base font-bold text-slate-900">{{ invoice.client?.name || '-' }}</div>
                                <div v-if="invoice.client?.email" class="text-xs text-slate-600">{{ invoice.client?.email }}</div>
                                <div v-if="invoice.client?.phone" class="text-xs text-slate-600">{{ invoice.client?.phone }}</div>
                                <div v-if="invoice.client?.address" class="text-xs text-slate-500 max-w-xs sm:ml-auto whitespace-pre-line">{{ invoice.client?.address }}</div>
                                <div v-if="invoice.client?.tax_id" class="mt-1 text-[11px] text-slate-400">NPWP/NIK: {{ invoice.client?.tax_id }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items Table -->
                    <div class="p-6 sm:p-8">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-slate-500">
                                        <th class="py-3 pr-3">Deskripsi Barang / Jasa</th>
                                        <th class="py-3 px-3 text-right">Kuantitas</th>
                                        <th class="py-3 px-3 text-right">Harga Satuan</th>
                                        <th class="py-3 pl-3 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm">
                                    <tr v-for="item in invoice.items" :key="item.id">
                                        <td class="py-4 pr-3">
                                            <div class="font-medium text-slate-900">{{ item.description }}</div>
                                        </td>
                                        <td class="py-4 px-3 text-right font-mono text-slate-600">{{ Number(item.quantity) }}</td>
                                        <td class="py-4 px-3 text-right font-mono text-slate-600">{{ formatCurrency(item.unit_price) }}</td>
                                        <td class="py-4 pl-3 text-right font-mono font-bold text-slate-900">{{ formatCurrency(item.total_price) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Calculations Breakdown -->
                        <div class="mt-6 flex flex-col justify-between gap-6 border-t border-slate-100 pt-6 sm:flex-row">
                            <!-- Catatan -->
                            <div class="flex-1 text-xs text-slate-500">
                                <span class="font-bold uppercase tracking-wider text-slate-400">Catatan / Pembayaran:</span>
                                <p class="mt-1 whitespace-pre-line text-slate-600">{{ invoice.notes || 'Tidak ada catatan tambahan.' }}</p>
                            </div>

                            <!-- Totals -->
                            <div class="w-full sm:w-72 space-y-2 text-sm">
                                <div class="flex justify-between text-slate-600">
                                    <span>Subtotal:</span>
                                    <span class="font-medium font-mono text-slate-800">{{ formatCurrency(invoice.subtotal) }}</span>
                                </div>
                                <div v-if="Number(invoice.discount_amount) > 0" class="flex justify-between text-emerald-600">
                                    <span>Diskon:</span>
                                    <span class="font-medium font-mono">- {{ formatCurrency(invoice.discount_amount) }}</span>
                                </div>
                                <div v-if="Number(invoice.tax_rate) > 0" class="flex justify-between text-slate-600">
                                    <span>PPN ({{ Number(invoice.tax_rate) }}%):</span>
                                    <span class="font-medium font-mono">+ {{ formatCurrency(invoice.tax_amount) }}</span>
                                </div>
                                <div class="border-t-2 border-slate-900 pt-2 flex justify-between items-baseline">
                                    <span class="font-bold text-slate-900">Total Tagihan:</span>
                                    <span class="text-xl font-black font-mono text-indigo-700">{{ formatCurrency(invoice.grand_total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
