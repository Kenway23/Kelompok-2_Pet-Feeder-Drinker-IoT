<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pakan - Smart Pet Feeder</title>
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
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 hover:bg-gray-800 px-4 py-3 rounded-xl font-medium text-gray-400 transition">
                <span>🏠</span> <span>Dashboard</span>
            </a>
            <a href="{{ route('jadwal.index') }}" class="flex items-center space-x-3 bg-blue-600 px-4 py-3 rounded-xl font-medium text-white shadow-sm">
                <span>📅</span> <span>Jadwal Pakan</span>
            </a>
        </nav>
        <div class="flex items-center space-x-2 bg-green-950/50 border border-green-800 px-3 py-1 rounded-full text-xs text-green-400">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            <span>Online</span>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-gray-100 px-6 py-6">
            <h1 class="text-2xl font-bold text-gray-900">Jadwal Pakan</h1>
            <p class="text-sm text-gray-500">Atur jadwal pemberian pakan otomatis secara terjadwal</p>
        </header>

        @if(session('success'))
        <div class="mx-6 mt-4 p-4 bg-green-100 border border-green-200 text-green-800 rounded-xl text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
        @endif

        <main class="flex-1 p-6 space-y-6 max-w-7xl w-full mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="w-full sm:w-72 bg-green-50 border border-green-100 p-5 rounded-2xl flex items-center space-x-4 shadow-sm">
                    <span class="text-4xl bg-white p-3 rounded-xl shadow-xs">🥣</span>
                    <div>
                        <span class="text-xs font-semibold text-gray-400 block">Total Jadwal Pakan</span>
                        <span class="text-xl font-bold text-green-700">{{ $totalPakanHariIni }} x / hari</span>
                    </div>
                </div>

                <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
                    <h2 class="text-base font-bold text-gray-800 mb-4">Daftar Jadwal</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-xs text-gray-400 font-bold uppercase tracking-wider">
                                    <th class="pb-3 w-12">No</th>
                                    <th class="pb-3">Waktu</th>
                                    <th class="pb-3">Jenis</th>
                                    <th class="pb-3">Detail</th>
                                    <th class="pb-3">Jumlah</th>
                                    <th class="pb-3 text-center">Status</th>
                                    <th class="pb-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 font-medium text-gray-700">
                                @forelse($schedules as $index => $sch)
                                <tr class="{{ !$sch->is_active ? 'opacity-50' : '' }}">
                                    <td class="py-4 text-gray-400">{{ $index + 1 }}.</td>
                                    <td class="py-4 font-bold text-gray-900">{{ \Carbon\Carbon::parse($sch->waktu_makan)->format('H:i') }}</td>
                                    <td class="py-4 text-gray-500">Makan</td>
                                    <td class="py-4 text-gray-600 font-normal">Memberi pakan</td>
                                    <td class="py-4 font-semibold text-blue-600">{{ $sch->porsi }} gram</td>
                                    <td class="py-4 text-center">
                                        <form action="{{ route('schedule.toggle', $sch->id) }}" method="POST" class="inline-block">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out outline-none {{ $sch->is_active ? 'bg-green-500' : 'bg-gray-200' }}">
                                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out {{ $sch->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-4 text-right">
                                        <form action="{{ route('schedule.destroy', $sch->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500 p-1.5 rounded-lg hover:bg-red-50 transition cursor-pointer">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-gray-400 font-normal">Belum ada daftar jadwal makan di database.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm space-y-5">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Tambah Jadwal</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Buat jadwal baru untuk pemberian pakan otomatis</p>
                </div>

                <form action="{{ route('schedule.store') }}" method="POST" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5">1. Jenis Jadwal</label>
                        <div class="w-full bg-green-50/50 border border-green-100 p-3 rounded-xl flex items-center space-x-3">
                            <span class="text-2xl">🥣</span>
                            <div>
                                <span class="text-xs font-bold text-green-800 block">Pakan</span>
                                <span class="text-[11px] text-green-600 font-normal">Memberi pakan otomatis</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5">2. Waktu</label>
                        <div class="relative flex items-center">
                            <input 
                                type="text" 
                                name="waktu_makan" 
                                id="waktu_makan"
                                placeholder="Pilih Jam"
                                class="timepicker w-full pl-4 pr-12 py-3 bg-gray-50/50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 outline-none transition focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 cursor-pointer" 
                                required
                            >
                            <span class="absolute right-4 text-lg text-gray-400 pointer-events-none select-none">⏰</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Pilih jam berapa pakan akan dikeluarkan secara otomatis.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5">3. Jumlah yang dikeluarkan</label>
                        <div class="relative flex items-center">
                            <input 
                                type="number" 
                                min="10" 
                                name="porsi" 
                                id="porsi"
                                placeholder="60" 
                                class="w-full pl-4 pr-16 py-3 bg-gray-50/50 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 outline-none transition focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100" 
                                required
                            >
                            <span class="absolute right-4 text-sm font-medium text-gray-400 pointer-events-none select-none">gram</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Berapa gram pakan yang dikeluarkan?</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-2">4. Status</label>
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" name="status" value="1" id="status-checkbox" checked class="hidden">
                            <button type="button" onclick="toggleFormStatus()" id="btn-switch" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out outline-none bg-green-500">
                                <span id="dot-switch" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out translate-x-4"></span>
                            </button>
                            <span class="text-xs text-gray-600 font-medium" id="txt-switch">Aktif</span>
                        </div>
                    </div>

                    <div class="flex space-x-2 pt-2">
                        <button type="reset" class="flex-1 border border-gray-200 text-gray-500 text-sm py-2.5 rounded-xl hover:bg-gray-50 font-medium cursor-pointer">Batal</button>
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 rounded-xl shadow-sm transition cursor-pointer">Simpan Jadwal</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-[#0B0F19] border-t border-gray-800 flex justify-around p-3 z-50">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center text-gray-400">
            <span class="text-xl">🏠</span><span class="text-[10px]">Dashboard</span>
        </a>
        <a href="{{ route('jadwal.index') }}" class="flex flex-col items-center text-blue-500">
            <span class="text-xl">📅</span><span class="text-[10px]">Jadwal</span>
        </a>
    </div>

    <script>
        function toggleFormStatus() {
            const checkbox = document.getElementById('status-checkbox');
            const btn = document.getElementById('btn-switch');
            const dot = document.getElementById('dot-switch');
            const txt = document.getElementById('txt-switch');

            checkbox.checked = !checkbox.checked;
            if(checkbox.checked) {
                btn.className = btn.className.replace('bg-gray-200', 'bg-green-500');
                dot.className = dot.className.replace('translate-x-0', 'translate-x-4');
                txt.innerText = 'Aktif';
            } else {
                btn.className = btn.className.replace('bg-green-500', 'bg-gray-200');
                dot.className = dot.className.replace('translate-x-4', 'translate-x-0');
                txt.innerText = 'Nonaktif';
            }
        }

        // Mengaktifkan fitur pop-up jam modern Flatpickr
        flatpickr(".timepicker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true
        });
    </script>
</body>
</html>