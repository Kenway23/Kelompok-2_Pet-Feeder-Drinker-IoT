<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Pet Feeder Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .paw-bg {
            background-image:
                radial-gradient(circle at 12px 12px, rgba(20, 184, 166, .10) 0 3px, transparent 4px),
                radial-gradient(circle at 28px 16px, rgba(245, 158, 11, .12) 0 2px, transparent 3px),
                radial-gradient(circle at 20px 30px, rgba(14, 165, 233, .10) 0 3px, transparent 4px);
            background-size: 64px 64px;
        }
        .pet-blob {
            background:
                radial-gradient(circle at 50% 26%, #fff7ed 0 15%, transparent 16%),
                radial-gradient(circle at 36% 42%, #fed7aa 0 8%, transparent 9%),
                radial-gradient(circle at 64% 42%, #fed7aa 0 8%, transparent 9%),
                linear-gradient(135deg, #14b8a6, #0ea5e9);
        }
    </style>
</head>
<body class="min-h-screen bg-[#f7fbf7] text-slate-900 paw-bg">
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">
        <aside class="bg-slate-950 text-white lg:min-h-screen lg:sticky lg:top-0">
            <div class="flex items-center justify-between gap-4 px-5 py-4 lg:block lg:p-6">
                <div class="flex items-center gap-3">
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-teal-400 text-slate-950 font-black">PF</div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-200">Pet care</p>
                        <h1 class="text-lg font-extrabold tracking-tight">Smart Feeder</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1.5 text-xs font-bold text-emerald-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                    Online
                </div>
            </div>

            <nav class="hidden px-4 pb-6 lg:block">
                <a href="{{ route('dashboard') }}" class="mb-2 flex items-center justify-between rounded-2xl bg-white px-4 py-3 font-bold text-slate-950 shadow-lg shadow-teal-950/20">
                    <span>Dashboard</span>
                    <span class="text-teal-600">01</span>
                </a>
                <a href="{{ route('jadwal.index') }}" class="flex items-center justify-between rounded-2xl px-4 py-3 font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white">
                    <span>Jadwal Pakan</span>
                    <span>02</span>
                </a>
            </nav>
        </aside>

        <div class="min-w-0">
            <header class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-5 py-6 sm:px-8 lg:flex-row lg:items-end lg:justify-between lg:py-8">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.22em] text-teal-700">Dashboard alat</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Kontrol pakan peliharaan</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Pantau koneksi, cek jadwal aktif, dan kirim perintah feeding langsung dari website.</p>
                </div>
                <div class="rounded-2xl border border-white bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Waktu server</p>
                    <p class="mt-1 text-2xl font-extrabold text-sky-700" id="live-clock">--:--:--</p>
                </div>
            </header>

            @if(session('success'))
                <div class="mx-auto mb-4 max-w-7xl px-5 sm:px-8">
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="mx-auto mb-4 max-w-7xl px-5 sm:px-8">
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 shadow-sm">
                        @if(session('error'))
                            {{ session('error') }}
                        @else
                            {{ $errors->first() }}
                        @endif
                    </div>
                </div>
            @endif

            <main class="mx-auto grid w-full max-w-7xl gap-6 px-5 pb-24 sm:px-8 lg:grid-cols-3 lg:pb-10">
                <section class="lg:col-span-2">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-white bg-white/90 p-5 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Perangkat</p>
                            <p class="mt-3 text-2xl font-extrabold text-emerald-700">Online</p>
                            <p class="mt-2 text-sm text-slate-500">ESP32 siap menerima command.</p>
                        </div>
                        <div class="rounded-3xl border border-white bg-white/90 p-5 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Jadwal aktif</p>
                            <p class="mt-3 text-2xl font-extrabold text-sky-700">{{ $schedules->where('is_active', true)->count() }}</p>
                            <p class="mt-2 text-sm text-slate-500">Jadwal sedang aktif.</p>
                        </div>
                        <div class="rounded-3xl border border-white bg-white/90 p-5 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Status pakan</p>
                            <p class="mt-3 text-2xl font-extrabold text-amber-600">Ready</p>
                            <p class="mt-2 text-sm text-slate-500">Siap untuk demo feeding.</p>
                        </div>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-3xl border border-white bg-white/95 shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-extrabold text-slate-950">Jadwal pakan aktif</h3>
                                <p class="mt-1 text-sm text-slate-500">Ringkasan jadwal yang akan dikirim dan disinkronkan ke alat.</p>
                            </div>
                            <a href="{{ route('jadwal.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700">Atur Jadwal</a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[640px] text-left text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-xs font-extrabold uppercase tracking-wider text-slate-400">
                                        <th class="px-5 py-3">No</th>
                                        <th class="px-5 py-3">Waktu</th>
                                        <th class="px-5 py-3">Porsi</th>
                                        <th class="px-5 py-3 text-center">Status</th>
                                        <th class="px-5 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($schedules as $index => $sch)
                                        <tr class="{{ !$sch->is_active ? 'bg-slate-50/70 text-slate-400' : 'text-slate-700' }}">
                                            <td class="px-5 py-4 font-bold">{{ $index + 1 }}</td>
                                            <td class="px-5 py-4">
                                                <span class="text-base font-extrabold text-slate-950">{{ \Carbon\Carbon::parse($sch->waktu_makan)->format('H:i') }}</span>
                                                <span class="ml-1 text-xs font-bold text-slate-400">WIB</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-extrabold text-amber-800">{{ $sch->porsi }} gram</span>
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                @if($sch->is_active)
                                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-700">Aktif</span>
                                                @else
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-extrabold text-slate-500">Nonaktif</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <form action="{{ route('schedule.destroy', $sch->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-xl border border-red-100 px-3 py-2 text-xs font-bold text-red-600 transition hover:bg-red-50">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">Belum ada jadwal makan otomatis.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <aside class="space-y-6">
                    <section class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl shadow-slate-200">
                        <div class="pet-blob h-36"></div>
                        <div class="p-6">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-200">Kontrol manual</p>
                            <h3 class="mt-2 text-2xl font-extrabold">Keluarkan pakan sekarang</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Perintah dikirim melalui MQTT dan hanya dijalankan ESP32 jika token cocok.</p>

                            <form action="{{ route('feed.now') }}" method="POST" class="mt-5 space-y-4">
                                @csrf
                                <label class="block">
                                    <span class="mb-2 block text-xs font-bold uppercase tracking-widest text-slate-400">Porsi</span>
                                    <div class="flex items-center rounded-2xl bg-white p-2">
                                        <input type="number" name="porsi_manual" min="10" placeholder="ex. 10" required class="w-full rounded-xl border-0 bg-transparent px-3 py-2 text-center text-lg font-extrabold text-slate-950 outline-none placeholder:text-slate-300">
                                        <span class="pr-3 text-sm font-bold text-slate-400">gram</span>
                                    </div>
                                </label>
                                <button type="submit" class="w-full rounded-2xl bg-teal-400 px-5 py-3.5 text-sm font-extrabold text-slate-950 shadow-lg shadow-teal-950/30 transition hover:bg-teal-300">Keluarkan Pakan</button>
                            </form>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-white bg-white/90 p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Alur demo</p>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <div class="flex gap-3"><span class="font-extrabold text-teal-700">1</span><span>Website publish command ke broker MQTT.</span></div>
                            <div class="flex gap-3"><span class="font-extrabold text-teal-700">2</span><span>ESP32 validasi device id dan token.</span></div>
                            <div class="flex gap-3"><span class="font-extrabold text-teal-700">3</span><span>Servo bergerak jika command diterima.</span></div>
                        </div>
                    </section>
                </aside>
            </main>
        </div>
    </div>

    <nav class="fixed inset-x-0 bottom-0 z-50 grid grid-cols-2 border-t border-slate-200 bg-white/95 p-2 shadow-2xl lg:hidden">
        <a href="{{ route('dashboard') }}" class="rounded-2xl bg-slate-950 py-3 text-center text-xs font-extrabold text-white">Dashboard</a>
        <a href="{{ route('jadwal.index') }}" class="rounded-2xl py-3 text-center text-xs font-extrabold text-slate-500">Jadwal</a>
    </nav>

    <script>
        function updateClock() {
            const now = new Date();
            const clock = document.getElementById('live-clock');
            if (clock) clock.innerText = now.toTimeString().split(' ')[0];
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
