<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    stages: Array,
    dealsByStage: Object,
    metrics: Object,
    clients: Array,
});

// Local reactive state for optimistic updates
const localDeals = ref({ ...props.dealsByStage });

watch(
    () => props.dealsByStage,
    (newVal) => {
        localDeals.value = { ...newVal };
    },
    { deep: true }
);

// Drag and drop state
const draggedDeal = ref(null);
const draggedSourceStage = ref(null);
const dragOverStage = ref(null);

function handleDragStart(deal, stage, event) {
    draggedDeal.value = deal;
    draggedSourceStage.value = stage;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', deal.id.toString());
}

function handleDragEnd() {
    draggedDeal.value = null;
    draggedSourceStage.value = null;
    dragOverStage.value = null;
}

function handleDragOver(stage, event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    dragOverStage.value = stage;
}

function handleDragLeave(stage, event) {
    if (dragOverStage.value === stage) {
        dragOverStage.value = null;
    }
}

function handleDrop(targetStage, event) {
    event.preventDefault();
    const deal = draggedDeal.value;
    const fromStage = draggedSourceStage.value;
    dragOverStage.value = null;

    if (!deal || fromStage === targetStage) {
        return;
    }

    // Optimistic UI move
    localDeals.value[fromStage] = (localDeals.value[fromStage] || []).filter((d) => d.id !== deal.id);
    deal.stage = targetStage;
    if (!localDeals.value[targetStage]) {
        localDeals.value[targetStage] = [];
    }
    localDeals.value[targetStage].unshift(deal);

    router.patch(
        route('crm.deals.update-stage', deal.id),
        { stage: targetStage },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                localDeals.value = { ...props.dealsByStage };
            },
        }
    );

    draggedDeal.value = null;
    draggedSourceStage.value = null;
}

// Mobile/Button move fallback
function moveStage(deal, targetStage) {
    if (deal.stage === targetStage) return;
    const fromStage = deal.stage;

    localDeals.value[fromStage] = (localDeals.value[fromStage] || []).filter((d) => d.id !== deal.id);
    deal.stage = targetStage;
    if (!localDeals.value[targetStage]) {
        localDeals.value[targetStage] = [];
    }
    localDeals.value[targetStage].unshift(deal);

    router.patch(
        route('crm.deals.update-stage', deal.id),
        { stage: targetStage },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                localDeals.value = { ...props.dealsByStage };
            },
        }
    );
}

// Modal Form Create/Edit Deal
const showModal = ref(false);
const editingDeal = ref(null);

const form = useForm({
    client_id: '',
    title: '',
    amount: '',
    stage: 'Prospek',
    expected_close_date: '',
});

function openCreateModal(defaultStage = 'Prospek') {
    editingDeal.value = null;
    form.reset();
    form.clearErrors();
    form.stage = defaultStage;
    if (props.clients.length > 0) {
        form.client_id = props.clients[0].id;
    }
    showModal.value = true;
}

function openEditModal(deal) {
    editingDeal.value = deal;
    form.clearErrors();
    form.client_id = deal.client_id;
    form.title = deal.title;
    form.amount = deal.amount;
    form.stage = deal.stage;
    form.expected_close_date = deal.expected_close_date ? deal.expected_close_date.split('T')[0] : '';
    showModal.value = true;
}

