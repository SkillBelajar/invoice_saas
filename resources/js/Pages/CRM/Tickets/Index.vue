<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tickets: Object,
    clients: Array,
    nextTicketNumber: String,
    filters: Object,
    metrics: Object,
    priorities: Array,
    statuses: Array,
});

const isModalOpen = ref(false);
const editingTicket = ref(null);
const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'Semua');
const selectedPriority = ref(props.filters.priority || '');

const form = useForm({
    client_id: '',
    subject: '',
    description: '',
    priority: 'Sedang',
    status: 'Buka',
    due_date: '',
});

// --- CSAT (Survei Kepuasan) ---
const surveyTicket = ref(null);
const surveyForm = useForm({ rating: 0, feedback: '' });

const openSurvey = (ticket) => {
    surveyTicket.value = ticket;
    surveyForm.reset();
};

const closeSurvey = () => {
    surveyTicket.value = null;
};

const submitSurvey = () => {
    surveyForm.post(route('crm.surveys.store', surveyTicket.value.id), {
        preserveScroll: true,
        onSuccess: () => closeSurvey(),
    });
};

function formatDateTimeForInput(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function formatDisplayDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function isOverdue(ticket) {
    if (['Selesai', 'Tutup'].includes(ticket.status)) return false;
    return new Date(ticket.due_date) < new Date();
}

function openCreateModal() {
    editingTicket.value = null;
    form.reset();
    form.clearErrors();
    form.client_id = props.clients.length > 0 ? props.clients[0].id : '';
    form.subject = '';
    form.description = '';
    form.priority = 'Sedang';
    form.status = 'Buka';
    // Default SLA 24 hours from now
    const in24Hours = new Date(Date.now() + 24 * 3600 * 1000);
    form.due_date = formatDateTimeForInput(in24Hours);
    isModalOpen.value = true;
}

function openEditModal(ticket) {
    editingTicket.value = ticket;
    form.clearErrors();
    form.client_id = ticket.client_id;
    form.subject = ticket.subject;
    form.description = ticket.description;
    form.priority = ticket.priority;
    form.status = ticket.status;
    form.due_date = formatDateTimeForInput(ticket.due_date);
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingTicket.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingTicket.value) {
        form.put(route('crm.tickets.update', editingTicket.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('crm.tickets.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

function quickUpdateStatus(ticket, newStatus) {
    if (ticket.status === newStatus) return;
    router.patch(
        route('crm.tickets.update-status', ticket.id),
        { status: newStatus },
        {
            preserveScroll: true,
        }
    );
}

function deleteTicket(ticket) {
    if (confirm(`Apakah Anda yakin ingin menghapus tiket ${ticket.ticket_number}?`)) {
        router.delete(route('crm.tickets.destroy', ticket.id), {
            preserveScroll: true,
        });
    }
}

function applyFilters() {
    router.get(
        route('crm.tickets.index'),
        {
            status: selectedStatus.value !== 'Semua' ? selectedStatus.value : undefined,
            priority: selectedPriority.value || undefined,
            search: search.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
}

function setStatusFilter(status) {
    selectedStatus.value = status;
    applyFilters();
}

function priorityBadgeClass(priority) {
    switch (priority) {
        case 'Tinggi':
            return 'bg-rose-100 text-rose-800 border-rose-200';
        case 'Sedang':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'Rendah':
            return 'bg-slate-100 text-slate-700 border-slate-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
}

function statusBadgeClass(status) {
    switch (status) {
        case 'Buka':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'Diproses':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'Selesai':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'Tutup':
            return 'bg-slate-100 text-slate-600 border-slate-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
}
</script>

<template>
    <Head title="Pusat Tiket Keluhan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Pusat Tiket Keluhan Pelanggan
                    </h1>
                    <p class="text-sm text-slate-500">
                        Pantau keluhan klien, target waktu penyelesaian (SLA), dan status penanganan keluhan secara profesional.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:bg-indigo-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ Terbitkan Tiket Baru</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Top Metric Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Tiket Buka / Baru</p>
                    <div class="mt-2 flex items-baseline justify-between">
                        <p class="text-2xl font-black text-blue-600">{{ metrics.open }}</p>
                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200">Buka</span>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Sedang Diproses</p>
                    <div class="mt-2 flex items-baseline justify-between">
                        <p class="text-2xl font-black text-amber-600">{{ metrics.in_progress }}</p>
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">Proses</span>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Lewat Target SLA</p>
                    <div class="mt-2 flex items-baseline justify-between">
                        <p class="text-2xl font-black text-rose-600">{{ metrics.overdue }}</p>
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200">Alert SLA</span>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Selesai / Tutup</p>
                    <div class="mt-2 flex items-baseline justify-between">
                        <p class="text-2xl font-black text-emerald-600">{{ metrics.resolved }}</p>
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">Tuntas</span>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Status Filter Tabs -->
                    <div class="inline-flex flex-wrap rounded-xl bg-slate-100 p-1 text-xs font-semibold">
                        <button
                            type="button"
                            @click="setStatusFilter('Semua')"
                            :class="[
                                'rounded-lg px-3 py-1.5 transition',
                                selectedStatus === 'Semua'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            Semua ({{ metrics.total }})
                        </button>
                        <button
                            type="button"
                            @click="setStatusFilter('Buka')"
                            :class="[
                                'rounded-lg px-3 py-1.5 transition',
                                selectedStatus === 'Buka'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            Buka ({{ metrics.open }})
                        </button>
                        <button
                            type="button"
                            @click="setStatusFilter('Diproses')"
                            :class="[
                                'rounded-lg px-3 py-1.5 transition',
                                selectedStatus === 'Diproses'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            Diproses ({{ metrics.in_progress }})
                        </button>
                        <button
                            type="button"
                            @click="setStatusFilter('Overdue')"
                            :class="[
                                'rounded-lg px-3 py-1.5 transition',
                                selectedStatus === 'Overdue'
                                    ? 'bg-rose-600 text-white shadow-xs'
                                    : 'text-rose-600 hover:text-rose-800',
                            ]"
                        >
                            ⚠️ Lewat SLA ({{ metrics.overdue }})
                        </button>
                        <button
                            type="button"
                            @click="setStatusFilter('Selesai')"
                            :class="[
                                'rounded-lg px-3 py-1.5 transition',
                                selectedStatus === 'Selesai'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            Selesai ({{ metrics.resolved }})
                        </button>
                    </div>

                    <!-- Priority Filter & Search -->
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="selectedPriority"
                            @change="applyFilters"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Semua Prioritas</option>
                            <option v-for="p in priorities" :key="p" :value="p">Prioritas: {{ p }}</option>
                        </select>

                        <div class="relative min-w-[220px]">
                            <input
                                v-model="search"
                                @keydown.enter="applyFilters"
                                type="text"
                                placeholder="Cari tiket, subjek, klien..."
                                class="w-full rounded-xl border border-slate-300 bg-white py-1.5 pl-8 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <svg class="pointer-events-none absolute left-2.5 top-2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <button
                            type="button"
                            @click="applyFilters"
                            class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 active:scale-[0.98]"
                        >
                            Cari
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tickets Table -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600">
                            <tr>
                                <th scope="col" class="px-5 py-3.5">No. Tiket</th>
                                <th scope="col" class="px-5 py-3.5">Pelanggan</th>
                                <th scope="col" class="px-5 py-3.5">Subjek & Rincian Keluhan</th>
                                <th scope="col" class="px-5 py-3.5">Prioritas</th>
                                <th scope="col" class="px-5 py-3.5">Status Penanganan</th>
                                <th scope="col" class="px-5 py-3.5">Target SLA (Tenggat)</th>
                                <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="tickets.data.length === 0">
                                <td colspan="7" class="p-12 text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">
                                        🎫
                                    </div>
                                    <h3 class="mt-3 text-base font-semibold text-slate-900">Belum Ada Tiket Keluhan</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Semua layanan pelanggan berjalan lancar tanpa keluhan tertunda.
                                    </p>
                                    <button
                                        type="button"
                                        @click="openCreateModal"
                                        class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition"
                                    >
                                        + Terbitkan Tiket Baru
                                    </button>
                                </td>
                            </tr>

                            <!-- Baris Tiket -->
                            <tr
                                v-for="ticket in tickets.data"
                                :key="ticket.id"
                                :class="[
                                    'transition',
                                    isOverdue(ticket)
                                        ? 'bg-rose-50/70 hover:bg-rose-100/70'
                                        : ticket.status === 'Selesai'
                                            ? 'bg-emerald-50/20 hover:bg-emerald-50/40'
                                            : 'hover:bg-slate-50/80',
                                ]"
                            >
                                <!-- No. Tiket -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-xs font-black text-indigo-700">
                                            {{ ticket.ticket_number }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-400">
                                        {{ formatDisplayDate(ticket.created_at) }}
                                    </p>
                                </td>

                                <!-- Pelanggan -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <Link
                                        :href="route('clients.show', ticket.client_id)"
                                        class="font-semibold text-slate-900 hover:text-indigo-600 hover:underline"
                                    >
                                        {{ ticket.client?.name || 'Pelanggan #' + ticket.client_id }}
                                    </Link>
                                    <p v-if="ticket.client?.phone" class="text-xs text-slate-500">
                                        {{ ticket.client.phone }}
                                    </p>
                                </td>

                                <!-- Subjek & Rincian -->
                                <td class="px-5 py-4 max-w-xs sm:max-w-md">
                                    <p class="font-bold text-slate-900">
                                        {{ ticket.subject }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-600 line-clamp-2">
                                        {{ ticket.description }}
                                    </p>
                                </td>

                                <!-- Prioritas -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-bold',
                                            priorityBadgeClass(ticket.priority),
                                        ]"
                                    >
                                        {{ ticket.priority }}
                                    </span>
                                </td>

                                <!-- Status Penanganan (Quick Update) -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <select
                                            :value="ticket.status"
                                            @change="quickUpdateStatus(ticket, $event.target.value)"
                                            :class="[
                                                'rounded-lg border py-1 pl-2.5 pr-7 text-xs font-bold transition focus:ring-indigo-500',
                                                statusBadgeClass(ticket.status),
                                            ]"
                                        >
                                            <option v-for="st in statuses" :key="st" :value="st">{{ st }}</option>
                                        </select>
                                    </div>
                                </td>

                                <!-- Target SLA (Indikator Merah bila Overdue) -->
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div :class="isOverdue(ticket) ? 'text-rose-600' : 'text-slate-800'">
                                        <div class="flex items-center gap-1.5">
                                            <span v-if="isOverdue(ticket)" class="text-base leading-none">⚠️</span>
                                            <span :class="['text-xs', isOverdue(ticket) ? 'font-black tracking-tight' : 'font-semibold']">
                                                {{ formatDisplayDate(ticket.due_date) }}
                                            </span>
                                        </div>
                                        <div v-if="isOverdue(ticket)" class="mt-1">
                                            <span class="inline-flex rounded-md bg-rose-600 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white shadow-xs">
                                                TERLAMBAT (SLA LEWAT)
                                            </span>
                                        </div>
                                        <div v-else-if="ticket.status === 'Selesai'" class="mt-0.5">
                                            <span class="text-[11px] text-emerald-600 font-semibold">Tuntas Tepat Waktu</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(ticket)"
                                            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 transition active:scale-95"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            v-if="ticket.status === 'Tutup' && !ticket.survey"
                                            type="button"
                                            @click="openSurvey(ticket)"
                                            class="rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition active:scale-95"
                                        >
                                            ★ Beri Rating
                                        </button>
                                        <span
                                            v-else-if="ticket.survey"
                                            class="rounded-lg bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700"
                                            :title="ticket.survey.feedback || ''"
                                        >
                                            ★ {{ ticket.survey.rating }}/5
                                        </span>
                                        <button
                                            type="button"
                                            @click="deleteTicket(ticket)"
                                            class="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 transition active:scale-95"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <div
                    v-if="tickets.links && tickets.links.length > 3"
                    class="border-t border-slate-200 bg-slate-50/50 px-4 py-3 flex items-center justify-between sm:px-6"
                >
                    <p class="text-xs text-slate-500">
                        Menampilkan <span class="font-bold">{{ tickets.from || 0 }}</span> sampai <span class="font-bold">{{ tickets.to || 0 }}</span> dari <span class="font-bold">{{ tickets.total }}</span> tiket
                    </p>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, idx) in tickets.links"
                            :key="idx"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-lg px-2.5 py-1 text-xs font-medium transition',
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : link.url
                                        ? 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'
                                        : 'bg-slate-100 text-slate-400 cursor-not-allowed border border-transparent',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Tiket -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
        >
            <div
                class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl transition-all"
                role="dialog"
                aria-modal="true"
            >
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">
                            {{ editingTicket ? 'Edit Tiket Keluhan' : 'Terbitkan Tiket Keluhan Baru' }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            Nomor Tiket: <span class="font-mono font-bold text-indigo-600">{{ editingTicket ? editingTicket.ticket_number : nextTicketNumber }}</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeModal"
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <!-- Pilih Pelanggan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pelanggan *</label>
                        <select
                            v-model="form.client_id"
                            required
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="" disabled>-- Pilih Pelanggan --</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">
                                {{ c.name }} {{ c.phone ? '(' + c.phone + ')' : '' }}
                            </option>
                        </select>
                        <p v-if="form.errors.client_id" class="mt-1 text-xs text-rose-600">{{ form.errors.client_id }}</p>
                    </div>

                    <!-- Subjek Tiket -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Subjek / Topik Keluhan *</label>
                        <input
                            v-model="form.subject"
                            type="text"
                            required
                            placeholder="Contoh: Kendala akses faktur atau revisi item pesanan..."
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.subject" class="mt-1 text-xs text-rose-600">{{ form.errors.subject }}</p>
                    </div>

                    <!-- Prioritas & Status -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Prioritas *</label>
                            <select
                                v-model="form.priority"
                                required
                                class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="p in priorities" :key="p" :value="p">{{ p }}</option>
                            </select>
                            <p v-if="form.errors.priority" class="mt-1 text-xs text-rose-600">{{ form.errors.priority }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Status *</label>
                            <select
                                v-model="form.status"
                                required
                                class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-xs text-rose-600">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <!-- Target SLA (Due Date) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Target SLA Penyelesaian (Tenggat) *</label>
                        <input
                            v-model="form.due_date"
                            type="datetime-local"
                            required
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p class="mt-1 text-[11px] text-slate-400">Batas waktu SLA sebelum sistem menandai tiket sebagai "Overdue (Terlambat)".</p>
                        <p v-if="form.errors.due_date" class="mt-1 text-xs text-rose-600">{{ form.errors.due_date }}</p>
                    </div>

                    <!-- Deskripsi Keluhan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Rincian Deskripsi Keluhan *</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            required
                            placeholder="Jelaskan secara rinci kronologi masalah atau permintaan perbaikan dari pelanggan..."
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition active:scale-[0.98]"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 active:bg-indigo-800 active:scale-[0.98] transition disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>{{ editingTicket ? 'Simpan Perubahan' : 'Terbitkan Tiket' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Survei Kepuasan (CSAT) -->
        <div
            v-if="surveyTicket"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="closeSurvey"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">Survei Kepuasan Pelanggan</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Tiket <span class="font-mono font-bold">{{ surveyTicket.ticket_number }}</span> — seberapa puas pelanggan dengan penanganannya?
                </p>

                <div class="mt-5 flex justify-center gap-2" role="radiogroup" aria-label="Rating 1 sampai 5 bintang">
                    <button
                        v-for="n in 5"
                        :key="n"
                        type="button"
                        role="radio"
                        :aria-checked="surveyForm.rating === n"
                        :aria-label="`${n} bintang`"
                        @click="surveyForm.rating = n"
                        class="text-4xl leading-none transition hover:scale-110 active:scale-95"
                        :class="n <= surveyForm.rating ? 'text-amber-400' : 'text-slate-300 hover:text-amber-200'"
                    >★</button>
                </div>
                <p v-if="surveyForm.errors.rating" class="mt-2 text-center text-xs text-rose-600">{{ surveyForm.errors.rating }}</p>

                <div class="mt-5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Ulasan singkat (opsional)</label>
                    <textarea
                        v-model="surveyForm.feedback"
                        rows="3"
                        maxlength="1000"
                        class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                    ></textarea>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="closeSurvey"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 active:scale-95 transition"
                    >Batal</button>
                    <button
                        type="button"
                        @click="submitSurvey"
                        :disabled="surveyForm.processing || surveyForm.rating === 0"
                        class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 active:scale-95 transition disabled:cursor-not-allowed disabled:opacity-60"
                    >{{ surveyForm.processing ? 'Menyimpan…' : 'Kirim Rating' }}</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
