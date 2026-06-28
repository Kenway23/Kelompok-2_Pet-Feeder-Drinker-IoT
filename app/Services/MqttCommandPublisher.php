<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttCommandPublisher
{
    public function publish(string $command, string $waktu, int $porsi): bool
    {
        try {
            $server = env('MQTT_HOST', '127.0.0.1');
            $port = (int) env('MQTT_PORT', 1883);
            $topic = env('MQTT_TOPIC_COMMAND', 'pet-feeder/pakan/jadwal');
            $deviceId = env('DEVICE_ID', 'petfeeder01');
            $token = env('DEVICE_MQTT_TOKEN', 'change-me');

            $connectionSettings = new ConnectionSettings();

            if (filled(env('MQTT_USERNAME'))) {
                $connectionSettings->setUsername(env('MQTT_USERNAME'));
            }

            if (filled(env('MQTT_PASSWORD'))) {
                $connectionSettings->setPassword(env('MQTT_PASSWORD'));
            }

            $mqtt = new MqttClient($server, $port, 'laravel_pet_feeder_' . uniqid('', true));
            $mqtt->connect($connectionSettings);

            $payload = $this->buildPayload($command, $waktu, $porsi, $deviceId, $token);
            $mqtt->publish($topic, json_encode($payload), 0);
            $mqtt->disconnect();

            return true;
        } catch (\Exception $e) {
            Log::error('MQTT publish failed: ' . $e->getMessage());

            return false;
        }
    }

    public function buildPayload(string $command, string $waktu, int $porsi, ?string $deviceId = null, ?string $token = null): array
    {
        return [
            'command' => $command,
            'device_id' => $deviceId ?? env('DEVICE_ID', 'petfeeder01'),
            'token' => $token ?? env('DEVICE_MQTT_TOKEN', 'change-me'),
            'waktu' => $waktu,
            'porsi' => $porsi,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
