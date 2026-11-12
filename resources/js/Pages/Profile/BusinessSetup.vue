<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();

const props = defineProps({
    profile: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    company_name: props.profile?.company_name ?? '',
    address: props.profile?.address ?? '',
    tax_id: props.profile?.tax_id ?? '',
    bank_name: props.profile?.bank_name ?? '',
    bank_account_number: props.profile?.bank_account_number ?? '',
    bank_account_name: props.profile?.bank_account_name ?? '',
    logo: null,
    signature: null,
});

const logoPreview = ref(props.profile?.logo_url ?? null);
const signaturePreview = ref(props.profile?.signature_url ?? null);

const pickFile = (e, key, previewRef) => {
    const file = e.target.files?.[0];
    if (file) {
        form[key] = file;
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = () => (previewRef.value = reader.result);
            reader.readAsDataURL(file);
        }
    }
};

const submit = () => {
    form.post(route('profile.business.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset('logo', 'signature'),
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Profil Usaha" />

        <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Profil Usaha</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Data ini menjadi master faktur — dipakai sebagai identitas di setiap invoice.
                </p>
            </div>

            <div
                v-if="page.props.flash?.success"
                class="mb-4 flex items-center gap-2 rounded-md bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200 ring-inset"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                {{ page.props.flash.success }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Info identitas -->
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-900">Identitas Usaha</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-1">
                        <div>
                            <InputLabel for="company_name" value="Nama Perusahaan / Usaha" />
                            <TextInput
                                id="company_name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.company_name"
                                required
                                placeholder="CV Sinar Jaya Material"
                            />
                            <InputError class="mt-2" :message="form.errors.company_name" />
                        </div>

                        <div>
                            <InputLabel for="address" value="Alamat" />
                            <textarea
                                id="address"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                v-model="form.address"
                                required
                                rows="3"
                                placeholder="Jalan, kelurahan, kecamatan, kota, provinsi, kode pos"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.address" />
                        </div>

                        <div>
                            <InputLabel for="tax_id" value="NPWP / NIK" />
                            <TextInput
                                id="tax_id"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.tax_id"
                                placeholder="00.000.000.0-000.000 atau NIK 16 digit"
                            />
                            <InputError class="mt-2" :message="form.errors.tax_id" />
                        </div>
                    </div>
                </section>

                <!-- Info bank -->
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-900">Rekening Pembayaran</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="bank_name" value="Nama Bank" />
                            <TextInput
                                id="bank_name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.bank_name"
                                placeholder="Bank Central Asia"
                            />
                            <InputError class="mt-2" :message="form.errors.bank_name" />
                        </div>
                        <div>
                            <InputLabel for="bank_account_number" value="Nomor Rekening" />
                            <TextInput
                                id="bank_account_number"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.bank_account_number"
                                placeholder="1234567890"
                            />
                            <InputError class="mt-2" :message="form.errors.bank_account_number" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="bank_account_name" value="Atas Nama" />
                            <TextInput
                                id="bank_account_name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.bank_account_name"
                                placeholder="Sesuai nama rekening"
                            />
                            <InputError class="mt-2" :message="form.errors.bank_account_name" />
                        </div>
                    </div>
                </section>

                <!-- Upload logo & tanda tangan -->
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-900">Aset Cetakan</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="logo" value="Logo Usaha" />
                            <input
                                id="logo"
                                type="file"
                                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                                @change="e => pickFile(e, 'logo', logoPreview)"
                            />
                            <p class="mt-1 text-xs text-slate-400">PNG/JPG/SVG, maks 2MB</p>
                            <InputError class="mt-2" :message="form.errors.logo" />
                            <div v-if="logoPreview" class="mt-3 flex items-center gap-3">
                                <img :src="logoPreview" alt="Logo usaha" class="h-12 w-12 rounded-lg border border-slate-200 object-contain" />
                                <button
                                    type="button"
                                    class="text-xs font-medium text-rose-600 hover:text-rose-500"
                                    @click="logoPreview = null; form.logo = null"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="signature" value="Stempel / Tanda Tangan" />
                            <input
                                id="signature"
                                type="file"
                                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                                @change="e => pickFile(e, 'signature', signaturePreview)"
                            />
                            <p class="mt-1 text-xs text-slate-400">Untuk stempel digital pada PDF</p>
                            <InputError class="mt-2" :message="form.errors.signature" />
                            <div v-if="signaturePreview" class="mt-3 flex items-center gap-3">
                                <img :src="signaturePreview" alt="Stempel/tanda tangan" class="h-12 w-24 rounded-lg border border-slate-200 object-contain" />
                                <button
                                    type="button"
                                    class="text-xs font-medium text-rose-600 hover:text-rose-500"
                                    @click="signaturePreview = null; form.signature = null"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-3">
                    <PrimaryButton
                        :class="{ 'opacity-50': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Menyimpan…' : 'Simpan Profil Usaha' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
