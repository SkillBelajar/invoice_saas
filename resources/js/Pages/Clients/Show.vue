<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    client: Object,
    stats: Object,
    dealStages: {
        type: Array,
        default: () => ['Prospek', 'Proposal', 'Negosiasi', 'Deal Menang', 'Deal Batal'],
    },
    interactionTypes: {
        type: Array,
        default: () => ['Telepon', 'Email', 'Meeting', 'Lainnya'],
    },
    interactionStatuses: {
        type: Array,
        default: () => ['Pending', 'Selesai'],
    },
    ticketPriorities: {
        type: Array,
        default: () => ['Rendah', 'Sedang', 'Tinggi'],
    },
    ticketStatuses: {
        type: Array,
        default: () => ['Buka', 'Diproses', 'Selesai', 'Tutup'],
    },
});

const activeTab = ref('invoices');

// Modals state
const showDealModal = ref(false);
const showInteractionModal = ref(false);
const showTicketModal = ref(false);

// Forms
const dealForm = useForm({
    client_id: props.client.id,
    title: '',
    amount: '',
    stage: 'Prospek',
    expected_close_date: '',
});

const interactionForm = useForm({
    client_id: props.client.id,
    type: 'Telepon',
    scheduled_at: '',
    notes: '',
    status: 'Pending',
});

const ticketForm = useForm({
    client_id: props.client.id,
    subject: '',
    description: '',
    priority: 'Sedang',
    due_date: '',
});

// Formatters
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

const formatDateTime = (val) => {
    if (!val) return '—';
    return new Date(val).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const isOverdue = (dateStr, status) => {
    if (!dateStr) return false;
    if (status === 'Selesai' || status === 'Tutup') return false;
    return new Date(dateStr) < new Date();
};

// Modal Openers
function openCreateDeal() {
    dealForm.reset();
    dealForm.client_id = props.client.id;
    dealForm.stage = 'Prospek';
    dealForm.clearErrors();
    showDealModal.value = true;
}

function submitDeal() {
    dealForm.post(route('crm.deals.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showDealModal.value = false;
            dealForm.reset();
        },
    });
}

function updateDealStage(deal, newStage) {
    router.patch(
        route('crm.deals.update-stage', deal.id),
        { stage: newStage },
        { preserveScroll: true }
    );
}

function deleteDeal(deal) {
    if (window.confirm(`Hapus peluang "${deal.title}"?`)) {
        router.delete(route('crm.deals.destroy', deal.id), { preserveScroll: true });
    }
}

function openCreateInteraction() {
    interactionForm.reset();
    interactionForm.client_id = props.client.id;
    interactionForm.type = 'Telepon';
    interactionForm.status = 'Pending';
    // default to tomorrow 09:00
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(9, 0, 0, 0);
    interactionForm.scheduled_at = tomorrow.toISOString().slice(0, 16);
    interactionForm.clearErrors();
    showInteractionModal.value = true;
}

function submitInteraction() {
    interactionForm.post(route('crm.interactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showInteractionModal.value = false;
            interactionForm.reset();
        },
    });
}

function toggleInteraction(act) {
    router.patch(route('crm.interactions.toggle', act.id), {}, { preserveScroll: true });
}

function deleteInteraction(act) {
    if (window.confirm('Hapus jadwal pengingat ini?')) {
        router.delete(route('crm.interactions.destroy', act.id), { preserveScroll: true });
    }
}

function openCreateTicket() {
    ticketForm.reset();
    ticketForm.client_id = props.client.id;
    ticketForm.priority = 'Sedang';
    // default SLA 3 days
    const due = new Date();
    due.setDate(due.getDate() + 3);
    ticketForm.due_date = due.toISOString().slice(0, 10);
    ticketForm.clearErrors();
    showTicketModal.value = true;
}

function submitTicket() {
    ticketForm.post(route('crm.tickets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showTicketModal.value = false;
            ticketForm.reset();
        },
    });
}

function updateTicketStatus(tk, newStatus) {
    router.patch(
        route('crm.tickets.update-status', tk.id),
        { status: newStatus },
        { preserveScroll: true }
    );
}

