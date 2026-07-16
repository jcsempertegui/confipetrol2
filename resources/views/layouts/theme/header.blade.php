<header>
    <div class="topbar d-flex align-items-center">
        <nav class="navbar navbar-expand gap-1 gap-md-3 w-100">
            
            <div class="mobile-toggle-menu"><i class='bx bx-menu'></i></div>

            <div class="top-menu ms-auto">
                <ul class="navbar-nav align-items-center gap-0">
                
                </ul>
            </div>

            <div class="user-box dropdown">
                <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    @if (auth()->user()->image)
                        <img src="{{ asset('storage/' . auth()->user()->image) }}" class="user-img" alt="user avatar">
                    @else
                        <img src="{{ asset('assets/images/avatar.png') }}" class="user-img" alt="user avatar">
                    @endif
                    <div class="user-info">
                        <p class="user-name mb-0">{{ auth()->user()->name }}</p>
                        <p class="designattion mb-0">{{ auth()->user()->email }}</p>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item d-flex align-items-center" href="{{ route('profile') }}"><i class="bx bx-user"></i><span>Mi perfil</span></a></li>
                    <li><div class="dropdown-divider mb-0"></div></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="bx bx-log-out-circle"></i><span>Salir</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</header>
