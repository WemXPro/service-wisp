<div class="grid gap-6 mt-6 lg:mt-6 sm:grid-cols-2 lg:grid-cols-4">
    <a href="{{ route('wisp.server.power', ['order' => $order->id, 'action' => 'start']) }}" class="p-6 text-center bg-white rounded-lg border border-gray-200 shadow-md dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 hover:shadow-lg">
        <div class="flex justify-center items-center mx-auto mb-4 w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-900 lg:h-12 lg:w-12">
            <div class="text-2xl text-gray-600 dark:text-gray-400" style="font-size: 30px">
                <i class='bx bx-power-off'></i>
            </div>
        </div>
        <h3 class="mb-2 text-lg font-semibold tracking-tight text-gray-500 dark:text-gray-400">Start</h3>
    </a>
    <a href="{{ route('wisp.server.power', ['order' => $order->id, 'action' => 'stop']) }}" class="p-6 text-center bg-white rounded-lg border border-gray-200 shadow-md dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 hover:shadow-lg">
        <div class="flex justify-center items-center mx-auto mb-4 w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-900 lg:h-12 lg:w-12">
            <div class="text-2xl text-gray-600 dark:text-gray-400" style="font-size: 30px">
                <i class='bx bx-stop'></i>
            </div>
        </div>
        <h3 class="mb-2 text-lg font-semibold tracking-tight text-gray-500 dark:text-gray-400">Stop</h3>
    </a>
    <a href="{{ route('wisp.server.power', ['order' => $order->id, 'action' => 'restart']) }}" class="p-6 text-center bg-white rounded-lg border border-gray-200 shadow-md dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 hover:shadow-lg">
        <div class="flex justify-center items-center mx-auto mb-4 w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-900 lg:h-12 lg:w-12">
            <div class="text-2xl text-gray-600 dark:text-gray-400" style="font-size: 35px">
                <i class='bx bx-refresh' ></i>
            </div>
        </div>
        <h3 class="mb-2 text-lg font-semibold tracking-tight text-gray-500 dark:text-gray-400">Restart</h3>
    </a>
    <a href="{{ route('wisp.server.power', ['order' => $order->id, 'action' => 'kill']) }}" class="p-6 text-center bg-white rounded-lg border border-gray-200 shadow-md dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 hover:shadow-lg">
        <div class="flex justify-center items-center mx-auto mb-4 w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-900 lg:h-12 lg:w-12">
            <div class="text-2xl text-gray-600 dark:text-gray-400" style="font-size: 30px">
                <i class='bx bx-x-circle' ></i>
            </div>
        </div>
        <h3 class="mb-2 text-lg font-semibold tracking-tight text-gray-500 dark:text-gray-400">Kill</h3>
    </a>
</div>