<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-slate-800">
                    Dashboard Ringkasan
                </h2>
                <Link
                    :href="route('invoices.create')"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                >
                    + Buat Faktur Baru
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Status Setup Rekening Bank -->
                <div
                    v-if="!$page.props.auth?.hasBankAccount"
                    class="overflow-hidden rounded-xl border border-amber-300 bg-amber-50 p-6 shadow-sm"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">⚠️</span>
                            <div>
                                <h3 class="text-base font-bold text-amber-900">
                                    Rekening Bank Pembayaran Belum Diatur!
                                </h3>
                                <p class="mt-1 text-sm text-amber-800">
                                    Agar invoice yang Anda cetak ke PDF menampilkan nomor rekening transfer resmi (sehingga customer tahu tujuan transfer resmi), silakan isi data bank dan nomor rekening usaha Anda di menu Profil Usaha.
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('profile.business.edit')"
                            class="inline-flex shrink-0 items-center justify-center rounded-lg bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:bg-amber-700"
                        >
                            Atur Profil & Rekening Bank &rarr;
                        </Link>
                    </div>
                </div>

                <!-- Quick Action Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        :href="route('profile.business.edit')"
                        class="group block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">🏢</span>
                            <span
                                :class="$page.props.auth?.hasBankAccount ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                            >
                                {{ $page.props.auth?.hasBankAccount ? 'Rekening Siap' : 'Belum Ada Rekening' }}
                            </span>
                        </div>
                        <h4 class="mt-3 font-semibold text-slate-900 group-hover:text-indigo-600">Profil Usaha & Bank</h4>
                        <p class="mt-1 text-xs text-slate-500">NPWP, logo usaha, rekening bank transfer resmi.</p>
                    </Link>

                    <Link
                        :href="route('invoices.index')"
                        class="group block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">📄</span>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[10px] font-bold text-indigo-700 uppercase tracking-wider">
                                Faktur
                            </span>
                        </div>
                        <h4 class="mt-3 font-semibold text-slate-900 group-hover:text-indigo-600">Kelola Faktur</h4>
                        <p class="mt-1 text-xs text-slate-500">Buat invoice, pantau status pembayaran & cetak PDF.</p>
                    </Link>

                    <Link
                        :href="route('clients.index')"
                        class="group block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">👥</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-700 uppercase tracking-wider">
                                Kontak
                            </span>
                        </div>
                        <h4 class="mt-3 font-semibold text-slate-900 group-hover:text-indigo-600">Buku Pelanggan</h4>
                        <p class="mt-1 text-xs text-slate-500">Daftar klien, alamat dan NPWP/NIK pembeli.</p>
                    </Link>

                    <Link
                        :href="route('products.index')"
                        class="group block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-2xl">📦</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-700 uppercase tracking-wider">
                                Katalog
                            </span>
                        </div>
                        <h4 class="mt-3 font-semibold text-slate-900 group-hover:text-indigo-600">Katalog Produk / Jasa</h4>
                        <p class="mt-1 text-xs text-slate-500">Master harga dan deskripsi untuk input cepat.</p>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
