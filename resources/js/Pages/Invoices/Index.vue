<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    invoices: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '' }),
    },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

let searchDebounceTimeout = null;
const handleFilterChange = () => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        router.get(
            route('invoices.index'),
            { search: search.value, status: status.value },
            { preserveState: true, replace: true }
        );
    }, 300);
};

watch(search, handleFilterChange);
watch(status, handleFilterChange);

// Format currency
const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

// Format date
const formatDate = (val) => {
    if (!val) return '-';
    const d = new Date(val);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Delete confirmation modal
const confirmingDeletion = ref(false);
const invoiceToDelete = ref(null);
const isDeleting = ref(false);

const markAsPaid = (inv) => {
    if (confirm(`Tandai faktur ${inv.invoice_number} sebagai Lunas?`)) {
        router.patch(route('invoices.update-status', inv.id), {
            status: 'paid',
        }, {
            preserveScroll: true,
        });
    }
};

const confirmDelete = (inv) => {
    invoiceToDelete.value = inv;
    confirmingDeletion.value = true;
};

const closeModal = () => {
    confirmingDeletion.value = false;
    invoiceToDelete.value = null;
};

const deleteInvoice = () => {
    if (!invoiceToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('invoices.destroy', invoiceToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => {
            isDeleting.value = false;
        },
    });
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
    <Head title="Manajemen Faktur" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Faktur Tagihan</h1>
                    <p class="text-sm text-slate-500">Kelola pembuatan invoice, termin jatuh tempo, dan status pembayaran.</p>
                </div>
                <div>
                    <Link
                        :href="route('invoices.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:bg-indigo-800 active:scale-[0.98]"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Buat Faktur Baru
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Filter Bar -->
                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative flex-1">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nomor faktur atau nama klien..."
                            class="w-full rounded-lg border-slate-300 pl-9 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <div class="flex items-center gap-2">
                        <select
                            v-model="status"
                            class="rounded-lg border-slate-300 py-2 pl-3 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="unpaid">Belum Dibayar</option>
                            <option value="paid">Lunas</option>
                            <option value="draft">Draf</option>
                        </select>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="props.invoices.data.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 mb-3">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900">Belum ada faktur</h3>
                    <p class="mt-1 text-sm text-slate-500 max-w-sm">
                        Mulai terbitkan tagihan profesional untuk klien Anda dengan auto-numbering dan kalkulasi otomatis.
                    </p>
                    <div class="mt-5">
                        <Link
                            :href="route('invoices.create')"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                        >
                            + Buat Faktur Pertama
                        </Link>
                    </div>
                </div>

                <!-- Table View (Desktop) -->
                <div v-else class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                <th class="py-3.5 pl-6 pr-3">No. Faktur</th>
                                <th class="py-3.5 px-3">Pelanggan</th>
                                <th class="py-3.5 px-3">Tanggal Terbit</th>
                                <th class="py-3.5 px-3">Jatuh Tempo</th>
                                <th class="py-3.5 px-3 text-right">Total Tagihan</th>
                                <th class="py-3.5 px-3 text-center">Status</th>
                                <th class="py-3.5 pl-3 pr-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="inv in props.invoices.data" :key="inv.id" class="hover:bg-slate-50/60">
                                <td class="py-4 pl-6 pr-3 font-mono text-xs font-bold text-indigo-600">
                                    <Link :href="route('invoices.show', inv.id)" class="hover:underline">
                                        {{ inv.invoice_number }}
                                    </Link>
                                </td>
                                <td class="py-4 px-3">
                                    <div class="text-sm font-medium text-slate-900">{{ inv.client?.name || '-' }}</div>
                                    <div class="text-xs text-slate-400">{{ inv.client?.email || '' }}</div>
                                </td>
                                <td class="py-4 px-3 text-xs text-slate-600">
                                    {{ formatDate(inv.issue_date) }}
                                </td>
                                <td class="py-4 px-3 text-xs text-slate-600">
                                    {{ formatDate(inv.due_date) }}
                                </td>
                                <td class="py-4 px-3 text-right text-sm font-bold text-slate-900">
                                    {{ formatCurrency(inv.grand_total) }}
                                </td>
                                <td class="py-4 px-3 text-center">
                                    <span
                                        :class="statusBadge(inv.status).class"
                                        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                    >
                                        {{ statusBadge(inv.status).label }}
                                    </span>
                                </td>
                                <td class="py-4 pl-3 pr-6 text-right text-xs space-x-2 whitespace-nowrap">
                                    <button
                                        v-if="inv.status !== 'paid'"
                                        type="button"
                                        @click="markAsPaid(inv)"
                                        class="font-semibold text-emerald-600 hover:text-emerald-900"
                                        title="Tandai faktur ini lunas"
                                    >
                                        ✓ Lunas
                                    </button>
                                    <a
                                        :href="route('invoices.pdf', inv.id)"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 font-semibold text-sky-600 hover:text-sky-900"
                                        title="Cetak PDF Faktur"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        <span>PDF</span>
                                    </a>
                                    <Link
                                        :href="route('invoices.show', inv.id)"
                                        class="font-semibold text-indigo-600 hover:text-indigo-900"
                                    >
                                        Lihat
                                    </Link>
                                    <button
                                        type="button"
                                        @click="confirmDelete(inv)"
                                        class="font-semibold text-rose-600 hover:text-rose-900"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Card List View (Mobile) -->
                <div v-if="props.invoices.data.length > 0" class="space-y-3 md:hidden">
                    <div
                        v-for="inv in props.invoices.data"
                        :key="inv.id"
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-indigo-600">{{ inv.invoice_number }}</span>
                            <span
                                :class="statusBadge(inv.status).class"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                            >
                                {{ statusBadge(inv.status).label }}
                            </span>
                        </div>

                        <div>
                            <div class="text-sm font-bold text-slate-900">{{ inv.client?.name || '-' }}</div>
                            <div class="text-xs text-slate-500">Jatuh tempo: {{ formatDate(inv.due_date) }}</div>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-100 pt-2">
                            <div>
                                <span class="text-[11px] text-slate-500">Total:</span>
                                <div class="text-base font-bold text-slate-900">{{ formatCurrency(inv.grand_total) }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="inv.status !== 'paid'"
                                    type="button"
                                    @click="markAsPaid(inv)"
                                    class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                                    title="Tandai faktur ini lunas"
                                >
                                    ✓ Lunas
                                </button>
                                <a
                                    :href="route('invoices.pdf', inv.id)"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 rounded-lg bg-sky-50 px-2.5 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-100"
                                    title="Cetak PDF Faktur"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    <span>PDF</span>
                                </a>
                                <Link
                                    :href="route('invoices.show', inv.id)"
                                    class="rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                >
                                    Detail
                                </Link>
                                <button
                                    type="button"
                                    @click="confirmDelete(inv)"
                                    class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination Links -->
                <div v-if="props.invoices.links && props.invoices.links.length > 3" class="flex justify-center gap-1 pt-4">
                    <template v-for="(link, i) in props.invoices.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="rounded-md px-3 py-1.5 text-xs font-medium border"
                            :class="link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="rounded-md px-3 py-1.5 text-xs text-slate-400 border border-slate-200"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <Modal :show="confirmingDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Konfirmasi Hapus Faktur</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Apakah Anda yakin ingin menghapus faktur
                    <span class="font-semibold text-indigo-600 font-mono">{{ invoiceToDelete?.invoice_number }}</span>?
                    Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                    <DangerButton @click="deleteInvoice" :disabled="isDeleting">
                        {{ isDeleting ? 'Menghapus...' : 'Hapus Faktur' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
