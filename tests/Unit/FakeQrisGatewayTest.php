<?php

namespace Tests\Unit;

use App\Modules\Payments\Data\PaymentChargeRequest;
use App\Modules\Payments\Exceptions\GatewayUnavailableException;
use App\Modules\Payments\Gateways\FakeQrisGateway;
use InvalidArgumentException;
use Tests\TestCase;

class FakeQrisGatewayTest extends TestCase
{
    private function crc16(string $data): string
    {
        $crc = 0xFFFF;
        foreach (str_split($data) as $char) {
            $crc ^= ord($char) << 8;
            for ($i = 0; $i < 8; $i++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    public function test_charge_has_sandbox_flag_and_reference(): void
    {
        $gateway = new FakeQrisGateway;

        $this->assertTrue($gateway->isSandbox());
        $this->assertSame('fake-qris-sandbox', $gateway->name());

        $charge = $gateway->createQrisCharge(new PaymentChargeRequest('PAY-ABC', 'ORD-1', 42000, 600));

        $this->assertStringStartsWith('FAKE-QRIS-', $charge->providerReference);
        $this->assertTrue($charge->expiresAt->isFuture());
    }

    public function test_payload_is_valid_emvco_with_correct_crc(): void
    {
        $charge = (new FakeQrisGateway)->createQrisCharge(new PaymentChargeRequest('PAY-XYZ', 'ORD-2', 42000, 600));
        $payload = $charge->qrisPayload;

        $this->assertStringStartsWith('000201', $payload);
        $this->assertStringContainsString('5303360', $payload);
        $this->assertStringContainsString('540542000', $payload);
        $this->assertStringContainsString('5909PETMARKET', $payload);

        $body = substr($payload, 0, -4);
        $crc = substr($payload, -4);

        $this->assertStringEndsWith('6304', $body);
        $this->assertSame($this->crc16($body), $crc, 'CRC16 EMVCo harus valid');
    }

    public function test_gateway_throws_unavailable_exception_when_unavailable(): void
    {
        $gateway = new FakeQrisGateway(unavailable: true);

        $this->expectException(GatewayUnavailableException::class);

        $gateway->createQrisCharge(new PaymentChargeRequest('PAY-DOWN', 'ORD-3', 50000, 600));
    }

    public function test_gateway_rejects_mismatched_split_totals(): void
    {
        $gateway = new FakeQrisGateway;

        $this->expectException(InvalidArgumentException::class);

        $gateway->createQrisCharge(new PaymentChargeRequest(
            reference: 'PAY-BADSPLIT',
            orderNumber: 'ORD-4',
            amount: 50000,
            expiresInSeconds: 600,
            splits: [
                ['tenant_id' => 1, 'gross' => 40000, 'commission' => 4000, 'net' => 36000],
            ],
        ));
    }
}
