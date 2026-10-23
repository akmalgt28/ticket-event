<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ event: { type: Object, required: true } });

const activeTab = ref('deskripsi');
const activeTabDesktop = ref('deskripsi');

const money = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));

const formatDate = (value) => new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' }).format(new Date(value));
const formatDateMobile = (value) => new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' }).format(new Date(value));

const formatTime = (value) => {
    const time = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' }).format(new Date(value));
    return `${time.replace('.', ':')} WIB`;
};

const lowestPrice = computed(() => {
    if (!props.event.ticket_types || props.event.ticket_types.length === 0) return 0;
    return Math.min(...props.event.ticket_types.map(t => t.price));
});
</script>

<template>
    <Head :title="event.title" />
    
    <PublicLayout>
        
        <!-- ========================================== -->
        <!-- TAMPILAN MOBILE (Hanya muncul di layar HP) -->
        <!-- ========================================== -->
        <div class="block lg:hidden bg-[#10489d] min-h-screen relative font-sans">
            
            <!-- Navbar Mobile (ARC) -->
            <div class="bg-white flex justify-between items-center p-4 sticky top-0 z-40 shadow-sm">
                <div class="text-[#10489d] text-2xl font-black italic tracking-tighter">ARC</div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-[#10489d]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </div>

            <!-- Banner -->
            <div class="w-full aspect-[4/3] bg-cover bg-center" :style="event.banner_url ? { backgroundImage: `url(${event.banner_url})` } : { backgroundColor: '#263246' }"></div>

            <!-- Info Event -->
            <div class="p-5 pb-4">
                <span class="bg-white text-[#10489d] px-3 py-1 rounded-full text-[11px] font-bold tracking-wide">Workshop</span>
                <h1 class="text-white text-xl font-bold mt-3 leading-snug">{{ event.title }}</h1>
                
                <div class="mt-4 space-y-2.5">
                    <div class="flex items-center text-white text-sm">
                        <svg class="w-5 h-5 mr-3 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ formatDateMobile(event.start_at) }}
                    </div>
                    <div class="flex items-center text-white text-sm">
                        <svg class="w-5 h-5 mr-3 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ formatTime(event.start_at) }}
                    </div>
                    <div class="flex items-start text-white text-sm">
                        <svg class="w-5 h-5 mr-3 opacity-90 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>{{ event.location_name }}<br><span class="text-xs opacity-75">{{ event.location_address }}</span></span>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-white font-semibold text-[13px] mb-1.5">Penyelenggara</p>
                    <div class="bg-white text-[#10489d] px-4 py-1.5 rounded-full text-xs font-bold inline-block shadow-sm">
                        {{ event.organizer_name || 'FOKPA × MC KARAWANG BERSATU' }}
                    </div>
                </div>

                <!-- Tab Mobile -->
                <div class="flex mt-8">
                    <button @click="activeTab = 'deskripsi'" :class="['pb-2 text-sm w-1/2 text-center transition-all', activeTab === 'deskripsi' ? 'text-white font-bold border-b-2 border-white' : 'text-blue-300 border-b border-transparent']">Deskripsi</button>
                    <button @click="activeTab = 'syarat'" :class="['pb-2 text-sm w-1/2 text-center transition-all', activeTab === 'syarat' ? 'text-white font-bold border-b-2 border-white' : 'text-blue-300 border-b border-transparent']">Syarat & Ketentuan</button>
                </div>
            </div>

            <!-- Konten Tab Mobile -->
            <div class="bg-[#f0f4f9] rounded-t-2xl p-6 min-h-[50vh] pb-32">
                <div v-if="activeTab === 'deskripsi'">
                    <h2 class="text-xl font-extrabold text-gray-900 border-b-[2.5px] border-gray-900 inline-block pb-0.5 mb-4">Deskripsi</h2>
                    <div class="text-sm text-gray-800 leading-relaxed whitespace-pre-line font-medium">{{ event.description }}</div>
                </div>
                <div v-if="activeTab === 'syarat'">
                    <h2 class="text-xl font-extrabold text-gray-900 border-b-[2.5px] border-gray-900 inline-block pb-0.5 mb-4">Syarat & Ketentuan</h2>
                    <ul class="list-disc list-inside text-sm text-gray-800 leading-relaxed font-medium space-y-2">
                        <li>Tiket resmi hanya dijual melalui situs resmi.</li>
                        <li>Tiket yang sudah dibeli tidak dapat dikembalikan.</li>
                    </ul>
                </div>
            </div>

            <!-- Floating Bottom Bar Mobile -->
            <div class="fixed bottom-0 w-full bg-white rounded-t-[20px] shadow-[0_-8px_20px_rgba(0,0,0,0.1)] p-5 z-50">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-[13px] font-extrabold text-gray-700">Mulai dari</span>
                    <span class="text-[#10489d] font-bold text-lg">{{ lowestPrice > 0 ? money(lowestPrice) : 'Gratis' }}</span>
                </div>
                <Link v-if="event.ticket_types && event.ticket_types.length > 0" :href="route('public.events.checkout', event.slug)" class="block w-full bg-[#10489d] hover:bg-blue-800 text-white text-center py-3 rounded-full font-bold shadow-md">
                    Pesan Sekarang
                </Link>
                <button v-else disabled class="block w-full bg-gray-300 text-gray-500 text-center py-3 rounded-full font-bold cursor-not-allowed">Tiket Belum Tersedia</button>
            </div>
        </div>


        <!-- ========================================== -->
        <!-- TAMPILAN DESKTOP (Hanya muncul di layar PC)-->
        <!-- ========================================== -->
        <div class="hidden lg:block max-w-6xl mx-auto px-8 py-12 font-sans">
            <div class="grid grid-cols-12 gap-16">
                
                <!-- KOLOM KIRI DESKTOP -->
                <div class="col-span-7">
                    <span class="bg-blue-50 text-blue-600 px-3 py-1.5 rounded-full text-xs font-bold flex items-center w-max gap-1.5">Workshop</span>
                    <h1 class="text-4xl font-bold mt-5 text-gray-900 leading-tight">{{ event.title }}</h1>

                    <div class="mt-6 mb-8">
                        <p class="text-gray-500 text-sm mb-3">Penyelenggara</p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-900 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                {{ event.organizer_name ? event.organizer_name.charAt(0) : 'P' }}
                            </div>
                            <span class="font-semibold text-gray-900 text-sm">{{ event.organizer_name || 'Penyelenggara Event' }}</span>
                        </div>
                    </div>

                    <!-- Tab Desktop -->
                    <div class="flex gap-6 mt-10 border-b border-gray-200">
                        <button @click="activeTabDesktop = 'deskripsi'" :class="['pb-4 font-semibold text-sm relative', activeTabDesktop === 'deskripsi' ? 'text-gray-900' : 'text-gray-500']">
                            Deskripsi <span v-if="activeTabDesktop === 'deskripsi'" class="absolute bottom-0 left-0 w-full h-[2px] bg-blue-600"></span>
                        </button>
                        <button @click="activeTabDesktop = 'syarat'" :class="['pb-4 font-semibold text-sm relative', activeTabDesktop === 'syarat' ? 'text-gray-900' : 'text-gray-500']">
                            Syarat & Ketentuan <span v-if="activeTabDesktop === 'syarat'" class="absolute bottom-0 left-0 w-full h-[2px] bg-blue-600"></span>
                        </button>
                    </div>

                    <div class="mt-8">
                        <div v-if="activeTabDesktop === 'deskripsi'">
                            <h3 class="text-xl font-bold mb-4 text-gray-900">Deskripsi</h3>
                            <div class="text-gray-600 leading-relaxed whitespace-pre-line">{{ event.description }}</div>
                        </div>
                        <div v-if="activeTabDesktop === 'syarat'">
                            <h3 class="text-xl font-bold mb-4 text-gray-900">Syarat & Ketentuan</h3>
                            <ul class="list-disc list-inside text-gray-600 leading-relaxed space-y-3">
                                <li>Tiket resmi hanya dijual melalui situs resmi.</li>
                                <li>Tiket yang sudah dibeli tidak dapat dikembalikan.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN DESKTOP -->
                <div class="col-span-5">
                    <div class="sticky top-24 space-y-8">
                        <div class="w-full rounded-2xl overflow-hidden shadow-md">
                            <div class="w-full aspect-[4/3] bg-cover bg-center" :style="event.banner_url ? { backgroundImage: `url(${event.banner_url})` } : { backgroundColor: '#1e293b' }"></div>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold mb-4 text-gray-900">Detail Acara</h3>
                            <div class="space-y-4">
                                <div class="flex items-center gap-4 text-gray-700 text-sm">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>
                                    <span class="font-medium">{{ formatDate(event.start_at) }}</span>
                                </div>
                                <div class="flex items-center gap-4 text-gray-700 text-sm">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                                    <span class="font-medium">{{ formatTime(event.start_at) }} - Selesai</span>
                                </div>
                                <div class="flex items-start gap-4 text-gray-700 text-sm">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 mt-0.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg></div>
                                    <span class="font-medium leading-relaxed">{{ event.location_name }}<br><span class="text-gray-500 font-normal">{{ event.location_address }}</span></span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-2xl p-6 flex flex-col xl:flex-row justify-between items-center gap-4">
                            <div class="w-full xl:w-auto text-left">
                                <p class="text-gray-500 text-sm mb-1">Harga mulai dari</p>
                                <p class="text-xl font-bold text-gray-900">{{ lowestPrice > 0 ? money(lowestPrice) : 'Gratis' }}</p>
                            </div>
                            <Link v-if="event.ticket_types && event.ticket_types.length > 0" :href="route('public.events.checkout', event.slug)" class="w-full xl:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg text-center text-sm">
                                Pesan Sekarang
                            </Link>
                            <button v-else disabled class="w-full xl:w-auto bg-gray-200 text-gray-500 font-bold py-3 px-8 rounded-lg text-center cursor-not-allowed text-sm">Belum Tersedia</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>