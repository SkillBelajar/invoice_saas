<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ products: Object, filters: Object });
const showForm = ref(false);
const editing = ref(null);
const form = useForm({ name: '', description: '', price: '', unit: '' });

function openCreate() {
    editing.value = null; form.reset(); form.clearErrors(); showForm.value = true;
}
function openEdit(product) {
    editing.value = product; form.clearErrors();
    form.name = product.name; form.description = product.description ?? ''; form.price = product.price; form.unit = product.unit ?? '';
    showForm.value = true;
}
function save() {
    const options = { onSuccess: () => { showForm.value = false; form.reset(); } };
    editing.value ? form.put(route('products.update', editing.value.id), options) : form.post(route('products.store'), options);
}
function remove(product) {
    if (window.confirm(`Hapus produk “${product.name}”?`)) router.delete(route('products.destroy', product.id));
}
const rupiah = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(value);
</script>

<template>
    <Head title="Produk & Layanan" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-xl font-semibold text-slate-800">Katalog Produk & Layanan</h1></template>
        <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-600">Atur barang dan layanan untuk invoice.</p><button @click="openCreate" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">+ Tambah Produk</button></div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="hidden min-w-full divide-y divide-slate-200 md:table"><thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Nama</th><th class="px-5 py-3">Satuan</th><th class="px-5 py-3">Harga</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100"><tr v-for="product in products.data" :key="product.id" class="text-sm"><td class="px-5 py-4 font-medium text-slate-900">{{ product.name }}<div v-if="product.description" class="mt-1 max-w-lg truncate text-xs font-normal text-slate-500">{{ product.description }}</div></td><td class="px-5 py-4 text-slate-600">{{ product.unit || '—' }}</td><td class="px-5 py-4 font-medium text-slate-800">{{ rupiah(product.price) }}</td><td class="px-5 py-4 text-right"><button @click="openEdit(product)" class="px-2 py-1 text-indigo-700 hover:underline">Edit</button><button @click="remove(product)" class="px-2 py-1 text-rose-600 hover:underline">Hapus</button></td></tr><tr v-if="!products.data.length"><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada produk atau layanan.</td></tr></tbody>
                </table>
                <div class="divide-y divide-slate-100 md:hidden"><article v-for="product in products.data" :key="product.id" class="space-y-2 p-4"><div class="flex items-start justify-between gap-3"><h2 class="font-semibold text-slate-900">{{ product.name }}</h2><span class="shrink-0 text-sm font-semibold text-indigo-700">{{ rupiah(product.price) }}</span></div><p class="text-sm text-slate-600">{{ product.unit || 'Tanpa satuan' }}</p><p v-if="product.description" class="text-xs text-slate-500">{{ product.description }}</p><div class="flex justify-end gap-3"><button @click="openEdit(product)" class="text-sm text-indigo-700">Edit</button><button @click="remove(product)" class="text-sm text-rose-600">Hapus</button></div></article><p v-if="!products.data.length" class="p-8 text-center text-sm text-slate-500">Belum ada produk atau layanan.</p></div>
            </div>
            <nav v-if="products.links.length > 3" class="flex flex-wrap justify-center gap-1"><template v-for="(link, i) in products.links" :key="i"><Link v-if="link.url" :href="link.url" preserve-scroll class="rounded border px-3 py-1.5 text-sm" :class="link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-700'" v-html="link.label" /><span v-else class="rounded border border-slate-100 px-3 py-1.5 text-sm text-slate-300" v-html="link.label" /></template></nav>
        </div>
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 sm:items-center sm:p-4" @click.self="showForm=false"><section role="dialog" aria-modal="true" aria-labelledby="product-form-title" class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-xl sm:rounded-2xl sm:p-6"><div class="mb-5 flex items-center justify-between"><h2 id="product-form-title" class="text-lg font-semibold">{{ editing ? 'Edit Produk' : 'Tambah Produk' }}</h2><button @click="showForm=false" aria-label="Tutup" class="rounded p-2 text-slate-500 hover:bg-slate-100">✕</button></div>
            <form @submit.prevent="save" class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-medium text-slate-700 sm:col-span-2">Nama <input v-model="form.name" required maxlength="255" class="mt-1 w-full rounded-lg border-slate-300" /><span v-if="form.errors.name" class="text-xs text-rose-600">{{ form.errors.name }}</span></label><label class="text-sm font-medium text-slate-700">Harga <input v-model="form.price" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-lg border-slate-300" /><span v-if="form.errors.price" class="text-xs text-rose-600">{{ form.errors.price }}</span></label><label class="text-sm font-medium text-slate-700">Satuan <input v-model="form.unit" placeholder="Pcs, Jam, Paket" class="mt-1 w-full rounded-lg border-slate-300" /></label><label class="text-sm font-medium text-slate-700 sm:col-span-2">Deskripsi <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-lg border-slate-300" /></label><div class="flex justify-end gap-2 sm:col-span-2"><button type="button" @click="showForm=false" class="rounded-lg border px-4 py-2 text-sm">Batal</button><button :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">{{ form.processing ? 'Menyimpan…' : 'Simpan' }}</button></div></form>
        </section></div>
    </AuthenticatedLayout>
</template>
