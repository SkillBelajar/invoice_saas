<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const page = usePage();
const appName = computed(() => page.props.appName ?? 'Invoice SaaS');

const menuOpen = ref(false);
const scrolled = ref(false);

const onScroll = () => { scrolled.value = window.scrollY > 8; };
onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onUnmounted(() => window.removeEventListener('scroll', onScroll));

const navLinks = [
    { label: 'Fitur', href: '#fitur' },
    { label: 'Testimoni', href: '#testimoni' },
];

const features = [
    {
        title: 'Penomoran Otomatis',
        body: 'Nomor invoice berurutan per tahun, tanpa bentrok dan tanpa duplikat. Format bisa diatur sendiri.',
        icon: 'hash',
    },
    {
        title: 'Kalkulasi Pajak & Diskon',
        body: 'DPP, PPN, dan diskon dihitung otomatis dengan pembulatan yang benar. Nominal tidak pernah meleset.',
        icon: 'calc',
    },
    {
        title: 'Stempel Digital PDF',
        body: 'Invoice PDF rapi dengan logo, tanda tangan, dan stempel usaha. Kirim ke klien dalam satu klik.',
        icon: 'seal',
    },
    {
        title: 'Laporan Piutang SAK EMKM',
        body: 'Aging piutang dan laporan penjualan mengikuti SAK EMKM. Siap dibaca saat tutup buku.',
        icon: 'chart',
    },
];

const testimonials = [
    {
        quote: 'Dulu saya tagih klien lewat chat dan catat di buku. Sekarang semua invoice ada nomornya, dan saya tahu persis siapa yang belum bayar.',
        name: 'Ratna Kusuma',
        role: 'Owner, CV Sinar Jaya Material',
        segment: 'B2B',
        initials: 'RK',
    },
    {
        quote: 'Yang paling menolong itu pajaknya dihitung sendiri. Saya tidak perlu takut salah PPN lagi saat kirim invoice ke perusahaan.',
        name: 'Bayu Pratama',
        role: 'Founder, Studio Desain Sore',
        segment: 'B2C',
        initials: 'BP',
    },
    {
        quote: 'Laporan piutangnya langsung kelihatan mana yang lewat jatuh tempo. Waktu tutup buku jadi setengah jam, bukan dua hari.',
        name: 'Siti Rahmawati',
        role: 'Admin Keuangan, Toko Bangunan Amanah',
        segment: 'B2B',
        initials: 'SR',
    },
];

const trustStats = [
    { value: '3.200+', label: 'Invoice diterbitkan' },
    { value: '< 30 dtk', label: 'Buat satu invoice' },
    { value: '100%', label: 'Sesuai UU HPP' },
];

const invoiceRows = [
    { item: 'Jasa Konsultasi Struktur', qty: 2, price: 'Rp 2.500.000', amount: 'Rp 5.000.000' },
    { item: 'Pengadaan Bahan Baku', qty: 1, price: 'Rp 12.400.000', amount: 'Rp 12.400.000' },
    { item: 'Biaya Pengiriman', qty: 3, price: 'Rp 350.000', amount: 'Rp 1.050.000' },
];
</script>

