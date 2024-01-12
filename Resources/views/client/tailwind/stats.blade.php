@php

try {
    $response = Http::withHeaders([
            'Authorization' => 'Bearer '. settings('encrypted::wisp::client_api_key'),
            'Accept' => 'Application/vnd.wisp.v1+json',
            'Content-Type' => 'application/json',
    ])->get(settings('wisp::hostname'). "/api/client/servers/{$order->data['identifier']}/resources");

    $status = $response->json()['status'];
    $resources = $response['proc'];

} catch (\Exception $e) {
    $status = 0;
}

if($status == 0) {
    $state = 'Stopped';
} elseif($status == 1) {
    $state = 'Running';
} elseif($status == 2) {
    $state = 'Starting';
} elseif($status == 3) {
    $state = 'Stopping';
} elseif($status == 10) {
    $state = 'Migrating';
} elseif($status == 20) {
    $state = 'Installing';
} elseif($status == 21) {
    $state = 'FailedInstall';
} elseif($status == 30) {
    $state = 'Suspended';
} elseif($status == 31) {
    $state = 'Updating';
} elseif($status == 32) {
    $state = 'Moving';
} elseif($status == 40) {
    $state = 'CreatingBackup';
} elseif($status == 41) {
    $state = 'DeployingBackup';
} else {
    $state = 'Unknown Status';
}

@endphp

<span class="flex items-center text-1xl uppercase font-medium text-gray-900 dark:text-white mb-4">
    <span class="flex w-4 h-4 @if($status == 1 OR $status == 2) bg-emerald-600 @elseif($status == 0 OR $status == 3) bg-red-600 @else bg-orange-600 @endif  rounded-full mr-1.5 flex-shrink-0"></span>
    {{  ucfirst($state)  }}
</span>

@if($status == 1 OR $status == 2)
<div class="flex flex-wrap">
    <div class="w-full md:w-1/3 pr-2 mb-4">
        <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
            <h5 class="mb-2 text-lg font-bold tracking-tight text-gray-900 dark:text-white">CPU Usage</h5>
            <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">{{ number_format($resources['cpu']['total'], 2) }}% / {{ number_format($resources['cpu']['limit'], 2) }}%</p>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ number_format($resources['cpu']['total'], 2) }}%"></div>
            </div>
        </div>

    </div>
    <div class="w-full md:w-1/3 pl-2 pr-2 mb-4">

        <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
            <h5 class="mb-2 text-lg font-bold tracking-tight text-gray-900 dark:text-white">Memory Usage</h5>
            <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">{{ bytesToMB($resources['memory']['total']) }} MB / {{ bytesToMB($resources['memory']['limit']) }} MB</p>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ number_format(($resources['memory']['total'] / $resources['memory']['limit'] * 100), 2) }}%"></div>
            </div>
        </div>

    </div>
    <div class="w-full md:w-1/3 pl-2 mb-4">

        <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
            <h5 class="mb-2 text-lg font-bold tracking-tight text-gray-900 dark:text-white">Disk Usage</h5>
            <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">{{ bytesToMB($resources['disk']['used']) }} MB / {{ bytesToMB($resources['disk']['limit']) }} MB</p>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ number_format(($resources['disk']['used'] / $resources['disk']['limit'] * 100), 2) }}%"></div>
            </div>
        </div>

    </div>
</div>
@endif