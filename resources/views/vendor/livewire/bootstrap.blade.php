@php
    if (!isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet =
        $scrollTo !== false
            ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()"
            : '';
@endphp

<style>
    .custom-pagination {
        padding: 0px 0;
        width: 100%;
    }

    .custom-pagination .pagination {
        display: flex !important;
        gap: 5px !important;
        margin: 0 !important;
        list-style: none !important;
        padding: 0 !important;
        flex-wrap: nowrap !important;
    }

    /* Estilo base de los botones */
    .custom-pagination .page-item .page-link {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        color: #64748b !important;
        font-weight: 700 !important;
        padding: 0 !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        background: #ffffff !important;
        min-width: 30px !important;
        height: 30px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 14px !important;
        text-decoration: none !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04) !important;
    }

    /* HOVER ROJO VIVO */
    .custom-pagination .page-item .page-link:hover {
        border-color: #fc0038 !important;
        color: #fc0038 !important;
        background: #fff5f6 !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 5px 12px rgba(252, 0, 56, 0.2) !important;
        z-index: 2 !important;
    }

    /* ESTADO ACTIVO (Página actual) */
    .custom-pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #fc0038 0%, #e60032 100%) !important;
        border-color: #fc0038 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(252, 0, 56, 0.35) !important;
        transform: scale(1.05) !important;
        z-index: 3 !important;
    }

    /* ESTADO DESHABILITADO */
    .custom-pagination .page-item.disabled .page-link {
        opacity: 0.4 !important;
        background: #f8fafc !important;
        border-color: #edf2f7 !important;
        color: #cbd5e1 !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
        transform: none !important;
    }

    /* Texto informativo */
    .pagination-info {
        font-size: 13px;
        color: #64748b;
        font-weight: 500;
    }

    .pagination-info b {
        color: #1e293b;
        font-weight: 800;
    }

    /* Ajustes para móviles */
    @media (max-width: 768px) {
        .pagination-info { 
            display: none !important; 
        }
        .custom-pagination nav {
            justify-content: center !important;
        }
        .custom-pagination .page-item .page-link {
            min-width: 34px !important;
            height: 34px !important;
            font-size: 13px !important;
        }
    }
</style>

<div class="custom-pagination">
    @if ($paginator->hasPages())
        <nav class="d-flex align-items-center justify-content-between">
            {{-- Info de páginas --}}
            <div class="pagination-info">
                Mostrando <b>{{ $paginator->firstItem() }}</b> - <b>{{ $paginator->lastItem() }}</b> de <b>{{ $paginator->total() }}</b> productos
            </div>

            <ul class="pagination">
                {{-- Botón Anterior --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled"><span class="page-link"><i class='bx bx-chevron-left'></i></span></li>
                @else
                    <li class="page-item">
                        <button type="button" class="page-link" wire:click="previousPage" x-on:click="{{ $scrollIntoViewJsSnippet }}">
                            <i class='bx bx-chevron-left'></i>
                        </button>
                    </li>
                @endif

                {{-- Números de Página con Lógica Compacta --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                {{-- Lógica para mostrar solo 1 vecino, la primera y la última página --}}
                                @if ($page == 1 || $page == $paginator->lastPage() || abs($page - $paginator->currentPage()) <= 1)
                                    <li class="page-item">
                                        <button type="button" class="page-link" wire:click="gotoPage({{ $page }})" x-on:click="{{ $scrollIntoViewJsSnippet }}">
                                            {{ $page }}
                                        </button>
                                    </li>
                                @elseif (($page == 2 && $paginator->currentPage() > 3) || ($page == $paginator->lastPage() - 1 && $paginator->currentPage() < $paginator->lastPage() - 2))
                                    {{-- Opcional: podrías poner puntos aquí, pero el foreach superior ya los maneja --}}
                                @endif
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Botón Siguiente --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <button type="button" class="page-link" wire:click="nextPage" x-on:click="{{ $scrollIntoViewJsSnippet }}">
                            <i class='bx bx-chevron-right'></i>
                        </button>
                    </li>
                @else
                    <li class="page-item disabled"><span class="page-link"><i class='bx bx-chevron-right'></i></span></li>
                @endif
            </ul>
        </nav>
    @endif
</div>