<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XUIApiController extends Controller
{
    private $client;
    private $baseUrl;
    private $headers;

    public function __construct()
    {
        $host     = getenv('BOTIX_API_HOST');
        $port     = getenv('BOTIX_API_PORT');
        $hasSSL   = getenv('BOTIX_API_SSL_ACTIVE') ? 's' : '';
        $basePath = $this->ensureLeadingSlash(getenv('BOTIX_API_BASE_PATH'));

        $this->baseUrl = "http{$hasSSL}://{$host}:{$port}{$basePath}";

        $this->headers = [
            'cf-shekan' => 'botix-project',
            'Accept'    => 'application/json',
        ];

        $this->client = new Client([
            'headers' => $this->headers,
        ]);
    }

    private function ensureLeadingSlash($path)
    {
        return ltrim($path, '/') !== $path ? $path : '/' . $path;
    }

    // Login Method
    public function login()
    {
        $username = getenv('BOTIX_API_USERNAME');
        $password = getenv('BOTIX_API_PASSWORD');

        try {
            $response = $this->client->post($this->baseUrl . '/login', [
                'form_params' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ]);
            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error('Login API error', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to login'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Get Inbounds List
    public function getInbounds()
    {
        try {
            $response = $this->client->get('/panel/api/inbounds/list');

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error('Get Inbounds API error', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch inbounds'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Get Inbound Details
    public function getInbound($id)
    {
        try {
            $response = $this->client->get("/panel/api/inbounds/get/{$id}");

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error("Get Inbound Details API error for ID {$id}", ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch inbound details'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Get Client Traffics by Email
    public function getClientTrafficsByEmail($email)
    {
        try {
            $response = $this->client->get("/panel/api/inbounds/getClientTraffics/{$email}");

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error("Get Client Traffics by Email API error for email {$email}", ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch client traffics'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Get Client Traffics by UUID
    public function getClientTrafficsById($uuid)
    {
        try {
            $response = $this->client->get("/panel/api/inbounds/getClientTrafficsById/{$uuid}");

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error("Get Client Traffics by UUID API error for UUID {$uuid}", ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch client traffics'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Add Inbound
    public function addInbound(Request $request)
    {
        try {
            $response = $this->client->post('/panel/api/inbounds/add', [
                'json' => $request->all(),
            ]);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error('Add Inbound API error', ['exception' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json(['error' => 'Failed to add inbound'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Update Inbound
    public function updateInbound($id, Request $request)
    {
        try {
            $response = $this->client->post("/panel/api/inbounds/update/{$id}", [
                'json' => $request->all(),
            ]);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error("Update Inbound API error for ID {$id}", ['exception' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json(['error' => 'Failed to update inbound'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Delete Inbound
    public function deleteInbound($id)
    {
        try {
            $response = $this->client->post("/panel/api/inbounds/del/{$id}");

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error("Delete Inbound API error for ID {$id}", ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete inbound'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Reset Client Traffic
    public function resetClientTraffic($id)
    {
        try {
            $response = $this->client->post("/panel/api/clients/resetTraffic/{$id}");

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error("Reset Client Traffic API error for ID {$id}", ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to reset client traffic'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Add Client to Inbound
    public function addClientToInbound($id, Request $request)
    {
        try {
            $response = $this->client->post("/panel/api/clients/add/{$id}", [
                'json' => $request->all(),
            ]);

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error("Add Client to Inbound API error for ID {$id}", ['exception' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json(['error' => 'Failed to add client to inbound'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Delete Client
    public function deleteClient($id)
    {
        try {
            $response = $this->client->post("/panel/api/clients/del/{$id}");

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error("Delete Client API error for ID {$id}", ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete client'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Reset All Inbound Traffics
    public function resetInboundTraffics()
    {
        try {
            $response = $this->client->post('/panel/api/inbounds/resetAllTraffics');

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error('Reset All Inbound Traffics API error', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to reset all inbound traffics'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Online Clients
    public function getOnlineClients()
    {
        try {
            $response = $this->client->get('/panel/api/clients/online');

            return $this->handleResponse($response);
        } catch (RequestException $e) {
            Log::channel('api')->error('Get Online Clients API error', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch online clients'], 500);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Handle API Response
    private function handleResponse($response)
    {
        try {
            $statusCode = $response->getStatusCode();
            $body       = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                return response()->json($body);
            }

            return response()->json(['error' => $body['message'] ?? 'Request failed'], $statusCode);
        } catch (\Throwable $th) {
            Log::channel('api')->error('In Handle Response Error: ', ['exception' => $e->getMessage()]);
            return response()->json([
                'error'   => 'An unexpected error occurred while handling the API response.',
                'code'    => 500,
                'message' => $th->getMessage(),
            ]);
        }

    }
}
