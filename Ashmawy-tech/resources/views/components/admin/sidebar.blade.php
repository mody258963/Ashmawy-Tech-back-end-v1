@php($moderator = auth()->user()?->isModerator())
<aside class="main-sidebar sidebar-dark-primary elevation-4 ashmawy-sidebar">
    <a href="{{ $moderator ? route('admin.orders.index') : route('admin.dashboard') }}" class="brand-link d-flex align-items-center justify-content-center" style="min-height: 4rem;">
        <img src="{{ asset('storage/ashmawy-logo.png') }}" alt="Ashmawy Tech Logo" class="brand-image img-circle elevation-2" style="opacity: .95; max-height: 46px; width: auto;">
        <span class="brand-text font-weight-bold text-white ml-2">ASHMAWY</span>
    </a>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                @if ($moderator)
                    <li class="nav-item">
                        <a href="{{ route('admin.customers.index') }}" class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <p>{{ __('messages.customers') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.devices.index') }}" class="nav-link {{ request()->routeIs('admin.devices.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-mobile-alt"></i>
                            <p>{{ __('messages.devices') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-clipboard-list"></i>
                            <p>{{ __('messages.orders') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>{{ __('messages.payments') }}</p>
                        </a>
                    </li>
                @else
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>{{ __('messages.dashboard') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.branches.index') }}" class="nav-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-code-branch"></i>
                            <p>{{ __('messages.branches') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.appointments.index') }}" class="nav-link {{ request()->routeIs('admin.appointments.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>{{ __('messages.appointments') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>{{ __('messages.users') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.customers.index') }}" class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <p>{{ __('messages.customers') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.devices.index') }}" class="nav-link {{ request()->routeIs('admin.devices.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-mobile-alt"></i>
                            <p>{{ __('messages.devices') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-clipboard-list"></i>
                            <p>{{ __('messages.orders') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>{{ __('messages.payments') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.expenses.index') }}" class="nav-link {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-receipt"></i>
                            <p>{{ __('messages.expenses') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.spare-parts.index') }}" class="nav-link {{ request()->routeIs('admin.spare-parts.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>{{ __('messages.inventory') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.follow-ups.index') }}" class="nav-link {{ request()->routeIs('admin.follow-ups.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-phone-volume"></i>
                            <p>{{ __('messages.follow_ups') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.performance.index') }}" class="nav-link {{ request()->routeIs('admin.performance.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>{{ __('messages.performance') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.penalties.index') }}" class="nav-link {{ request()->routeIs('admin.penalties.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-gavel"></i>
                            <p>{{ __('messages.penalties') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.salaries.index') }}" class="nav-link {{ request()->routeIs('admin.salaries.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-wallet"></i>
                            <p>{{ __('messages.salaries') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.fleet-vehicles.index') }}" class="nav-link {{ request()->routeIs('admin.fleet-vehicles.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>{{ __('messages.fleet_vehicles') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.fleet-maintenances.index') }}" class="nav-link {{ request()->routeIs('admin.fleet-maintenances.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-oil-can"></i>
                            <p>{{ __('messages.fleet_maintenance') }}</p>
                        </a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
