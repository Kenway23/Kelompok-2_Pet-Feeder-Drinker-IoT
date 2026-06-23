<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeedingSchedule;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class FeederController extends Controller
{
    // 1. Tampilkan Halaman Utama Dashboard
    public function index()
    {
        $schedules = FeedingSchedule::orderBy('waktu_makan', 'asc')->get();
        return view('dashboard', compact('schedules'));
    }

    // 2. Tampilkan Halaman Manajemen Jadwal Pakan
    public function jadwalIndex()
    {
        $schedules = FeedingSchedule::orderBy('waktu_makan', 'asc')->get();
        
        
        $totalPakanHariIni = $schedules->count();
        
        return view('jadwal', compact('schedules', 'totalPakanHariIni'));
    }

    // 3. Simpan Jadwal Baru & Publish ke MQTT
    public function store(Request $request)
    {
        $request->validate([
            'waktu_makan' => 'required',
            'porsi' => 'required|numeric|min:10',
        ]);

        
        FeedingSchedule::create([
            'waktu_makan' => $request->waktu_makan,
            'porsi' => $request->porsi,
            'is_active' => $request->has('status') ? $request->status : true
        ]);

        
        $this->publishToMqtt('add_schedule', $request->waktu_makan, $request->porsi);

        return redirect()->back()->with('success', 'Jadwal pakan berhasil disimpan dan disinkronkan ke alat!');
    }

    // 4. Saklar Ubah Status Aktif/Nonaktif 
    public function toggleStatus($id)
    {
        $schedule = FeedingSchedule::findOrFail($id);
        
        $schedule->is_active = !$schedule->is_active;
        $schedule->save();

        $action = $schedule->is_active ? 'activate_schedule' : 'deactivate_schedule';
        $this->publishToMqtt($action, $schedule->waktu_makan, $schedule->porsi);

        return redirect()->back()->with('success', 'Status keaktifan jadwal berhasil diperbarui!');
    }

    // 5. Tombol Trigger Manual "Keluarkan Pakan Sekarang" 
    public function feedNow(Request $request)
    {
        $request->validate([
            'porsi_manual' => 'required|numeric|min:10',
        ]);

        $porsi = $request->input('porsi_manual');

        $this->publishToMqtt('feed_now', now()->format('H:i'), $porsi);

        return redirect()->back()->with('success', 'Perintah instan berhasil dikirim! Mengeluarkan pakan sebanyak ' . $porsi . ' gram.');
    }

    // 6. Hapus Jadwal
    public function destroy($id)
    {
        $schedule = FeedingSchedule::findOrFail($id);
        
        $this->publishToMqtt('delete_schedule', $schedule->waktu_makan, $schedule->porsi);
        
        $schedule->delete();

        return redirect()->back()->with('success', 'Jadwal berhasil dihapus dari sistem!');
    }

    private function publishToMqtt($action, $waktu, $porsi)
    {
        try {
            $server = env('MQTT_HOST', 'iot-cat-feeder.cloud.shiftr.io');
            $port = env('MQTT_PORT', 1883);

            $connectionSettings = (new ConnectionSettings)
                ->setUsername(env('MQTT_USERNAME'))
                ->setPassword(env('MQTT_PASSWORD'));
            
            $mqtt = new MqttClient($server, $port, 'laravel_pet_feeder_' . uniqid());
            $mqtt->connect($connectionSettings);

            $payload = json_encode([
                'action' => $action,
                'waktu'  => $waktu,
                'porsi'  => (int)$porsi
            ]);

            $mqtt->publish('pet-feeder/pakan/jadwal', $payload, 0);
            $mqtt->disconnect();
        } catch (\Exception $e) {
            logger('MQTT Error: ' . $e->getMessage());
        }
    }
}