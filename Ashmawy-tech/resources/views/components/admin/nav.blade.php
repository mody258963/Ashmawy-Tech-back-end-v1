<nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom ashmawy-navbar">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link text-white d-inline-flex align-items-center justify-content-center pushmenu-trigger"
               data-widget="pushmenu"
               href="#"
               role="button"
               style="position: relative; z-index: 1100; min-width: 40px;">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item d-none d-sm-inline-block">
            <form method="POST" action="{{ route('locale.switch') }}" class="d-inline mr-1">
                @csrf
                <input type="hidden" name="locale" value="en">
                <button type="submit" class="btn btn-link nav-link {{ app()->getLocale() === 'en' ? 'font-weight-bold' : 'text-white' }}">EN</button>
            </form>
            <form method="POST" action="{{ route('locale.switch') }}" class="d-inline">
                @csrf
                <input type="hidden" name="locale" value="ar">
                <button type="submit" class="btn btn-link nav-link {{ app()->getLocale() === 'ar' ? 'font-weight-bold' : 'text-white' }}">AR</button>
            </form>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <span class="nav-link text-white">{{ auth()->user()?->name }}</span>
        </li>
        <li class="nav-item">
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link nav-link text-white">{{ __('messages.logout') }}</button>
            </form>
        </li>
    </ul>
</nav>
