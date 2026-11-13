<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    clients: Array,
    selectedClient: Object,
    thread: Array,
    channels: Array,
});

const search = ref('');
const filteredClients = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? props.clients.filter((c) => c.name.toLowerCase().includes(q)) : props.clients;
});

const form = useForm({ channel: props.channels[0] ?? 'WhatsApp', content: '' });

const threadEl = ref(null);
const scrollToBottom = async () => {
    await nextTick();
    if (threadEl.value) threadEl.value.scrollTop = threadEl.value.scrollHeight;
};

const sendMessage = () => {
    if (!props.selectedClient || !form.content.trim() || form.processing) return;
    form.post(route('crm.messages.store', props.selectedClient.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('content'),
    });
};

const formatTime = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};

onMounted(scrollToBottom);
watch(() => props.thread, scrollToBottom, { deep: true });
</script>

<template>
    <Head title="Pusat Pesan" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Pusat Pesan</h2>
                <p class="text-sm text-slate-500">Riwayat komunikasi dengan pelanggan. Pengiriman otomatis ke WhatsApp/Email belum aktif.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex h-[70vh] min-h-[420px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <!-- Daftar klien (kiri) -->
                <aside :class="['w-full max-w-xs flex-col border-r border-slate-200 sm:flex sm:w-80', selectedClient ? 'hidden' : 'flex']">
                    <div class="border-b border-slate-100 p-3">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Cari pelanggan…"
                            class="block w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <ul class="flex-1 divide-y divide-slate-100 overflow-y-auto">
                        <li v-if="!filteredClients.length" class="p-6 text-center text-sm text-slate-500">
                            Belum ada pelanggan.
                        </li>
                        <li v-for="c in filteredClients" :key="c.id">
                            <Link
                                :href="route('crm.messages.index', { client: c.id })"
                                :class="[
                                    'flex items-start gap-3 px-4 py-3 transition hover:bg-slate-50',
                                    selectedClient?.id === c.id ? 'bg-indigo-50' : '',
                                ]"
                            >
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                                    {{ c.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ c.name }}</p>
                                        <span v-if="c.unread_count" class="shrink-0 rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-bold text-white">
                                            {{ c.unread_count }}
                                        </span>
                                    </div>
                                    <p class="truncate text-xs text-slate-500">{{ c.last_message || 'Belum ada percakapan' }}</p>
                                </div>
                            </Link>
                        </li>
                    </ul>
                </aside>

                <!-- Riwayat percakapan (kanan) -->
                <section :class="['flex-1 flex-col bg-slate-50', selectedClient ? 'flex' : 'hidden sm:flex']">
                    <template v-if="selectedClient">
                        <header class="flex items-center justify-between gap-2 border-b border-slate-200 bg-white px-5 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-900">{{ selectedClient.name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ selectedClient.phone || selectedClient.email || '—' }}</p>
                            </div>
                            <Link :href="route('clients.show', selectedClient.id)" class="shrink-0 text-xs font-semibold text-indigo-600 hover:underline">
                                Buka Profil →
                            </Link>
                        </header>

                        <div ref="threadEl" class="flex-1 space-y-3 overflow-y-auto p-5">
                            <p v-if="!thread.length" class="pt-10 text-center text-sm text-slate-500">
                                Belum ada pesan. Kirim pesan pertama di bawah.
                            </p>
                            <div
                                v-for="m in thread"
                                :key="m.id"
                                :class="['flex', m.direction === 'Keluar' ? 'justify-end' : 'justify-start']"
                            >
                                <div
                                    :class="[
                                        'max-w-[75%] rounded-2xl px-4 py-2 text-sm shadow-sm',
                                        m.direction === 'Keluar'
                                            ? 'rounded-br-sm bg-emerald-500 text-white'
                                            : 'rounded-bl-sm bg-white text-slate-800',
                                    ]"
                                >
                                    <p class="whitespace-pre-line break-words">{{ m.content }}</p>
                                    <p :class="['mt-1 text-right text-[10px]', m.direction === 'Keluar' ? 'text-emerald-100' : 'text-slate-400']">
                                        {{ m.channel }} · {{ formatTime(m.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form class="flex items-end gap-2 border-t border-slate-200 bg-white p-3" @submit.prevent="sendMessage">
                            <select v-model="form.channel" class="rounded-xl border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="ch in channels" :key="ch" :value="ch">{{ ch }}</option>
                            </select>
                            <textarea
                                v-model="form.content"
                                rows="1"
                                maxlength="2000"
                                placeholder="Tulis pesan…"
                                class="flex-1 resize-none rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @keydown.enter.exact.prevent="sendMessage"
                            ></textarea>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.content.trim()"
                                class="rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600 active:scale-95 transition disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ form.processing ? '…' : 'Kirim' }}
                            </button>
                        </form>
                        <p v-if="form.errors.content" class="bg-white px-4 pb-2 text-xs text-rose-600">{{ form.errors.content }}</p>
                    </template>

                    <div v-else class="flex flex-1 items-center justify-center p-8 text-center text-sm text-slate-500">
                        Pilih pelanggan di sebelah kiri untuk membuka percakapan.
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
