<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ clients: Object, filters: Object });
const showForm = ref(false);
const editing = ref(null);
const form = useForm({ name: '', email: '', phone: '', address: '', tax_id: '', source: '' });

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
}
function openEdit(client) {
    editing.value = client;
    form.clearErrors();
    form.name = client.name;
    form.email = client.email ?? '';
    form.phone = client.phone ?? '';
    form.address = client.address;
    form.tax_id = client.tax_id ?? '';
    form.source = client.source ?? '';
    showForm.value = true;
}
function save() {
    const options = { onSuccess: () => { showForm.value = false; form.reset(); } };
    editing.value ? form.put(route('clients.update', editing.value.id), options) : form.post(route('clients.store'), options);
}
function remove(client) {
    const warning = (client.invoices_count && client.invoices_count > 0)
        ? `Hapus pelanggan “${client.name}”?\n\nPerhatian: Pelanggan ini memiliki ${client.invoices_count} faktur. Semua faktur terkait akan ikut terhapus.`
        : `Hapus pelanggan “${client.name}”?`;
    if (window.confirm(warning)) router.delete(route('clients.destroy', client.id));
}
</script>

<template>
    <Head title="Pelanggan" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-xl font-semibold text-slate-800">Buku Pelanggan</h1></template>
        <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-600">Kelola klien dan informasi penagihan.</p>
                <button @click="openCreate" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:bg-indigo-800 active:scale-[0.98]">+ Tambah Pelanggan</button>
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="hidden min-w-full divide-y divide-slate-200 md:table">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Nama & Sumber</th>
                            <th class="px-5 py-3">Kontak</th>
                            <th class="px-5 py-3">NPWP/NIK</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="client in clients.data" :key="client.id" class="text-sm hover:bg-slate-50/60">
                            <td class="px-5 py-4 font-medium text-slate-900">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('clients.show', client.id)" class="text-indigo-600 hover:text-indigo-900 hover:underline font-semibold">
                                        {{ client.name }}
                                    </Link>
                                    <span v-if="client.source" class="rounded bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700">
                                        {{ client.source }}
                                    </span>
                                </div>
                                <div class="mt-1 max-w-sm truncate text-xs text-slate-500">{{ client.address }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ client.email || '—' }}
                                <div class="mt-1 text-xs">{{ client.phone || '—' }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ client.tax_id || '—' }}</td>
                            <td class="px-5 py-4 text-right whitespace-nowrap text-xs space-x-2">
                                <Link :href="route('clients.show', client.id)" class="font-semibold text-slate-600 hover:text-slate-900 hover:underline">
                                    Riwayat
                                </Link>
                                <button @click="openEdit(client)" class="font-semibold text-indigo-700 hover:underline">Edit</button>
                                <button @click="remove(client)" class="font-semibold text-rose-600 hover:underline">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="!clients.data.length"><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada pelanggan.</td></tr>
                    </tbody>
                </table>
                <div class="divide-y divide-slate-100 md:hidden">
                    <article v-for="client in clients.data" :key="client.id" class="space-y-2 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <Link :href="route('clients.show', client.id)" class="font-semibold text-indigo-600 hover:underline">
                                    {{ client.name }}
                                </Link>
                                <span v-if="client.source" class="ms-2 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] text-indigo-700">
                                    {{ client.source }}
                                </span>
                            </div>
                            <span class="text-xs text-slate-500">{{ client.tax_id || '—' }}</span>
                        </div>
                        <p class="text-sm text-slate-600">{{ client.email || '—' }} · {{ client.phone || '—' }}</p>
                        <p class="text-xs text-slate-500">{{ client.address }}</p>
                        <div class="flex justify-end gap-3 pt-1">
                            <Link :href="route('clients.show', client.id)" class="text-sm text-slate-600 font-semibold">Riwayat</Link>
                            <button @click="openEdit(client)" class="text-sm text-indigo-700 font-semibold">Edit</button>
                            <button @click="remove(client)" class="text-sm text-rose-600 font-semibold">Hapus</button>
                        </div>
                    </article>
                    <p v-if="!clients.data.length" class="p-8 text-center text-sm text-slate-500">Belum ada pelanggan.</p>
                </div>
            </div>
            <nav v-if="clients.links.length > 3" class="flex flex-wrap justify-center gap-1"><template v-for="(link, i) in clients.links" :key="i"><Link v-if="link.url" :href="link.url" preserve-scroll class="rounded border px-3 py-1.5 text-sm" :class="link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-700'" v-html="link.label" /><span v-else class="rounded border border-slate-100 px-3 py-1.5 text-sm text-slate-300" v-html="link.label" /></template></nav>
        </div>
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/40 sm:items-center sm:p-4" @click.self="showForm=false">
            <section role="dialog" aria-modal="true" aria-labelledby="client-form-title" class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-xl sm:rounded-2xl sm:p-6">
                <div class="mb-5 flex items-center justify-between"><h2 id="client-form-title" class="text-lg font-semibold">{{ editing ? 'Edit Pelanggan' : 'Tambah Pelanggan' }}</h2><button @click="showForm=false" aria-label="Tutup" class="rounded p-2 text-slate-500 hover:bg-slate-100">✕</button></div>
                <form @submit.prevent="save" class="grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-medium text-slate-700">Nama <input v-model="form.name" required maxlength="255" class="mt-1 w-full rounded-lg border-slate-300" /> <span v-if="form.errors.name" class="text-xs text-rose-600">{{ form.errors.name }}</span></label>
                    <label class="text-sm font-medium text-slate-700">Email <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-slate-300" /><span v-if="form.errors.email" class="text-xs text-rose-600">{{ form.errors.email }}</span></label>
                    <label class="text-sm font-medium text-slate-700">Telepon <input v-model="form.phone" class="mt-1 w-full rounded-lg border-slate-300" /></label>
                    <label class="text-sm font-medium text-slate-700">NPWP / NIK <input v-model="form.tax_id" class="mt-1 w-full rounded-lg border-slate-300" /></label>
                    <label class="text-sm font-medium text-slate-700">Sumber Prospek (Lead Source) <input v-model="form.source" placeholder="Contoh: Instagram, Rujukan, Cold Call..." class="mt-1 w-full rounded-lg border-slate-300" /><span v-if="form.errors.source" class="text-xs text-rose-600">{{ form.errors.source }}</span></label>
                    <label class="text-sm font-medium text-slate-700 sm:col-span-2">Alamat <textarea v-model="form.address" required rows="3" class="mt-1 w-full rounded-lg border-slate-300" /><span v-if="form.errors.address" class="text-xs text-rose-600">{{ form.errors.address }}</span></label>
                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <button type="button" @click="showForm=false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50 active:bg-slate-100">Batal</button>
                        <button :disabled="form.processing" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 active:bg-indigo-800 active:scale-[0.98] disabled:opacity-50">
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan…' : 'Simpan' }}</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
