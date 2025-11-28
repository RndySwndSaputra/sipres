<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIPRES - Sistem Presensi Acara</title>
    <link rel="icon" href="{{ asset('assets/icon/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/landing.css', 'resources/js/landing.js'])

    <style>
        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }

        .blob-bg {
            position: absolute;
            filter: blur(60px); /* Blur dikurangi dikit untuk mobile agar performa ringan */
            z-index: -1;
            opacity: 0.5;
            animation: moveBlob 10s infinite alternate;
        }
        @media (min-width: 768px) {
            .blob-bg { filter: blur(80px); opacity: 0.4; }
        }
        @keyframes moveBlob {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(20px, -20px) scale(1.1); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased overflow-x-hidden relative selection:bg-sipres-green selection:text-white">

    <div class="fixed top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
        <div class="blob-bg top-[-5%] left-[-20%] w-64 h-64 md:w-96 md:h-96 bg-sipres-lime rounded-full mix-blend-multiply"></div>
        <div class="blob-bg top-[-5%] right-[-20%] w-64 h-64 md:w-96 md:h-96 bg-sipres-green rounded-full mix-blend-multiply animation-delay-2000"></div>
        <div class="blob-bg bottom-[-10%] left-[10%] w-72 h-72 md:w-96 md:h-96 bg-blue-200 rounded-full mix-blend-multiply animation-delay-4000"></div>
    </div>

    <nav id="navbar" class="fixed top-0 w-full z-50 transition-all duration-500 ease-in-out bg-transparent py-4 md:py-6" data-aos="fade-down">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            
            <div class="flex items-center gap-3">
                {{-- LOGO NAVBAR: Ukuran Responsif (w-12 di HP, w-16 di Desktop) --}}
                <img src="{{ asset('assets/image/sipres.webp') }}" alt="Logo SIPRES" class="w-20 h-20 md:w-22 md:h-22 object-contain drop-shadow-sm hover:scale-105 transition duration-300">
            </div>
            
            <div>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/admin/dashboard') }}" class="px-4 py-2 md:px-6 md:py-2.5 bg-sipres-green text-white rounded-full hover:bg-emerald-700 transition shadow-lg shadow-emerald-700/20 font-medium text-xs md:text-sm">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="group relative px-5 py-2 md:px-6 md:py-2.5 bg-slate-900 text-white rounded-full hover:bg-slate-800 transition shadow-lg font-medium text-xs md:text-sm overflow-hidden inline-flex items-center gap-2">
                            <span class="relative z-10">Masuk Admin</span>
                            {{-- Icon Panah Kecil untuk Mobile --}}
                            <svg class="w-3 h-3 md:w-4 md:h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            <div class="absolute inset-0 h-full w-full scale-0 rounded-full transition-all duration-300 group-hover:scale-100 group-hover:bg-sipres-green/20"></div>
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <header class="relative pt-28 pb-16 md:pt-40 md:pb-28 px-4 flex flex-col items-center justify-center text-center overflow-hidden">
        
        <div class="relative mb-6 md:mb-8 group" data-aos="zoom-in" data-aos-duration="1000">
            <div class="absolute -inset-4 bg-gradient-to-r from-sipres-lime to-sipres-green rounded-full opacity-20 blur-xl group-hover:opacity-40 transition duration-500"></div>
            {{-- Ukuran Responsif: w-24 (HP) -> w-32 (Tablet) -> w-40 (Desktop) --}}
            <img src="{{ asset('assets/image/logo-besar.png') }}" alt="Logo Besar SIPRES" class="relative w-28 h-28 md:w-36 md:h-36 lg:w-44 lg:h-44 object-contain drop-shadow-xl transform transition group-hover:scale-105 duration-500">
        </div>

        <div class="max-w-3xl mx-auto mb-8 md:mb-10" data-aos="fade-up" data-aos-delay="100">
            {{-- Typography Responsif: text-4xl (HP) -> text-7xl (Desktop) --}}
            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-slate-900 mb-3 md:mb-4 leading-tight">
                Kelola Presensi <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-sipres-green to-emerald-500">Tanpa Ribet.</span>
            </h1>
            <p class="text-base sm:text-lg lg:text-xl text-slate-600 font-medium max-w-xs sm:max-w-xl lg:max-w-2xl mx-auto leading-relaxed">
                Sistem presensi acara modern untuk BKPSDM Karawang. 
                Buat acara, sebar undangan, dan pantau kehadiran tamu secara 
                <span class="font-bold text-sipres-green">Real-Time</span>.
            </p>
        </div>

        <div class="flex flex-wrap justify-center gap-4 md:gap-6 mb-8 md:mb-12" data-aos="fade-up" data-aos-delay="200">
            <div class="group w-16 h-16 md:w-20 md:h-20 bg-white rounded-2xl flex items-center justify-center border border-slate-200 shadow-lg hover:shadow-xl hover:-translate-y-1 transition duration-300">
                <svg class="w-8 h-8 md:w-10 md:h-10 text-sipres-green group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            <div class="group w-16 h-16 md:w-20 md:h-20 bg-white rounded-2xl flex items-center justify-center border border-slate-200 shadow-lg hover:shadow-xl hover:-translate-y-1 transition duration-300">
                <svg class="w-8 h-8 md:w-10 md:h-10 text-sipres-lime group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4h2v-4zM5 6h4v4H5V6zm0 8h4v4H5v-4zm8-8h4v4h-4V6z"></path></svg>
            </div>
            <div class="group w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-sipres-green to-emerald-700 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-600/30 hover:shadow-xl hover:-translate-y-1 transition duration-300">
                <svg class="w-8 h-8 md:w-10 md:h-10 text-white group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
        </div>
    </header>

    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 md:pb-24">
        {{-- Grid: 1 kolom di HP, 2 Kolom di Tablet/Desktop --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-8">
            
            <div class="bg-white rounded-3xl p-6 md:p-8 h-56 md:h-64 relative overflow-hidden group shadow-lg md:shadow-xl shadow-slate-200/60 hover:shadow-2xl transition duration-500 border border-slate-100" data-aos="fade-right">
                <div class="relative z-20">
                    <span class="bg-lime-100 text-lime-700 px-3 py-1 text-[10px] md:text-xs font-bold uppercase tracking-wider rounded-full mb-3 inline-block">User Friendly</span>
                    <h3 class="text-xl md:text-2xl font-bold text-slate-800 mb-2">Coba SIPRES,</h3>
                    <p class="text-sm md:text-base text-slate-500 font-medium">Rasakan kemudahan manajemen <br class="hidden md:block"> acara dalam satu genggaman.</p>
                </div>
                <div class="absolute -bottom-6 -right-6 w-40 md:w-48 h-32 md:h-36 bg-slate-50 rounded-tl-2xl border border-slate-200 shadow-inner flex flex-col p-3 gap-2 group-hover:scale-105 group-hover:-translate-y-2 transition duration-500">
                    <div class="w-full h-2 bg-slate-200 rounded-full"></div>
                    <div class="w-2/3 h-2 bg-slate-200 rounded-full mb-2"></div>
                    <div class="flex gap-2">
                        <div class="w-1/2 h-12 md:h-16 bg-blue-50 rounded-lg border border-blue-100"></div>
                        <div class="w-1/2 h-12 md:h-16 bg-white rounded-lg border border-slate-100"></div>
                    </div>
                </div>
            </div>

            <div class="bg-sipres-green rounded-3xl p-6 md:p-8 h-56 md:h-64 relative overflow-hidden group shadow-lg md:shadow-xl shadow-emerald-900/20 hover:shadow-2xl transition duration-500" data-aos="fade-left" data-aos-delay="100">
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>
                <div class="relative z-20">
                    <span class="bg-white/20 backdrop-blur text-white px-3 py-1 text-[10px] md:text-xs font-bold uppercase tracking-wider rounded-full mb-3 inline-block border border-white/30">Efisien</span>
                    <h3 class="text-xl md:text-2xl font-bold text-white mb-2">Kelola Kehadiran</h3>
                    <p class="text-sm md:text-base text-emerald-100 font-medium">Tamu undangan terdata dengan <br class="hidden md:block"> cepat dan tanpa antrian.</p>
                </div>
                 <div class="absolute bottom-5 right-5 w-14 h-14 md:w-16 md:h-16 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 flex items-center justify-center group-hover:rotate-12 transition duration-500">
                    <svg class="w-6 h-6 md:w-8 md:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-[#ebfccb] rounded-3xl p-6 md:p-8 h-56 md:h-64 relative overflow-hidden group shadow-lg md:shadow-xl shadow-lime-200/50 hover:shadow-2xl transition duration-500 border border-lime-200" data-aos="fade-right" data-aos-delay="100">
                <div class="relative z-20">
                    <span class="bg-emerald-600 text-white px-3 py-1 text-[10px] md:text-xs font-bold uppercase tracking-wider rounded-full mb-3 inline-block">Event Maker</span>
                    <h3 class="text-xl md:text-2xl font-bold text-slate-900 mb-2">Buat Acara</h3>
                    <p class="text-sm md:text-base text-slate-700 font-medium">Setup acara dinas atau publik <br class="hidden md:block"> hanya dalam hitungan menit.</p>
                </div>
                 <div class="absolute bottom-0 right-0 translate-x-4 translate-y-4 w-32 h-32 md:w-40 md:h-40 bg-lime-400/30 rounded-full blur-2xl group-hover:blur-xl transition duration-700"></div>
                 <div class="absolute bottom-6 right-6 md:bottom-8 md:right-8 w-12 h-12 md:w-14 md:h-14 bg-white rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition duration-300 z-10">
                    <svg class="w-5 h-5 md:w-6 md:h-6 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                 </div>
            </div>

            <div class="bg-slate-900 rounded-3xl p-6 md:p-8 h-56 md:h-64 relative overflow-hidden group shadow-lg md:shadow-xl shadow-slate-900/30 hover:shadow-2xl transition duration-500" data-aos="fade-left" data-aos-delay="200">
                <div class="relative z-20">
                    <span class="bg-slate-700 text-slate-200 px-3 py-1 text-[10px] md:text-xs font-bold uppercase tracking-wider rounded-full mb-3 inline-block border border-slate-600">Real-Time Data</span>
                    <h3 class="text-xl md:text-2xl font-bold text-white mb-2">Analisis Lengkap</h3>
                    <p class="text-sm md:text-base text-slate-400 font-medium">Pantau grafik kehadiran dan <br class="hidden md:block"> unduh laporan pasca acara.</p>
                </div>
                <div class="absolute bottom-6 right-6 w-28 md:w-36 h-20 md:h-24 flex items-end gap-2 opacity-80 group-hover:opacity-100 transition duration-500">
                    <div class="w-6 md:w-8 bg-emerald-500 rounded-t-md h-8 md:h-10 group-hover:h-14 transition-all duration-500 delay-75"></div>
                    <div class="w-6 md:w-8 bg-sipres-lime rounded-t-md h-12 md:h-16 group-hover:h-20 transition-all duration-500 delay-100"></div>
                    <div class="w-6 md:w-8 bg-blue-500 rounded-t-md h-10 md:h-12 group-hover:h-16 transition-all duration-500 delay-150"></div>
                </div>
            </div>

        </div>
    </section>

    <footer class="border-t border-slate-200 bg-white/50 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto py-8 md:py-12 px-6 flex flex-col items-center justify-center">
            <div class="flex items-center gap-3 mb-4 opacity-80 hover:opacity-100 transition">
                <img src="{{ asset('assets/image/logo-karawang.png') }}" alt="Logo Karawang" class="h-8 md:h-10 w-auto">
                <div class="h-6 md:h-8 w-px bg-slate-300"></div>
                <img src="{{ asset('assets/image/sipres.webp') }}" alt="Logo SIPRES" class="h-6 md:h-8 w-auto grayscale hover:grayscale-0 transition duration-300">
            </div>
            <p class="text-[10px] md:text-xs text-slate-400 font-bold tracking-widest uppercase text-center leading-relaxed">
                © 2025 Badan Kepegawaian dan Pengembangan Sumber Daya Manusia<br>
                Kabupaten Karawang
            </p>
        </div>
    </footer>

    <script>
        const navbar = document.getElementById('navbar');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                // Mode Scroll: Putih Kaca, Padding Kecil
                navbar.classList.remove('bg-transparent', 'py-4', 'md:py-6');
                navbar.classList.add('bg-white/90', 'backdrop-blur-md', 'shadow-sm', 'border-b', 'border-slate-200/50', 'py-2', 'md:py-3');
            } else {
                // Mode Atas: Transparan, Padding Besar
                navbar.classList.add('bg-transparent', 'py-4', 'md:py-6');
                navbar.classList.remove('bg-white/90', 'backdrop-blur-md', 'shadow-sm', 'border-b', 'border-slate-200/50', 'py-2', 'md:py-3');
            }
        });
    </script>

</body>
</html>