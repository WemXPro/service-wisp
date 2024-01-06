<?php

namespace App\Services\Wisp;

use App\Services\ServiceInterface;
use Illuminate\Support\Facades\Http;
use App\Services\Wisp\Api\WispAPI;
use App\Models\Package;
use App\Models\Order;

class Service implements ServiceInterface
{
    /**
     * Unique key used to store settings 
     * for this service.
     * 
     * @return string
     */
    public static $key = 'wisp'; 

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    
    /**
     * Returns the meta data about this Server/Service
     *
     * @return object
     */
    public static function metaData(): object
    {
        return (object)
        [
          'display_name' => 'Wisp',
          'author' => 'WemX',
          'version' => '1.0.0',
          'wemx_version' => ['dev', '>=1.8.0'],
        ];
    }

    /**
     * Define the default configuration values required to setup this service
     * i.e host, api key, or other values. Use Laravel validation rules for
     *
     * Laravel validation rules: https://laravel.com/docs/10.x/validation
     *
     * @return array
     */
    public static function setConfig(): array
    {
        return [
            [
                "key" => "wisp::hostname",
                "name" => "Hostname",
                "description" => "Hostname of your WISP panel i.e https://panel.example.com",
                "type" => "url",
                "rules" => ['required', 'active_url'], // laravel validation rules
            ],
            [
                "key" => "encrypted::wisp::api_key",
                "name" => "API Key",
                "description" => "API Key of your WISP panel",
                "type" => "password",
                "rules" => ['required'], // laravel validation rules
            ],
        ];
    }

    /**
     * Define the default package configuration values required when creatig
     * new packages. i.e maximum ram usage, allowed databases and backups etc.
     *
     * Laravel validation rules: https://laravel.com/docs/10.x/validation
     *
     * @return array
     */
    public static function setPackageConfig(Package $package): array
    {
        $locations = wisp()->locations()->all()->mapWithKeys(function($item) {
            return [$item['attributes']['id'] => $item['attributes']['long']];
        })->toArray();

        $eggs = wisp()->eggs()->all()->mapWithKeys(function($item) {
            return [$item['attributes']['id'] => $item['attributes']['name']];
        })->toArray();

        return [
            [
                "col" => "col-4",
                "key" => "database_limit",
                "name" => "Database Limit",
                "description" => "The total number of databases a user is allowed to create for this server on Pterodactyl Panel.",
                "type" => "number",
                "min" => 0,
                "rules" => ['required'], // laravel validation rules
            ],
            [
                "col" => "col-4",
                "key" => "allocation_limit",
                "name" => "Allocation Limit",
                "description" => "The total number of allocations a user is allowed to create for this server Pterodactyl Panel.",
                "type" => "number",
                "min" => 0,
                "rules" => ['required'],
            ],
            [
                "col" => "col-4",
                "key" => "backup_limit",
                "name" => "Backup Limit",
                "description" => "The total number of backups that can be created for this server Pterodactyl Panel.",
                "type" => "number",
                "min" => 0,
                "rules" => ['required'],
            ],
            [
                "col" => "col-4",
                "key" => "cpu_limit",
                "name" => "CPU Limit in %",
                "description" => "If you do not want to limit CPU usage, set the value to0. To use a single thread set it to 100%, for 4 threads set to 400% etc",
                "type" => "number",
                "min" => 0,
                "rules" => ['required'],
            ],
            [
                "col" => "col-4",
                "key" => "memory_limit",
                "name" => "Memory Limit in MB",
                "description" => "The maximum amount of memory allowed for this container. Setting this to 0 will allow unlimited memory in a container.",
                "type" => "number",
                "min" => 0,
                "rules" => ['required'],
            ],
            [
                "col" => "col-4",
                "key" => "disk_limit",
                "name" => "Disk Limit in MB",
                "description" => "The maximum amount of memory allowed for this container. Setting this to 0 will allow unlimited memory in a container.",
                "type" => "number",
                "min" => 0,
                "rules" => ['required'],
            ],
            [
                "col" => "col-4",
                "key" => "cpu_pinning",
                "name" => "CPU Pinning (optional)",
                "description" => __('admin.cpu_pinning_desc'),
                "type" => "text",
                "rules" => ['nullable'],
            ],
            [
                "col" => "col-4",
                "key" => "swap_limit",
                "name" => __('admin.swap'),
                "description" => __('admin.swap_desc'),
                "type" => "number",
                "default_value" => 0,
                "rules" => ['required'],
            ],
            [
                "col" => "col-4",
                "key" => "block_io_weight",
                "name" => __('admin.block_io_weight'),
                "description" =>  __('admin.block_io_weight_desc'),
                "type" => "number",
                "default_value" => 500,
                "rules" => ['required'],
            ],

            // locations
            [
                "key" => "locations[]",
                "name" => __('admin.allowed_locations'),
                "description" =>  __('admin.allowed_locations_desc'),
                "type" => "select",
                "options" => $locations,
                "multiple" => true,
                "rules" => ['required'],
            ],
            [
                "key" => "egg_id",
                "name" => __('admin.egg'),
                "description" =>  __('admin.egg_desc'),
                "type" => "select",
                "options" => $eggs,
                "save_only" => true,
                "rules" => ['required'],
            ],
        ];
    }

    /**
     * Define the checkout config that is required at checkout and is fillable by
     * the client. Its important to properly sanatize all inputted data with rules
     *
     * Laravel validation rules: https://laravel.com/docs/10.x/validation
     *
     * @return array
     */
    public static function setCheckoutConfig(Package $package): array
    {
        return [];
    }

    /**
     * Define buttons shown at order management page
     *
     * @return array
     */
    public static function setServiceButtons(Order $order): array
    {
        return [];    
    }

    /**
     * Test API connection
    */
    public static function testConnection()
    {
        if(!function_exists('wisp')) {
            return redirect()->back()->withError('Wisp service has not been enabled');
        }

        try {
            $nodes = wisp()->nodes()->all();
        } catch (\Exception $e) {
            return redirect()->back()->withError($e->getMessage());
        }

        return redirect()->back()->withSuccess('Successfully connected to Wisp API');
    }

    /**
     * This function is responsible for creating an instance of the
     * service. This can be anything such as a server, vps or any other instance.
     * 
     * @return void
     */
    public function create(array $data = [])
    {
        return [];
    }

    /**
     * This function is responsible for upgrading or downgrading
     * an instance of this service. This method is optional
     * If your service doesn't support upgrading, remove this method.
     * 
     * Optional
     * @return void
    */
    public function upgrade(Package $oldPackage, Package $newPackage)
    {
        return [];
    }

    /**
     * This function is responsible for suspending an instance of the
     * service. This method is called when a order is expired or
     * suspended by an admin
     * 
     * @return void
    */
    public function suspend(array $data = [])
    {
        return [];
    }

    /**
     * This function is responsible for unsuspending an instance of the
     * service. This method is called when a order is activated or
     * unsuspended by an admin
     * 
     * @return void
    */
    public function unsuspend(array $data = [])
    {
        return [];
    }

    /**
     * This function is responsible for deleting an instance of the
     * service. This can be anything such as a server, vps or any other instance.
     * 
     * @return void
    */
    public function terminate(array $data = [])
    {
        return [];
    }

}
