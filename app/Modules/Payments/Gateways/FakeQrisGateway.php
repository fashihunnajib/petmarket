<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Data\PaymentChargeRequest;
use App\Modules\Payments\Data\QrisCharge;
use App\Modules\Payments\Exceptions\GatewayUnavailableException;
use Illuminate\Support\Str;

final class FakeQrisGateway implements PaymentGateway
{
    public function __construct(private bool $unavailable = false) {}

    public function createQrisCharge(PaymentChargeRequest $request): QrisCharge
    {
        if ($this->unavailable) {
            throw new GatewayUnavailableException('Gateway QRIS sandbox sedang tidak merespons.');
        }

        $providerReference = 'FAKE-QRIS-'.strtoupper(Str::random(20));
        $payload = $this->buildEmvcoPayload($request->amount, $request->reference);

        return new QrisCharge(
            providerReference: $providerReference,
            qrisPayload: $payload,
            expiresAt: now()->addSeconds($request->expiresInSeconds),
        );
    }

    public function name(): string
    {
        return 'fake-qris-sandbox';
    }

    public function isSandbox(): bool
    {
        return true;
    }

    private function buildEmvcoPayload(int $amount, string $reference): string
    {
        $fields = $this->tlv('00', '01')
            .$this->tlv('01', '12')
            .$this->tlv('52', '0000')
            .$this->tlv('53', '360')
            .$this->tlv('54', (string) $amount)
            .$this->tlv('58', 'ID')
            .$this->tlv('59', 'PETMARKET')
            .$this->tlv('60', 'BANYUWANGI')
            .$this->tlv('62', $this->tlv('05', substr($reference, 0, 25)));

        $withCrcTag = $fields.'6304';

        return $withCrcTag.$this->crc16($withCrcTag);
    }

    private function tlv(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

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
}
