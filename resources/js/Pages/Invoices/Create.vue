<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    clients: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
    businessProfile: {
        type: Object,
        default: null,
    },
    nextInvoiceNumber: {
        type: String,
        default: 'INV-202611-001',
    },
});

// Helper format tanggal YYYY-MM-DD
const formatDate = (date) => {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const today = new Date();
const defaultDue = new Date();
defaultDue.setDate(today.getDate() + 14);

const form = useForm({
    client_id: props.clients.length > 0 ? props.clients[0].id : '',
    invoice_number: props.nextInvoiceNumber,
    issue_date: formatDate(today),
    due_date: formatDate(defaultDue),
    status: 'unpaid',
    notes: 'Terima kasih atas kerja samanya. Pembayaran mohon ditransfer sesuai rekening yang tertera.',
    discount_type: 'none', // 'none' | 'percentage' | 'fixed'
    discount_value: 0,
    tax_rate: 11, // default PPN 11%
    items: [
        {
            product_id: '',
            description: '',
            quantity: 1,
            unit_price: 0,
        },
    ],
});

// Quick termin setter
const setDueTerm = (days) => {
    const base = new Date(form.issue_date || new Date());
    base.setDate(base.getDate() + days);
    form.due_date = formatDate(base);
};

// Item handlers
const addItem = () => {
    form.items.push({
        product_id: '',
        description: '',
        quantity: 1,
        unit_price: 0,
    });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const onProductSelect = (index) => {
    const item = form.items[index];
    if (!item.product_id) return;
    const prod = props.products.find((p) => p.id === Number(item.product_id));
    if (prod) {
        item.description = prod.name + (prod.unit ? ` (${prod.unit})` : '');
        item.unit_price = Number(prod.price) || 0;
    }
};

// Reactive calculations
const lineTotals = computed(() => {
    return form.items.map((item) => {
        const q = Number(item.quantity) || 0;
        const p = Number(item.unit_price) || 0;
        return Math.max(0, q * p);
    });
});

const subtotal = computed(() => {
    return lineTotals.value.reduce((acc, curr) => acc + curr, 0);
});

const discountAmount = computed(() => {
    const val = Number(form.discount_value) || 0;
    if (form.discount_type === 'percentage') {
        return Math.round(((subtotal.value * Math.min(100, Math.max(0, val))) / 100) * 100) / 100;
    }
    if (form.discount_type === 'fixed') {
        return Math.min(subtotal.value, Math.max(0, val));
    }
    return 0;
});

const taxableAmount = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value);
});

const taxAmount = computed(() => {
    const rate = Number(form.tax_rate) || 0;
    if (rate <= 0) return 0;
    return Math.round(((taxableAmount.value * rate) / 100) * 100) / 100;
});

