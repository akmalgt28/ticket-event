<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({ event: { type: Object, required: true } });

// State reaktif untuk menyimpan jumlah setiap tiket yang dipilih
const quantities = reactive(Object.fromEntries(props.event.ticket_types.map((ticket) => [ticket.id, 0])));

// Computed property untuk menghitung total tiket dan estimasi harga
const selectedItems = computed(() => props.event.ticket_types.filter((ticket) => quantities[ticket.id] > 0).map((ticket) => ({ ticket, quantity: quantities[ticket.id] })));
const totalQuantity = computed(() => selectedItems.value.reduce((total, item) => total + item.quantity, 0));
const estimatedTotal = computed(() => selectedItems.value.reduce((total, item) => total + (Number(item.ticket.price) * item.quantity), 0));

// Format mata uang
const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));

// Fungsi untuk melanjutkan ke halaman isi data diri
function lanjutKeDataDiri() {
    if (totalQuantity.value === 0) return;
    
    // Mengarahkan ke rute baru menggunakan helper route() bawaan Laravel/Inertia
    // Kita juga bisa mengirimkan data jumlah tiket melalui URL parameter
    router.get(route('public.events.data-diri', props.event.slug), {
        qty: quantities // Mengirim state quantities agar bisa dibaca di halaman data diri
    });

    // TODO: Arahkan ke rute halaman pengisian data diri Anda. 
    // Contoh menggunakan Inertia router:
    // router.get(route('public.events.checkout.data-diri', props.event.slug), { items: payload });
    
    console.log("Tiket yang dipilih siap dibawa ke halaman selanjutnya:", payload);
    alert("Tombol Beli diklik! Silakan hubungkan dengan router.get() ke halaman data diri Anda di script.");
}
</script>

<template>
    <Head :title="`Pilih Tiket · ${event.title}`" />
    
    <PublicLayout>
        <div class="flex justify-center bg-gray-100 min-h-screen font-sans">
            <!-- Container khusus gaya mobile[cite: 10] -->
            <div class="w-full max-w-[414px] bg-[#10489d] min-h-screen relative shadow-2xl overflow-hidden pb-32">
                
                <!-- Navbar Mobile-Like ARC[cite: 10] -->
                <div class="bg-white flex justify-between items-center p-4 sticky top-0 z-40 shadow-sm">
                    <div class="text-[#10489d] text-2xl font-black italic tracking-tighter">ARC</div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-[#10489d] cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </div>

                <!-- Banner Event[cite: 10] -->
                <div 
                    class="w-full aspect-[4/3] bg-cover bg-center" 
                    :style="event.banner_url ? { backgroundImage: `url(${event.banner_url})` } : { backgroundColor: '#263246' }" 
                    role="img" 
                ></div>

                <div class="p-5">
                    
                    <!-- Loop untuk merender setiap tiket dari database -->
                    <div v-for="ticket in event.ticket_types" :key="ticket.id" class="mb-6">
                        
                        <!-- Badge Status Tiket[cite: 10] -->
                        <span 
                            class="px-4 py-1.5 rounded-full text-xs font-bold inline-block mb-3 shadow-sm"
                            :class="ticket.remaining_quota > 0 ? 'bg-white text-[#10489d]' : 'bg-gray-200 text-[#10489d] opacity-90'"
                        >
                            {{ ticket.remaining_quota > 0 ? 'Tersedia' : 'Habis' }}
                        </span>
                        
                        <!-- Kartu Tiket[cite: 10] -->
                        <div 
                            class="rounded-xl relative overflow-hidden transition-colors duration-200"
                            :class="[
                                ticket.remaining_quota === 0 ? 'bg-[#d8d8d8] opacity-80' : 
                                quantities[ticket.id] > 0 ? 'bg-[#e5e7eb]' : 'bg-white'
                            ]"
                        >
                            <!-- Bagian Atas Kartu (Nama Tiket) -->
                            <div class="p-4 pb-3">
                                <h4 class="font-bold text-gray-900 text-sm">{{ ticket.name }}</h4>
                            </div>
                            
                            <!-- Bagian Bawah Kartu (Harga & Kontrol) dengan efek potongan tiket -->
                            <div class="border-t-[1.5px] border-gray-400 relative p-4 flex justify-between items-center min-h-[70px]">
                                <!-- Lingkaran pemotong kiri & kanan (Efek visual tiket) -->
                                <div class="absolute -left-3 top-[-10px] w-5 h-5 bg-[#10489d] rounded-full"></div>
                                <div class="absolute -right-3 top-[-10px] w-5 h-5 bg-[#10489d] rounded-full"></div>

                                <div class="font-bold text-gray-900 text-sm">
                                    {{ money(ticket.price) }}
                                </div>
                                
                                <!-- Kondisi Logika Tombol[cite: 10] -->
                                <!-- 1. Jika Tiket Habis -->
                                <div v-if="ticket.remaining_quota === 0">
                                    <!-- Tombol tidak muncul jika habis, sesuai desain referensi -->
                                </div>
                                
                                <!-- 2. Jika Tiket Tersedia tapi belum dipilih (Tampil tombol 'Pilih') -->
                                <button 
                                    v-else-if="quantities[ticket.id] === 0" 
                                    @click="quantities[ticket.id] = 1"
                                    class="bg-[#10489d] hover:bg-blue-800 text-white px-6 py-1.5 rounded-full font-bold text-xs transition-colors"
                                >
                                    Pilih
                                </button>
                                
                                <!-- 3. Jika Tiket sudah dipilih (Tampil kontrol + dan -) -->
                                <div v-else class="flex items-center gap-3">
                                    <button 
                                        @click="quantities[ticket.id]--" 
                                        class="w-7 h-7 rounded-full bg-[#10489d] text-white flex items-center justify-center font-bold text-lg leading-none active:scale-95 transition-transform"
                                    >−</button>
                                    <span class="font-bold text-gray-900 text-sm w-4 text-center">{{ quantities[ticket.id] }}</span>
                                    <button 
                                        @click="quantities[ticket.id] < ticket.remaining_quota ? quantities[ticket.id]++ : null" 
                                        class="w-7 h-7 rounded-full bg-[#10489d] text-white flex items-center justify-center font-bold text-lg leading-none active:scale-95 transition-transform"
                                        :class="{'opacity-50 cursor-not-allowed': quantities[ticket.id] >= ticket.remaining_quota}"
                                    >+</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Floating Bottom Bar[cite: 10] -->
                <div class="fixed bottom-0 w-full max-w-[414px] bg-[#f0f4f9] rounded-t-[20px] shadow-[0_-8px_20px_rgba(0,0,0,0.15)] p-5 px-6 flex justify-between items-center z-50">
                    <div>
                        <p class="text-[13px] font-bold text-gray-900 mb-1">
                            {{ totalQuantity > 0 ? `${selectedItems[0].ticket.name} x${totalQuantity}` : 'Pilih tiket' }}
                        </p>
                        <h3 class="text-gray-900 font-extrabold text-sm">
                            {{ money(estimatedTotal) }}
                        </h3>
                    </div>
                    
                    <button 
                        @click="lanjutKeDataDiri"
                        :disabled="totalQuantity === 0"
                        class="bg-[#10489d] text-white px-8 py-2 rounded-full font-bold text-sm transition-all"
                        :class="totalQuantity === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-800 hover:shadow-lg'"
                    >
                        Beli
                    </button>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>