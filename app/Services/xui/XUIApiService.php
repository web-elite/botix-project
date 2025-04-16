<?php
namespace App\Services\xui;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XUIApiService
{
    protected $host;
    protected $port;
    protected $webBasePath;
    protected $sessionCookie;
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->host        = env('XUI_HOST');
        $this->port        = env('XUI_PORT');
        $this->webBasePath = env('XUI_BASE_PATH');
        $this->username    = env('XUI_USERNAME');
        $this->password    = env('XUI_PASSWORD');
        if (env('XUI_SSL_ACTIVE')) {
            $this->baseUrl = "https://{$this->host}:{$this->port}/{$this->webBasePath}";
        } else {
            $this->baseUrl = "http://{$this->host}:{$this->port}/{$this->webBasePath}";
        }
        $this->login();
    }

    private function login()
    {
        try {
            $response = Http::asForm()->post("{$this->baseUrl}/login", [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                Log::channel('xui-api')->info('Login successful', ['username' => $this->username]);
                $this->sessionCookie = $response->header('Set-Cookie');
                return true;
            }

            Log::channel('xui-api')->error('Login failed', [
                'username' => $this->username,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::channel('xui-api')->error('ConnectionException during login', [
                'username' => $this->username,
                'message'  => $e->getMessage(),
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::channel('xui-api')->error('RequestException during login', [
                'username' => $this->username,
                'message'  => $e->getMessage(),
                'response' => optional($e->response)->body(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('xui-api')->error('Unexpected error during login', [
                'username' => $this->username,
                'message'  => $e->getMessage(),
            ]);
        }

        return false;
    }

    protected function request($method, $endpoint, $data = [])
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $response = Http::withHeaders([
            'Cookie' => $this->sessionCookie,
        ])->{$method}($url, $data);

        return $this->handleResponse($response, $method, $endpoint, $data);
    }

    protected function handleResponse($response, $method, $endpoint, $data)
    {
        if ($response->successful()) {
            $json = $response->json();
            if ($json['success']) {
                Log::channel('xui-api')->info('API call successful', [
                    'method'   => $method,
                    'endpoint' => $endpoint,
                    'data'     => $data,
                ]);
                return $json['obj'];
            }
        }

        Log::channel('xui-api')->error('API call failed', [
            'method'   => $method,
            'endpoint' => $endpoint,
            'data'     => $data,
            'response' => $response->body(),
        ]);
        return false;
    }

    // دریافت لیست inbounds
    public function getInbounds()
    {
        return $this->request('GET', '/panel/api/inbounds/list');
    }

    // دریافت اطلاعات یک inbound خاص
    public function getInbound($id)
    {
        return $this->request('GET', "/panel/api/inbounds/get/{$id}");
    }

    // اضافه کردن inbound جدید
    public function addInbound($data)
    {
        return $this->request('POST', '/panel/api/inbounds/add', $data);
    }

    // حذف یک inbound
    public function deleteInbound($id)
    {
        return $this->request('POST', "/panel/api/inbounds/del/{$id}");
    }

    // اضافه کردن کلاینت به inbound
    public function addClient($inboundId, $clientData)
    {
        $data = ['id' => $inboundId, 'settings' => json_encode(['clients' => [$clientData]])];
        return $this->request('POST', '/panel/api/inbounds/addClient', $data);
    }

    // دریافت اطلاعات ترافیک یک کلاینت بر اساس ایمیل
    public function getClientTrafficByEmail($email)
    {
        return $this->request('GET', "/panel/api/inbounds/getClientTraffics/{$email}");
    }

    // دریافت اطلاعات ترافیک یک کلاینت بر اساس UUID
    public function getClientTrafficById($uuid)
    {
        return $this->request('GET', "/panel/api/inbounds/getClientTrafficsById/{$uuid}");
    }

    // حذف یک کلاینت
    public function deleteClient($email)
    {
        return $this->request('POST', "/panel/api/inbounds/delClient/{$email}");
    }

    // ریست کردن ترافیک کلاینت
    public function resetClientTraffic($email)
    {
        return $this->request('POST', "/panel/api/inbounds/resetClientTraffic/{$email}");
    }

    // بکاپ گیری و ارسال به تلگرام
    public function createBackup()
    {
        return $this->request('GET', '/panel/api/inbounds/createbackup');
    }

    // دریافت اطلاعات کلی سیستم
    public function getSystemInfo()
    {
        return $this->request('GET', '/panel/api/system/info');
    }

    // دریافت کاربران غیرفعال‌شده
    public function getDeactivatedUsers()
    {
        return $this->request('GET', '/panel/api/inbounds/getDeactivatedUsers');
    }

    // ویرایش تنظیمات یک inbound
    public function updateInbound($data)
    {
        return $this->request('POST', '/panel/api/inbounds/update', $data);
    }

    // ویرایش اطلاعات یک کلاینت
    public function updateClient($data,$uuid)
    {
        return $this->request('POST', "/panel/api/inbounds/updateClient/{$uuid}", $data);
    }

    // تغییر وضعیت فعال/غیرفعال یک inbound
    public function toggleInbound($id)
    {
        return $this->request('POST', "/panel/api/inbounds/toggle/{$id}");
    }

    // دریافت لیست کاربران آنلاین (کلاینت‌هایی که آنلاین هستند)
    public function getOnlineClients()
    {
        return $this->request('POST', '/onlines');
    }

}
