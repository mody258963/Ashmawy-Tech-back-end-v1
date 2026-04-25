<?php

namespace App\Livewire\Admin;

use App\Services\Stats\AdminDashboardStatsService;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];

    public function mount(AdminDashboardStatsService $stats): void
    {
        $this->stats = $stats->dashboard();
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
