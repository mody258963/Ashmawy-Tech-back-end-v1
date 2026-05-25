<?php

namespace App\Http\Controllers\Iot;

use App\Http\Controllers\Controller;
use App\Repository\Iot\IotDeviceRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\Iot\IotUser $user */
        $user = $request->user('iot-web');

        $devices = $this->devices->paginateForUser($user, 50);

        return view('iot.dashboard', [
            'devices' => $devices,
            'onlineCount' => $devices->getCollection()->where('status', 'online')->count(),
        ]);
    }
}
