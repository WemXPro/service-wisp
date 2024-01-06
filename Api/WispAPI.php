<?php

namespace App\Services\Wisp\Api;

use App\Services\Wisp\Api\Application\Location;
use App\Services\Wisp\Api\Application\Egg;
use App\Services\Wisp\Api\Application\Node;
use Illuminate\Support\Facades\Http;

class WispAPI
{
    public function locations()
    {
        return new Location($this);
    }

    public function eggs()
    {
        return new Egg($this);
    }

    public function nodes()
    {
        return new Node($this);
    }

    /**
     * Init connection with API
    */
    public function api($method, $endpoint, $data = [])
    {
        $url = settings('wisp::hostname'). '/api/admin' . $endpoint;
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . settings('encrypted::wisp::api_key'),
            'Accept' => 'application/json',
        ])->$method($url, $data);

        if($response->failed())
        {
            // dd($response, $response->json());

            if($response->unauthorized() OR $response->forbidden()) {
                throw new \Exception("[WISP] This action is unauthorized! Confirm that API token has the right permissions");
            }

            // dd($response);
            if($response->serverError()) {
                throw new \Exception("[WISP] Internal Server Error: {$response->status()}");
            }

            throw new \Exception("[WISP] Failed to connect to the API. Ensure the API details and hostname are valid.");
        }

        return $response;
    }
}