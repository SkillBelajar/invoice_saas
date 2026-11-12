<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
// computed, not plain — a static read caches across client-side transitions
const appVersion = computed(() => page.props.appVersion ?? '0.4');
const appName = computed(() => page.props.appName ?? 'Invoice SaaS');

const features = [
    {
        title: 'Terbitkan invoice dalam hitungan detik',
        body: 'Isi klien, tambah item, pajak dan diskon dihitung otomatis. Nomor invoice berurutan tanpa bentrok.',
    },
    {
        title: 'Pantau piutang, bukan menebak',
        body: 'Setiap invoice punya status jelas: draft, terkirim, dibayar sebagian, lunas, atau jatuh tempo.',
    },
    {
        title: 'Rapi menurut aturan pajak',
        body: 'DPP dan PPN ditampilkan terpisah sesuai UU HPP. Laporan mengikuti SAK EMKM untuk usaha mikro dan kecil.',
    },
];
</script>

<template>
    <Head title="Beranda" />

    <div class="flex min-h-screen flex-col bg-slate-50 text-slate-900">
        <!-- Header -->
        <header class="border-b border-slate-200 bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-600 text-sm font-bold text-white">
                        IS
                    </span>
                    <span class="text-sm font-semibold tracking-tight">{{ appName }}</span>
                </div>
                <nav class="hidden items-center gap-6 text-sm text-slate-600 sm:flex">
                    <span class="text-slate-400">Fitur</span>
                    <span class="text-slate-400">Harga</span>
                    <span class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white">
                        Masuk
                    </span>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <main class="mx-auto w-full max-w-5xl flex-1 px-6 py-16">
            <div class="max-w-2xl">
                <p
                    class="mb-4 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                    Slice 1 — infrastruktur siap
                </p>
                <h1 class="text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                    Tagih klien tanpa<br class="hidden sm:block" />
                    kehilangan jejak.
                </h1>
                <p class="mt-5 text-base leading-relaxed text-slate-600">
                    Invoice, piutang, dan jatuh tempo dalam satu tempat. Dibuat untuk owner dan admin usaha
                    yang menagih lewat WhatsApp dan catatan manual.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <span
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm"
                    >
                        Mulai gratis
                    </span>
                    <span
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700"
                    >
                        Lihat contoh invoice
                    </span>
                </div>
            </div>

            <!-- Feature cards -->
            <div class="mt-16 grid gap-4 sm:grid-cols-3">
                <div
                    v-for="f in features"
                    :key="f.title"
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="text-sm font-semibold leading-snug tracking-tight">{{ f.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ f.body }}</p>
                </div>
            </div>

            <!-- Stack note -->
            <div class="mt-12 rounded-xl border border-dashed border-slate-300 bg-white/60 p-5">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Stack aktif</p>
                <p class="mt-2 text-sm text-slate-600">
                    Laravel 13 · Octane (Swoole) · Inertia + Vue 3 · Tailwind CSS · MySQL 8 · Redis · Nginx
                </p>
            </div>
        </main>

        <!-- Footer: pinned to bottom, version must be visible here -->
        <footer class="mt-auto border-t border-slate-200 bg-white">
            <div
                class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-2 px-6 py-5 text-xs text-slate-500 sm:flex-row"
            >
                <p>&copy; {{ new Date().getFullYear() }} {{ appName }}. Semua hak dilindungi.</p>
                <p class="font-medium text-slate-700" data-testid="app-version">
                    Versi: {{ appVersion }}
                </p>
            </div>
        </footer>
    </div>
</template>
