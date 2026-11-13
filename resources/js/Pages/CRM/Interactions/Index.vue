<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    interactions: Object,
    clients: Array,
    filters: Object,
    metrics: Object,
    interactionTypes: Array,
    interactionStatuses: Array,
});

const isModalOpen = ref(false);
const editingInteraction = ref(null);
const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'Pending');
const selectedType = ref(props.filters.type || '');

const form = useForm({
    client_id: '',
    type: 'Telepon',
    scheduled_at: '',
    notes: '',
    status: 'Pending',
});

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

function isOverdue(item) {
    if (item.status === 'Selesai') return false;
    return new Date(item.scheduled_at) < new Date();
}

function isToday(item) {
    const d = new Date(item.scheduled_at);
    const now = new Date();
    return d.toDateString() === now.toDateString();
}

function openCreateModal() {
    editingInteraction.value = null;
    form.reset();
    form.clearErrors();
    form.client_id = props.clients.length > 0 ? props.clients[0].id : '';
    form.type = 'Telepon';
    form.status = 'Pending';
    // Default 1 hour from now formatted
    const inOneHour = new Date(Date.now() + 3600 * 1000);
    form.scheduled_at = formatDateTimeForInput(inOneHour);
    form.notes = '';
    isModalOpen.value = true;
}

