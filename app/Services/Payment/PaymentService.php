<?php
namespace App\Services\Payment;

use App\Models\Transaction;
use App\Services\Payment\Gateways\ZibalService;

class PaymentService
{
    protected $gateway;

    public function __construct($gateway = null)
    {
        $this->gateway = $gateway ?? new ZibalService();
    }

    public function createPaymentLink(int $amount, ?int $mobile = null, ?string $callbackUrl = null): string
    {
        return $this->gateway->createPaymentLink($amount, $mobile, $callbackUrl);
    }

    public function verifyTransaction(int $trackId)
    {
        return $this->gateway->verify($trackId);
    }

    public function createOrder($userId, $amount, $planLabel)
    {
        return $this->gateway->createOrder($userId, $amount, $planLabel);
    }

    public function confirmTransaction(array $data)
    {
        $transaction = Transaction::where('ref_id', $data['ref_id'])->first();

        if ($transaction) {
            $transaction->status = $data['status'] ? 'paid' : 'failed';
            $transaction->save();

            if ($transaction->status == 'paid') {
            }
        }
    }
}
