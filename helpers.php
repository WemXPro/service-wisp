<?php 

if(!function_exists('wisp')) {
    function wisp()
    {
        return new \App\Services\Wisp\Api\WispAPI;
    }
}