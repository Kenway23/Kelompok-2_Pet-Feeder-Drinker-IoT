<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeedingSchedule;
use App\Services\MqttCommandPublisher;

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


        $published = $this->publishToMqtt('add_schedule', $request->waktu_makan, $request->porsi);

        if (!$published) {
            return redirect()->back()->with('error', 'Jadwal tersimpan, tetapi gagal dikirim ke ESP32. Pastikan Mosquitto aktif dan MQTT_HOST benar.');
        }

        return redirect()->back()->with('success', 'Jadwal pakan berhasil disimpan dan disinkronkan ke alat!');
    }

    // 4. Saklar Ubah Status Aktif/Nonaktif 
    public function toggleStatus($id)
    {
        $schedule = FeedingSchedule::findOrFail($id);

        $schedule->is_active = !$schedule->is_active;
        $schedule->save();

        $action = $schedule->is_active ? 'activate_schedule' : 'deactivate_schedule';
        $published = $this->publishToMqtt($action, $schedule->waktu_makan, $schedule->porsi);

        if (!$published) {
            return redirect()->back()->with('error', 'Status jadwal berubah, tetapi gagal dikirim ke ESP32. Cek koneksi MQTT.');
        }

        return redirect()->back()->with('success', 'Status keaktifan jadwal berhasil diperbarui!');
    }

    // 5. Tombol Trigger Manual "Keluarkan Pakan Sekarang" 
    public function feedNow(Request $request)
    {
        $request->validate([
            'porsi_manual' => 'required|numeric|min:10',
        ]);

        $porsi = $request->input('porsi_manual');

        $published = $this->publishToMqtt('feed_now', now()->format('H:i'), $porsi);

        if (!$published) {
            return redirect()->back()->with('error', 'Perintah gagal dikirim ke ESP32. Pastikan Mosquitto berjalan, MQTT_HOST benar, dan ESP32 sudah MQTT connected.');
        }

        return redirect()->back()->with('success', 'Perintah instan berhasil dikirim! Mengeluarkan pakan sebanyak ' . $porsi . ' gram.');
    }

    // 6. Hapus Jadwal
    public function destroy($id)
    {
        $schedule = FeedingSchedule::findOrFail($id);

        $published = $this->publishToMqtt('delete_schedule', $schedule->waktu_makan, $schedule->porsi);

        $schedule->delete();

        if (!$published) {
            return redirect()->back()->with('error', 'Jadwal terhapus dari website, tetapi gagal dikirim ke ESP32. Cek koneksi MQTT.');
        }

        return redirect()->back()->with('success', 'Jadwal berhasil dihapus dari sistem!');
    }

    private function publishToMqtt($action, $waktu, $porsi): bool
    {
        $publisher = app(MqttCommandPublisher::class);
        return $publisher->publish($action, $waktu, (int) $porsi);
    }
}