<template>
    <Head title="Beranda" />

    <div class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900">
        <!-- ============ NAVBAR ============ -->
        <header
            class="sticky top-0 z-50 border-b transition-all duration-200"
            :class="scrolled
                ? 'border-slate-200 bg-white/90 shadow-sm backdrop-blur-md'
                : 'border-transparent bg-white/70 backdrop-blur'"
        >
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-5 py-3.5 sm:px-6">
                <!-- Logo -->
                <a href="#" class="flex items-center gap-2.5">
                    <span
                        class="grid h-9 w-9 place-items-center rounded-lg bg-gradient-to-br from-indigo-600 to-blue-600 text-sm font-bold text-white shadow-sm"
                    >
                        IS
                    </span>
                    <span class="text-sm font-semibold tracking-tight sm:text-base">{{ appName }}</span>
                </a>

                <!-- Desktop nav: centered -->
                <div class="hidden items-center gap-1 md:flex">
                    <a
                        v-for="l in navLinks"
                        :key="l.href"
                        :href="l.href"
                        class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900"
                    >
                        {{ l.label }}
                    </a>
                </div>

                <!-- Desktop actions -->
                <div class="hidden items-center gap-2 md:flex">
                    <a
                        href="/login"
                        class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100"
                    >
                        Login
                    </a>
                    <a
                        href="/register"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                    >
                        Daftar Gratis
                    </a>
                </div>

                <!-- Mobile hamburger -->
                <button
                    type="button"
                    class="grid h-10 w-10 place-items-center rounded-lg text-slate-700 transition-colors hover:bg-slate-100 md:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="mobile-menu"
                    aria-label="Buka menu navigasi"
                    @click="menuOpen = !menuOpen"
                >
                    <svg v-if="!menuOpen" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </nav>

            <!-- Mobile menu panel -->
            <div v-show="menuOpen" id="mobile-menu" class="border-t border-slate-200 bg-white md:hidden">
                <div class="mx-auto max-w-6xl space-y-1 px-5 py-4">
                    <a
                        v-for="l in navLinks"
                        :key="l.href"
                        :href="l.href"
                        class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
                        @click="menuOpen = false"
                    >
                        {{ l.label }}
                    </a>
                    <div class="mt-3 grid gap-2 border-t border-slate-200 pt-3">
                        <a href="/login" class="rounded-lg border border-slate-300 px-3 py-2.5 text-center text-sm font-medium text-slate-700">
                            Login
                        </a>
                        <a href="/register" class="rounded-lg bg-indigo-600 px-3 py-2.5 text-center text-sm font-semibold text-white">
                            Daftar Gratis
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <!-- ============ HERO ============ -->
            <section class="relative overflow-hidden">
                <!-- soft background wash -->
                <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
                    <div class="absolute -top-40 left-1/2 h-[28rem] w-[28rem] -translate-x-1/2 rounded-full bg-indigo-200/40 blur-3xl"></div>
                    <div class="absolute -right-24 top-24 h-72 w-72 rounded-full bg-blue-200/40 blur-3xl"></div>
                </div>

                <div class="mx-auto grid max-w-6xl items-center gap-12 px-5 py-14 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:py-24">
                    <!-- Copy -->
                    <div>
                        <p
                            class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                            Standar UU HPP &amp; SAK EMKM
                        </p>

                        <h1 class="mt-5 text-3xl font-bold leading-[1.15] tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                            Faktur Komersial &amp; Laporan Penjualan
                            <span class="bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
                                Standar UU HPP
                            </span>
                            dalam Genggaman
                        </h1>

                        <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                            Terbitkan invoice profesional, hitung pajak dan diskon otomatis, lalu pantau
                            piutang yang jatuh tempo — semuanya dari satu dashboard.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a
                                href="/register"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                            >
                                Buat Invoice Sekarang - Gratis
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                            <a
                                href="#fitur"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50"
                            >
                                Lihat Fitur
                            </a>
                        </div>

                        <dl class="mt-10 grid grid-cols-3 gap-4 border-t border-slate-200 pt-6">
                            <div v-for="s in trustStats" :key="s.label">
                                <dt class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">{{ s.value }}</dt>
                                <dd class="mt-0.5 text-xs leading-snug text-slate-500">{{ s.label }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Dashboard mockup -->
                    <div class="relative">
                        <div
                            class="absolute inset-0 -z-10 translate-x-3 translate-y-3 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-blue-500/20 blur-xl"
                            aria-hidden="true"
                        ></div>

                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5">
                            <!-- mock window bar -->
                            <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50/80 px-4 py-2.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                <span class="ml-2 truncate text-[11px] font-medium text-slate-400">invoice / INV-2026-0042</span>
                            </div>

                            <div class="p-4 sm:p-5">
                                <!-- invoice header -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Faktur Komersial</p>
                                        <p class="mt-0.5 truncate text-sm font-bold tracking-tight text-slate-900">INV-2026-0042</p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200 ring-inset"
                                    >
                                        Lunas
                                    </span>
                                </div>

                                <!-- client row -->
                                <div class="mt-4 flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2.5">
                                    <div class="min-w-0">
                                        <p class="text-[10px] uppercase tracking-wide text-slate-400">Ditagihkan ke</p>
                                        <p class="truncate text-xs font-semibold text-slate-800">CV Sinar Jaya Material</p>
                                    </div>
                                    <p class="shrink-0 text-[10px] text-slate-500">Jatuh tempo 28 Nov</p>
                                </div>

                                <!-- line items -->
                                <div class="mt-4">
                                    <div class="flex items-center justify-between border-b border-slate-200 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                        <span>Item</span>
                                        <span>Jumlah</span>
                                    </div>
                                    <div
                                        v-for="r in invoiceRows"
                                        :key="r.item"
                                        class="flex items-center justify-between gap-3 border-b border-slate-100 py-2"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-medium text-slate-800">{{ r.item }}</p>
                                            <p class="text-[10px] text-slate-400">{{ r.qty }} × {{ r.price }}</p>
                                        </div>
                                        <p class="shrink-0 text-xs font-semibold tabular-nums text-slate-700">{{ r.amount }}</p>
                                    </div>
                                </div>

                                <!-- totals -->
                                <div class="mt-3 space-y-1.5">
                                    <div class="flex justify-between text-[11px] text-slate-500">
                                        <span>DPP</span><span class="tabular-nums">Rp 18.450.000</span>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-slate-500">
                                        <span>PPN 11%</span><span class="tabular-nums">Rp 2.029.500</span>
                                    </div>
                                    <div class="flex justify-between border-t-2 border-slate-900 pt-2 text-sm font-bold text-slate-900">
                                        <span>Total</span><span class="tabular-nums">Rp 20.479.500</span>
                                    </div>
                                </div>

                                <!-- mock action -->
                                <div class="mt-4 flex gap-2">
                                    <div class="flex-1 rounded-md bg-indigo-600 py-2 text-center text-[11px] font-semibold text-white">
                                        Kirim PDF
                                    </div>
                                    <div class="rounded-md border border-slate-300 px-3 py-2 text-center text-[11px] font-medium text-slate-600">
                                        Cetak
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ FITUR ============ -->
            <section id="fitur" class="scroll-mt-20 border-y border-slate-200 bg-white">
                <div class="mx-auto max-w-6xl px-5 py-16 sm:px-6 sm:py-20">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Fitur</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                            Semua yang dibutuhkan untuk menagih dengan rapi
                        </h2>
                        <p class="mt-3 text-base leading-relaxed text-slate-600">
                            Dibangun untuk owner dan admin usaha yang ingin cepat, tanpa harus paham akuntansi.
                        </p>
                    </div>

                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="f in features"
                            :key="f.title"
                            class="group rounded-xl border border-slate-200 bg-slate-50/50 p-5 transition-all hover:border-indigo-200 hover:bg-white hover:shadow-md hover:shadow-indigo-900/5"
                        >
                            <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 ring-inset transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                                <!-- hash -->
                                <svg v-if="f.icon === 'hash'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" d="M9.5 4 7 20M17 4l-2.5 16M4 9h15M3 15h15" />
                                </svg>
                                <!-- calculator -->
                                <svg v-else-if="f.icon === 'calc'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="4" y="3" width="16" height="18" rx="2.5" />
                                    <path stroke-linecap="round" d="M8 7.5h8M8.5 12h.01M12 12h.01M15.5 12h.01M8.5 16h.01M12 16h.01M15.5 16h.01" />
                                </svg>
                                <!-- seal / stamp -->
                                <svg v-else-if="f.icon === 'seal'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="9" r="5.5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.5 13.8-1 7.2 4.5-2.4 4.5 2.4-1-7.2" />
                                </svg>
                                <!-- chart -->
                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20V4M4 20h16" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 15.5v-3M11.5 15.5v-6M15.5 15.5v-4.5M19.5 15.5v-8" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-sm font-semibold leading-snug tracking-tight text-slate-900">{{ f.title }}</h3>
                            <p class="mt-2 text-[13px] leading-relaxed text-slate-600">{{ f.body }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ TESTIMONI ============ -->
            <section id="testimoni" class="scroll-mt-20">
                <div class="mx-auto max-w-6xl px-5 py-16 sm:px-6 sm:py-20">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Testimoni</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                            Dipakai usaha yang menagih setiap hari
                        </h2>
                        <p class="mt-3 text-base leading-relaxed text-slate-600">
                            Dari vendor material sampai studio desain — piutangnya akhirnya terlacak.
                        </p>
                    </div>

                    <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <figure
                            v-for="t in testimonials"
                            :key="t.name"
                            class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <!-- stars -->
                                <div class="flex gap-0.5" aria-label="Penilaian 5 dari 5">
                                    <svg v-for="n in 5" :key="n" class="h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M10 1.6l2.6 5.3 5.9.9-4.3 4.2 1 5.9L10 15l-5.2 2.9 1-5.9L1.5 7.8l5.9-.9L10 1.6z" />
                                    </svg>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                    {{ t.segment }}
                                </span>
                            </div>

                            <blockquote class="mt-3 flex-1 text-[13px] leading-relaxed text-slate-700">
                                &ldquo;{{ t.quote }}&rdquo;
                            </blockquote>

                            <figcaption class="mt-4 flex items-center gap-3 border-t border-slate-100 pt-4">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-indigo-600 text-[11px] font-bold text-white">
                                    {{ t.initials }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-semibold text-slate-900">{{ t.name }}</p>
                                    <p class="truncate text-[11px] text-slate-500">{{ t.role }}</p>
                                </div>
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </section>

            <!-- ============ CTA BAND ============ -->
            <section class="mx-auto max-w-6xl px-5 pb-16 sm:px-6 sm:pb-20">
                <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-700 px-6 py-10 text-center shadow-lg shadow-indigo-900/20 sm:px-10 sm:py-12">
                    <h2 class="text-xl font-bold tracking-tight text-white sm:text-2xl">
                        Invoice pertama Anda bisa jadi dalam satu menit
                    </h2>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-indigo-100">
                        Gratis untuk mulai. Tanpa kartu kredit, tanpa pemasangan aplikasi.
                    </p>
                    <a
                        href="/register"
                        class="mt-6 inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-sm font-semibold text-indigo-700 shadow-sm transition-transform hover:-translate-y-0.5"
                    >
                        Buat Invoice Sekarang - Gratis
                    </a>
                </div>
            </section>
        </main>

        <!-- ============ FOOTER ============ -->
        <footer class="mt-auto border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-5 py-10 sm:px-6">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-gradient-to-br from-indigo-600 to-blue-600 text-xs font-bold text-white">
                                IS
                            </span>
                            <span class="text-sm font-semibold tracking-tight text-slate-900">{{ appName }}</span>
                        </div>
                        <p class="mt-3 max-w-sm text-[13px] leading-relaxed text-slate-600">
                            Aplikasi invoicing dan manajemen piutang untuk usaha mikro, kecil, dan menengah.
                            Dibangun mengikuti UU HPP dan SAK EMKM.
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Bantuan</p>
                        <ul class="mt-3 space-y-2 text-[13px]">
                            <li><a href="#" class="text-slate-600 transition-colors hover:text-indigo-600">Panduan Pengguna</a></li>
                            <li><a href="#" class="text-slate-600 transition-colors hover:text-indigo-600">Pertanyaan Umum</a></li>
                            <li><a href="#" class="text-slate-600 transition-colors hover:text-indigo-600">Hubungi Kami</a></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Legal</p>
                        <ul class="mt-3 space-y-2 text-[13px]">
                            <li><a href="#" class="text-slate-600 transition-colors hover:text-indigo-600">Syarat Layanan</a></li>
                            <li><a href="#" class="text-slate-600 transition-colors hover:text-indigo-600">Kebijakan Privasi</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 flex flex-col items-center justify-between gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                    <p class="text-xs text-slate-500">
                        &copy; 2026 {{ appName }}. Semua hak dilindungi.
                    </p>
                    <!-- Versi ditampilkan sebagai badge mencolok -->
                    <p
                        class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700"
                        data-testid="app-version"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                        Versi: 0.9
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
