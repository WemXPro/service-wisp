<?php

namespace App\Services\Wisp\Api\Application;

use App\Services\Wisp\Api\WispAPI; 

class Egg
{
    protected $endpoint;
    protected $wisp;

    public function __construct()
    {
        $this->endpoint = '/nests';
        $this->wisp = new WispAPI;
    }

    /**
     * Summary of all nests
     * @param int $page
     * @return mixed
     */
    public function nests()
    {
        return collect($this->wisp->api('get', $this->endpoint)['data']);
    }

    /**
     * Summary of all eggs from nest
     * @param int $nest_id
     * @return mixed
    */
    public function getNestsEggs(int $nest_id)
    {
        return $this->wisp->api('get', $this->endpoint . '/' . $nest_id . '/eggs?include=nest,servers,variables')['data'];
    }

    /**
     * Summary of all
     * @return mixed
     */
    public function all()
    {
        $eggs = [];
        foreach($this->nests() as $nest)
        {
            $eggs[] = $this->getNestsEggs($nest['attributes']['id']);
        }

        $eggs = call_user_func_array('array_merge', $eggs);
        return collect($eggs);
    }
}