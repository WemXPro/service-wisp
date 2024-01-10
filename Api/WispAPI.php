<?php

namespace App\Services\Wisp\Api;

use Illuminate\Support\Facades\Http;

class WispAPI
{
    /**
     * Init connection with API
    */
    public function api($method, $endpoint, $data = [])
    {
        $url = settings('wisp::hostname'). '/api/application' . $endpoint;
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . settings('encrypted::wisp::api_key'),
            'Accept' => 'application/json',
        ])->$method($url, $data);

        if($response->failed())
        {
            dd($response, $response->json(), $url);

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