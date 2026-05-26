<div class="filter-pro-dropdown dropdown">
    <button class="btn filter-pro-btn dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside" title="Filtros">
        <i class="bx bx-filter-alt" style="font-size: 1.3rem;"></i>
        @if(isset($filterCount) && $filterCount > 0)
            <span class="filter-pro-badge">
                {{ $filterCount }}
            </span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end filter-pro-menu p-3" aria-labelledby="filterDropdown">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="filter-pro-title">Filtros</span>
            <a href="javascript:void(0);" wire:click="clearFilters" class="filter-pro-clear">Limpiar</a>
        </div>
        {{ $slot }}
    </div>
</div>