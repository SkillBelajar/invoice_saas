<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    client: Object,
    stats: Object,
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const formatDate = (val) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head :title="`Profil Pelanggan: ${client.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('clients.index')"
                        class="rounded-lg border border-slate-300 bg-white p-2 text-slate-600 shadow-sm transition hover:bg-slate-50 active:bg-slate-100"
                        title="Kembali ke Daftar Pelanggan"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ client.name }}</h1>
                            <span
                                v-if="client.source"
                                class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700"
                            >
                                Sumber: {{ client.source }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500">Buku Profil Kontak Terpadu & Riwayat Transaksi Faktur (SKKNI CRM Solo).</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('invoices.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:bg-indigo-800 active:scale-[0.98]"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ Buat Faktur Baru</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Top Summary Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs font-medium text-slate-500">Total Nilai Transaksi</div>
                        <div class="mt-1 text-2xl font-bold text-slate-900">{{ formatRupiah(stats.total_invoiced) }}</div>
                        <div class="mt-1 text-xs text-slate-400">{{ stats.invoices_count }} Faktur Penagihan</div>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                        <div class="text-xs font-medium text-emerald-800">Total Terbayar (Lunas)</div>
                        <div class="mt-1 text-2xl font-bold text-emerald-600">{{ formatRupiah(stats.total_paid) }}</div>
                        <div class="mt-1 text-xs text-emerald-700">Dana kas masuk bersih</div>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                        <div class="text-xs font-medium text-amber-800">Sisa Piutang Berjalan</div>
                        <div class="mt-1 text-2xl font-bold text-amber-600">{{ formatRupiah(stats.total_unpaid) }}</div>
                        <div class="mt-1 text-xs text-amber-700">Tagihan belum terbayar</div>
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4 shadow-sm">
                        <div class="text-xs font-medium text-indigo-800">Peluang CRM (Deals)</div>
                        <div class="mt-1 text-2xl font-bold text-indigo-600">{{ stats.deals_count }}</div>
                        <div class="mt-1 text-xs text-indigo-700">Pipa transaksi tercatat</div>
                    </div>
                </div>

                <!-- Profile Information Card -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/70 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📇</span>
                            <h2 class="font-bold text-slate-900">Rincian Informasi Kontak & Finansial</h2>
                        </div>
                        <Link
                            :href="route('clients.index')"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline"
                        >
                            Edit di Buku Pelanggan &rarr;
                        </Link>
                    </div>

                    <div class="p-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nama Pelanggan</span>
                            <p class="mt-1 font-semibold text-slate-900">{{ client.name }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Email Korespondensi</span>
                            <p class="mt-1 text-slate-700">
                                <a v-if="client.email" :href="`mailto:${client.email}`" class="text-indigo-600 hover:underline">
                                    {{ client.email }}
                                </a>
                                <span v-else class="text-slate-400">—</span>
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nomor Telepon / WhatsApp</span>
                            <p class="mt-1 text-slate-700">
                                <a v-if="client.phone" :href="`tel:${client.phone}`" class="text-indigo-600 hover:underline">
                                    {{ client.phone }}
                                </a>
                                <span v-else class="text-slate-400">—</span>
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nomor Pokok Wajib Pajak (NPWP / NIK)</span>
                            <p class="mt-1 font-mono text-xs font-medium text-slate-700">{{ client.tax_id || '—' }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sumber Prospek (Lead Source)</span>
                            <p class="mt-1 text-slate-700">
                                <span
                                    v-if="client.source"
                                    class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10"
                                >
                                    {{ client.source }}
                                </span>
                                <span v-else class="text-slate-400">Tidak ada sumber tercatat</span>
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tanggal Terdaftar</span>
                            <p class="mt-1 text-slate-700">{{ formatDate(client.created_at) }}</p>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Alamat Lengkap Penagihan</span>
                            <p class="mt-1 text-slate-700 whitespace-pre-line bg-slate-50 p-3 rounded-lg border border-slate-100">
                                {{ client.address }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Transaksi (Invoices) Table -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-bold text-slate-900 text-base">Buku Riwayat Transaksi (Faktur)</h2>
                            <p class="text-xs text-slate-500">Seluruh dokumen invoice yang pernah diterbitkan untuk {{ client.name }}.</p>
                        </div>
                        <Link
                            :href="route('invoices.create')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 active:bg-slate-100"
                        >
                            <span>+ Buat Faktur</span>
                        </Link>
                    </div>

                    <!-- Desktop Invoices Table -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                <tr>
                                    <th class="py-3.5 pl-6 pr-3">No. Faktur</th>
                                    <th class="py-3.5 px-3">Tanggal Terbit</th>
                                    <th class="py-3.5 px-3">Jatuh Tempo</th>
                                    <th class="py-3.5 px-3 text-center">Item</th>
                                    <th class="py-3.5 px-3 text-right">Total Tagihan</th>
                                    <th class="py-3.5 px-3 text-center">Status</th>
                                    <th class="py-3.5 pl-3 pr-6 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="inv in client.invoices" :key="inv.id" class="hover:bg-slate-50/70">
                                    <td class="py-4 pl-6 pr-3 font-mono text-xs font-bold text-indigo-600">
                                        <Link :href="route('invoices.show', inv.id)" class="hover:underline">
                                            {{ inv.invoice_number }}
                                        </Link>
                                    </td>
                                    <td class="py-4 px-3 text-xs text-slate-600">{{ formatDate(inv.issue_date) }}</td>
                                    <td class="py-4 px-3 text-xs text-slate-600">{{ formatDate(inv.due_date) }}</td>
                                    <td class="py-4 px-3 text-center text-xs text-slate-500">{{ inv.items_count || '—' }}</td>
                                    <td class="py-4 px-3 text-right font-bold text-slate-900">{{ formatRupiah(inv.grand_total) }}</td>
                                    <td class="py-4 px-3 text-center">
                                        <span
                                            v-if="inv.status === 'paid'"
                                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"
                                        >
                                            ✓ Lunas
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700"
                                        >
                                            Belum Bayar
                                        </span>
                                    </td>
                                    <td class="py-4 pl-3 pr-6 text-right text-xs space-x-2 whitespace-nowrap">
                                        <a
                                            :href="route('invoices.pdf', inv.id)"
                                            target="_blank"
                                            class="font-semibold text-sky-600 hover:text-sky-900"
                                        >
                                            PDF
                                        </a>
                                        <Link
                                            :href="route('invoices.show', inv.id)"
                                            class="font-semibold text-indigo-600 hover:text-indigo-900"
                                        >
                                            Lihat
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!client.invoices?.length">
                                    <td colspan="7" class="py-12 text-center text-sm text-slate-500">
                                        Belum ada riwayat transaksi faktur untuk pelanggan ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Invoices List -->
                    <div class="divide-y divide-slate-100 md:hidden">
                        <div v-for="inv in client.invoices" :key="inv.id" class="p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <Link :href="route('invoices.show', inv.id)" class="font-mono text-xs font-bold text-indigo-600">
                                    {{ inv.invoice_number }}
                                </Link>
                                <span
                                    v-if="inv.status === 'paid'"
                                    class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700"
                                >
                                    ✓ Lunas
                                </span>
                                <span
                                    v-else
                                    class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                                >
                                    Belum Bayar
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>Terbit: {{ formatDate(inv.issue_date) }}</span>
                                <span class="font-bold text-slate-900 text-sm">{{ formatRupiah(inv.grand_total) }}</span>
                            </div>
                            <div class="flex justify-end gap-2 pt-1">
                                <a
                                    :href="route('invoices.pdf', inv.id)"
                                    target="_blank"
                                    class="rounded bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700"
                                >
                                    PDF
                                </a>
                                <Link
                                    :href="route('invoices.show', inv.id)"
                                    class="rounded bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700"
                                >
                                    Detail
                                </Link>
                            </div>
                        </div>
                        <div v-if="!client.invoices?.length" class="p-6 text-center text-sm text-slate-500">
                            Belum ada riwayat transaksi faktur untuk pelanggan ini.
                        </div>
                    </div>
                </div>

                <!-- Peluang (Deals) Terkait Klien -->
                <div v-if="client.deals?.length" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h2 class="font-bold text-slate-900 text-base">Peluang Penjualan Terkait (Pipeline Deals)</h2>
                            <p class="text-xs text-slate-500">Peluang CRM yang sedang atau telah berjalan dengan pelanggan ini.</p>
                        </div>
                        <Link :href="route('crm.deals.index')" class="text-xs font-semibold text-indigo-600 hover:underline">
                            Buka Kanban Pipa &rarr;
                        </Link>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <div v-for="deal in client.deals" :key="deal.id" class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <h3 class="font-semibold text-sm text-slate-900">{{ deal.title }}</h3>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    Target Closing: {{ formatDate(deal.expected_close_date) }}
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                    {{ deal.stage }}
                                </span>
                                <span class="font-bold text-sm text-slate-900">
                                    {{ formatRupiah(deal.amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid: Jadwal Interaksi & Tiket Keluhan Pelanggan -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Jadwal Interaksi Terkait -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                            <div>
                                <h2 class="font-bold text-slate-900 text-base">Jadwal Pengingat / Interaksi</h2>
                                <p class="text-xs text-slate-500">Agenda follow-up dengan pelanggan ini.</p>
                            </div>
                            <Link :href="route('crm.interactions.index')" class="text-xs font-semibold text-indigo-600 hover:underline">
                                Lihat Semua &rarr;
                            </Link>
                        </div>
                        <div v-if="client.interactions?.length" class="divide-y divide-slate-100">
                            <div v-for="act in client.interactions.slice(0, 5)" :key="act.id" class="p-4 flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700 border border-indigo-200">
                                            {{ act.type }}
                                        </span>
                                        <span :class="['text-xs font-semibold', act.status === 'Selesai' ? 'text-emerald-600' : 'text-amber-600']">
                                            {{ act.status }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-700 whitespace-pre-wrap">{{ act.notes || 'Tanpa catatan' }}</p>
                                </div>
                                <span class="text-xs font-medium text-slate-500 shrink-0">
                                    {{ formatDate(act.scheduled_at) }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="p-6 text-center text-sm text-slate-500">
                            Belum ada jadwal interaksi tercatat.
                        </div>
                    </div>

                    <!-- Tiket Keluhan Terkait -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                            <div>
                                <h2 class="font-bold text-slate-900 text-base">Pusat Tiket Keluhan</h2>
                                <p class="text-xs text-slate-500">Riwayat keluhan & layanan pelanggan.</p>
                            </div>
                            <Link :href="route('crm.tickets.index')" class="text-xs font-semibold text-indigo-600 hover:underline">
                                Lihat Semua &rarr;
                            </Link>
                        </div>
                        <div v-if="client.tickets?.length" class="divide-y divide-slate-100">
                            <div v-for="tk in client.tickets.slice(0, 5)" :key="tk.id" class="p-4 flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold text-indigo-600">{{ tk.ticket_number }}</span>
                                        <span class="text-xs font-bold text-slate-900">{{ tk.subject }}</span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-2 text-xs">
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">{{ tk.priority }}</span>
                                        <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700">{{ tk.status }}</span>
                                    </div>
                                </div>
                                <span class="text-xs font-medium text-slate-500 shrink-0">
                                    SLA: {{ formatDate(tk.due_date) }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="p-6 text-center text-sm text-slate-500">
                            Tidak ada tiket keluhan tercatat.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
