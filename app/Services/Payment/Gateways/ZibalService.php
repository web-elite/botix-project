<?php
namespace App\Services\Payment\Gateways;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZibalService
{
    private $trackId;
    private $merchantId;

    public function __construct()
    {
        $this->merchantId = env('ZIBAL_MERCHANT_KEY');
    }

    public function createPaymentLink(int $amount, int $mobile = null, ?string $callbackUrl = null): array
    {
        try {
            $callbackUrl = $callbackUrl ?? 'https://bya2game.ir/thanks';

            $paymentData = [
                'merchant'    => $this->merchantId,
                'amount'      => $amount * 10,
                'callbackUrl' => $callbackUrl,
            ];

            $response = $this->sendToZibal($paymentData);

            if ($response && isset($response['trackId'])) {
                $this->trackId = $response['trackId'];
                return [
                    'url'      => 'https://gateway.zibal.ir/start/' . $this->trackId,
                    'ref_id' => $response['trackId'],
                ];
            }

        } catch (Throwable $e) {
            Log::channel('gateways')->error("Zibal Error in {$context}:", [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'request_data'  => $data,
                'timestamp'     => now()->toDateTimeString(),
            ]);
        }

        return [];
    }

    public function sendToZibal(array $paymentData)
    {
        $url = 'https://gateway.zibal.ir/v1/request';

        try {
            $client   = new Client();
            $response = $client->post($url, [
                'json'    => $paymentData,
                'headers' => ['Content-Type' => 'application/json'],
            ]);

            $responseData = json_decode($response->getBody(), true);

            return $responseData;
        } catch (\Exception $e) {
            Log::channel('gateways')->error('Zibal Payment Error:', [
                'error'   => $e->getMessage(),
                'request' => $paymentData,
            ]);

            return ['status' => 'error', 'message' => 'Payment request failed'];
        }
    }

    public function verify(int $trackId)
    {
        $url = 'https://gateway.zibal.ir/v1/verify';

        $data = [
            'merchant' => $this->merchantId,
            'trackId'  => $trackId,
        ];

        try {
            $client   = new Client();
            $response = $client->post($url, [
                'json'    => $data,
                'headers' => ['Content-Type' => 'application/json'],
            ]);

            $responseData = json_decode($response->getBody(), true);

            Log::channel('gateways')->info('Zibal Verify:', [
                'request'  => $data,
                'response' => $responseData,
            ]);

            if ($responseData['result'] == 100 && $responseData['status'] == 1) {
                return $responseData;
            }

            return null;

        } catch (\Exception $e) {
            Log::channel('gateways')->error('Zibal Verify Error:', [
                'error'   => $e->getMessage(),
                'request' => $data,
            ]);

            return ['status' => 'error', 'message' => 'Verify request failed'];
        }
    }

    public function inquiry(int $trackId)
    {
        $url = 'https://gateway.zibal.ir/v1/inquiry';

        $data = [
            'merchant' => $this->merchantId,
            'trackId'  => $trackId,
        ];

        try {
            $client   = new Client();
            $response = $client->post($url, [
                'json'    => $data,
                'headers' => ['Content-Type' => 'application/json'],
            ]);

            $responseData = json_decode($response->getBody(), true);

            Log::channel('gateways')->info('Zibal Inquiry:', [
                'request'  => $data,
                'response' => $responseData,
            ]);

            if ($responseData['result'] == 100) {
                return $responseData;
            }

            return null;

        } catch (\Exception $e) {
            Log::channel('gateways')->error('Zibal Inquiry Error:', [
                'error'   => $e->getMessage(),
                'request' => $data,
            ]);

            return ['status' => 'error', 'message' => 'Inquiry request failed'];
        }
    }

    public function createOrder($userId, $amount, $planLabel)
    {
        $endpoint = "https://bya2game.ir/wp-json/wc/v3/orders";
        $data     = [
            'payment_method'       => 'zibal',
            'payment_method_title' => 'Zibal Payment Gateway',
            'set_paid'             => false,
            'customer_id'          => $userId,
            'line_items'           => [
                [
                    'name'  => $planLabel,
                    'total' => $amount,
                ],
            ],
        ];

        $response = Http::withBasicAuth('consumer_key', 'consumer_secret')
            ->post($endpoint, $data);

        return $response->json();
    }
}
