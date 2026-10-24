<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    event: { type: Object, required: true },
    selectedItems: { type: Array, default: () => [] },
});

const form = useForm({
    nama: '',
    wa: '',
    email: '',
    profesi: '',
    brand: '',
    domisili: '',
});

const submitForm = async () => {
    if (form.processing || !props.selectedItems.length) {
        return;
    }

    form.processing = true;

    try {
        const response = await fetch('/api/orders', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_id: props.event.id,
                customer_name: form.nama,
                customer_email: form.email,
                customer_phone: form.wa,
                items: props.selectedItems,
                attendees: props.selectedItems.flatMap(({ quantity }) => Array.from({ length: quantity }, () => ({
                    name: form.nama,
                    email: form.email,
                    phone: form.wa,
                }))),
            }),
        });
        const body = await response.json();

        if (!response.ok) {
            alert(Object.values(body.errors || {}).flat()[0] || body.message || 'Order gagal dibuat.');
            return;
        }

        const paymentResponse = await fetch(`/api/orders/${body.data.id}/payments/manual`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ method: 'bank_transfer', customer_email: form.email }),
        });
        const paymentBody = await paymentResponse.json();

        if (!paymentResponse.ok) {
            alert(Object.values(paymentBody.errors || {}).flat()[0] || paymentBody.message || 'Metode pembayaran gagal dipilih.');
            return;
        }

        alert(`Order ${body.data.order_number} berhasil dibuat. Silakan simpan nomor order untuk cek status pembayaran.`);
        window.location.assign(route('public.orders.status'));
    } catch {
        alert('Koneksi gagal. Coba lagi.');
    } finally {
        form.processing = false;
    }
};
</script>

<template>
    <Head title="Isi Data Diri" />
    
    <PublicLayout>
        <!-- Container Mobile-First -->
        <div class="flex justify-center bg-gray-100 min-h-screen font-sans">
            <div class="w-full max-w-[414px] bg-[#10489d] min-h-screen relative shadow-2xl overflow-hidden pb-10">
                
                <!-- Navbar ARC[cite: 12] -->
                <div class="bg-white flex justify-between items-center p-4 sticky top-0 z-40 shadow-sm">
                    <div class="text-[#10489d] text-2xl font-black italic tracking-tighter">ARC</div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-[#10489d] cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </div>

                <div class="p-6 mt-2">
                    <!-- Badge "Isi Data Diri"[cite: 12] -->
                    <div class="mb-5">
                        <span class="bg-white text-[#10489d] px-5 py-1.5 rounded-full text-sm font-bold inline-block shadow-sm">
                            Isi Data Diri
                        </span>
                    </div>
                    
                    <!-- Card Formulir[cite: 12] -->
                    <div class="bg-white rounded-[20px] p-6 shadow-lg">
                        <form @submit.prevent="submitForm" class="space-y-4">
                            
                            <!-- Input Nama -->
                            <div>
                                <label class="block text-[13px] font-bold text-gray-900 mb-1.5 ml-1">Nama</label>
                                <input 
                                    v-model="form.nama" 
                                    type="text" 
                                    required
                                    class="w-full border-[1.5px] border-blue-400 rounded-lg p-2 text-sm outline-none focus:border-[#10489d] focus:ring-1 focus:ring-[#10489d] transition-all bg-white"
                                >
                            </div>

                            <!-- Input WA -->
                            <div>
                                <label class="block text-[13px] font-bold text-gray-900 mb-1.5 ml-1">Nomor WA aktif</label>
                                <input 
                                    v-model="form.wa" 
                                    type="tel" 
                                    required
                                    class="w-full border-[1.5px] border-blue-400 rounded-lg p-2 text-sm outline-none focus:border-[#10489d] focus:ring-1 focus:ring-[#10489d] transition-all bg-white"
                                >
                            </div>

                            <!-- Input Email -->
                            <div>
                                <label class="block text-[13px] font-bold text-gray-900 mb-1.5 ml-1">Email</label>
                                <input 
                                    v-model="form.email" 
                                    type="email" 
                                    required
                                    class="w-full border-[1.5px] border-blue-400 rounded-lg p-2 text-sm outline-none focus:border-[#10489d] focus:ring-1 focus:ring-[#10489d] transition-all bg-white"
                                >
                            </div>

                            <!-- Input Profesi -->
                            <div>
                                <label class="block text-[13px] font-bold text-gray-900 mb-1.5 ml-1">Status / Profesi saat ini</label>
                                <input 
                                    v-model="form.profesi" 
                                    type="text" 
                                    class="w-full border-[1.5px] border-blue-400 rounded-lg p-2 text-sm outline-none focus:border-[#10489d] focus:ring-1 focus:ring-[#10489d] transition-all bg-white"
                                >
                            </div>

                            <!-- Input Brand -->
                            <div>
                                <label class="block text-[13px] font-bold text-gray-900 mb-1.5 ml-1">Nama Usaha / Brand</label>
                                <input 
                                    v-model="form.brand" 
                                    type="text" 
                                    class="w-full border-[1.5px] border-blue-400 rounded-lg p-2 text-sm outline-none focus:border-[#10489d] focus:ring-1 focus:ring-[#10489d] transition-all bg-white"
                                >
                            </div>

                            <!-- Input Domisili -->
                            <div>
                                <label class="block text-[13px] font-bold text-gray-900 mb-1.5 ml-1">Domisili</label>
                                <input 
                                    v-model="form.domisili" 
                                    type="text" 
                                    class="w-full border-[1.5px] border-blue-400 rounded-lg p-2 text-sm outline-none focus:border-[#10489d] focus:ring-1 focus:ring-[#10489d] transition-all bg-white"
                                >
                            </div>
                            
                            <!-- Tombol Submit[cite: 12] -->
                            <div class="pt-4 pb-2 flex justify-center">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="bg-[#10489d] hover:bg-blue-800 text-white px-10 py-2.5 rounded-full font-bold text-sm transition-colors shadow-md disabled:opacity-50"
                                >
                                    Submit
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>
