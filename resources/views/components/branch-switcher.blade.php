<div class="branch-selector-modern" style="min-width: 0;">
    <div class="branch-info d-flex align-items-center" style="min-width: 0;">
        <div class="branch-details" style="min-width: 0;">
            <div class="branch-current" style="min-width: 0;">
                <span class="branch-icon">
                    <i class='bx bx-store-alt'></i>
                </span>
                <div class="branch-text" style="min-width: 0;">
                    <span class="branch-label">SUCURSAL</span>
                    <span class="branch-name" id="current-branch-name">
                        {{ session('branch_user_id') ? \App\Models\Branche::find(session('branch_user_id'))->name ?? 'SIN SUCURSAL' : (auth()->user()->branche ? auth()->user()->branche->name : 'SIN SUCURSAL ASIGNADA') }}
                    </span>
                </div>
            </div>
        </div>
        @can('cambiar-sucursal')
            <div>
                <button class="branch-switch-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class='bx bx-chevron-down'></i>
                </button>
                <ul class="dropdown-menu branch-dropdown">
                    @php
                        $currentBranchId = session('branch_user_id', auth()->user()->branch_id);
                        $branches = \App\Models\Branche::where('status', 1)->get();
                    @endphp
                    @foreach ($branches as $branch)
                        @php $isActive = $branch->id == $currentBranchId; @endphp
                        <li>
                            <a class="branch-option {{ $isActive ? 'active' : '' }}" href="javascript:void(0)"
                                onclick="switchBranch({{ $branch->id }}, '{{ $branch->name }}')"
                                data-branch-id="{{ $branch->id }}">
                                <div class="branch-option-content">
                                    <i class='bx {{ $isActive ? 'bx-check-circle' : 'bx-store' }}'></i>
                                    <span class="branch-option-name">{{ $branch->name }}</span>
                                    @if ($isActive)
                                        <span class="badge-current">ACTUAL</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endcan
    </div>
</div>

<style>
    .pace.changing-branch::after {
        content: "CAMBIANDO SUCURSAL" !important;
    }
    @media (max-width: 768px) {
        .branch-current {
            padding: 4px 6px !important;
        }
        .branch-name {
            max-width: 80px !important;
        }
        .branch-switch-btn {
            padding: 4px 6px !important;
        }
    }
    @media (max-width: 400px) {
        .branch-name {
            max-width: 55px !important;
        }
    }
</style>

<script>
    function switchBranch(branchId, branchName) {
        showPacelsmLoader();

        fetch('/branch/switch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ branch_id: branchId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateBranchUI(branchId, branchName);

                    const dropdown = document.querySelector('.branch-switch-btn');
                    if (dropdown) {
                        const bsDropdown = bootstrap.Dropdown.getInstance(dropdown);
                        if (bsDropdown) bsDropdown.hide();
                    }

                    const currentPath = window.location.pathname.toLowerCase();
                    const livewireRoutes = [
                        '/home', '/sales_interface', '/sales_restaurant', '/sales',
                        '/quotes', '/kardexs', '/purchase_lists', '/products','reservations',
                        '/settings', '/inventory_adjustments', '/cash_boxes', '/cash_boxes_lists',
                        '/orders_lists', '/zones', '/settings', '/tables_view', '/general_movements',
                    ];

                    const needsLivewire = livewireRoutes.some(route =>
                        currentPath.includes(route) || currentPath === route
                    );

                    if (needsLivewire && window.Livewire) {
                        window.Livewire.dispatch('branchChanged', {
                            branchId: branchId,
                            branchName: branchName
                        });

                        document.querySelectorAll('[wire\\:id]').forEach(element => {
                            try {
                                const component = window.Livewire.find(element.getAttribute('wire:id'));
                                if (component && component.call) {
                                    component.call('refreshData', branchId).catch(err => { });
                                }
                            } catch (e) { }
                        });
                    }

                    fetch(window.location.href)
                    .then(res => res.text())
                    .then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newLogoSrc = doc.querySelector('.logo-icon')?.src;
                        const newLogoText = doc.querySelector('.logo-text')?.textContent;
                        if(newLogoSrc) document.querySelector('.logo-icon').src = newLogoSrc;
                        if(newLogoText) document.querySelector('.logo-text').textContent = newLogoText;
                    });

                    hidePacelsmLoader();

                } else {
                    hidePacelsmLoader();
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                hidePacelsmLoader();
                console.error('Error:', error);
            });
    }

    function updateBranchUI(branchId, branchName) {
        const currentBranchName = document.getElementById('current-branch-name');
        if (currentBranchName) currentBranchName.textContent = branchName;

        const options = document.querySelectorAll('.branch-option');
        options.forEach(option => {
            const optionBranchId = option.getAttribute('data-branch-id');
            const icon = option.querySelector('i');
            const badge = option.querySelector('.badge-current');

            if (optionBranchId == branchId) {
                option.classList.add('active');
                icon.className = 'bx bx-check-circle';
                if (!badge) {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'badge-current';
                    newBadge.textContent = 'ACTUAL';
                    option.querySelector('.branch-option-content').appendChild(newBadge);
                }
            } else {
                option.classList.remove('active');
                icon.className = 'bx bx-store';
                if (badge) badge.remove();
            }
        });
    }

    function showPacelsmLoader() {
        const loader = document.querySelector('.pace');
        if (loader) {
            loader.classList.add('pace-active');
            loader.classList.add('changing-branch'); 
        }
    }

    function hidePacelsmLoader() {
        const loader = document.querySelector('.pace');
        if (loader) {
            setTimeout(() => {
                loader.classList.remove('pace-active');
                loader.classList.remove('changing-branch');
            }, 300);
        }
    }
</script>