<?php

namespace Tests\Unit;

use App\Services\MqttCommandPublisher;
use PHPUnit\Framework\TestCase;

class MqttCommandPublisherTest extends TestCase
{
    public function test_build_payload_contains_command_security_and_schedule_data(): void
    {
        $publisher = new MqttCommandPublisher();

        $payload = $publisher->buildPayload('feed_now', '08:00', 30, 'petfeeder01', 'secret123');

        $this->assertSame('feed_now', $payload['command']);
        $this->assertSame('petfeeder01', $payload['device_id']);
        $this->assertSame('secret123', $payload['token']);
        $this->assertSame('08:00', $payload['waktu']);
        $this->assertSame(30, $payload['porsi']);
        $this->assertArrayHasKey('timestamp', $payload);
    }
}
