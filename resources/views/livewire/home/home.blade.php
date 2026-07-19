@push('title', 'Inicio operativo')

<div class="page-content warehouse-dashboard">
    <style>
        .warehouse-dashboard{--dash-primary:#2457d6;--dash-navy:#14213d;--dash-green:#18a76f;--dash-amber:#f59e0b;--dash-red:#e5484d;--dash-purple:#7c5ce7;--dash-cyan:#0891b2;--dash-text:#24324a;--dash-muted:#718096;--dash-line:#e7edf5;--dash-soft:#f6f8fc}
        .dashboard-hero{position:relative;overflow:hidden;margin-bottom:1.25rem;padding:1.5rem;border-radius:1rem;background:linear-gradient(125deg,#102a63 0%,#2457d6 58%,#1384b5 100%);box-shadow:0 16px 36px rgba(36,87,214,.2);color:#fff}
        .dashboard-hero:before,.dashboard-hero:after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.08)}
        .dashboard-hero:before{width:250px;height:250px;right:-80px;top:-135px}.dashboard-hero:after{width:150px;height:150px;right:19%;bottom:-110px}
        .dashboard-hero-content{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:flex-start;gap:1.5rem}
        .dashboard-eyebrow{display:flex;align-items:center;gap:.45rem;margin-bottom:.45rem;color:rgba(255,255,255,.72);font-size:.76rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase}
        .dashboard-hero h3{margin:0 0 .35rem;color:#fff;font-size:1.55rem;font-weight:700}.dashboard-hero p{margin:0;color:rgba(255,255,255,.78)}
        .dashboard-site{display:inline-flex;align-items:center;gap:.45rem;padding:.48rem .75rem;border:1px solid rgba(255,255,255,.22);border-radius:999px;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);font-size:.78rem;font-weight:600;white-space:nowrap}
        .dashboard-actions{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:.55rem;margin-top:1.2rem}
        .dashboard-action{display:flex;align-items:center;gap:.55rem;padding:.55rem .75rem;border:1px solid rgba(255,255,255,.2);border-radius:.65rem;background:rgba(255,255,255,.11);color:#fff;transition:.2s ease}.dashboard-action:hover{transform:translateY(-2px);background:rgba(255,255,255,.2);color:#fff}.dashboard-action i{font-size:1.15rem}.dashboard-action span{display:block;font-size:.78rem;font-weight:600}.dashboard-action small{display:block;color:rgba(255,255,255,.65);font-size:.66rem}
        .dashboard-section-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;margin:1.25rem 0 .75rem}.dashboard-section-heading h5{margin:0;color:var(--dash-text);font-size:1rem;font-weight:700}.dashboard-section-heading p{margin:.2rem 0 0;color:var(--dash-muted);font-size:.78rem}.dashboard-section-heading a{color:var(--dash-primary);font-size:.78rem;font-weight:600;white-space:nowrap}
        .dashboard-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem}.dashboard-kpi{position:relative;display:flex;align-items:center;gap:.8rem;min-height:104px;padding:1rem;border:1px solid var(--dash-line);border-radius:.85rem;background:#fff;box-shadow:0 5px 18px rgba(32,52,89,.055);transition:.2s ease}.dashboard-kpi:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(32,52,89,.1)}
        .dashboard-kpi-icon{display:grid;flex:0 0 46px;height:46px;place-items:center;border-radius:.75rem;font-size:1.45rem}.dashboard-kpi-value{color:var(--dash-text);font-size:1.5rem;font-weight:750;line-height:1}.dashboard-kpi-label{margin-top:.28rem;color:#45546c;font-size:.78rem;font-weight:650}.dashboard-kpi-caption{margin-top:.15rem;color:var(--dash-muted);font-size:.69rem}
        .tone-primary .dashboard-kpi-icon,.dashboard-mini-icon.tone-primary{background:#e9efff;color:var(--dash-primary)}.tone-danger .dashboard-kpi-icon,.dashboard-mini-icon.tone-danger{background:#fff0f1;color:var(--dash-red)}.tone-warning .dashboard-kpi-icon,.dashboard-mini-icon.tone-warning{background:#fff6e5;color:#d88700}.tone-success .dashboard-kpi-icon,.dashboard-mini-icon.tone-success{background:#e8f8f1;color:var(--dash-green)}.tone-info .dashboard-kpi-icon,.dashboard-mini-icon.tone-info{background:#e7f7fb;color:var(--dash-cyan)}.tone-purple .dashboard-kpi-icon,.dashboard-mini-icon.tone-purple{background:#f0edff;color:var(--dash-purple)}
        .dashboard-card{height:100%;border:1px solid var(--dash-line)!important;border-radius:.9rem!important;background:#fff;box-shadow:0 5px 18px rgba(32,52,89,.05)!important;overflow:hidden!important}.dashboard-card-header{display:flex;justify-content:space-between;align-items:center;gap:1rem;min-height:60px;padding:.9rem 1rem;border-bottom:1px solid var(--dash-line);background:#fff}.dashboard-card-header h6{margin:0;color:var(--dash-text);font-weight:700}.dashboard-card-header p{margin:.15rem 0 0;color:var(--dash-muted);font-size:.72rem}.dashboard-card-body{padding:1rem}
        .stock-health{display:grid;grid-template-columns:150px 1fr;align-items:center;gap:1.2rem}.stock-ring{position:relative;display:grid;width:138px;height:138px;margin:auto;place-items:center;border-radius:50%;background:conic-gradient(var(--dash-green) 0 var(--healthy),var(--dash-amber) var(--healthy) var(--low-end),var(--dash-red) var(--low-end) 100%)}.stock-ring:after{content:"";position:absolute;width:98px;height:98px;border-radius:50%;background:#fff}.stock-ring-center{position:relative;z-index:1;text-align:center}.stock-ring-center strong{display:block;color:var(--dash-text);font-size:1.4rem}.stock-ring-center small{color:var(--dash-muted);font-size:.66rem}
        .stock-legend{display:grid;gap:.65rem}.stock-legend-row{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:.55rem}.stock-dot{width:9px;height:9px;border-radius:50%}.stock-legend-row span{color:#4b5b73;font-size:.75rem}.stock-legend-row strong{color:var(--dash-text);font-size:.8rem}.stock-dot.green{background:var(--dash-green)}.stock-dot.amber{background:var(--dash-amber)}.stock-dot.red{background:var(--dash-red)}
        .dashboard-list{display:grid;gap:.1rem}.dashboard-list-item{display:flex;align-items:center;gap:.75rem;padding:.75rem 0;border-bottom:1px solid #edf1f6}.dashboard-list-item:last-child{border-bottom:0}.dashboard-list-main{min-width:0;flex:1}.dashboard-list-title{overflow:hidden;color:var(--dash-text);font-size:.8rem;font-weight:650;text-overflow:ellipsis;white-space:nowrap}.dashboard-list-subtitle{overflow:hidden;margin-top:.16rem;color:var(--dash-muted);font-size:.69rem;text-overflow:ellipsis;white-space:nowrap}.dashboard-list-value{text-align:right}.dashboard-list-value strong{display:block;color:var(--dash-text);font-size:.82rem}.dashboard-list-value small{color:var(--dash-muted);font-size:.66rem}
        .dashboard-mini-icon{display:grid;flex:0 0 38px;height:38px;place-items:center;border-radius:.65rem;font-size:1.15rem}.dashboard-status{display:inline-flex;padding:.2rem .45rem;border-radius:999px;font-size:.63rem;font-weight:700}.dashboard-status.danger{background:#fff0f1;color:var(--dash-red)}.dashboard-status.warning{background:#fff6e5;color:#b66f00}.dashboard-status.success{background:#e8f8f1;color:#14845a}.dashboard-status.secondary{background:#eef2f7;color:#64748b}.dashboard-status.primary{background:#e9efff;color:var(--dash-primary)}
        .dashboard-timeline{position:relative;display:grid}.dashboard-timeline-item{position:relative;display:grid;grid-template-columns:40px 1fr auto;gap:.7rem;padding:.72rem 0}.dashboard-timeline-item:not(:last-child):before{content:"";position:absolute;left:19px;top:42px;bottom:-4px;width:1px;background:var(--dash-line)}.dashboard-timeline-copy{min-width:0}.dashboard-timeline-copy strong{display:block;overflow:hidden;color:var(--dash-text);font-size:.78rem;text-overflow:ellipsis;white-space:nowrap}.dashboard-timeline-copy span{display:block;overflow:hidden;margin-top:.15rem;color:var(--dash-muted);font-size:.68rem;text-overflow:ellipsis;white-space:nowrap}.dashboard-timeline-time{text-align:right;color:var(--dash-muted);font-size:.63rem;white-space:nowrap}
        .expiry-banner{display:flex;gap:.65rem;margin-bottom:.75rem;padding:.75rem;border-radius:.7rem;background:linear-gradient(135deg,#fff6e5,#fffaf1);color:#845400}.expiry-banner i{font-size:1.35rem}.expiry-banner strong{display:block;font-size:.8rem}.expiry-banner span{font-size:.68rem}
        .dashboard-system-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem}.dashboard-system-item{display:flex;align-items:center;gap:.65rem;padding:.8rem;border:1px solid var(--dash-line);border-radius:.7rem;background:var(--dash-soft)}.dashboard-system-item i{color:var(--dash-primary);font-size:1.25rem}.dashboard-system-item strong{display:block;color:var(--dash-text);font-size:1rem}.dashboard-system-item span{color:var(--dash-muted);font-size:.68rem}
        .dashboard-empty{padding:1.6rem;text-align:center}.dashboard-empty i{display:grid;width:48px;height:48px;margin:0 auto .7rem;place-items:center;border-radius:50%;background:#edf7f2;color:var(--dash-green);font-size:1.4rem}.dashboard-empty strong{display:block;color:var(--dash-text);font-size:.83rem}.dashboard-empty span{display:block;margin-top:.2rem;color:var(--dash-muted);font-size:.7rem}
        body.dark-mode .warehouse-dashboard{--dash-text:#eef2f7;--dash-muted:#a5afbd;--dash-line:#3a424c;--dash-soft:#22272d}.dark-mode .dashboard-kpi,.dark-mode .dashboard-card,.dark-mode .dashboard-card-header{background:#292e34}.dark-mode .stock-ring:after{background:#292e34}.dark-mode .dashboard-kpi-label,.dark-mode .stock-legend-row span{color:#c6ced8}.dark-mode .dashboard-list-item{border-bottom-color:#39414a}.dark-mode .expiry-banner{background:#3a3325;color:#ffd68a}
        @media(max-width:1199.98px){.dashboard-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-system-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:767.98px){.dashboard-hero{padding:1.15rem}.dashboard-hero-content{flex-direction:column}.dashboard-site{align-self:flex-start}.dashboard-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-action{padding:.55rem}.dashboard-action small{display:none}.dashboard-kpi-grid{grid-template-columns:1fr}.dashboard-kpi{min-height:88px}.stock-health{grid-template-columns:1fr}.dashboard-system-grid{grid-template-columns:1fr}.dashboard-timeline-item{grid-template-columns:38px 1fr}.dashboard-timeline-time{display:none}}
    </style>

    <section class="dashboard-hero">
        <div class="dashboard-hero-content">
            <div>
                <div class="dashboard-eyebrow"><i class="bx bx-grid-alt"></i> Centro de control del almacén</div>
                <h3>Hola, {{ auth()->user()->name }}</h3>
                <p>{{ $todayLabel }}. Aquí tienes el estado operativo más importante.</p>
            </div>
            <div class="dashboard-site"><i class="bx bx-map"></i> Almacén {{ $siteCode }} · Actualizado {{ now()->format('H:i') }}</div>
        </div>
        @if($quickActions->isNotEmpty())
            <div class="dashboard-actions">
                @foreach($quickActions as $action)
                    <a href="{{ $action['route'] }}" class="dashboard-action">
                        <i class="bx {{ $action['icon'] }}"></i>
                        <div><span>{{ $action['label'] }}</span><small>{{ $action['caption'] }}</small></div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @if($warehouseMetrics)
        <div class="dashboard-section-heading">
            <div><h5>Estado del inventario</h5><p>Indicadores que requieren seguimiento diario.</p></div>
            @can('ver-inventario')<a href="{{ route('inventory') }}">Ver inventario <i class="bx bx-right-arrow-alt"></i></a>@endcan
        </div>
        <div class="dashboard-kpi-grid">
            @foreach($warehouseMetrics as $metric)
                <a href="{{ $metric['route'] }}" class="dashboard-kpi tone-{{ $metric['tone'] }}">
                    <div class="dashboard-kpi-icon"><i class="bx {{ $metric['icon'] }}"></i></div>
                    <div><div class="dashboard-kpi-value">{{ number_format($metric['value']) }}</div><div class="dashboard-kpi-label">{{ $metric['label'] }}</div><div class="dashboard-kpi-caption">{{ $metric['caption'] }}</div></div>
                </a>
            @endforeach
        </div>
    @endif

    @if($operationMetrics)
        <div class="dashboard-section-heading"><div><h5>Operación de hoy</h5><p>Actividad confirmada y trabajo pendiente.</p></div></div>
        <div class="dashboard-kpi-grid">
            @foreach($operationMetrics as $metric)
                <a href="{{ $metric['route'] }}" class="dashboard-kpi tone-{{ $metric['tone'] }}">
                    <div class="dashboard-kpi-icon"><i class="bx {{ $metric['icon'] }}"></i></div>
                    <div><div class="dashboard-kpi-value">{{ number_format($metric['value']) }}</div><div class="dashboard-kpi-label">{{ $metric['label'] }}</div><div class="dashboard-kpi-caption">{{ $metric['caption'] }}</div></div>
                </a>
            @endforeach
        </div>
    @endif

    @if($stockOverview)
        <div class="row g-3 mt-1">
            <div class="col-12 col-xl-5">
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><div><h6>Salud del stock</h6><p>Distribución de referencias activas.</p></div><i class="bx bx-doughnut-chart fs-4 text-primary"></i></div>
                    <div class="dashboard-card-body">
                        <div class="stock-health">
                            <div class="stock-ring" style="--healthy:{{ $stockOverview['healthyPercent'] }}%;--low-end:{{ $stockOverview['healthyPercent'] + $stockOverview['lowPercent'] }}%">
                                <div class="stock-ring-center"><strong>{{ $stockOverview['total'] }}</strong><small>referencias</small></div>
                            </div>
                            <div class="stock-legend">
                                <div class="stock-legend-row"><i class="stock-dot green"></i><span>Stock normal</span><strong>{{ $stockOverview['healthy'] }}</strong></div>
                                <div class="stock-legend-row"><i class="stock-dot amber"></i><span>Stock bajo</span><strong>{{ $stockOverview['low'] }}</strong></div>
                                <div class="stock-legend-row"><i class="stock-dot red"></i><span>Agotado</span><strong>{{ $stockOverview['out'] }}</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-7">
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><div><h6>Productos que requieren atención</h6><p>Agotados y por debajo del stock mínimo.</p></div><a href="{{ route('inventory') }}" class="small fw-semibold">Revisar</a></div>
                    <div class="dashboard-card-body py-1">
                        @forelse($criticalStock as $variant)
                            @php($stock = (float) ($variant->stock ?? 0))
                            <div class="dashboard-list-item">
                                <div class="dashboard-mini-icon tone-{{ $stock <= 0 ? 'danger' : 'warning' }}"><i class="bx {{ $stock <= 0 ? 'bx-x-circle' : 'bx-down-arrow-circle' }}"></i></div>
                                <div class="dashboard-list-main"><div class="dashboard-list-title">{{ $variant->product->name }}{{ $variant->name ? ' · '.$variant->name : '' }}</div><div class="dashboard-list-subtitle">{{ $variant->sku }} · {{ $variant->product->category?->name }}</div></div>
                                <div class="dashboard-list-value"><strong>{{ number_format($stock, 3) }} {{ $variant->product->unit }}</strong><small>Mínimo {{ number_format((float) $variant->minimum_stock, 3) }}</small></div>
                            </div>
                        @empty
                            <div class="dashboard-empty"><i class="bx bx-check"></i><strong>Todo el stock está en niveles correctos</strong><span>No existen referencias agotadas ni bajo el mínimo.</span></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-12 col-xl-7">
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><div><h6>Movimientos recientes</h6><p>Últimas entradas, salidas y entregas registradas.</p></div><a href="{{ route('inventory') }}" class="small fw-semibold">Abrir Kardex</a></div>
                    <div class="dashboard-card-body py-1">
                        <div class="dashboard-timeline">
                            @forelse($recentMovements as $movement)
                                @php($tone = $this->movementTone($movement->movement_type))
                                <div class="dashboard-timeline-item">
                                    <div class="dashboard-mini-icon tone-{{ $tone }}"><i class="bx bx-transfer-alt"></i></div>
                                    <div class="dashboard-timeline-copy">
                                        <strong>{{ $movement->variant?->product?->name }} · {{ $this->movementLabel($movement->movement_type) }}</strong>
                                        <span>{{ $movement->dispatchNote?->number ?? $movement->delivery?->number ?? 'Movimiento interno' }}@if($movement->delivery?->worker) · {{ $movement->delivery->worker->full_name }}@endif @if($movement->serializedItem) · Serie {{ $movement->serializedItem->serial_number }}@endif</span>
                                    </div>
                                    <div class="dashboard-timeline-time"><strong class="text-{{ (float) $movement->quantity >= 0 ? 'success' : 'danger' }}">{{ (float) $movement->quantity > 0 ? '+' : '' }}{{ number_format((float) $movement->quantity, 3) }}</strong><br>{{ $movement->occurred_at?->locale('es')->diffForHumans() }}</div>
                                </div>
                            @empty
                                <div class="dashboard-empty"><i class="bx bx-transfer"></i><strong>Todavía no existen movimientos</strong><span>La actividad aparecerá al confirmar remitos y entregas.</span></div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><div><h6>Control de vencimientos</h6><p>Productos vencidos o por vencer en 90 días.</p></div>@can('ver-reporte')<a href="{{ route('reports') }}" class="small fw-semibold">Reporte</a>@endcan</div>
                    <div class="dashboard-card-body">
                        <div class="expiry-banner"><i class="bx bx-calendar-exclamation"></i><div><strong>{{ $expirySummary['expired'] }} vencido(s) · {{ $expirySummary['soon'] }} por vencer</strong><span>Considera su rotación o baja oportuna.</span></div></div>
                        @forelse($expiryAlerts as $variant)
                            <div class="dashboard-list-item">
                                <div class="dashboard-list-main"><div class="dashboard-list-title">{{ $variant->product->name }}</div><div class="dashboard-list-subtitle">{{ $variant->sku }} · Stock {{ number_format((float) $variant->stock, 3) }}</div></div>
                                <div class="dashboard-list-value"><strong>{{ \Illuminate\Support\Carbon::parse($variant->expiration_date)->format('d/m/Y') }}</strong><span class="dashboard-status {{ $variant->days_to_expiry < 0 ? 'danger' : ($variant->days_to_expiry <= 30 ? 'warning' : 'secondary') }}">{{ $variant->days_to_expiry < 0 ? 'Vencido' : ($variant->days_to_expiry === 0 ? 'Vence hoy' : $variant->days_to_expiry.' días') }}</span></div>
                            </div>
                        @empty
                            <div class="dashboard-empty"><i class="bx bx-calendar-check"></i><strong>Sin vencimientos cercanos</strong><span>No hay productos con stock que venzan en los próximos 90 días.</span></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($recentDocuments->isNotEmpty())
        <div class="dashboard-section-heading"><div><h5>Documentos recientes</h5><p>Seguimiento rápido de remitos y entregas.</p></div></div>
        <div class="dashboard-card">
            <div class="dashboard-card-body py-1">
                <div class="row">
                    @foreach($recentDocuments as $document)
                        <div class="col-12 col-lg-6">
                            <a href="{{ $document['route'] }}" class="dashboard-list-item">
                                <div class="dashboard-mini-icon tone-primary"><i class="bx {{ $document['icon'] }}"></i></div>
                                <div class="dashboard-list-main"><div class="dashboard-list-title">{{ $document['number'] }} · {{ $document['kind'] }}</div><div class="dashboard-list-subtitle">{{ $document['subject'] }} · {{ $document['items'] }} producto(s)</div></div>
                                <div class="dashboard-list-value"><span class="dashboard-status {{ ['draft'=>'secondary','confirmed'=>'success','annulled'=>'danger'][$document['status']] }}">{{ ['draft'=>'Borrador','confirmed'=>'Confirmado','annulled'=>'Anulado'][$document['status']] }}</span><small>{{ $document['date']?->locale('es')->diffForHumans() }}</small></div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($systemMetrics)
        <div class="dashboard-section-heading"><div><h5>Resumen del sistema</h5><p>Personal, accesos y trazabilidad.</p></div></div>
        <div class="dashboard-system-grid">
            @foreach($systemMetrics as $metric)
                <div class="dashboard-system-item"><i class="bx {{ $metric['icon'] }}"></i><div><strong>{{ number_format($metric['value']) }}</strong><span>{{ $metric['label'] }}</span></div></div>
            @endforeach
        </div>
    @endif

    @if($canViewLogs)
        <div class="dashboard-section-heading"><div><h5>Trazabilidad reciente</h5><p>Últimas acciones registradas en el sistema.</p></div><a href="{{ route('logs') }}">Ver historial completo <i class="bx bx-right-arrow-alt"></i></a></div>
        <div class="dashboard-card">
            <div class="dashboard-card-body py-1">
                <div class="row">
                    @forelse($recentLogs as $log)
                        <div class="col-12 col-lg-6"><div class="dashboard-list-item"><div class="dashboard-mini-icon tone-purple"><i class="bx bx-history"></i></div><div class="dashboard-list-main"><div class="dashboard-list-title">{{ $log->modulo }} · {{ $log->accion }}</div><div class="dashboard-list-subtitle">{{ $log->descripcion }} · {{ $log->actor_login ?? $log->user?->login ?? 'Sistema' }}</div></div><div class="dashboard-list-value"><small>{{ $log->created_at?->locale('es')->diffForHumans() }}</small></div></div></div>
                    @empty
                        <div class="dashboard-empty"><i class="bx bx-history"></i><strong>Todavía no hay actividad registrada</strong></div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if(!$hasWarehouseAccess && !$systemMetrics)
        <div class="dashboard-card"><div class="dashboard-empty"><i class="bx bx-check-shield"></i><strong>Tu cuenta está activa</strong><span>Utiliza el menú para acceder a los módulos asignados a tu rol.</span></div></div>
    @endif
</div>