function openEditModal(item) {
    editingInteraction.value = item;
    form.clearErrors();
    form.client_id = item.client_id;
    form.type = item.type;
    form.scheduled_at = formatDateTimeForInput(item.scheduled_at);
    form.notes = item.notes || '';
    form.status = item.status;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingInteraction.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (editingInteraction.value) {
        form.put(route('crm.interactions.update', editingInteraction.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('crm.interactions.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

function toggleStatus(item) {
    router.patch(
        route('crm.interactions.toggle', item.id),
        {},
        {
            preserveScroll: true,
        }
    );
}

function deleteInteraction(item) {
    if (confirm(`Hapus jadwal pengingat untuk "${item.client?.name || 'Pelanggan'}"?`)) {
        router.delete(route('crm.interactions.destroy', item.id), {
            preserveScroll: true,
        });
    }
}

function applyFilters() {
    router.get(
        route('crm.interactions.index'),
        {
            status: selectedStatus.value,
            type: selectedType.value || undefined,
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

function typeBadgeClass(type) {
    switch (type) {
        case 'Telepon':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'Email':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'Meeting':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        default:
            return 'bg-amber-50 text-amber-700 border-amber-200';
    }
}

function typeIcon(type) {
    switch (type) {
        case 'Telepon':
            return '📞';
        case 'Email':
            return '✉️';
        case 'Meeting':
            return '🤝';
        default:
            return '📋';
    }
}
</script>

<template>
    <Head title="Jadwal Pengingat Interaksi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Jadwal & Pengingat Interaksi
                    </h1>
                    <p class="text-sm text-slate-500">
                        Kelola agenda tindak lanjut (follow-up) telepon, email, dan rapat bersama pelanggan.
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
                        <span>+ Tambah Pengingat</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Top Metric Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Perlu Tindak Lanjut</p>
                    <div class="mt-2 flex items-baseline justify-between">
                        <p class="text-2xl font-black text-amber-600">{{ metrics.pending }}</p>
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">Pending</span>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Jadwal Hari Ini</p>
                    <div class="mt-2 flex items-baseline justify-between">
                        <p class="text-2xl font-black text-indigo-600">{{ metrics.today }}</p>
                        <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 border border-indigo-200">Hari ini</span>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Lewat Jadwal</p>
                    <div class="mt-2 flex items-baseline justify-between">
                        <p class="text-2xl font-black text-rose-600">{{ metrics.overdue }}</p>
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200">Overdue</span>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Telah Selesai</p>
                    <div class="mt-2 flex items-baseline justify-between">
                        <p class="text-2xl font-black text-emerald-600">{{ metrics.completed }}</p>
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">Selesai</span>
                    </div>
                </div>
            </div>

            <!-- Filter Bar & Search -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Status Filter Tabs -->
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-semibold">
                        <button
                            type="button"
                            @click="setStatusFilter('Pending')"
                            :class="[
                                'rounded-lg px-3 py-1.5 transition',
                                selectedStatus === 'Pending'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            Tertunda / Pending ({{ metrics.pending }})
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
                            Selesai ({{ metrics.completed }})
                        </button>
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
                    </div>

                    <!-- Type Filter & Search -->
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="selectedType"
                            @change="applyFilters"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Semua Jenis Interaksi</option>
                            <option v-for="t in interactionTypes" :key="t" :value="t">{{ t }}</option>
                        </select>

                        <div class="relative min-w-[220px]">
                            <input
                                v-model="search"
                                @keydown.enter="applyFilters"
                                type="text"
                                placeholder="Cari klien / catatan..."
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

            <!-- Interaktif List Table -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                <div v-if="interactions.data.length === 0" class="p-12 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">
                        📅
                    </div>
                    <h3 class="mt-3 text-base font-semibold text-slate-900">Belum Ada Pengingat</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ selectedStatus === 'Pending' ? 'Tidak ada jadwal pengingat yang tertunda.' : 'Tidak ditemukan pengingat dengan filter saat ini.' }}
                    </p>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition"
                    >
                        + Tambah Pengingat Sekarang
                    </button>
                </div>

                <div v-else class="divide-y divide-slate-100">
                    <div
                        v-for="item in interactions.data"
                        :key="item.id"
                        :class="[
                            'p-4 sm:p-5 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4',
                            item.status === 'Selesai'
                                ? 'bg-slate-50/60 opacity-80'
                                : isOverdue(item)
                                    ? 'bg-rose-50/40 hover:bg-rose-50/70 border-l-4 border-l-rose-500'
                                    : isToday(item)
                                        ? 'bg-indigo-50/30 hover:bg-indigo-50/60 border-l-4 border-l-indigo-500'
                                        : 'hover:bg-slate-50/80',
                        ]"
                    >
                        <!-- Kiri: Checkbox, Tipe, Detail Pelanggan & Catatan -->
                        <div class="flex items-start gap-3.5 flex-1 min-w-0">
                            <!-- Checkbox Tandai Selesai / Pending -->
                            <button
                                type="button"
                                @click="toggleStatus(item)"
                                :title="item.status === 'Pending' ? 'Klik untuk tandai selesai' : 'Klik untuk buka kembali'"
                                :class="[
                                    'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border transition active:scale-90',
                                    item.status === 'Selesai'
                                        ? 'border-emerald-500 bg-emerald-500 text-white'
                                        : 'border-slate-300 bg-white hover:border-indigo-500 text-transparent hover:text-slate-300',
                                ]"
                            >
                                <svg class="h-4 w-4 stroke-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>

                            <div class="space-y-1.5 min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-bold',
                                            typeBadgeClass(item.type),
                                        ]"
                                    >
                                        <span>{{ typeIcon(item.type) }}</span>
                                        <span>{{ item.type }}</span>
                                    </span>

                                    <Link
                                        :href="route('clients.show', item.client_id)"
                                        class="text-sm font-bold text-slate-900 hover:text-indigo-600 hover:underline truncate"
                                    >
                                        {{ item.client?.name || 'Pelanggan #' + item.client_id }}
                                    </Link>

                                    <span
                                        v-if="item.client?.phone"
                                        class="text-xs text-slate-500"
                                    >
                                        • {{ item.client.phone }}
                                    </span>

                                    <!-- Status Alert Badge -->
                                    <span
                                        v-if="isOverdue(item)"
                                        class="rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700"
                                    >
                                        ⚠️ Terlambat
                                    </span>
                                    <span
                                        v-else-if="isToday(item) && item.status === 'Pending'"
                                        class="rounded-md bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-700"
                                    >
                                        Hari Ini
                                    </span>
                                    <span
                                        v-if="item.status === 'Selesai'"
                                        class="rounded-md bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800"
                                    >
                                        ✓ Selesai
                                    </span>
                                </div>

                                <!-- Catatan / Notes -->
                                <p
                                    :class="[
                                        'text-xs sm:text-sm leading-relaxed whitespace-pre-wrap',
                                        item.status === 'Selesai' ? 'text-slate-500 line-through' : 'text-slate-700',
                                    ]"
                                >
                                    {{ item.notes || 'Tidak ada catatan khusus.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Kanan: Jadwal Waktu & Tombol Aksi -->
                        <div class="flex sm:flex-col items-end justify-between sm:justify-center gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                            <div class="text-left sm:text-right">
                                <p class="text-xs font-semibold text-slate-500">Waktu Dijadwalkan</p>
                                <p
                                    :class="[
                                        'text-xs sm:text-sm font-bold tracking-tight',
                                        isOverdue(item) ? 'text-rose-600' : 'text-slate-900',
                                    ]"
                                >
                                    {{ formatDisplayDate(item.scheduled_at) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="openEditModal(item)"
                                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 active:scale-95 transition"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    @click="deleteInteraction(item)"
                                    class="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 active:scale-95 transition"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination Links -->
                <div
                    v-if="interactions.links && interactions.links.length > 3"
                    class="border-t border-slate-200 bg-slate-50/50 px-4 py-3 flex items-center justify-between sm:px-6"
                >
                    <p class="text-xs text-slate-500">
                        Menampilkan <span class="font-bold">{{ interactions.from || 0 }}</span> sampai <span class="font-bold">{{ interactions.to || 0 }}</span> dari <span class="font-bold">{{ interactions.total }}</span> jadwal
                    </p>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, idx) in interactions.links"
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

        <!-- Modal Tambah / Edit Pengingat -->
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
                    <h3 class="text-lg font-bold text-slate-900">
                        {{ editingInteraction ? 'Edit Jadwal Pengingat' : 'Tambah Jadwal Pengingat' }}
                    </h3>
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

                    <!-- Jenis Interaksi & Status -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Jenis Interaksi *</label>
                            <select
                                v-model="form.type"
                                required
                                class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="t in interactionTypes" :key="t" :value="t">{{ t }}</option>
                            </select>
                            <p v-if="form.errors.type" class="mt-1 text-xs text-rose-600">{{ form.errors.type }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Status *</label>
                            <select
                                v-model="form.status"
                                required
                                class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="s in interactionStatuses" :key="s" :value="s">{{ s }}</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-xs text-rose-600">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <!-- Waktu Dijadwalkan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Waktu Dijadwalkan *</label>
                        <input
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            required
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.scheduled_at" class="mt-1 text-xs text-rose-600">{{ form.errors.scheduled_at }}</p>
                    </div>

                    <!-- Catatan Agenda -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Catatan Agenda / Topik</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            placeholder="Contoh: Konfirmasi penawaran proposal dan tindak lanjut pembayaran faktur..."
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-rose-600">{{ form.errors.notes }}</p>
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
                            <span>{{ editingInteraction ? 'Simpan Perubahan' : 'Simpan Pengingat' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
