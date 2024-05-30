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
        // Check if the URL ends with a slash
        $doesNotEndWithSlash = function ($attribute, $value, $fail) {
            if (preg_match('/\/$/', $value)) {
                return $fail('Hostname URL must not end with a slash "/". It should be like https://panel.example.com');
            }
        };
        
        return [
            [
                "key" => "wisp::hostname",
                "name" => "Hostname",
                "description" => "Hostname of your WISP panel i.e https://panel.example.com",
                "type" => "url",
                "rules" => ['required', 'active_url', $doesNotEndWithSlash], // laravel validation rules
            ],
            [
                "key" => "encrypted::wisp::api_key",
                "name" => "API Key",
                "description" => "API Key of your WISP panel",
                "type" => "password",
                "rules" => ['required'], // laravel validation rules
            ],
            [
                "key" => "encrypted::wisp::client_api_key",
                "name" => "Client API Key",
                "description" => "Admin Client API Key of your WISP panel",
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
        $collected_locations = collect(Service::api('get', '/locations')['data']);
        $locations = $collected_locations->mapWithKeys(function($item) {
            return [$item['attributes']['id'] => $item['attributes']['long']];
        })->toArray();

        $config = [
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
                "key" => "backup_limit_size",
                "name" => "Backup Size Limit in MB",
                "description" => "The total size of backups that can be created for this server Pterodactyl Panel.",
                "type" => "number",
                "min" => 0,
                "rules" => ['required', 'numeric'],
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
            [
                "col" => "col-12",
                "key" => "locations[]",
                "name" => "Selectable Locations",
                "description" =>  "The locations that can be selected at checkout by the user.",
                "type" => "select",
                "options" => $locations,
                "multiple" => true,
                "rules" => ['required'],
            ],
            [
                "key" => "nest_id",
                "name" => "Nest ID",
                "description" =>  "Nest ID of the server you want to use for this package. You can find the nest ID by going to the egg page and looking at the URL. It will be the number at the end of the URL.",
                "type" => "text",
                "default_value" => 2, // "Minecraft
                "rules" => ['required', 'numeric'],
            ],
            [
                "key" => "egg_id",
                "name" => "Egg ID",
                "description" =>  "Egg ID of the server you want to use for this package. You can find the egg ID by going to the egg page and looking at the URL. It will be the number at the end of the URL.",
                "type" => "text",
                "default_value" => 2, // "Minecraft
                "rules" => ['required', 'numeric'],
            ],
            [
                "key" => "dedicated_IP",
                "name" => "Dedicated IP",
                "description" =>  "If you want to assign a dedicated IP to this server, set this to true.",
                "type" => "bool",
                "rules" => ['boolean'],
            ],
        ];

        try {
            $egg = Service::api('get', "/nests/{$package->data('nest_id', 2)}/eggs/{$package->data('egg_id', 2)}", ['include' => 'variables'])->collect();
            
            $config = array_merge($config, [
                [
                    "col" => "col-12",
                    "key" => "docker_image",
                    "name" => "Docker Image",
                    "description" =>  __('admin.docker_image_desc'),
                    "type" => "text",
                    "default_value" => $egg['attributes']['docker_image'],
                    "rules" => ['required'],
                ],
                [
                    "col" => "col-12",
                    "key" => "startup",
                    "name" => "Start Up Command",
                    "description" =>  "The command that will be executed when the server is started. This is usually the command to start the server. i.e java -Xms128M -Xmx128M -jar server.jar",
                    "default_value" => $egg['attributes']['startup'],

                    "type" => "text",
                    "rules" => ['required'],
                ],
            ]);

            foreach($egg['attributes']['relationships']['variables']['data'] as $variable) {
                $config[] = [
                    "col" => "col-4",
                    "key" => "environment[{$variable['attributes']['env_variable']}]",
                    "name" => $variable['attributes']['name'],
                    "description" => $variable['attributes']['description'],
                    "type" => "text",
                    "default_value" => $variable['attributes']['default_value'],
                    "rules" => explode('|', $variable['attributes']['rules'] ?? 'nullable'),
                ];
            }

        } catch (\Exception $e) {
            ErrorLog('wisp::setPackageConfig', $e->getMessage());
            return $config;
        }

        return $config;
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
        $collected_locations = collect(Service::api('get', '/locations')['data']);
        $locations = $collected_locations->mapWithKeys(function($item) use ($package) {
            if(!in_array($item['attributes']['id'], $package->data('locations', []))) {
                return [];
            }
            return [$item['attributes']['id'] => $item['attributes']['long']];
        })->toArray();

        return [
            [
                "col" => "w-full",
                "key" => "location",
                "name" => "Location",
                "description" =>  "The location where this server will be deployed.",
                "type" => "select",
                "options" => $locations,
                "rules" => ['required'],
            ],
        ];
    }

    /**
     * Define buttons shown at order management page
     *
     * @return array
     */
    public static function setServiceButtons(Order $order): array
    {
        return [
            [
                "name" => "Login to Game Panel",
                "color" => "primary",
                "href" => settings('wisp::hostname'),
                "target" => "_blank", // optional
            ],
        ];    
    }

    /**
     * Test API connection
    */
    public static function testConnection()
    {
        try {
            $nodes = Service::api('get', '/nodes')->collect();
        } catch (\Exception $e) {
            return redirect()->back()->withError($e->getMessage());
        }

        return redirect()->back()->withSuccess('Successfully connected to Wisp API');
    }

    /**
     * Init connection with API
    */
    public static function api($method, $endpoint, $data = [], $type = 'application')
    {
        $url = settings('wisp::hostname'). "/api/{$type}{$endpoint}";
        
        $apiKey = $type == 'application' ? settings('encrypted::wisp::api_key') : settings('encrypted::wisp::client_api_key');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])->$method($url, $data);

        if($response->failed())
        {
           // dd($response, $response->json(), $url);

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

    public static function createWispUser($order)
    {
        $user = $order->user;
        // check if a user with same email exists on wisp
        try {
            $wisp_user = Service::api('get', '/users', ['search' => $user->email])->collect();
            if(isset($wisp_user['data'][0])) {
                $wisp_user = $wisp_user['data'][0];

                // ensure the email is the same
                if($wisp_user['attributes']['email'] == $user->email) {
                    $order->createExternalUser([
                        'external_id' => $wisp_user['attributes']['id'],
                        'username' => $wisp_user['attributes']['email'],
                        'data' => $wisp_user['attributes'],
                        'password' => '',
                    ]);
                    return;
                }
            }

            // create user on wisp
            $password = str_random(12);
            $wisp_user = Service::api('post', '/users', [
                'external_id' => "wemx-{$user->id}",
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'password' => $password,
                'root_admin' => false,
            ])->collect();

            $order->createExternalUser([
                'external_id' => $wisp_user['attributes']['id'],
                'username' => $wisp_user['attributes']['email'],
                'password' => $password,
                'data' => $wisp_user['attributes'],
            ]);

            // send email to user
            $user->email([
                'subject' => 'Game Panel Account Created',
                'content' => "Your account has been created on the game panel. You can login using the following details: <br><br> Email: {$wisp_user['attributes']['email']} <br> Password: {$password}",
            ]);

        } catch (\Exception $e) {
            ErrorLog('wisp::createWispUser', $e->getMessage());
            throw new \Exception("[WISP] Failed to create user on Wisp. Error: {$e->getMessage()}");
        }
    }

    /**
     * Change the Wisp password
     */
    public function changePassword(Order $order, string $newPassword)
    {
        try {
            $wispUser = $order->getExternalUser()->data;

            $response = Service::api('patch', "/users/{$wispUser['id']}", [
                'first_name' => $order->user->first_name,
                'last_name' => $order->user->last_name,
                'email' => $order->user->email,
                'password' => $newPassword,
            ]);

            $order->updateExternalPassword($newPassword);
        } catch (\Exception $error) {
            return redirect()->back()->withError("Something went wrong, please try again.");
        }

        return redirect()->back()->withSuccess("Password has been changed");
    }

    /**
     * This function is responsible for creating an instance of the
     * service. This can be anything such as a server, vps or any other instance.
     * 
     * @return void
     */
    public function create(array $data = [])
    {
        $order = $this->order;
        $package = $this->order->package;
        $user = $this->order->user;

        if(!$order->hasExternalUser()) {
            self::createWispUser($order);
        }

        $response = Service::api('post', '/servers', [
            "name" => "{$order->user->username} {$order->package->name} Server",
            'external_id' => "wemx-{$order->id}",
            'description' => settings('app_name', 'WemX') . " || {$this->order->user->username}", 
            "user" => $order->getExternalUser()->external_id,
            "nest" => $package->data('nest_id', 2),
            "egg" => $package->data('egg_id', 2),
            "docker_image" => $package->data('docker_image'),
            "startup" => $package->data('startup'),
            "environment" => $package->data('environment', []),
            "limits" => [
                "memory" => $package->data('memory_limit', 0),
                "swap" => $package->data('swap_limit', 0),
                "disk" => $package->data('disk_limit', 0),
                "io" => $package->data('block_io_weight', 500),
                "cpu" => $package->data('cpu_limit', 0),
            ],
            "feature_limits" => [
                "databases" => $package->data('database_limit', 0),
                "backup_megabytes_limit" => $package->data('backup_limit_size', 0),
            ],
            "deploy" => [
                "locations" => $order->options['location'] ? [$order->options['location']] : $package->data('locations', []),
                "dedicated_ip" => $package->data('dedicated_IP', false),
                "port_range" => [],
            ],
            "start_on_completion" => true,
            "skip_scripts" => false,
            "oom_disabled" => false,
            "swap_disabled" => false,
        ]);

        if($response->failed()) {
            ErrorLog('wisp::create', $response->json() . $response);
        }

        if(!isset($response['attributes'])) {
            if(isset($response['errors']) AND $response['errors'][0]['code'] == 'admin.servers.deployment.no_viable_nodes') {
                throw new \Exception("[WISP] No viable nodes found for deployment within the allowed locations. Please ensure the nodes arent full.");
            }

            if(isset($response['errors']) AND $response['errors'][0]['code'] == 'admin.servers.deployment.no_viable_allocations') {
                throw new \Exception("[WISP] No viable allocations found for deployment within the allowed locations. Please ensure the nodes have available allocations");
            }

            throw new \Exception("[WISP] Failed to create server on Wisp.");
        }

        $server = $response['attributes'];
        $order->update([
            'data' => $server,
        ]);
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
        $order = $this->order;
        $server = $order->data;
        $response = Service::api('put', "/servers/{$server['id']}/build", [
            "allocation_id" => $server['allocation'],
            "memory" => $newPackage->data('memory_limit', 0),
            "swap" => $newPackage->data('swap_limit', 0),
            "disk" => $newPackage->data('disk_limit', 0),
            "io" => $newPackage->data('block_io_weight', 500),
            "cpu" => $newPackage->data('cpu_limit', 0),
            "database_limit" => $newPackage->data('database_limit', 0),
            "backup_megabytes_limit" => $newPackage->data('backup_limit_size', 0),
        ], 'admin');

        if($response->failed()) {
            throw new \Exception("[WISP] Failed to upgrade server on Wisp. Error: {$response->json()} Response: {$response}");
        }
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
        $order = $this->order->data;
        $response = Service::api('post', "/servers/{$order['id']}/suspend");
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
        $order = $this->order->data;
        $response = Service::api('post', "/servers/{$order['id']}/unsuspend");
    }

    /**
     * This function is responsible for deleting an instance of the
     * service. This can be anything such as a server, vps or any other instance.
     * 
     * @return void
    */
    public function terminate(array $data = [])
    {
        $order = $this->order->data;
        $response = Service::api('delete', "/servers/{$order['id']}");
    }

    /**
     * Send a power action to the server
     * 
     * @return void
    */
    public function powerAction(Order $order, string $action)
    {
        if(!in_array($action, ['start', 'stop', 'restart', 'kill'])) {
            return redirect()->back()->withError("Power action is not supported");
        }

        $server = $order->data;
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '. settings('encrypted::wisp::client_api_key'),
                'Accept' => 'Application/vnd.wisp.v1+json',
                'Content-Type' => 'application/json',
            ])->post(settings('wisp::hostname'). "/api/client/servers/{$server['identifier']}/power", [
                'signal' => $action,
            ]);

            if($response->failed()) {
                throw new \Exception("[WISP] Failed to perform power action server on Wisp. Error: {$response->json()} Response: {$response}");
            }

            sleep(3);

            return redirect()->back()->withSuccess("Power action has been sent");
        } catch (\Exception $e) {
            ErrorLog('wisp::powerAction', $response->json());
            return redirect()->back()->withError("Something went wrong, please try again.");
        }
    }


}
