<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    campaigns: Object,
    channels: Array,
    statuses: Array,
});

const form = useForm({
    title: '',
    channel: props.channels[0] ?? 'WhatsApp',
    content: '',
});

const submit = () => {
    form.post(route('crm.campaigns.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('title', 'content'),
    });
};

const remove = (campaign) => {
    if (confirm(`Hapus draft "${campaign.title}"?`)) {
        router.delete(route('crm.campaigns.destroy', campaign.id), { preserveScroll: true });
    }
};

const formatDate = (iso) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Promosi" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Promosi & Broadcast</h2>
                <p class="text-sm text-slate-500">Susun pesan promosi massal. Saat ini tersimpan sebagai draft; pengiriman belum aktif.</p>
            </div>
        </template>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-5 lg:px-8">
            <!-- Form draft -->
            <form class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2" @submit.prevent="submit">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700">Buat Draft Promosi</h3>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Judul *</label>
                    <input v-model="form.title" type="text" required maxlength="255" placeholder="Contoh: Diskon Akhir Tahun 20%"
                        class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Saluran *</label>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        <label v-for="ch in channels" :key="ch"
                            :class="['cursor-pointer rounded-xl border px-3 py-2 text-center text-sm font-semibold transition active:scale-95',
                                form.channel === ch ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50']">
                            <input v-model="form.channel" type="radio" :value="ch" class="sr-only" />
                            {{ ch === 'WhatsApp' ? 'WhatsApp' : 'Email' }}
                        </label>
                    </div>
                    <p v-if="form.errors.channel" class="mt-1 text-xs text-rose-600">{{ form.errors.channel }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Isi Pesan *</label>
                    <textarea v-model="form.content" rows="6" required maxlength="4000" placeholder="Tulis isi promosi…"
                        class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <p class="mt-1 text-right text-[11px] text-slate-400">{{ form.content.length }}/4000</p>
                    <p v-if="form.errors.content" class="mt-1 text-xs text-rose-600">{{ form.errors.content }}</p>
                </div>

                <button type="submit" :disabled="form.processing"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.98] transition disabled:cursor-not-allowed disabled:opacity-60">
                    {{ form.processing ? 'Menyimpan…' : 'Simpan Draft' }}
                </button>
            </form>

            <!-- Daftar draft -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-3">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700">Daftar Promosi ({{ campaigns.total }})</h3>
                </div>
                <ul class="divide-y divide-slate-100">
                    <li v-if="!campaigns.data.length" class="p-8 text-center text-sm text-slate-500">
                        Belum ada draft promosi.
                    </li>
                    <li v-for="c in campaigns.data" :key="c.id" class="flex items-start justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-slate-900">{{ c.title }}</p>
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ c.channel }}</span>
                                <span :class="['rounded-md px-2 py-0.5 text-[11px] font-bold',
                                    c.status === 'Terkirim' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                    {{ c.status }}
                                </span>
                            </div>
                            <p class="mt-1 line-clamp-2 whitespace-pre-line text-sm text-slate-600">{{ c.content }}</p>
                            <p class="mt-1 text-[11px] text-slate-400">Dibuat {{ formatDate(c.created_at) }}</p>
                        </div>
                        <button type="button" @click="remove(c)"
                            class="shrink-0 rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 active:scale-95 transition">
                            Hapus
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