function deleteTicket(tk) {
    if (window.confirm(`Hapus tiket keluhan ${tk.ticket_number}?`)) {
        router.delete(route('crm.tickets.destroy', tk.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="`Detail Pelanggan: ${client.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('clients.index')"
                        class="rounded-lg border border-slate-300 bg-white p-2 text-slate-600 shadow-sm transition hover:bg-slate-50 active:bg-slate-100"
                        title="Kembali ke Buku Pelanggan"
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
                                {{ client.source }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500">Pusat Data Pelanggan, Faktur, Pipa CRM, Jadwal Interaksi & Tiket Layanan.</p>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        @click="openCreateDeal"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50/70 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 active:bg-indigo-200"
                    >
                        <span>+ Pipa Deal</span>
                    </button>
                    <button
                        @click="openCreateInteraction"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-sky-200 bg-sky-50/70 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:bg-sky-100 active:bg-sky-200"
                    >
                        <span>+ Jadwal Interaksi</span>
                    </button>
                    <button
                        @click="openCreateTicket"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 active:bg-amber-200"
                    >
                        <span>+ Tiket Keluhan</span>
                    </button>
                    <Link
                        :href="route('invoices.create')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:bg-indigo-800"
                    >
                        <span>+ Buat Faktur</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Summary Metrics Cards -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                        <div class="text-[11px] font-medium text-slate-500">Total Faktur</div>
                        <div class="mt-1 text-lg font-bold text-slate-900 truncate">{{ formatRupiah(stats.total_invoiced) }}</div>
                        <div class="text-[11px] text-slate-400">{{ stats.invoices_count }} dokumen</div>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3.5 shadow-sm">
                        <div class="text-[11px] font-medium text-emerald-800">Total Terbayar</div>
                        <div class="mt-1 text-lg font-bold text-emerald-600 truncate">{{ formatRupiah(stats.total_paid) }}</div>
                        <div class="text-[11px] text-emerald-700">Lunas</div>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-3.5 shadow-sm">
                        <div class="text-[11px] font-medium text-amber-800">Sisa Piutang</div>
                        <div class="mt-1 text-lg font-bold text-amber-600 truncate">{{ formatRupiah(stats.total_unpaid) }}</div>
                        <div class="text-[11px] text-amber-700">Belum dibayar</div>
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-3.5 shadow-sm">
                        <div class="text-[11px] font-medium text-indigo-800">Peluang Deals</div>
                        <div class="mt-1 text-lg font-bold text-indigo-600">{{ stats.deals_count }}</div>
                        <div class="text-[11px] text-indigo-700">Pipa transaksi</div>
                    </div>
                    <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-3.5 shadow-sm">
                        <div class="text-[11px] font-medium text-sky-800">Jadwal Follow-up</div>
                        <div class="mt-1 text-lg font-bold text-sky-600">{{ stats.pending_interactions_count }}</div>
                        <div class="text-[11px] text-sky-700">Pending</div>
                    </div>
                    <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-3.5 shadow-sm">
                        <div class="text-[11px] font-medium text-rose-800">Tiket Aktif</div>
                        <div class="mt-1 text-lg font-bold text-rose-600">{{ stats.open_tickets_count }}</div>
                        <div class="text-[11px] text-rose-700">Butuh ditangani</div>
                    </div>
                </div>

                <!-- Client Contact Information Accordion/Card -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📇</span>
                            <h2 class="font-bold text-slate-800 text-sm">Informasi Kontak & Penagihan</h2>
                        </div>
                        <Link
                            :href="route('clients.index')"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline"
                        >
                            Edit di Buku Pelanggan &rarr;
                        </Link>
                    </div>

                    <div class="p-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 text-xs">
                        <div>
                            <span class="font-semibold uppercase tracking-wider text-slate-400">Email</span>
                            <p class="mt-1 font-medium text-slate-800">
                                <a v-if="client.email" :href="`mailto:${client.email}`" class="text-indigo-600 hover:underline">
                                    {{ client.email }}
                                </a>
                                <span v-else class="text-slate-400">—</span>
                            </p>
                        </div>
                        <div>
                            <span class="font-semibold uppercase tracking-wider text-slate-400">Telepon / WhatsApp</span>
                            <p class="mt-1 font-medium text-slate-800">
                                <a v-if="client.phone" :href="`tel:${client.phone}`" class="text-indigo-600 hover:underline">
                                    {{ client.phone }}
                                </a>
                                <span v-else class="text-slate-400">—</span>
                            </p>
                        </div>
                        <div>
                            <span class="font-semibold uppercase tracking-wider text-slate-400">NPWP / NIK</span>
                            <p class="mt-1 font-mono font-medium text-slate-700">{{ client.tax_id || '—' }}</p>
                        </div>
                        <div>
                            <span class="font-semibold uppercase tracking-wider text-slate-400">Terdaftar Sejak</span>
                            <p class="mt-1 font-medium text-slate-700">{{ formatDate(client.created_at) }}</p>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4">
                            <span class="font-semibold uppercase tracking-wider text-slate-400">Alamat Penagihan</span>
                            <p class="mt-1 text-slate-700 bg-slate-50 p-2.5 rounded-lg border border-slate-100 whitespace-pre-line">
                                {{ client.address }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- CRM & INVOICE TABS SECTION -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <!-- Tab Navigation -->
                    <div class="border-b border-slate-200 bg-slate-50/50 px-4 sm:px-6">
                        <nav class="-mb-px flex space-x-6 overflow-x-auto text-sm font-semibold" aria-label="Tabs">
                            <!-- Tab Faktur -->
                            <button
                                @click="activeTab = 'invoices'"
                                :class="[
                                    activeTab === 'invoices'
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-bold flex items-center gap-2'
                                ]"
                            >
                                <span>📄 Faktur Penagihan</span>
                                <span
                                    :class="[
                                        activeTab === 'invoices' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600',
                                        'rounded-full px-2 py-0.5 text-xs'
                                    ]"
                                >
                                    {{ client.invoices?.length || 0 }}
                                </span>
                            </button>

                            <!-- Tab Pipa Deals -->
                            <button
                                @click="activeTab = 'deals'"
                                :class="[
                                    activeTab === 'deals'
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-bold flex items-center gap-2'
                                ]"
                            >
                                <span>🎯 Pipa CRM / Peluang Deals</span>
                                <span
                                    :class="[
                                        activeTab === 'deals' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600',
                                        'rounded-full px-2 py-0.5 text-xs'
                                    ]"
                                >
                                    {{ client.deals?.length || 0 }}
                                </span>
                            </button>

                            <!-- Tab Jadwal Interaksi -->
                            <button
                                @click="activeTab = 'interactions'"
                                :class="[
                                    activeTab === 'interactions'
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-bold flex items-center gap-2'
                                ]"
                            >
                                <span>📅 Jadwal Interaksi</span>
                                <span
                                    :class="[
                                        activeTab === 'interactions' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600',
                                        'rounded-full px-2 py-0.5 text-xs'
                                    ]"
                                >
                                    {{ client.interactions?.length || 0 }}
                                </span>
                            </button>

                            <!-- Tab Tiket Keluhan -->
                            <button
                                @click="activeTab = 'tickets'"
                                :class="[
                                    activeTab === 'tickets'
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                                    'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-bold flex items-center gap-2'
                                ]"
                            >
                                <span>🎫 Tiket Keluhan</span>
                                <span
                                    :class="[
                                        activeTab === 'tickets' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600',
                                        'rounded-full px-2 py-0.5 text-xs'
                                    ]"
                                >
                                    {{ client.tickets?.length || 0 }}
                                </span>
                            </button>
                        </nav>
                    </div>

                    <!-- TAB CONTENT: INVOICES -->
                    <div v-if="activeTab === 'invoices'" class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Riwayat Faktur Penagihan</h3>
                                <p class="text-xs text-slate-500">Daftar invoice resmi yang diterbitkan untuk {{ client.name }}.</p>
                            </div>
                            <Link
                                :href="route('invoices.create')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                            >
                                <span>+ Buat Faktur Baru</span>
                            </Link>
                        </div>

                        <!-- Table Invoices -->
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                    <tr>
                                        <th class="py-3 pl-4 pr-3">No. Faktur</th>
                                        <th class="py-3 px-3">Tanggal Terbit</th>
                                        <th class="py-3 px-3">Jatuh Tempo</th>
                                        <th class="py-3 px-3 text-center">Item</th>
                                        <th class="py-3 px-3 text-right">Total Tagihan</th>
                                        <th class="py-3 px-3 text-center">Status</th>
                                        <th class="py-3 pl-3 pr-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="inv in client.invoices" :key="inv.id" class="hover:bg-slate-50/70">
                                        <td class="py-3.5 pl-4 pr-3 font-mono text-xs font-bold text-indigo-600">
                                            <Link :href="route('invoices.show', inv.id)" class="hover:underline">
                                                {{ inv.invoice_number }}
                                            </Link>
                                        </td>
                                        <td class="py-3.5 px-3 text-xs text-slate-600">{{ formatDate(inv.issue_date) }}</td>
                                        <td class="py-3.5 px-3 text-xs text-slate-600">{{ formatDate(inv.due_date) }}</td>
                                        <td class="py-3.5 px-3 text-center text-xs text-slate-500">{{ inv.items_count || '—' }}</td>
                                        <td class="py-3.5 px-3 text-right font-bold text-slate-900">{{ formatRupiah(inv.grand_total) }}</td>
                                        <td class="py-3.5 px-3 text-center">
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
                                        <td class="py-3.5 pl-3 pr-4 text-right text-xs space-x-2 whitespace-nowrap">
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
                                                Detail
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="!client.invoices?.length">
                                        <td colspan="7" class="py-12 text-center text-sm text-slate-500">
                                            Belum ada dokumen faktur untuk pelanggan ini.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB CONTENT: CRM DEALS -->
                    <div v-if="activeTab === 'deals'" class="p-6 space-y-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Pipa Penjualan & Peluang (Deals)</h3>
                                <p class="text-xs text-slate-500">Pantau dan dorong peluang penawaran kepada {{ client.name }}.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="route('crm.deals.index')"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline mr-2"
                                >
                                    Papan Kanban Penuh &rarr;
                                </Link>
                                <button
                                    @click="openCreateDeal"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                                >
                                    <span>+ Tambah Peluang (Deal)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Deals List -->
                        <div v-if="client.deals?.length" class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                    <tr>
                                        <th class="py-3 pl-4 pr-3">Judul Peluang</th>
                                        <th class="py-3 px-3">Estimasi Nilai</th>
                                        <th class="py-3 px-3">Tahap (Pipeline Stage)</th>
                                        <th class="py-3 px-3">Target Closing</th>
                                        <th class="py-3 pl-3 pr-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="deal in client.deals" :key="deal.id" class="hover:bg-slate-50/70">
                                        <td class="py-3.5 pl-4 pr-3 font-semibold text-slate-900">
                                            {{ deal.title }}
                                        </td>
                                        <td class="py-3.5 px-3 font-bold text-indigo-600">
                                            {{ formatRupiah(deal.amount) }}
                                        </td>
                                        <td class="py-3.5 px-3">
                                            <select
                                                :value="deal.stage"
                                                @change="updateDealStage(deal, $event.target.value)"
                                                class="rounded-lg border-slate-300 text-xs py-1 px-2.5 font-medium text-slate-700 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option v-for="stage in dealStages" :key="stage" :value="stage">
                                                    {{ stage }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="py-3.5 px-3 text-xs text-slate-600">
                                            {{ formatDate(deal.expected_close_date) }}
                                        </td>
                                        <td class="py-3.5 pl-3 pr-4 text-right text-xs">
                                            <button
                                                @click="deleteDeal(deal)"
                                                class="font-semibold text-rose-600 hover:text-rose-900 hover:underline"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State Deals -->
                        <div v-else class="rounded-xl border border-dashed border-slate-200 p-8 text-center space-y-3">
                            <span class="text-3xl">🎯</span>
                            <h4 class="font-bold text-slate-800 text-sm">Belum Ada Peluang Tercatat</h4>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">
                                Mulai catat peluang penawaran produk atau proyek untuk pelanggan ini agar proses negosiasi terpantau teratur.
                            </p>
                            <button
                                @click="openCreateDeal"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                            >
                                + Catat Peluang Pertama
                            </button>
                        </div>
                    </div>

                    <!-- TAB CONTENT: JADWAL INTERAKSI -->
                    <div v-if="activeTab === 'interactions'" class="p-6 space-y-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Jadwal Pengingat & Interaksi Klien</h3>
                                <p class="text-xs text-slate-500">Agenda follow-up, panggilan telepon, email, dan pertemuan.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="route('crm.interactions.index')"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline mr-2"
                                >
                                    Semua Interaksi &rarr;
                                </Link>
                                <button
                                    @click="openCreateInteraction"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                                >
                                    <span>+ Catat Jadwal Interaksi</span>
                                </button>
                            </div>
                        </div>

                        <!-- Interactions List -->
                        <div v-if="client.interactions?.length" class="divide-y divide-slate-100 rounded-xl border border-slate-200 overflow-hidden">
                            <div
                                v-for="act in client.interactions"
                                :key="act.id"
                                class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50/60"
                            >
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700">
                                            {{ act.type }}
                                        </span>
                                        <span
                                            :class="[
                                                act.status === 'Selesai'
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                    : 'border-amber-200 bg-amber-50 text-amber-700',
                                                'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold'
                                            ]"
                                        >
                                            {{ act.status === 'Selesai' ? '✓ Selesai' : '⏳ Pending' }}
                                        </span>
                                        <span
                                            v-if="isOverdue(act.scheduled_at, act.status)"
                                            class="rounded-full bg-rose-50 border border-rose-200 px-2 py-0.5 text-[10px] font-bold text-rose-700"
                                        >
                                            Lewat Waktu!
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-800 whitespace-pre-line font-medium">
                                        {{ act.notes || 'Tanpa catatan spesifik.' }}
                                    </p>
                                    <div class="text-[11px] text-slate-400">
                                        Waktu Jadwal: {{ formatDateTime(act.scheduled_at) }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <button
                                        @click="toggleInteraction(act)"
                                        :class="[
                                            act.status === 'Selesai'
                                                ? 'border-slate-300 text-slate-700 bg-white hover:bg-slate-50'
                                                : 'border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100',
                                            'rounded-lg border px-3 py-1.5 text-xs font-semibold transition'
                                        ]"
                                    >
                                        {{ act.status === 'Selesai' ? 'Ubah ke Pending' : 'Tandai Selesai ✓' }}
                                    </button>
                                    <button
                                        @click="deleteInteraction(act)"
                                        class="rounded-lg p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        title="Hapus Jadwal"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State Interactions -->
                        <div v-else class="rounded-xl border border-dashed border-slate-200 p-8 text-center space-y-3">
                            <span class="text-3xl">📅</span>
                            <h4 class="font-bold text-slate-800 text-sm">Belum Ada Agenda Interaksi</h4>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">
                                Jadwalkan panggilan telepon, janji temu, atau email penawaran berikutnya agar komunikasi dengan pelanggan selalu terjaga.
                            </p>
                            <button
                                @click="openCreateInteraction"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                            >
                                + Buat Jadwal Interaksi
                            </button>
                        </div>
                    </div>

                    <!-- TAB CONTENT: TIKET KELUHAN -->
                    <div v-if="activeTab === 'tickets'" class="p-6 space-y-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Pusat Tiket Keluhan & Layanan</h3>
                                <p class="text-xs text-slate-500">Kelola keluhan, eskalasi penanganan, dan komitmen SLA pelanggan.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="route('crm.tickets.index')"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline mr-2"
                                >
                                    Semua Tiket &rarr;
                                </Link>
                                <button
                                    @click="openCreateTicket"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                                >
                                    <span>+ Buka Tiket Keluhan</span>
                                </button>
                            </div>
                        </div>

                        <!-- Tickets List -->
                        <div v-if="client.tickets?.length" class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                    <tr>
                                        <th class="py-3 pl-4 pr-3">No. Tiket & Subjek</th>
                                        <th class="py-3 px-3">Prioritas</th>
                                        <th class="py-3 px-3">Status Penanganan</th>
                                        <th class="py-3 px-3">Target SLA</th>
                                        <th class="py-3 px-3">Kepuasan CSAT</th>
                                        <th class="py-3 pl-3 pr-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="tk in client.tickets" :key="tk.id" class="hover:bg-slate-50/70">
                                        <td class="py-3.5 pl-4 pr-3">
                                            <div class="font-mono text-xs font-bold text-indigo-600">{{ tk.ticket_number }}</div>
                                            <div class="font-semibold text-slate-900 text-xs mt-0.5">{{ tk.subject }}</div>
                                            <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1 max-w-xs">{{ tk.description }}</p>
                                        </td>
                                        <td class="py-3.5 px-3">
                                            <span
                                                :class="[
                                                    tk.priority === 'Tinggi'
                                                        ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                        : tk.priority === 'Sedang'
                                                        ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                        : 'bg-slate-50 text-slate-700 border-slate-200',
                                                    'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold'
                                                ]"
                                            >
                                                {{ tk.priority }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-3">
                                            <select
                                                :value="tk.status"
                                                @change="updateTicketStatus(tk, $event.target.value)"
                                                class="rounded-lg border-slate-300 text-xs py-1 px-2.5 font-medium text-slate-700 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option v-for="st in ticketStatuses" :key="st" :value="st">
                                                    {{ st }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="py-3.5 px-3 text-xs">
                                            <div class="text-slate-700">{{ formatDate(tk.due_date) }}</div>
                                            <span
                                                v-if="isOverdue(tk.due_date, tk.status)"
                                                class="text-[10px] font-bold text-rose-600 block"
                                            >
                                                Lewat SLA!
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-3 text-xs">
                                            <div v-if="tk.survey" class="flex items-center gap-1 text-amber-500 font-bold">
                                                <span>★</span>
                                                <span class="text-slate-800 text-xs">{{ tk.survey.rating }}/5</span>
                                            </div>
                                            <span v-else class="text-slate-400 text-xs">—</span>
                                        </td>
                                        <td class="py-3.5 pl-3 pr-4 text-right text-xs">
                                            <button
                                                @click="deleteTicket(tk)"
                                                class="font-semibold text-rose-600 hover:text-rose-900 hover:underline"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State Tickets -->
                        <div v-else class="rounded-xl border border-dashed border-slate-200 p-8 text-center space-y-3">
                            <span class="text-3xl">🎫</span>
                            <h4 class="font-bold text-slate-800 text-sm">Tidak Ada Tiket Keluhan</h4>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">
                                Semua keluhan atau permintaan layanan dari pelanggan ini telah tertangani dengan baik.
                            </p>
                            <button
                                @click="openCreateTicket"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                            >
                                + Buka Tiket Baru
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: TAMBAH DEAL -->
        <div v-if="showDealModal" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 sm:items-center sm:p-4" @click.self="showDealModal=false">
            <section role="dialog" aria-modal="true" class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-md sm:rounded-2xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Tambah Peluang (Deal) Baru</h2>
                        <p class="text-xs text-slate-500">Pelanggan: {{ client.name }}</p>
                    </div>
                    <button @click="showDealModal=false" class="rounded p-1 text-slate-400 hover:bg-slate-100">✕</button>
                </div>
                <form @submit.prevent="submitDeal" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Judul Peluang / Proyek *</label>
                        <input
                            v-model="dealForm.title"
                            type="text"
                            required
                            placeholder="Contoh: Pengadaan Software Tahunan"
                            class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span v-if="dealForm.errors.title" class="text-rose-600 text-[11px]">{{ dealForm.errors.title }}</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Estimasi Nilai Kesepakatan (Rp) *</label>
                        <input
                            v-model="dealForm.amount"
                            type="number"
                            min="0"
                            required
                            placeholder="0"
                            class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span v-if="dealForm.errors.amount" class="text-rose-600 text-[11px]">{{ dealForm.errors.amount }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tahap Awal *</label>
                            <select
                                v-model="dealForm.stage"
                                class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="st in dealStages" :key="st" :value="st">{{ st }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Target Closing</label>
                            <input
                                v-model="dealForm.expected_close_date"
                                type="date"
                                class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showDealModal=false"
                            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="dealForm.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ dealForm.processing ? 'Menyimpan...' : 'Simpan Peluang' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <!-- MODAL: TAMBAH INTERAKSI -->
        <div v-if="showInteractionModal" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 sm:items-center sm:p-4" @click.self="showInteractionModal=false">
            <section role="dialog" aria-modal="true" class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-md sm:rounded-2xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Catat Jadwal Interaksi Baru</h2>
                        <p class="text-xs text-slate-500">Pelanggan: {{ client.name }}</p>
                    </div>
                    <button @click="showInteractionModal=false" class="rounded p-1 text-slate-400 hover:bg-slate-100">✕</button>
                </div>
                <form @submit.prevent="submitInteraction" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tipe Interaksi *</label>
                            <select
                                v-model="interactionForm.type"
                                class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="t in interactionTypes" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select
                                v-model="interactionForm.status"
                                class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="s in interactionStatuses" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tanggal & Waktu Jadwal *</label>
                        <input
                            v-model="interactionForm.scheduled_at"
                            type="datetime-local"
                            required
                            class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span v-if="interactionForm.errors.scheduled_at" class="text-rose-600 text-[11px]">{{ interactionForm.errors.scheduled_at }}</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Catatan Interaksi / Agenda</label>
                        <textarea
                            v-model="interactionForm.notes"
                            rows="3"
                            placeholder="Contoh: Telepon konfirmasi tagihan faktur bulan ini..."
                            class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showInteractionModal=false"
                            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="interactionForm.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ interactionForm.processing ? 'Menyimpan...' : 'Simpan Jadwal' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <!-- MODAL: TAMBAH TIKET KELUHAN -->
        <div v-if="showTicketModal" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 sm:items-center sm:p-4" @click.self="showTicketModal=false">
            <section role="dialog" aria-modal="true" class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-md sm:rounded-2xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Buka Tiket Keluhan Baru</h2>
                        <p class="text-xs text-slate-500">Pelanggan: {{ client.name }}</p>
                    </div>
                    <button @click="showTicketModal=false" class="rounded p-1 text-slate-400 hover:bg-slate-100">✕</button>
                </div>
                <form @submit.prevent="submitTicket" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Subjek Keluhan *</label>
                        <input
                            v-model="ticketForm.subject"
                            type="text"
                            required
                            placeholder="Contoh: Kesalahan rincian item invoice #INV-001"
                            class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span v-if="ticketForm.errors.subject" class="text-rose-600 text-[11px]">{{ ticketForm.errors.subject }}</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Deskripsi Lengkap Masalah *</label>
                        <textarea
                            v-model="ticketForm.description"
                            required
                            rows="3"
                            placeholder="Jelaskan kendala yang dialami pelanggan..."
                            class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <span v-if="ticketForm.errors.description" class="text-rose-600 text-[11px]">{{ ticketForm.errors.description }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Prioritas *</label>
                            <select
                                v-model="ticketForm.priority"
                                class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="p in ticketPriorities" :key="p" :value="p">{{ p }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Batas Waktu SLA *</label>
                            <input
                                v-model="ticketForm.due_date"
                                type="date"
                                required
                                class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <span v-if="ticketForm.errors.due_date" class="text-rose-600 text-[11px]">{{ ticketForm.errors.due_date }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showTicketModal=false"
                            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="ticketForm.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ ticketForm.processing ? 'Menyimpan...' : 'Terbitkan Tiket' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
