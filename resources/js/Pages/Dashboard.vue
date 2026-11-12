<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            total_receivables: 0,
            total_invoices_count: 0,
            paid_count: 0,
            unpaid_count: 0,
            draft_count: 0,
            total_clients_count: 0,
        }),
    },
    aging: {
        type: Object,
        default: () => ({
            current: { label: 'Belum Jatuh Tempo', amount: 0, count: 0 },
            overdue_1_30: { label: '1 - 30 Hari', amount: 0, count: 0 },
            overdue_31_60: { label: '31 - 60 Hari', amount: 0, count: 0 },
            overdue_60_plus: { label: '> 60 Hari', amount: 0, count: 0 },
            total: 0,
        }),
    },
    monthlyTrend: {
        type: Array,
        default: () => [],
    },
    attentionInvoices: {
        type: Array,
        default: () => [],
    },
    recentInvoices: {
        type: Array,
        default: () => [],
    },
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

const hoveredMonth = ref(null);

// Hitung nilai maksimum tren untuk skala grafik SVG / bar
const maxTrendValue = computed(() => {
    let max = 1;
    props.monthlyTrend.forEach((m) => {
        if (m.billed > max) max = m.billed;
        if (m.paid > max) max = m.paid;
    });
    return max;
});

// Hitung persentase Aging Piutang
const getAgingPercentage = (amount) => {
    if (!props.aging.total || props.aging.total <= 0) return 0;
    return Math.min(100, Math.round((amount / props.aging.total) * 100));
};

