<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pet Feeder Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col md:flex-row pb-20 md:pb-0">

    <aside class="w-full md:w-64 bg-[#0B0F19] text-white flex flex-row md:flex-col justify-between md:justify-start p-4 md:p-6 md:min-h-screen shrink-0 z-50 shadow-md">
        <div class="flex items-center space-x-3 md:mb-10">
            <span class="text-2xl">🐾</span>
            <span class="font-bold text-lg tracking-wide">Smart Pet Feeder</span>
        </div>
        <nav class="hidden md:flex flex-col space-y-2 w-full">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 bg-blue-600 px-4 py-3 rounded-xl font-medium text-white shadow-sm">
                <span>🏠</span> <span>Dashboard</span>
            </a>
            <a href="{{ route('jadwal.index') }}" class="flex items-center space-x-3 hover:bg-gray-800 px-4 py-3 rounded-xl font-medium text-gray-400 transition">
                <span>📅</span> <span>Jadwal Pakan</span>
            </a>
        </nav>
        <div class="flex items-center space-x-2 bg-green-950/50 border border-green-800 px-3 py-1 rounded-full text-xs text-green-400">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            <span>Online</span>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-gray-100 px-6 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-500">Monitoring & kontrol alat pakan hewan otomatis</p>
            </div>
        </header>

        @if(session('success'))
        <div class="mx-6 mt-4 p-4 bg-green-100 border border-green-200 text-green-800 rounded-xl text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
        @endif

        <main class="flex-1 p-6 space-y-6 max-w-7xl w-full mx-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="bg-green-50/60 border border-green-100 p-5 rounded-2xl flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 block mb-1">Status Perangkat</span>
                        <span class="text-xl font-bold text-green-700">Online</span>
                    </div>
                    <span class="text-3xl">📶</span>
                </div>
                <div class="bg-blue-50/60 border border-blue-100 p-5 rounded-2xl flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 block mb-1">Waktu Server</span>
                        <span class="text-xl font-bold text-blue-700" id="live-clock">--:--:--</span>
                    </div>
                    <span class="text-3xl">⏰</span>
                </div>
                <div class="bg-amber-50/60 border border-amber-100 p-5 rounded-2xl flex items-center justify-between sm:col-span-2 lg:col-span-1">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 block mb-1">Pakan Terakhir</span>
                        <span class="text-xl font-bold text-amber-700">Selesai</span>
                    </div>
                    <span class="text-3xl">🍖</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                <div class="space-y-6">
                    <section class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm text-center">
    <h2 class="text-sm font-bold text-gray-800 mb-3 text-left">Kontrol Manual</h2>
    <div class="bg-gray-50 border border-gray-100 p-4 rounded-xl space-y-3">
        <span class="text-4xl block">🥣</span>
        <p class="text-xs text-gray-500">Buka katup penampung pakan secara instan sekarang.</p>
        
        <form action="{{ route('feed.now') }}" method="POST" class="space-y-4">
            @csrf
            
            <div class="flex items-center justify-center space-x-2">
                <div class="relative max-w-[100px]">
                    <input 
                        type="number" 
                        name="porsi_manual" 
                        min="10" 
                        value="20" 
                        class="w-full text-center pl-2 pr-2 py-2 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        required
                    >
                </div>
                <span class="text-sm font-semibold text-gray-500 select-none">gram</span>
            </div>
            
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold text-sm py-2.5 rounded-xl transition cursor-pointer shadow-sm">
                Keluarkan Pakan
            </button>
        </form>
    </div>
</section>

                </div>

                <div class="lg:col-span-2 bg-white border border-gray-100 rounded-2xl p-5 shadow-sm overflow-hidden">
                    <h2 class="text-sm font-bold text-gray-800 mb-4">Daftar Jadwal Pakan Aktif</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-xs text-gray-400 font-bold">
                                    <th class="pb-2">No</th>
                                    <th class="pb-2">Waktu</th>
                                    <th class="pb-2">Takaran</th>
                                    <th class="pb-2 text-center">Status</th>
                                    <th class="pb-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 font-medium text-gray-700">
                            @forelse($schedules as $index => $sch)
                            <tr class="{{ !$sch->is_active ? 'opacity-60 bg-gray-50/50' : '' }}">
                            <td class="py-3.5 text-gray-400">{{ $index + 1 }}.</td>
                            <td class="py-3.5 font-bold text-gray-900">⏰ {{ \Carbon\Carbon::parse($sch->waktu_makan)->format('H:i') }} WIB</td>
                            <td class="py-3.5"><span class="bg-amber-50 text-amber-700 text-xs px-2.5 py-1 rounded-md">🍖 {{ $sch->porsi }} gram</span></td>
        
                            <td class="py-3.5 text-center">
                            @if($sch->is_active)
                                <span class="text-[11px] bg-green-50 text-green-700 px-2.5 py-0.5 rounded-full border border-green-100 font-semibold">Aktif</span>
                            @else
                                <span class="text-[11px] bg-gray-100 text-gray-500 px-2.5 py-0.5 rounded-full border border-gray-200 font-semibold">Nonaktif</span>
                            @endif
                            </td>
        
                            <td class="py-3.5 text-right">
                            <form action="{{ route('schedule.destroy', $sch->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-500 p-1 rounded-lg hover:bg-red-50 cursor-pointer">🗑️</button>
                            </form>
                            </td>
                            </tr>
                            @empty
                            <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400 font-normal">Belum ada pengaturan jadwal makan otomatis.</td>
                            </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-[#0B0F19] border-t border-gray-800 flex justify-around p-3 z-50">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center text-blue-500">
            <span>🏠</span><span class="text-[10px]">Dashboard</span>
        </a>
        <a href="{{ route('jadwal.index') }}" class="flex flex-col items-center text-gray-400">
            <span>📅</span><span class="text-[10px]">Jadwal</span>
        </a>
    </div>

    <script>
       
        setInterval(() => {
            const now = new Date();
            document.getElementById('live-clock').innerText = now.toTimeString().split(' ')[0];
        }, 1000);

        
        flatpickr(".timepicker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true
        });
    </script>
</body>
</html>