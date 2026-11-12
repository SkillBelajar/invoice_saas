<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    appVersion: {
        type: String,
        default: '0.11',
    },
    user: {
        type: Object,
        default: () => ({ name: 'Owner', email: '' }),
    },
});

const topics = [
    {
        id: 'setup-profil',
        title: 'Mulai (Setup Profil)',
        badge: 'Wajib Awal',
        badgeColor: 'bg-indigo-100 text-indigo-700',
        icon: '🏢',
        summary: 'Atur identitas resmi usaha, nomor rekening transfer, logo, dan stempel digital.',
    },
    {
        id: 'buat-invoice',
        title: 'Cara Buat Invoice',
        badge: 'Operasional',
        badgeColor: 'bg-emerald-100 text-emerald-700',
        icon: '📄',
        summary: 'Pahami cara kerja penomoran otomatis INV-YYYYMM-XXX dan input baris item faktur.',
    },
    {
        id: 'pajak-diskon',
        title: 'Pajak & Diskon',
        badge: 'Kepatuhan UU HPP',
        badgeColor: 'bg-amber-100 text-amber-700',
        icon: '⚖️',
        summary: 'Kalkulasi Dasar Pengenaan Pajak (DPP), sakelar tarif PPN 11%/12%, dan diskon nominal/persentase.',
    },
    {
        id: 'export-laporan',
        title: 'Export Laporan & Cetak PDF',
        badge: 'Dokumentasi',
        badgeColor: 'bg-sky-100 text-sky-700',
        icon: '📊',
        summary: 'Cetak faktur PDF berstandar SAK EMKM, verifikasi QR Code publik, dan monitoring umur piutang.',
    },
];

const activeTopicId = ref('setup-profil');
const mobileMenuOpen = ref(false);

const activeTopic = computed(() => {
    return topics.find((t) => t.id === activeTopicId.value) || topics[0];
});