// Aksi cepat Tandai Lunas
const markAsPaid = (invoiceId, invoiceNumber) => {
    if (confirm(`Ubah status faktur ${invoiceNumber} menjadi "Lunas"?`)) {
        router.patch(route('invoices.update-status', invoiceId), {
            status: 'paid',
        }, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Dashboard Ringkasan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-800">
                        Dashboard & Laporan Piutang
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Ringkasan pendapatan, status penagihan aktif, dan pemantauan umur piutang pelanggan.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('invoices.create')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition"
                    >
                        <span>+</span>
                        <span>Buat Faktur</span>
                    </Link>
                    <Link
                        :href="route('clients.index')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                    >
                        <span>+</span>
                        <span>Pelanggan</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Status Setup Rekening Bank Jika Belum Ada -->
                <div
                    v-if="!$page.props.auth?.hasBankAccount"
                    class="overflow-hidden rounded-xl border border-amber-300 bg-amber-50 p-5 shadow-sm"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">⚠️</span>
                            <div>
                                <h3 class="text-base font-bold text-amber-900">
                                    Rekening Bank Pembayaran Belum Diatur!
                                </h3>
                                <p class="mt-1 text-xs text-amber-800">
                                    Agar invoice yang dicetak ke PDF menampilkan nomor rekening transfer resmi (sehingga pelanggan tidak bingung saat membayar), silakan isi nama bank dan nomor rekening usaha Anda di menu Profil Usaha.
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('profile.business.edit')"
                            class="inline-flex shrink-0 items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow hover:bg-amber-700 transition"
                        >
                            Atur Rekening Usaha &rarr;
                        </Link>
                    </div>
                </div>

                <!-- 1. Empat Kartu KPI Finansial -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Pendapatan Lunas -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pendapatan Lunas</span>
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                {{ metrics.paid_count }} Faktur
                            </span>
                        </div>
                        <div class="mt-3 text-2xl font-extrabold text-slate-900 tracking-tight">
                            {{ formatCurrency(metrics.total_revenue) }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Total dana yang telah lunas diterima masuk kas.
                        </p>
                    </div>

                    <!-- Total Piutang Belum Terbayar -->
                    <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-800">Total Piutang Aktif</span>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-900">
                                {{ metrics.unpaid_count }} Belum Lunas
                            </span>
                        </div>
                        <div class="mt-3 text-2xl font-extrabold text-amber-900 tracking-tight">
                            {{ formatCurrency(metrics.total_receivables) }}
                        </div>
                        <p class="mt-1 text-xs text-amber-700">
                            Tagihan faktur yang masih menunggu pembayaran pelanggan.
                        </p>
                    </div>

                    <!-- Total Faktur Diterbitkan -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Faktur</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">
                                {{ metrics.draft_count }} Draft
                            </span>
                        </div>
                        <div class="mt-3 text-2xl font-extrabold text-slate-900 tracking-tight">
                            {{ metrics.total_invoices_count }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Akumulasi faktur sah yang telah diterbitkan.
                        </p>
                    </div>

                    <!-- Total Pelanggan Aktif -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Buku Pelanggan</span>
                            <Link :href="route('clients.index')" class="text-[11px] font-bold text-indigo-600 hover:underline">
                                Kelola &rarr;
                            </Link>
                        </div>
                        <div class="mt-3 text-2xl font-extrabold text-slate-900 tracking-tight">
                            {{ metrics.total_clients_count }}
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Entitas klien dan mitra bisnis terdaftar.
                        </p>
                    </div>
                </div>

                <!-- 2. Laporan Aging Piutang (Umur Piutang) -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span>📊</span>
                                <span>Laporan Aging Piutang (Umur Piutang)</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Klasifikasi penagihan piutang berdasarkan sisa waktu atau keterlambatan jatuh tempo.
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-400">Total Piutang:</span>
                            <span class="ml-1.5 text-sm font-bold text-slate-900">{{ formatCurrency(aging.total) }}</span>
                        </div>
                    </div>

                    <!-- Progress Bar Multi-segmen -->
                    <div class="mt-5">
                        <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100 flex">
                            <div
                                :style="{ width: getAgingPercentage(aging.current.amount) + '%' }"
                                class="bg-emerald-500 transition-all duration-500"
                                :title="`Belum Jatuh Tempo: ${formatCurrency(aging.current.amount)}`"
                            ></div>
                            <div
                                :style="{ width: getAgingPercentage(aging.overdue_1_30.amount) + '%' }"
                                class="bg-amber-400 transition-all duration-500"
                                :title="`Terlambat 1-30 Hari: ${formatCurrency(aging.overdue_1_30.amount)}`"
                            ></div>
                            <div
                                :style="{ width: getAgingPercentage(aging.overdue_31_60.amount) + '%' }"
                                class="bg-orange-500 transition-all duration-500"
                                :title="`Terlambat 31-60 Hari: ${formatCurrency(aging.overdue_31_60.amount)}`"
                            ></div>
                            <div
                                :style="{ width: getAgingPercentage(aging.overdue_60_plus.amount) + '%' }"
                                class="bg-rose-600 transition-all duration-500"
                                :title="`Terlambat >60 Hari: ${formatCurrency(aging.overdue_60_plus.amount)}`"
                            ></div>
                        </div>

                        <!-- 4 Kartu Breakdown Umur Piutang -->
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <!-- Belum Jatuh Tempo -->
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50/40 p-3">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-800">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span>Lancar (Belum Jatuh Tempo)</span>
                                </div>
                                <div class="mt-2 text-base font-bold text-slate-900">
                                    {{ formatCurrency(aging.current.amount) }}
                                </div>
                                <div class="mt-0.5 text-[11px] text-slate-500">
                                    {{ aging.current.count }} Faktur ({{ getAgingPercentage(aging.current.amount) }}%)
                                </div>
                            </div>

                            <!-- 1 - 30 Hari -->
                            <div class="rounded-lg border border-amber-100 bg-amber-50/40 p-3">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-amber-800">
                                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                                    <span>Terlambat 1 - 30 Hari</span>
                                </div>
                                <div class="mt-2 text-base font-bold text-slate-900">
                                    {{ formatCurrency(aging.overdue_1_30.amount) }}
                                </div>
                                <div class="mt-0.5 text-[11px] text-slate-500">
                                    {{ aging.overdue_1_30.count }} Faktur ({{ getAgingPercentage(aging.overdue_1_30.amount) }}%)
                                </div>
                            </div>

                            <!-- 31 - 60 Hari -->
                            <div class="rounded-lg border border-orange-100 bg-orange-50/40 p-3">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-orange-800">
                                    <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                                    <span>Terlambat 31 - 60 Hari</span>
                                </div>
                                <div class="mt-2 text-base font-bold text-slate-900">
                                    {{ formatCurrency(aging.overdue_31_60.amount) }}
                                </div>
                                <div class="mt-0.5 text-[11px] text-slate-500">
                                    {{ aging.overdue_31_60.count }} Faktur ({{ getAgingPercentage(aging.overdue_31_60.amount) }}%)
                                </div>
                            </div>

                            <!-- > 60 Hari -->
                            <div class="rounded-lg border border-rose-100 bg-rose-50/40 p-3">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-rose-800">
                                    <span class="h-2 w-2 rounded-full bg-rose-600"></span>
                                    <span>Kritis (&gt; 60 Hari)</span>
                                </div>
                                <div class="mt-2 text-base font-bold text-rose-900">
                                    {{ formatCurrency(aging.overdue_60_plus.amount) }}
                                </div>
                                <div class="mt-0.5 text-[11px] text-rose-700">
                                    {{ aging.overdue_60_plus.count }} Faktur ({{ getAgingPercentage(aging.overdue_60_plus.amount) }}%)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Dua Kolom: Grafik Tren 6 Bulan & Faktur Jatuh Tempo -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                    <!-- Grafik Tren 6 Bulan Terakhir -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <span>📈</span>
                                        <span>Tren Penagihan 6 Bulan Terakhir</span>
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Perbandingan nilai faktur yang ditagihkan vs yang berhasil dicairkan.
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 text-xs">
                                    <div class="flex items-center gap-1.5 text-slate-600">
                                        <span class="h-2.5 w-2.5 rounded bg-indigo-200"></span>
                                        <span>Ditagih</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-slate-600">
                                        <span class="h-2.5 w-2.5 rounded bg-emerald-600"></span>
                                        <span>Lunas</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Visual Bar Chart Interaktif -->
                            <div class="mt-6">
                                <div class="flex items-end justify-between gap-3 h-48 pt-6 pb-2 px-2 border-b border-slate-100">
                                    <div
                                        v-for="(item, idx) in monthlyTrend"
                                        :key="item.period"
                                        class="flex-1 flex flex-col items-center h-full justify-end group relative cursor-pointer"
                                        @mouseenter="hoveredMonth = item"
                                        @mouseleave="hoveredMonth = null"
                                    >
                                        <!-- Tooltip saat hover -->
                                        <div
                                            v-if="hoveredMonth?.period === item.period"
                                            class="absolute -top-12 z-20 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1 text-[11px] font-medium text-white shadow-lg pointer-events-none"
                                        >
                                            <div>{{ item.label }}: {{ item.count }} Faktur</div>
                                            <div class="text-emerald-300">Lunas: {{ formatCurrency(item.paid) }}</div>
                                            <div class="text-indigo-200">Ditagih: {{ formatCurrency(item.billed) }}</div>
                                        </div>

                                        <!-- Bars (Ditagih & Lunas berdampingan) -->
                                        <div class="w-full flex items-end justify-center gap-1 h-full">
                                            <!-- Bar Ditagih -->
                                            <div
                                                :style="{ height: Math.max(6, Math.round((item.billed / maxTrendValue) * 100)) + '%' }"
                                                class="w-1/2 max-w-[20px] rounded-t bg-indigo-200 transition-all group-hover:bg-indigo-300"
                                            ></div>
                                            <!-- Bar Lunas -->
                                            <div
                                                :style="{ height: Math.max(6, Math.round((item.paid / maxTrendValue) * 100)) + '%' }"
                                                class="w-1/2 max-w-[20px] rounded-t bg-emerald-600 transition-all group-hover:bg-emerald-700"
                                            ></div>
                                        </div>

                                        <!-- Label Bulan -->
                                        <span class="mt-2 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                            {{ item.label.split(' ')[0] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Ringkasan Grafik -->
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Maksimal per bulan: <strong>{{ formatCurrency(maxTrendValue) }}</strong></span>
                            <span class="text-emerald-700 font-semibold">Tingkat Penagihan: {{ metrics.total_invoices_count > 0 ? Math.round((metrics.paid_count / metrics.total_invoices_count) * 100) : 0 }}%</span>
                        </div>
                    </div>

                    <!-- Faktur Perlu Ditindaklanjuti (Jatuh Tempo / Terlambat) -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <span>⏰</span>
                                        <span>Perlu Ditindaklanjuti</span>
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Faktur belum lunas yang telah lewat atau mendekati batas jatuh tempo.
                                    </p>
                                </div>
                                <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700">
                                    {{ attentionInvoices.length }} Item
                                </span>
                            </div>

                            <!-- List Faktur Perhatian -->
                            <div v-if="attentionInvoices.length > 0" class="mt-4 divide-y divide-slate-100">
                                <div
                                    v-for="inv in attentionInvoices"
                                    :key="inv.id"
                                    class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                                >
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <Link
                                                :href="route('invoices.show', inv.id)"
                                                class="font-mono text-xs font-bold text-indigo-600 hover:underline"
                                            >
                                                {{ inv.invoice_number }}
                                            </Link>
                                            <span
                                                :class="inv.is_overdue ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800'"
                                                class="rounded px-1.5 py-0.5 text-[10px] font-bold"
                                            >
                                                {{ inv.days_text }}
                                            </span>
                                        </div>
                                        <div class="text-xs font-medium text-slate-700 mt-0.5 truncate">
                                            {{ inv.client_name }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            Jatuh tempo: {{ inv.due_date }}
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                        <span class="text-xs font-extrabold text-slate-900">
                                            {{ formatCurrency(inv.grand_total) }}
                                        </span>
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="markAsPaid(inv.id, inv.invoice_number)"
                                                class="rounded bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 hover:bg-emerald-100 active:bg-emerald-200 active:scale-[0.98] transition shadow-sm"
                                                title="Tandai pembayaran telah diterima"
                                            >
                                                ✓ Lunas
                                            </button>
                                            <a
                                                :href="route('invoices.pdf', inv.id)"
                                                class="rounded border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 active:bg-slate-100 active:scale-[0.98] transition"
                                                title="Cetak PDF Faktur"
                                            >
                                                PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State Perlu Ditindaklanjuti -->
                            <div v-else class="py-10 text-center">
                                <span class="text-3xl">🎉</span>
                                <h4 class="mt-2 text-sm font-bold text-slate-800">Semua Tagihan Terkendali!</h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Tidak ada faktur yang terlambat atau mendekati jatuh tempo saat ini.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 text-right">
                            <Link :href="route('invoices.index')" class="text-xs font-bold text-indigo-600 hover:underline">
                                Lihat Semua Faktur &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- 4. Faktur Terbaru & Quick Actions -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Tabel Faktur Terbaru (2 Kolom) -->
                    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span>📑</span>
                                <span>Faktur Terbaru Diterbitkan</span>
                            </h3>
                            <Link :href="route('invoices.index')" class="text-xs font-bold text-indigo-600 hover:underline">
                                Semua Faktur &rarr;
                            </Link>
                        </div>

                        <div v-if="recentInvoices.length > 0" class="mt-4 overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider">
                                        <th class="py-2.5">No. Faktur</th>
                                        <th class="py-2.5">Pelanggan</th>
                                        <th class="py-2.5">Tanggal</th>
                                        <th class="py-2.5 text-right">Total</th>
                                        <th class="py-2.5 text-center">Status</th>
                                        <th class="py-2.5 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="inv in recentInvoices" :key="inv.id" class="hover:bg-slate-50">
                                        <td class="py-3 font-mono font-bold text-indigo-600">
                                            <Link :href="route('invoices.show', inv.id)" class="hover:underline">
                                                {{ inv.invoice_number }}
                                            </Link>
                                        </td>
                                        <td class="py-3 text-slate-700 font-medium">{{ inv.client_name }}</td>
                                        <td class="py-3 text-slate-500">{{ inv.issue_date }}</td>
                                        <td class="py-3 text-right font-bold text-slate-900">
                                            {{ formatCurrency(inv.grand_total) }}
                                        </td>
                                        <td class="py-3 text-center">
                                            <span
                                                :class="{
                                                    'bg-emerald-100 text-emerald-800': inv.status === 'paid',
                                                    'bg-amber-100 text-amber-800': inv.status === 'unpaid',
                                                    'bg-slate-100 text-slate-800': inv.status === 'draft',
                                                }"
                                                class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                            >
                                                {{ inv.status === 'paid' ? 'Lunas' : (inv.status === 'unpaid' ? 'Belum Bayar' : 'Draft') }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-right">
                                            <a
                                                :href="route('invoices.pdf', inv.id)"
                                                class="font-semibold text-slate-600 hover:text-indigo-600"
                                            >
                                                PDF
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="py-10 text-center">
                            <span class="text-3xl">📝</span>
                            <h4 class="mt-2 text-sm font-bold text-slate-800">Belum Ada Faktur</h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Terbitkan faktur pertama Anda untuk memulai pencatatan tagihan dan piutang.
                            </p>
                            <Link
                                :href="route('invoices.create')"
                                class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:bg-indigo-800 active:scale-[0.98]"
                            >
                                + Buat Faktur Sekarang
                            </Link>
                        </div>
                    </div>

                    <!-- Panduan / Quick Setup Card (1 Kolom) -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                                <span>⚡</span>
                                <span>Aksi Cepat & Navigasi</span>
                            </h3>

                            <div class="mt-4 space-y-3">
                                <Link
                                    :href="route('profile.business.edit')"
                                    class="flex items-center justify-between rounded-lg border border-slate-100 p-3 hover:border-indigo-200 hover:bg-slate-50 transition"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">🏢</span>
                                        <div>
                                            <div class="text-xs font-bold text-slate-800">Profil Usaha & Rekening Bank</div>
                                            <div class="text-[11px] text-slate-500">NPWP, Rekening Pembayaran, Stempel TTE</div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-slate-400">&rarr;</span>
                                </Link>

                                <Link
                                    :href="route('clients.index')"
                                    class="flex items-center justify-between rounded-lg border border-slate-100 p-3 hover:border-indigo-200 hover:bg-slate-50 transition"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">👥</span>
                                        <div>
                                            <div class="text-xs font-bold text-slate-800">Manajemen Pelanggan</div>
                                            <div class="text-[11px] text-slate-500">Kelola buku kontak & NPWP pembeli</div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-slate-400">&rarr;</span>
                                </Link>

                                <Link
                                    :href="route('products.index')"
                                    class="flex items-center justify-between rounded-lg border border-slate-100 p-3 hover:border-indigo-200 hover:bg-slate-50 transition"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">📦</span>
                                        <div>
                                            <div class="text-xs font-bold text-slate-800">Katalog Produk & Jasa</div>
                                            <div class="text-[11px] text-slate-500">Daftar harga satuan dan layanan</div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-slate-400">&rarr;</span>
                                </Link>

                                <Link
                                    :href="route('invoices.create')"
                                    class="flex items-center justify-between rounded-lg border border-indigo-100 bg-indigo-50/50 p-3 hover:bg-indigo-50 transition"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">📄</span>
                                        <div>
                                            <div class="text-xs font-bold text-indigo-900">Buat Faktur Baru</div>
                                            <div class="text-[11px] text-indigo-700">Hitung otomatis PPN & diskon</div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-indigo-500">&rarr;</span>
                                </Link>
                            </div>
                        </div>

                        <div class="mt-6 rounded-lg bg-slate-50 p-3 text-[11px] text-slate-500 border border-slate-100">
                            💡 <strong>Tips Penagihan:</strong> Faktur yang memiliki nomor rekening bank jelas dan QR verifikasi resmi terbukti 40% lebih cepat dibayar oleh pelanggan korporat.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
