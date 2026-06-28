<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pakan - Smart Pet Feeder</title>
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
                <a href="{{ route('dashboard') }}" class="mb-2 flex items-center justify-between rounded-2xl px-4 py-3 font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white">
                    <span>Dashboard</span>
                    <span>01</span>
                </a>
                <a href="{{ route('jadwal.index') }}" class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 font-bold text-slate-950 shadow-lg shadow-teal-950/20">
                    <span>Jadwal Pakan</span>
                    <span class="text-teal-600">02</span>
                </a>
            </nav>

            <div class="hidden px-6 pb-6 lg:block">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                    <div class="mb-4 h-28 rounded-2xl pet-blob"></div>
                    <p class="text-sm font-bold text-white">Jadwal otomatis</p>
                    <p class="mt-1 text-xs leading-5 text-slate-400">Setiap perubahan jadwal dipublish ke ESP32 melalui MQTT.</p>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-5 py-6 sm:px-8 lg:flex-row lg:items-end lg:justify-between lg:py-8">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.22em] text-teal-700">Jadwal feeding</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Atur jam makan otomatis</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Kelola waktu dan porsi agar alat memberi pakan sesuai rutinitas peliharaan.</p>
                </div>
                <div class="rounded-2xl border border-white bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Total jadwal</p>
                    <p class="mt-1 text-2xl font-extrabold text-teal-700">{{ $totalPakanHariIni }} x / hari</p>
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

            <main class="mx-auto grid w-full max-w-7xl gap-6 px-5 pb-24 sm:px-8 lg:grid-cols-[1fr_380px] lg:pb-10">
                <section class="overflow-hidden rounded-3xl border border-white bg-white/95 shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-950">Daftar jadwal</h3>
                            <p class="mt-1 text-sm text-slate-500">Aktifkan, nonaktifkan, atau hapus jadwal yang sudah dibuat.</p>
                        </div>
                        <span class="w-fit rounded-full bg-teal-100 px-3 py-1 text-xs font-extrabold text-teal-700">{{ $schedules->where('is_active', true)->count() }} aktif</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-xs font-extrabold uppercase tracking-wider text-slate-400">
                                    <th class="px-5 py-3">No</th>
                                    <th class="px-5 py-3">Waktu</th>
                                    <th class="px-5 py-3">Jenis</th>
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
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 rounded-2xl bg-amber-100"></div>
                                                <div>
                                                    <p class="font-extrabold text-slate-800">Pakan</p>
                                                    <p class="text-xs text-slate-400">Servo feeder</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-extrabold text-amber-800">{{ $sch->porsi }} gram</span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <form action="{{ route('schedule.toggle', $sch->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" aria-label="Ubah status jadwal" class="relative inline-flex h-7 w-12 items-center rounded-full transition {{ $sch->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}">
                                                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition {{ $sch->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                                </button>
                                            </form>
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
                                        <td colspan="6" class="px-5 py-12 text-center text-slate-400">Belum ada jadwal makan di database.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <aside class="space-y-6">
                    <section class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl shadow-slate-200">
                        <div class="pet-blob h-28"></div>
                        <form action="{{ route('schedule.store') }}" method="POST" class="space-y-4 p-6">
                            @csrf
                            <div class="border-b border-white/10 pb-4">
                                <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-200">Tambah jadwal</p>
                                <h3 class="mt-2 text-2xl font-extrabold">Rutinitas baru</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Atur jam dan porsi pakan otomatis.</p>
                            </div>

                            <label class="block rounded-3xl border border-white/10 bg-white/5 p-4">
                                <span class="block text-xs font-bold uppercase tracking-widest text-slate-400">Waktu makan</span>
                                <span class="mt-1 block text-xs font-semibold text-slate-500">Format 24 jam, contoh 19:00</span>
                                <input type="text" name="waktu_makan" id="waktu_makan" placeholder="09:47" maxlength="5" pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$" inputmode="numeric" required class="mt-3 w-full rounded-2xl border border-white/10 bg-white px-4 py-3 text-lg font-extrabold text-slate-950 outline-none transition placeholder:text-slate-300 focus:border-teal-300 focus:ring-4 focus:ring-teal-300/20">
                            </label>

                            <label class="block rounded-3xl border border-white/10 bg-white/5 p-4">
                                <span class="block text-xs font-bold uppercase tracking-widest text-slate-400">Porsi pakan</span>
                                <span class="mt-1 block text-xs font-semibold text-slate-500">Minimal 10 gram</span>
                                <div class="mt-3 flex items-center rounded-2xl bg-white p-2">
                                    <input type="number" min="10" name="porsi" id="porsi" placeholder="ex. 10" required class="w-full rounded-xl border-0 bg-transparent px-3 py-2 text-lg font-extrabold text-slate-950 outline-none placeholder:text-slate-300">
                                    <span class="pr-3 text-sm font-bold text-slate-400">gram</span>
                                </div>
                            </label>

                            <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                <span class="block text-xs font-bold uppercase tracking-widest text-slate-400">Status jadwal</span>
                                <div class="mt-3 flex items-center justify-between rounded-2xl bg-slate-900/80 px-4 py-3">
                                    <span>
                                        <span class="block text-sm font-extrabold" id="txt-switch">Aktif</span>
                                        <span class="block text-xs font-semibold text-slate-500">Langsung dikirim ke alat</span>
                                    </span>
                                    <input type="checkbox" name="status" value="1" id="status-checkbox" checked class="hidden">
                                    <button type="button" onclick="toggleFormStatus()" id="btn-switch" aria-label="Ubah status form" class="relative inline-flex h-7 w-12 items-center rounded-full bg-emerald-500 transition">
                                        <span id="dot-switch" class="inline-block h-5 w-5 translate-x-6 rounded-full bg-white shadow transition"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-1">
                                <button type="reset" class="rounded-2xl border border-white/10 px-4 py-3 text-sm font-extrabold text-slate-300 transition hover:bg-white/10">Batal</button>
                                <button type="submit" class="rounded-2xl bg-teal-400 px-4 py-3 text-sm font-extrabold text-slate-950 shadow-lg shadow-teal-950/30 transition hover:bg-teal-300">Simpan</button>
                            </div>
                        </form>
                    </section>

                    <section class="rounded-3xl border border-white bg-white/90 p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Tips demo</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Buat jadwal satu atau dua menit dari waktu sekarang, lalu lihat log Mosquitto dan Serial Monitor ESP32 saat command diterima.</p>
                    </section>
                </aside>
            </main>
        </div>
    </div>

    <nav class="fixed inset-x-0 bottom-0 z-50 grid grid-cols-2 border-t border-slate-200 bg-white/95 p-2 shadow-2xl lg:hidden">
        <a href="{{ route('dashboard') }}" class="rounded-2xl py-3 text-center text-xs font-extrabold text-slate-500">Dashboard</a>
        <a href="{{ route('jadwal.index') }}" class="rounded-2xl bg-slate-950 py-3 text-center text-xs font-extrabold text-white">Jadwal</a>
    </nav>

    <script>
        function toggleFormStatus() {
            const checkbox = document.getElementById('status-checkbox');
            const btn = document.getElementById('btn-switch');
            const dot = document.getElementById('dot-switch');
            const txt = document.getElementById('txt-switch');

            checkbox.checked = !checkbox.checked;

            if (checkbox.checked) {
                btn.classList.remove('bg-slate-500');
                btn.classList.add('bg-emerald-500');
                dot.classList.remove('translate-x-1');
                dot.classList.add('translate-x-6');
                txt.innerText = 'Aktif';
            } else {
                btn.classList.remove('bg-emerald-500');
                btn.classList.add('bg-slate-500');
                dot.classList.remove('translate-x-6');
                dot.classList.add('translate-x-1');
                txt.innerText = 'Nonaktif';
            }
        }

        const waktuInput = document.getElementById('waktu_makan');
        if (waktuInput) {
            waktuInput.addEventListener('input', () => {
                let value = waktuInput.value.replace(/\D/g, '').slice(0, 4);

                if (value.length >= 3) {
                    value = value.slice(0, 2) + ':' + value.slice(2);
                }

                waktuInput.value = value;
            });
        }
    </script>
</body>
</html>
