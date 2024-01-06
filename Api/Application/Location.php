<?php

namespace App\Services\Wisp\Api\Application;

use App\Services\Wisp\Api\WispAPI; 

class Location
{
    protected $endpoint;
    protected $wisp;

    public function __construct()
    {
        $this->endpoint = '/locations';
        $this->wisp = new WispAPI;
    }

    /**
     * Summary of all
     * @return mixed
     */
    public function all()
    {
        return collect($this->wisp->api('get', $this->endpoint)['data']);
    }
}