function selectTopic(id) {
    activeTopicId.value = id;
    mobileMenuOpen.value = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<template>
    <Head title="Panduan Pengguna (User Guide)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-6 items-center rounded-md bg-indigo-50 px-2 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                            Dokumentasi Resmi
                        </span>
                        <span class="text-xs text-slate-400">&bull;</span>
                        <span class="text-xs text-slate-500">Versi Sistem: {{ appVersion }}</span>
                    </div>
                    <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                        Buku Panduan Pengguna (Owner & Admin)
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('invoices.create')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition"
                    >
                        <span>+ Buat Faktur Sekarang</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Mobile Navigation Bar (Dropdown + Toggle) -->
                <div class="mb-6 block md:hidden">
                    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 truncate">
                                <span class="text-xl">{{ activeTopic.icon }}</span>
                                <div class="truncate">
                                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Topik Aktif</span>
                                    <span class="block truncate text-sm font-bold text-slate-800">{{ activeTopic.title }}</span>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="mobileMenuOpen = !mobileMenuOpen"
                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                            >
                                <span>{{ mobileMenuOpen ? 'Tutup Menu' : 'Pilih Topik' }}</span>
                                <svg
                                    class="h-4 w-4 transition-transform"
                                    :class="{ 'rotate-180': mobileMenuOpen }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Mobile Dropdown Options -->
                        <div v-if="mobileMenuOpen" class="mt-3 space-y-1.5 border-t border-slate-100 pt-3">
                            <button
                                v-for="t in topics"
                                :key="t.id"
                                type="button"
                                @click="selectTopic(t.id)"
                                :class="[
                                    activeTopicId === t.id
                                        ? 'bg-indigo-50 font-bold text-indigo-700'
                                        : 'text-slate-600 hover:bg-slate-50',
                                    'flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs',
                                ]"
                            >
                                <span class="flex items-center gap-2">
                                    <span>{{ t.icon }}</span>
                                    <span>{{ t.title }}</span>
                                </span>
                                <span :class="['rounded px-1.5 py-0.5 text-[10px] font-semibold', t.badgeColor]">
                                    {{ t.badge }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Grid Layout (Sidebar Desktop + Content Area) -->
                <div class="grid grid-cols-1 gap-8 md:grid-cols-12">
                    <!-- Left Sidebar (Desktop) -->
                    <aside class="hidden md:col-span-4 md:block lg:col-span-3">
                        <div class="sticky top-6 space-y-4">
                            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                    Daftar Materi Panduan
                                </h3>
                                <nav class="mt-3 space-y-1.5">
                                    <button
                                        v-for="t in topics"
                                        :key="t.id"
                                        type="button"
                                        @click="selectTopic(t.id)"
                                        :class="[
                                            activeTopicId === t.id
                                                ? 'bg-indigo-50 font-bold text-indigo-700 ring-1 ring-indigo-200'
                                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
                                            'group flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-xs transition',
                                        ]"
                                    >
                                        <div class="flex items-center gap-2.5 truncate">
                                            <span class="text-base">{{ t.icon }}</span>
                                            <span class="truncate">{{ t.title }}</span>
                                        </div>
                                        <span
                                            :class="[
                                                'shrink-0 rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                                t.badgeColor,
                                            ]"
                                        >
                                            {{ t.badge }}
                                        </span>
                                    </button>
                                </nav>
                            </div>

                            <!-- Quick Tip Box -->
                            <div class="rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 to-sky-50/50 p-4 text-xs text-indigo-900 shadow-sm">
                                <div class="flex items-center gap-2 font-bold text-indigo-950">
                                    <span>💡</span>
                                    <span>Butuh Bantuan Cepat?</span>
                                </div>
                                <p class="mt-1.5 text-slate-600 leading-relaxed">
                                    Setiap halaman utama memiliki pintasan otomatis. Pastikan selalu mengisi <strong>Profil Usaha</strong> terlebih dahulu sebelum mencetak invoice pertama.
                                </p>
                                <div class="mt-3 pt-3 border-t border-indigo-100/80">
                                    <Link
                                        :href="route('profile.business.edit')"
                                        class="font-bold text-indigo-600 hover:text-indigo-800 underline"
                                    >
                                        Buka Profil Usaha &rarr;
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Right Content Area -->
                    <main class="md:col-span-8 lg:col-span-9">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <!-- TOPIC 1: MULAI (SETUP PROFIL) -->
                            <section v-if="activeTopicId === 'setup-profil'" class="space-y-6">
                                <div class="border-b border-slate-100 pb-5">
                                    <div class="flex items-center gap-2 text-indigo-600">
                                        <span class="text-2xl">🏢</span>
                                        <span class="text-xs font-bold uppercase tracking-wider">Modul Dasar Usaha</span>
                                    </div>
                                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                                        Mulai: Setup Profil Usaha & Rekening Resmi
                                    </h1>
                                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                                        Data pada Profil Usaha merupakan identitas penerbit yang akan dicetak di seluruh lembar faktur resmi (kop surat, rincian pembayaran, dan tanda tangan).
                                    </p>
                                </div>

                                <!-- Step by step -->
                                <div class="space-y-4">
                                    <div class="flex items-start gap-4 rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">1</span>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-900">Nama Usaha & Alamat Lengkap</h4>
                                            <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                                Tuliskan nama resmi entitas usaha (PT, CV, Toko, atau Perorangan) beserta alamat operasional lengkap. Bagian ini akan menjadi identitas penjual pada bagian kiri atas invoice.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-4 rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">2</span>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-900">NPWP / NIK Entitas Usaha (Kepatuhan UU HPP)</h4>
                                            <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                                Masukkan 15 atau 16 digit Nomor Pokok Wajib Pajak (atau NIK KTP jika perorangan). Data ini penting untuk keabsahan faktur komersial dan pencatatan pajak masukan pelanggan Anda.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-4 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">3</span>
                                        <div>
                                            <h4 class="text-sm font-bold text-emerald-950">Rekening Bank Pembayaran (Sangat Penting)</h4>
                                            <p class="mt-1 text-xs text-emerald-800 leading-relaxed">
                                                Isikan <strong>Nama Bank</strong> (misal: BCA, Mandiri, BRI), <strong>Nomor Rekening</strong>, dan <strong>Atas Nama Pemilik Rekening</strong>. Data ini akan otomatis tercetak di kotak instruksi pembayaran pada lembar PDF faktur agar pelanggan tahu persis tujuan transfer resmi Anda.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-4 rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">4</span>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-900">Cara Upload Logo Usaha & Stempel Digital</h4>
                                            <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                                Pada bagian bawah formulir Profil Usaha, unggah berkas gambar logo (format PNG/JPG, latar transparan disarankan). Unggah juga gambar stempel atau spesimen tanda tangan digital (PNG transparan) yang akan diletakkan di footer kanan faktur PDF sebagai bukti pengesahan dokumen.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 rounded-xl border border-indigo-200 bg-indigo-50/50 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="text-xs text-indigo-900">
                                            <strong>Sudah siap mengatur profil?</strong> Kunjungi halaman pengaturan profil usaha sekarang.
                                        </div>
                                        <Link
                                            :href="route('profile.business.edit')"
                                            class="inline-flex shrink-0 items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700"
                                        >
                                            Atur Profil Usaha &rarr;
                                        </Link>
                                    </div>
                                </div>
                            </section>

                            <!-- TOPIC 2: CARA BUAT INVOICE -->
                            <section v-if="activeTopicId === 'buat-invoice'" class="space-y-6">
                                <div class="border-b border-slate-100 pb-5">
                                    <div class="flex items-center gap-2 text-emerald-600">
                                        <span class="text-2xl">📄</span>
                                        <span class="text-xs font-bold uppercase tracking-wider">Modul Operasional Faktur</span>
                                    </div>
                                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                                        Cara Buat Faktur & Mekanisme Auto-Numbering
                                    </h1>
                                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                                        Pelajari bagaimana sistem mengelola pembuatan faktur dinamis, penomoran urut otomatis bulanan, serta pengisian baris barang/jasa.
                                    </p>
                                </div>

                                <!-- Deep-dive Auto Numbering -->
                                <div class="rounded-xl border border-slate-200 bg-slate-900 p-5 text-white">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-mono uppercase tracking-wider text-indigo-400">Sistem Penomoran Cerdas</span>
                                        <span class="rounded bg-indigo-500/20 px-2 py-0.5 text-[10px] font-bold text-indigo-300">Unik & Urut</span>
                                    </div>
                                    <div class="mt-3 flex items-baseline gap-2">
                                        <span class="font-mono text-2xl font-extrabold text-emerald-400">INV-YYYYMM-XXX</span>
                                        <span class="text-xs text-slate-400">(Contoh: INV-202611-001)</span>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-300 leading-relaxed">
                                        Sistem secara otomatis mendeteksi tahun dan bulan berjalan. Nomor urut (<code class="text-indigo-300">001</code>, <code class="text-indigo-300">002</code>, dst.) dihitung otomatis berdasarkan faktur terakhir yang diterbitkan oleh akun Anda pada bulan tersebut, sehingga tidak akan pernah terjadi nomor ganda.
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <h3 class="text-sm font-bold text-slate-900">Tahapan Pembuatan Faktur:</h3>

                                    <div class="space-y-3">
                                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                                            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Langkah 1</span>
                                            <h4 class="mt-1 text-sm font-bold text-slate-900">Pilih Pelanggan dari Buku Kontak</h4>
                                            <p class="mt-1 text-xs text-slate-600">
                                                Pilih pelanggan yang telah didaftarkan pada menu Pelanggan. Seluruh informasi alamat dan NPWP/NIK pembeli akan langsung terhubung ke faktur.
                                            </p>
                                        </div>

                                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                                            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Langkah 2</span>
                                            <h4 class="mt-1 text-sm font-bold text-slate-900">Tentukan Tanggal Terbit & Termin Jatuh Tempo</h4>
                                            <p class="mt-1 text-xs text-slate-600">
                                                Tanggal jatuh tempo menjadi acuan sistem dalam mengkalkulasi umur piutang (*Aging Receivables*) dan memicu pengingat tagihan kritis di Dashboard.
                                            </p>
                                        </div>

                                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                                            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Langkah 3</span>
                                            <h4 class="mt-1 text-sm font-bold text-slate-900">Mengisi Baris Item (Line Items)</h4>
                                            <p class="mt-1 text-xs text-slate-600">
                                                Klik tombol <strong>"+ Tambah Baris"</strong> untuk menambahkan baris produk/jasa. Anda dapat memilih item dari katalog Produk yang sudah ada (harga dan nama akan terisi otomatis), atau mengetikkan deskripsi kustom dan harga satuan secara manual.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <Link
                                        :href="route('invoices.create')"
                                        class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700"
                                    >
                                        Mulai Buat Faktur Baru &rarr;
                                    </Link>
                                </div>
                            </section>

                            <!-- TOPIC 3: PAJAK & DISKON -->
                            <section v-if="activeTopicId === 'pajak-diskon'" class="space-y-6">
                                <div class="border-b border-slate-100 pb-5">
                                    <div class="flex items-center gap-2 text-amber-600">
                                        <span class="text-2xl">⚖️</span>
                                        <span class="text-xs font-bold uppercase tracking-wider">Kepatuhan Perpajakan & Diskon</span>
                                    </div>
                                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                                        Aturan Pajak PPN 11%/12% & Perhitungan Diskon
                                    </h1>
                                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                                        Kalkulasi finansial faktur dirancang sesuai standar perpajakan UU Harmonisasi Peraturan Perpajakan (UU HPP) dan standar akuntansi EMKM.
                                    </p>
                                </div>

                                <!-- Tax Card Breakdown -->
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-amber-900">Sakelar Tarif PPN</span>
                                            <span class="rounded bg-amber-200/60 px-2 py-0.5 text-[10px] font-bold text-amber-800">11% / 12%</span>
                                        </div>
                                        <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                                            Sistem menyediakan opsi tarif PPN <strong>11%</strong> (tarif resmi saat ini) serta opsi <strong>12%</strong> sesuai penyesuaian tarif UU HPP. Anda juga dapat memilih <strong>0% (Bebas PPN)</strong> jika transaksi bukan merupakan objek pajak pertambahan nilai.
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-indigo-900">Formula DPP (Dasar Pajak)</span>
                                            <span class="rounded bg-indigo-200/60 px-2 py-0.5 text-[10px] font-bold text-indigo-800">Standar Ditjen Pajak</span>
                                        </div>
                                        <p class="mt-2 text-xs text-slate-600 leading-relaxed">
                                            Dasar Pengenaan Pajak dihitung setelah pengurangan diskon: <br />
                                            <strong class="font-mono text-indigo-900">DPP = Subtotal - Potongan Diskon</strong>.<br />
                                            Nilai PPN dihitung dari persentase atas nilai DPP tersebut.
                                        </p>
                                    </div>
                                </div>

                                <!-- Discount Engine Explanation -->
                                <div class="rounded-xl border border-slate-200 p-5">
                                    <h4 class="text-sm font-bold text-slate-900">Dua Metode Diskon yang Didukung:</h4>
                                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs">
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-3.5">
                                            <span class="font-bold text-slate-900">1. Diskon Nominal Tetap (Fixed Rupiah)</span>
                                            <p class="mt-1 text-slate-500">
                                                Contoh: Potongan langsung sebesar <code class="font-mono text-indigo-600 font-bold">Rp 500.000</code> dari total tagihan kotor.
                                            </p>
                                        </div>
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-3.5">
                                            <span class="font-bold text-slate-900">2. Diskon Persentase (%)</span>
                                            <p class="mt-1 text-slate-500">
                                                Contoh: Diskon <code class="font-mono text-indigo-600 font-bold">10%</code>, di mana sistem menghitung otomatis nilai potongan berdasarkan subtotal seluruh line item.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- TOPIC 4: EXPORT LAPORAN & CETAK PDF -->
                            <section v-if="activeTopicId === 'export-laporan'" class="space-y-6">
                                <div class="border-b border-slate-100 pb-5">
                                    <div class="flex items-center gap-2 text-sky-600">
                                        <span class="text-2xl">📊</span>
                                        <span class="text-xs font-bold uppercase tracking-wider">Cetak & Analitik Pelaporan</span>
                                    </div>
                                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                                        Cetak PDF Faktur, Verifikasi QR, & Monitoring Piutang
                                    </h1>
                                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                                        Panduan mencetak dokumen fisik berstandar hukum, memverifikasi keaslian faktur secara publik, serta memantau kesehatan kas melalui umur piutang.
                                    </p>
                                </div>

                                <div class="space-y-4 text-xs">
                                    <div class="rounded-xl border border-slate-200 p-4">
                                        <h4 class="font-bold text-sm text-slate-900">1. Cetak Dokumen PDF Resmi (A4)</h4>
                                        <p class="mt-1 text-slate-600 leading-relaxed">
                                            Tombol <strong>"PDF"</strong> dapat diakses langsung dari daftar faktur atau halaman rincian faktur. Dokumen dicetak dalam format A4 standar industri dengan tata letak kop surat, nomor seri, detail DPP, PPN, terbilang angka, nomor rekening bank, dan stempel digital.
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 p-4">
                                        <h4 class="font-bold text-sm text-slate-900">2. Fitur Keaslian QR Code Publik</h4>
                                        <p class="mt-1 text-slate-600 leading-relaxed">
                                            Setiap lembar PDF faktur dilengkapi dengan <strong>QR Code unik</strong> di bagian kiri bawah. Saat dipindai menggunakan kamera ponsel oleh pelanggan Anda, QR Code akan langsung membuka halaman verifikasi publik yang menyatakan dokumen sah terdaftar pada sistem tanpa perlu login.
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 p-4">
                                        <h4 class="font-bold text-sm text-slate-900">3. Analitik Aging Piutang (Umur Piutang)</h4>
                                        <p class="mt-1 text-slate-600 leading-relaxed">
                                            Pada menu <strong>Dashboard</strong>, sistem secara berkala mengelompokkan piutang aktif Anda ke dalam 4 kategori umur:
                                        </p>
                                        <ul class="mt-2 list-disc list-inside space-y-1 text-slate-600">
                                            <li><strong class="text-emerald-700">Lancar</strong>: Faktur yang belum melewati batas jatuh tempo.</li>
                                            <li><strong class="text-amber-700">Terlambat 1 - 30 Hari</strong>: Tagihan jatuh tempo tahap awal, disarankan mengirim pesan pengingat.</li>
                                            <li><strong class="text-orange-700">Terlambat 31 - 60 Hari</strong>: Tagihan menunggak tahap sedang, perlu eskalasi penagihan.</li>
                                            <li><strong class="text-rose-700">Kritis (&gt; 60 Hari)</strong>: Tagihan macet yang membutuhkan tindakan penagihan intensif.</li>
                                        </ul>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 p-4">
                                        <h4 class="font-bold text-sm text-slate-900">4. Tombol Aksi Cepat "✓ Lunas"</h4>
                                        <p class="mt-1 text-slate-600 leading-relaxed">
                                            Saat dana pembayaran dari pelanggan telah masuk ke rekening, Anda dapat langsung mengklik tombol <strong>"✓ Lunas"</strong> di Dashboard atau menu Faktur untuk memperbarui status piutang dan omzet kas secara realtime.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-between items-center pt-4 border-t border-slate-100">
                                    <Link
                                        :href="route('dashboard')"
                                        class="text-xs font-semibold text-slate-600 hover:text-slate-900"
                                    >
                                        &larr; Kembali ke Dashboard
                                    </Link>
                                    <Link
                                        :href="route('invoices.index')"
                                        class="inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-xs font-bold text-white hover:bg-sky-700"
                                    >
                                        Lihat Riwayat Faktur &rarr;
                                    </Link>
                                </div>
                            </section>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