function saveDeal() {
    if (editingDeal.value) {
        form.put(route('crm.deals.update', editingDeal.value.id), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('crm.deals.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
}

function removeDeal(deal) {
    if (confirm(`Apakah Anda yakin ingin menghapus peluang "${deal.title}"?`)) {
        router.delete(route('crm.deals.destroy', deal.id), {
            preserveScroll: true,
        });
    }
}

// Helper format Rupiah
const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

// Stage visual configurations
const stageConfigs = {
    'Prospek': {
        border: 'border-sky-300',
        bg: 'bg-sky-50',
        badge: 'bg-sky-100 text-sky-800 border-sky-200',
        headerText: 'text-sky-900',
    },
    'Proposal': {
        border: 'border-indigo-300',
        bg: 'bg-indigo-50',
        badge: 'bg-indigo-100 text-indigo-800 border-indigo-200',
        headerText: 'text-indigo-900',
    },
    'Negosiasi': {
        border: 'border-amber-300',
        bg: 'bg-amber-50',
        badge: 'bg-amber-100 text-amber-800 border-amber-200',
        headerText: 'text-amber-900',
    },
    'Deal Menang': {
        border: 'border-emerald-300',
        bg: 'bg-emerald-50',
        badge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        headerText: 'text-emerald-900',
    },
    'Deal Batal': {
        border: 'border-rose-300',
        bg: 'bg-rose-50',
        badge: 'bg-rose-100 text-rose-800 border-rose-200',
        headerText: 'text-rose-900',
    },
};

const stageTotalAmount = (stage) => {
    const list = localDeals.value[stage] || [];
    return list.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
};
</script>

<template>
    <Head title="Pipa Penjualan CRM" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pipa Penjualan (CRM Solo)</h1>
                    <p class="text-sm text-slate-500">Kelola prospek closing, tahapan negosiasi, dan visualisasi kanban pipa transaksi.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:bg-indigo-800 active:scale-[0.98]"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ Tambah Peluang (Deal)</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Summary Metrics Bar -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs font-medium text-slate-500">Total Peluang</div>
                        <div class="mt-1 text-2xl font-bold text-slate-900">{{ metrics.total_deals }}</div>
                        <div class="mt-1 text-xs text-slate-400">Di semua tahapan pipeline</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs font-medium text-slate-500">Total Nilai Pipa</div>
                        <div class="mt-1 text-2xl font-bold text-indigo-600">{{ formatRupiah(metrics.total_value) }}</div>
                        <div class="mt-1 text-xs text-slate-400">Kumulatif seluruh deal</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs font-medium text-slate-500">Pipa Aktif (In Progress)</div>
                        <div class="mt-1 text-2xl font-bold text-amber-600">{{ formatRupiah(metrics.active_value) }}</div>
                        <div class="mt-1 text-xs text-slate-400">Prospek, Proposal, Negosiasi</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs font-medium text-slate-500">Deal Menang (Won)</div>
                        <div class="mt-1 text-2xl font-bold text-emerald-600">{{ formatRupiah(metrics.won_value) }}</div>
                        <div class="mt-1 text-xs text-slate-400">Potensi konversi faktur siap tagih</div>
                    </div>
                </div>

                <!-- Guidance Info Box -->
                <div class="rounded-lg border border-indigo-100 bg-indigo-50/60 p-3.5 text-xs text-indigo-800 sm:text-sm flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-base">💡</span>
                        <span>
                            <strong>Tips Kanban:</strong> Tarik (drag & drop) kartu peluang dari satu kolom ke kolom berikutnya untuk mengubah tahapan prospek. Klik nama klien untuk melihat buku riwayat transaksi.
                        </span>
                    </div>
                    <Link :href="route('clients.index')" class="hidden font-bold underline hover:text-indigo-950 sm:inline">
                        Buku Kontak Pelanggan &rarr;
                    </Link>
                </div>

                <!-- Kanban Board (5 Columns) -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 lg:grid-cols-5 items-start overflow-x-auto pb-6">
                    <div
                        v-for="stage in stages"
                        :key="stage"
                        @dragover="handleDragOver(stage, $event)"
                        @dragleave="handleDragLeave(stage, $event)"
                        @drop="handleDrop(stage, $event)"
                        class="flex min-w-[260px] flex-col rounded-2xl border transition duration-150"
                        :class="[
                            stageConfigs[stage]?.bg || 'bg-slate-50',
                            dragOverStage === stage ? 'ring-2 ring-indigo-500 ring-offset-2 border-indigo-400 bg-indigo-50/70' : (stageConfigs[stage]?.border || 'border-slate-200'),
                        ]"
                    >
                        <!-- Column Header -->
                        <div class="p-3.5 border-b border-black/5 flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm tracking-tight" :class="stageConfigs[stage]?.headerText || 'text-slate-900'">
                                        {{ stage }}
                                    </span>
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-[11px] font-bold"
                                        :class="stageConfigs[stage]?.badge || 'bg-white text-slate-700 border-slate-200'"
                                    >
                                        {{ (localDeals[stage] || []).length }}
                                    </span>
                                </div>
                                <div class="mt-0.5 text-[11px] font-medium text-slate-500">
                                    {{ formatRupiah(stageTotalAmount(stage)) }}
                                </div>
                            </div>
                            <button
                                @click="openCreateModal(stage)"
                                class="rounded p-1 text-slate-500 hover:bg-black/5 hover:text-slate-800 transition"
                                title="Tambah deal di kolom ini"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        <!-- Cards Container -->
                        <div class="flex flex-col gap-3 p-3 min-h-[380px]">
                            <!-- Draggable Deal Card -->
                            <div
                                v-for="deal in (localDeals[stage] || [])"
                                :key="deal.id"
                                draggable="true"
                                @dragstart="handleDragStart(deal, stage, $event)"
                                @dragend="handleDragEnd"
                                class="group relative cursor-grab rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:border-indigo-300 hover:shadow-md active:cursor-grabbing active:scale-[0.99]"
                            >
                                <!-- Title & Quick Menu -->
                                <div class="flex items-start justify-between gap-2">
                                    <h2 class="text-sm font-semibold text-slate-900 leading-snug group-hover:text-indigo-600 transition">
                                        {{ deal.title }}
                                    </h2>
                                    <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100">
                                        <button
                                            @click.stop="openEditModal(deal)"
                                            class="rounded p-1 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 transition"
                                            title="Edit"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click.stop="removeDeal(deal)"
                                            class="rounded p-1 text-slate-400 hover:text-rose-600 hover:bg-slate-100 transition"
                                            title="Hapus"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Client Name Link -->
                                <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-600">
                                    <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <Link
                                        v-if="deal.client"
                                        :href="route('clients.show', deal.client.id)"
                                        class="truncate font-medium text-slate-700 hover:text-indigo-600 hover:underline"
                                        title="Buka profil dan riwayat transaksi klien"
                                    >
                                        {{ deal.client.name }}
                                    </Link>
                                    <span v-else class="text-slate-400">—</span>
                                    <span
                                        v-if="deal.client?.source"
                                        class="rounded bg-slate-100 px-1.5 py-0.2 text-[10px] text-slate-500"
                                    >
                                        {{ deal.client.source }}
                                    </span>
                                </div>

                                <!-- Amount (Nilai Rp) -->
                                <div class="mt-2.5 flex items-center justify-between border-t border-slate-100 pt-2">
                                    <div class="text-xs font-bold text-slate-900">
                                        {{ formatRupiah(deal.amount) }}
                                    </div>
                                    <div v-if="deal.expected_close_date" class="text-[11px] text-slate-400" title="Estimasi tanggal closing">
                                        📅 {{ new Date(deal.expected_close_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                                    </div>
                                </div>

                                <!-- Mobile / Touchscreen Stage Selector -->
                                <div class="mt-2 pt-1 flex items-center justify-between border-t border-slate-50 lg:hidden">
                                    <span class="text-[10px] text-slate-400">Pindah:</span>
                                    <select
                                        :value="deal.stage"
                                        @change="moveStage(deal, $event.target.value)"
                                        class="rounded border-slate-200 py-0.5 px-2 text-[11px] text-slate-600 focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option v-for="s in stages" :key="s" :value="s">{{ s }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Empty Column State -->
                            <div
                                v-if="!(localDeals[stage] || []).length"
                                class="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400"
                            >
                                <span>Tarik kartu prospek ke sini</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Deal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 sm:items-center sm:p-4"
            @click.self="showModal = false"
        >
            <section
                role="dialog"
                aria-modal="true"
                class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-lg sm:rounded-2xl sm:p-6"
            >
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900">
                        {{ editingDeal ? 'Edit Peluang (Deal)' : 'Tambah Peluang Baru' }}
                    </h2>
                    <button @click="showModal = false" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="saveDeal" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Klien / Pelanggan <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.client_id"
                            required
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="" disabled>-- Pilih Klien --</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">
                                {{ c.name }} {{ c.source ? `(${c.source})` : '' }}
                            </option>
                        </select>
                        <span v-if="form.errors.client_id" class="text-xs text-rose-600">{{ form.errors.client_id }}</span>
                        <div v-if="!clients.length" class="mt-1 text-xs text-amber-600">
                            Belum ada klien. <Link :href="route('clients.index')" class="underline font-semibold">Tambah klien terlebih dahulu</Link>.
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Judul Peluang / Prospek <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="Contoh: Pengadaan Software ERP Q4, Jasa Konsultasi..."
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span v-if="form.errors.title" class="text-xs text-rose-600">{{ form.errors.title }}</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                Nilai Deal (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.amount"
                                type="number"
                                min="0"
                                step="1000"
                                required
                                placeholder="0"
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <span v-if="form.errors.amount" class="text-xs text-rose-600">{{ form.errors.amount }}</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                Tahapan (Stage) <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.stage"
                                required
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="s in stages" :key="s" :value="s">{{ s }}</option>
                            </select>
                            <span v-if="form.errors.stage" class="text-xs text-rose-600">{{ form.errors.stage }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Perkiraan Closing (Opsional)
                        </label>
                        <input
                            v-model="form.expected_close_date"
                            type="date"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span v-if="form.errors.expected_close_date" class="text-xs text-rose-600">{{ form.errors.expected_close_date }}</span>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50 active:bg-slate-100"
                        >
                            Batal
                        </button>
                        <button
                            :disabled="form.processing"
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:bg-indigo-800 active:scale-[0.98] disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan…' : 'Simpan Peluang' }}</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
