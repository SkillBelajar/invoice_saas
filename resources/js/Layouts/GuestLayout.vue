<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50 text-slate-900">
        <main class="flex flex-1 flex-col items-center justify-start px-4 py-10 sm:justify-center sm:py-12">
            <!-- Kembali ke Beranda / Landing -->
            <Link
                href="/"
                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm transition-colors hover:text-slate-900"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12l7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Beranda
            </Link>

            <div class="mt-6 w-full max-w-md">
                <div class="mb-5 flex justify-center">
                    <Link href="/">
                        <ApplicationLogo class="h-11 w-auto" />
                    </Link>
                </div>

                <!-- soft glow behind card -->
                <div class="relative">
                    <div
                        class="pointer-events-none absolute inset-x-6 -top-3 h-20 rounded-full bg-indigo-200/40 blur-2xl"
                        aria-hidden="true"
                    ></div>

                    <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        <slot />
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer: versi mencolok -->
        <footer class="mt-auto border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-5 py-5 text-xs text-slate-500 sm:flex-row sm:px-6">
                <p>&copy; 2026 Invoice SaaS. Semua hak dilindungi.</p>
                <p
                    class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700"
                    data-testid="app-version"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                    Versi: 1.0
                </p>
            </div>
        </footer>
    </div>
</template>