const grandTotal = computed(() => {
    return Math.round((taxableAmount.value + taxAmount.value) * 100) / 100;
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

const submit = () => {
    // Map payload
    const payload = {
        ...form.data(),
        discount_type: form.discount_type === 'none' ? null : form.discount_type,
        discount_value: form.discount_type === 'none' ? 0 : form.discount_value,
        tax_rate: Number(form.tax_rate) || 0,
    };

    form.transform(() => payload).post(route('invoices.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Buat Faktur Baru" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                        <Link :href="route('invoices.index')" class="hover:text-indigo-600">Faktur</Link>
                        <span>/</span>
                        <span class="text-slate-800">Buat Faktur Baru</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900">Buat Faktur Baru</h1>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('invoices.index')"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                    >
                        Batal
                    </Link>
                    <PrimaryButton
                        @click="submit"
                        :disabled="form.processing"
                        class="gap-1.5"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Faktur' }}</span>
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Warning jika klien belum ada -->
                <div
                    v-if="props.clients.length === 0"
                    class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-sm font-medium">
                                Anda belum memiliki data Pelanggan. Tambahkan pelanggan terlebih dahulu untuk membuat faktur.
                            </p>
                        </div>
                        <Link
                            :href="route('clients.index')"
                            class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700"
                        >
                            + Tambah Pelanggan
                        </Link>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Section 1: Informasi Utama Faktur -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-base font-semibold text-slate-900">1. Informasi Faktur</h2>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                            <!-- Nomor Faktur (Auto-Numbering) -->
                            <div>
                                <InputLabel for="invoice_number" value="Nomor Faktur (Otomatis)" />
                                <div class="mt-1 relative">
                                    <TextInput
                                        id="invoice_number"
                                        v-model="form.invoice_number"
                                        type="text"
                                        class="block w-full font-mono font-semibold text-indigo-700 bg-slate-50"
                                        required
                                    />
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Auto-numbering INV-YYYYMM-XXX</p>
                                <InputError class="mt-1" :message="form.errors.invoice_number" />
                            </div>

                            <!-- Pilih Klien -->
                            <div class="md:col-span-1">
                                <div class="flex items-center justify-between">
                                    <InputLabel for="client_id" value="Pilih Pelanggan / Klien *" />
                                    <Link :href="route('clients.index')" class="text-xs font-semibold text-indigo-600 hover:underline">
                                        + Baru
                                    </Link>
                                </div>
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    class="mt-1 block w-full rounded-md border-slate-300 py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                    required
                                >
                                    <option value="" disabled>-- Pilih Pelanggan --</option>
                                    <option v-for="client in props.clients" :key="client.id" :value="client.id">
                                        {{ client.name }} {{ client.phone ? `(${client.phone})` : '' }}
                                    </option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.client_id" />
                            </div>

                            <!-- Tanggal Terbit -->
                            <div>
                                <InputLabel for="issue_date" value="Tanggal Faktur *" />
                                <input
                                    id="issue_date"
                                    v-model="form.issue_date"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <InputError class="mt-1" :message="form.errors.issue_date" />
                            </div>

                            <!-- Jatuh Tempo & Shortcut Termin -->
                            <div>
                                <div class="flex items-center justify-between">
                                    <InputLabel for="due_date" value="Jatuh Tempo *" />
                                    <div class="flex gap-1 text-[11px]">
                                        <button type="button" @click="setDueTerm(0)" class="text-slate-500 hover:text-indigo-600">Hari ini</button>
                                        <span>&bull;</span>
                                        <button type="button" @click="setDueTerm(7)" class="text-slate-500 hover:text-indigo-600">+7h</button>
                                        <span>&bull;</span>
                                        <button type="button" @click="setDueTerm(14)" class="text-indigo-600 font-semibold hover:underline">+14h</button>
                                        <span>&bull;</span>
                                        <button type="button" @click="setDueTerm(30)" class="text-slate-500 hover:text-indigo-600">+30h</button>
                                    </div>
                                </div>
                                <input
                                    id="due_date"
                                    v-model="form.due_date"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-slate-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <InputError class="mt-1" :message="form.errors.due_date" />
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-4">
                            <!-- Status Faktur -->
                            <div>
                                <InputLabel for="status" value="Status Awal *" />
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-md border-slate-300 py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="unpaid">Belum Dibayar (Unpaid)</option>
                                    <option value="draft">Draf (Draft)</option>
                                    <option value="paid">Lunas (Paid)</option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.status" />
                            </div>

                            <!-- Catatan Faktur -->
                            <div class="md:col-span-3">
                                <InputLabel for="notes" value="Catatan / Instruksi Pembayaran (Opsional)" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="2"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Nomor rekening, syarat ketentuan, atau terima kasih..."
                                ></textarea>
                                <InputError class="mt-1" :message="form.errors.notes" />
                            </div>
                        </div>

                        <!-- Info / Peringatan Rekening Transfer PDF -->
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <div
                                v-if="props.businessProfile?.bank_name || props.businessProfile?.bank_account_number"
                                class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 p-3 text-xs text-emerald-800"
                            >
                                <span class="text-base leading-none">💳</span>
                                <div class="flex-1">
                                    <span class="font-semibold text-emerald-900">Rekening Transfer Resmi (Tercetak di PDF):</span>
                                    <div class="mt-0.5 text-emerald-800">
                                        <strong>{{ props.businessProfile.bank_name || 'Bank' }}</strong> — No. Rekening: <strong class="font-mono text-emerald-950">{{ props.businessProfile.bank_account_number || '-' }}</strong> a.n <strong>{{ props.businessProfile.bank_account_name || '-' }}</strong>
                                    </div>
                                </div>
                                <Link :href="route('profile.business.edit')" class="shrink-0 text-[11px] font-semibold text-emerald-700 underline hover:text-emerald-900">
                                    Ubah di Profil Usaha
                                </Link>
                            </div>

                            <div
                                v-else
                                class="flex flex-col gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-base">⚠️</span>
                                    <div>
                                        <strong>Nomor Rekening Bank Belum Diisi di Profil Usaha!</strong>
                                        <p class="text-amber-800 mt-0.5">
                                            Di PDF faktur, bagian nomor rekening akan kosong sehingga customer bingung harus transfer ke mana.
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    :href="route('profile.business.edit')"
                                    class="inline-flex shrink-0 items-center justify-center rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-700"
                                >
                                    Isi Rekening Bank Sekarang &rarr;
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Tabel Line Items Dinamis -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">2. Rincian Barang / Jasa (Line Items)</h2>
                                <p class="text-xs text-slate-500">Pilih dari katalog produk atau ketik manual deskripsi dan harga.</p>
                            </div>
                            <button
                                type="button"
                                @click="addItem"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-100"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Baris
                            </button>
                        </div>

                        <!-- Desktop Table View -->
                        <div class="hidden overflow-x-auto md:block">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                        <th class="py-3 pl-3 pr-2 w-1/4">Katalog (Opsional)</th>
                                        <th class="py-3 px-2 w-1/3">Deskripsi Item / Layanan *</th>
                                        <th class="py-3 px-2 w-24 text-right">Kuantitas *</th>
                                        <th class="py-3 px-2 w-36 text-right">Harga Satuan (Rp) *</th>
                                        <th class="py-3 px-2 w-36 text-right">Subtotal</th>
                                        <th class="py-3 pr-3 pl-2 w-12 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-slate-50/60">
                                        <!-- Pilih Produk dari Master -->
                                        <td class="py-2.5 pl-3 pr-2">
                                            <select
                                                v-model="item.product_id"
                                                @change="onProductSelect(index)"
                                                class="w-full rounded-md border-slate-300 py-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">-- Pilih Produk/Jasa --</option>
                                                <option v-for="prod in props.products" :key="prod.id" :value="prod.id">
                                                    {{ prod.name }} ({{ formatCurrency(prod.price) }})
                                                </option>
                                            </select>
                                        </td>

                                        <!-- Deskripsi -->
                                        <td class="py-2.5 px-2">
                                            <input
                                                v-model="item.description"
                                                type="text"
                                                class="w-full rounded-md border-slate-300 py-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder="Nama barang atau rincian pekerjaan"
                                                required
                                            />
                                        </td>

                                        <!-- Kuantitas -->
                                        <td class="py-2.5 px-2 text-right">
                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                step="any"
                                                min="0.01"
                                                class="w-full rounded-md border-slate-300 py-1.5 text-right text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                                required
                                            />
                                        </td>

                                        <!-- Harga Satuan -->
                                        <td class="py-2.5 px-2 text-right">
                                            <input
                                                v-model.number="item.unit_price"
                                                type="number"
                                                step="any"
                                                min="0"
                                                class="w-full rounded-md border-slate-300 py-1.5 text-right text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                                required
                                            />
                                        </td>

                                        <!-- Subtotal Baris -->
                                        <td class="py-2.5 px-2 text-right font-medium text-slate-800 text-xs">
                                            {{ formatCurrency(lineTotals[index]) }}
                                        </td>

                                        <!-- Hapus Baris -->
                                        <td class="py-2.5 pr-3 pl-2 text-center">
                                            <button
                                                type="button"
                                                @click="removeItem(index)"
                                                :disabled="form.items.length === 1"
                                                title="Hapus baris"
                                                class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30 disabled:hover:bg-transparent"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card List for Line Items -->
                        <div class="space-y-4 md:hidden">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 space-y-3"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                        Item #{{ index + 1 }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="removeItem(index)"
                                        :disabled="form.items.length === 1"
                                        class="text-xs font-semibold text-rose-600 disabled:opacity-30"
                                    >
                                        Hapus
                                    </button>
                                </div>

                                <div>
                                    <label class="text-[11px] font-medium text-slate-600">Pilih dari Katalog (Opsional)</label>
                                    <select
                                        v-model="item.product_id"
                                        @change="onProductSelect(index)"
                                        class="mt-0.5 w-full rounded-md border-slate-300 py-1.5 text-xs"
                                    >
                                        <option value="">-- Pilih Produk/Jasa --</option>
                                        <option v-for="prod in props.products" :key="prod.id" :value="prod.id">
                                            {{ prod.name }} ({{ formatCurrency(prod.price) }})
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-[11px] font-medium text-slate-600">Deskripsi *</label>
                                    <input
                                        v-model="item.description"
                                        type="text"
                                        class="mt-0.5 w-full rounded-md border-slate-300 py-1.5 text-xs"
                                        placeholder="Nama barang / jasa"
                                        required
                                    />
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[11px] font-medium text-slate-600">Qty *</label>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            step="any"
                                            min="0.01"
                                            class="mt-0.5 w-full rounded-md border-slate-300 py-1.5 text-xs text-right"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-medium text-slate-600">Harga Satuan *</label>
                                        <input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="any"
                                            min="0"
                                            class="mt-0.5 w-full rounded-md border-slate-300 py-1.5 text-xs text-right"
                                            required
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between border-t border-slate-200 pt-2 text-xs">
                                    <span class="font-medium text-slate-600">Subtotal Baris:</span>
                                    <span class="font-bold text-slate-900">{{ formatCurrency(lineTotals[index]) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button
                                type="button"
                                @click="addItem"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                            >
                                + Tambah Baris Baru
                            </button>
                        </div>
                    </div>

                    <!-- Section 3: Tax & Discount Engine + Summary Ringkasan -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <!-- Diskon & Pajak Config -->
                        <div class="space-y-6 lg:col-span-7">
                            <!-- Diskon Engine -->
                            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                                <h3 class="text-sm font-semibold text-slate-900">Pengaturan Diskon</h3>
                                <p class="text-xs text-slate-500">Berikan potongan harga flat atau persentase.</p>

                                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="discount_type" value="Tipe Diskon" />
                                        <select
                                            id="discount_type"
                                            v-model="form.discount_type"
                                            class="mt-1 block w-full rounded-md border-slate-300 py-2 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="none">Tanpa Diskon</option>
                                            <option value="percentage">Persentase (%)</option>
                                            <option value="fixed">Nominal Tetap (Rp)</option>
                                        </select>
                                    </div>

                                    <div v-if="form.discount_type !== 'none'">
                                        <InputLabel
                                            for="discount_value"
                                            :value="form.discount_type === 'percentage' ? 'Besar Persen (%)' : 'Nominal Diskon (Rp)'"
                                        />
                                        <input
                                            id="discount_value"
                                            v-model.number="form.discount_value"
                                            type="number"
                                            min="0"
                                            :max="form.discount_type === 'percentage' ? 100 : undefined"
                                            class="mt-1 block w-full rounded-md border-slate-300 py-1.5 text-xs text-right focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Pajak Engine (PPN) -->
                            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                                <h3 class="text-sm font-semibold text-slate-900">Pajak Pertambahan Nilai (PPN)</h3>
                                <p class="text-xs text-slate-500">Pilih tarif PPN sesuai regulasi atau tentukan tarif khusus.</p>

                                <div class="mt-4 flex flex-wrap gap-3">
                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium cursor-pointer hover:bg-slate-50" :class="{ 'border-indigo-600 bg-indigo-50 text-indigo-700': form.tax_rate === 0 }">
                                        <input type="radio" :value="0" v-model.number="form.tax_rate" class="text-indigo-600" />
                                        <span>Tanpa PPN (0%)</span>
                                    </label>
                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium cursor-pointer hover:bg-slate-50" :class="{ 'border-indigo-600 bg-indigo-50 text-indigo-700': form.tax_rate === 11 }">
                                        <input type="radio" :value="11" v-model.number="form.tax_rate" class="text-indigo-600" />
                                        <span>PPN 11%</span>
                                    </label>
                                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium cursor-pointer hover:bg-slate-50" :class="{ 'border-indigo-600 bg-indigo-50 text-indigo-700': form.tax_rate === 12 }">
                                        <input type="radio" :value="12" v-model.number="form.tax_rate" class="text-indigo-600" />
                                        <span>PPN 12%</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Grand Total Summary Card -->
                        <div class="lg:col-span-5">
                            <div class="sticky top-6 rounded-xl border border-indigo-200 bg-gradient-to-b from-indigo-50/70 to-white p-6 shadow-sm space-y-4">
                                <h3 class="text-base font-bold text-slate-900">Ringkasan Kalkulasi</h3>

                                <div class="space-y-2.5 text-sm">
                                    <div class="flex items-center justify-between text-slate-600">
                                        <span>Subtotal Baris:</span>
                                        <span class="font-semibold text-slate-800">{{ formatCurrency(subtotal) }}</span>
                                    </div>

                                    <div v-if="discountAmount > 0" class="flex items-center justify-between text-emerald-600">
                                        <span>
                                            Potongan Diskon
                                            <span v-if="form.discount_type === 'percentage'">({{ form.discount_value }}%)</span>:
                                        </span>
                                        <span class="font-semibold">- {{ formatCurrency(discountAmount) }}</span>
                                    </div>

                                    <div class="flex items-center justify-between text-slate-600 border-t border-dashed border-slate-200 pt-2">
                                        <span>Dasar Pengenaan Pajak (DPP):</span>
                                        <span class="font-medium text-slate-700">{{ formatCurrency(taxableAmount) }}</span>
                                    </div>

                                    <div class="flex items-center justify-between text-slate-600">
                                        <span>PPN ({{ form.tax_rate }}%):</span>
                                        <span class="font-semibold text-slate-800">+ {{ formatCurrency(taxAmount) }}</span>
                                    </div>

                                    <div class="border-t-2 border-indigo-200 pt-3">
                                        <div class="flex items-baseline justify-between">
                                            <div>
                                                <span class="text-xs uppercase tracking-wider font-bold text-slate-500">Total Tagihan</span>
                                                <div class="text-xs text-slate-400">Grand Total</div>
                                            </div>
                                            <span class="text-2xl font-black text-indigo-700">{{ formatCurrency(grandTotal) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-slate-100">
                                    <PrimaryButton
                                        type="button"
                                        @click="submit"
                                        :disabled="form.processing || props.clients.length === 0"
                                        class="w-full justify-center py-3 text-base shadow-md inline-flex items-center gap-2 hover:bg-slate-700 active:bg-slate-900 active:scale-[0.99] transition disabled:opacity-50"
                                    >
                                        <svg v-if="form.processing" class="h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>{{ form.processing ? 'Menyimpan Faktur...' : 'Buat & Simpan Faktur' }}</span>
                                    </PrimaryButton>
                                    <p class="mt-2 text-center text-xs text-slate-400">
                                        Faktur akan tersimpan dan siap dicetak / dikirim ke klien.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
