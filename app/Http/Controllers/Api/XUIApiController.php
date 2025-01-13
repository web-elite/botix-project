<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class XUIApiController extends Controller
{
    private $host;
    private $port;
    private $basePath;
    private $username;
    private $password;

    public function __construct()
    {
        $this->host = config('xui.host');
        $this->port = config('xui.port');
        $this->basePath = config('xui.base_path');
        $this->username = config('xui.username');
        $this->password = config('xui.password');
    }

    // Login Method
    public function login()
    {
        $response = Http::asForm()->post("http://{$this->host}:{$this->port}{$this->basePath}/login", [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Login failed'], $response->status());
    }

    // Get Inbounds List
    public function getInbounds()
    {
        $response = Http::acceptJson()->get("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/inbounds/list");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch inbounds'], $response->status());
    }

    // Get Inbound Details
    public function getInbound($id)
    {
        $response = Http::acceptJson()->get("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/inbounds/get/{$id}");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch inbound details'], $response->status());
    }

    // Get Client Traffics by Email
    public function getClientTrafficsByEmail($email)
    {
        $response = Http::acceptJson()->get("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/inbounds/getClientTraffics/{$email}");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch client traffics'], $response->status());
    }

    // Get Client Traffics by UUID
    public function getClientTrafficsById($uuid)
    {
        $response = Http::acceptJson()->get("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/inbounds/getClientTrafficsById/{$uuid}");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch client traffics'], $response->status());
    }

    // Add Inbound
    public function addInbound(Request $request)
    {
        $response = Http::acceptJson()->post("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/inbounds/add", $request->all());

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to add inbound'], $response->status());
    }

    // Update Inbound
    public function updateInbound($id, Request $request)
    {
        $response = Http::acceptJson()->post("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/inbounds/update/{$id}", $request->all());

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to update inbound'], $response->status());
    }

    // Delete Inbound
    public function deleteInbound($id)
    {
        $response = Http::acceptJson()->post("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/inbounds/del/{$id}");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to delete inbound'], $response->status());
    }

    // Reset Client Traffic
    public function resetClientTraffic($id)
    {
        $response = Http::acceptJson()->post("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/clients/resetTraffic/{$id}");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to reset client traffic'], $response->status());
    }

    // Add Client to Inbound
    public function addClientToInbound($id, Request $request)
    {
        $response = Http::acceptJson()->post("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/clients/add/{$id}", $request->all());

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to add client to inbound'], $response->status());
    }

    // Delete Client
    public function deleteClient($id)
    {
        $response = Http::acceptJson()->post("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/clients/del/{$id}");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to delete client'], $response->status());
    }

    // Reset All Inbound Traffics
    public function resetInboundTraffics()
    {
        $response = Http::acceptJson()->post("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/inbounds/resetAllTraffics");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to reset all inbound traffics'], $response->status());
    }

    // Online Clients
    public function getOnlineClients()
    {
        $response = Http::acceptJson()->get("http://{$this->host}:{$this->port}{$this->basePath}/panel/api/clients/online");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch online clients'], $response->status());
    }
}
