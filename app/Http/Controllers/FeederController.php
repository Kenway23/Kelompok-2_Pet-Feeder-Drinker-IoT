<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeedingSchedule;
use PhpMqtt\Client\MqttClient;

class FeederController extends Controller
{
    // 1. Tampilkan Halaman Utama Dashboard
    public function index()
    {
        $schedules = FeedingSchedule::orderBy('waktu_makan', 'asc')->get();
        return view('dashboard', compact('schedules'));
    }

    // [REVISI] 2. Tampilkan Halaman Manajemen Jadwal Pakan
    public function jadwalIndex()
    {
        $schedules = FeedingSchedule::orderBy('waktu_makan', 'asc')->get();
        
        // Menghitung jumlah baris jadwal yang ada untuk kartu informasi di UI
        $totalPakanHariIni = $schedules->count();
        
        return view('jadwal', compact('schedules', 'totalPakanHariIni'));
    }

    // [REVISI] 3. Simpan Jadwal Baru & Publish ke MQTT
    public function store(Request $request)
    {
        $request->validate([
            'waktu_makan' => 'required',
            'porsi' => 'required|numeric|min:10',
        ]);

        // Menyimpan ke Database (Membaca checkbox status jika ada, jika tidak otomatis true)
        FeedingSchedule::create([
            'waktu_makan' => $request->waktu_makan,
            'porsi' => $request->porsi,
            'is_active' => $request->has('status') ? $request->status : true
        ]);

        // Kirim Sinyal via MQTT Protokol
        $this->publishToMqtt('add_schedule', $request->waktu_makan, $request->porsi);

        return redirect()->back()->with('success', 'Jadwal pakan berhasil disimpan dan disinkronkan ke alat!');
    }

    // [BARU] 4. Saklar Ubah Status Aktif/Nonaktif (Toggle Status)
    public function toggleStatus($id)
    {
        $schedule = FeedingSchedule::findOrFail($id);
        
        // Membalikkan status (jika true jadi false, jika false jadi true)
        $schedule->is_active = !$schedule->is_active;
        $schedule->save();

        // Tentukan aksi payload MQTT berdasarkan status terbaru
        $action = $schedule->is_active ? 'activate_schedule' : 'deactivate_schedule';
        $this->publishToMqtt($action, $schedule->waktu_makan, $schedule->porsi);

        return redirect()->back()->with('success', 'Status keaktifan jadwal berhasil diperbarui!');
    }

    // 5. Tombol Trigger Manual "Keluarkan Pakan Sekarang"
    public function feedNow()
    {
        // Kirim perintah instan porsi standar ke alat tanpa simpan jadwal
        $this->publishToMqtt('feed_now', now()->format('H:i'), 50);

        return redirect()->back()->with('success', 'Perintah instan berhasil dikirim! Motor pakan berputar.');
    }

    // 6. Hapus Jadwal
    public function destroy($id)
    {
        $schedule = FeedingSchedule::findOrFail($id);
        
        // Beritahu alat bahwa jadwal dengan waktu tersebut dihapus
        $this->publishToMqtt('delete_schedule', $schedule->waktu_makan, $schedule->porsi);
        
        $schedule->delete();

        return redirect()->back()->with('success', 'Jadwal berhasil dihapus dari sistem!');
    }

    // Fungsi Pembantu (Helper) untuk Koneksi MQTT
    private function publishToMqtt($action, $waktu, $porsi)
    {
        try {
            $server = env('MQTT_HOST', 'broker.hivemq.com');
            $port = env('MQTT_PORT', 1883);
            
            $mqtt = new MqttClient($server, $port, 'laravel_pet_feeder_' . uniqid());
            $mqtt->connect();

            $payload = json_encode([
                'action' => $action,
                'waktu'  => $waktu,
                'porsi'  => (int)$porsi
            ]);

            // Publish ke topic khusus pakan hewan
            $mqtt->publish('pet-feeder/pakan/jadwal', $payload, 0);
            $mqtt->disconnect();
        } catch (\Exception $e) {
            // Log error jika broker offline agar web tidak crash saat simulasi koding lokal
            logger('MQTT Error: ' . $e->getMessage());
        }
    }
}