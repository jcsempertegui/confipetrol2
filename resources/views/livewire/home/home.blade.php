
@push('title', 'Home')

<div class="page-dashboard">
    <div class="row">
        <div class="mx-auto">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label>Selecciona un mes:</label>
                        </div>
                        <div class="col-auto">
                            <input type="month" class="form-control" wire:model="fromDate">
                        </div>
                        <div class="col-auto">
                            <button wire:click="totalesByDate" wire:loading.attr="disabled"
                                class="btn btn-outline-secondary btnIcon"
                                :disabled="!@this.fromDate || !@this.branch_id">
                                <span wire:loading.remove wire:target="totalesByDate">
                                    <i class="bx bx-search-alt"></i>
                                    BUSCAR CONSULTA
                                </span>
                                <span wire:loading wire:target="totalesByDate">
                                    <i class="bx bx-spin bx-loader"></i>
                                    PROCESANDO...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-2 row-cols-md-4 row-cols-xl-8">
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-info text-info mb-2">
                            <i class='bx bx-package'></i>
                        </div>
                        <h4 class="my-1">{{ $totalProducts }}</h4>
                        <p class="mb-0 text-secondary">Productos</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-primary text-primary mb-2">
                            <i class='bx bx-hard-hat'></i>
                        </div>
                        <h4 class="my-1">{{ $totalWorkers }}</h4>
                        <p class="mb-0 text-secondary">Trabajadores</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-deepblue text-deepblue mb-2">
                            <i class='bx bx-file-blank'></i>
                        </div>
                        <h4 class="my-1">{{ $totalRemitos }}</h4>
                        <p class="mb-0 text-secondary">Remitos</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-orange text-orange mb-2">
                            <i class='bx bx-truck'></i>
                        </div>
                        <h4 class="my-1">{{ $totalDeliveries }}</h4>
                        <p class="mb-0 text-secondary">Entregas</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-info text-info mb-2">
                            <i class='bx bx-send'></i>
                        </div>
                        <h4 class="my-1">{{ $totalRemitoItems }}</h4>
                        <p class="mb-0 text-secondary">Items Despachados</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-success text-success mb-2">
                            <i class='bx bx-check-circle'></i>
                        </div>
                        <h4 class="my-1">{{ $totalDeliveryItems }}</h4>
                        <p class="mb-0 text-secondary">Items Entregados</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-danger text-danger mb-2">
                            <i class='bx bx-building-house'></i>
                        </div>
                        <h4 class="my-1">{{ $totalWarehouses }}</h4>
                        <p class="mb-0 text-secondary">Almacenes</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-warning text-warning mb-2">
                            <i class='bx bx-error'></i>
                        </div>
                        <h4 class="my-1">{{ $totalLowStock }}</h4>
                        <p class="mb-0 text-secondary">Stock Bajo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-7 col-xl-8 d-flex">
            <div class="card radius-10 w-100">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                        <div>
                            <h6 class="mb-0">Remitos y Entregas Anuales (Mes a Mes)</h6>
                        </div>
                    </div>
                    <div class="d-flex align-items-center ms-auto font-13 gap-2 my-3">
                        <span class="border px-1 rounded cursor-pointer">
                            <i class="bx bxs-circle me-1" style="color: #b83dba"></i>Remitos
                        </span>
                        <span class="border px-1 rounded cursor-pointer">
                            <i class="bx bxs-circle me-1" style="color: #ffc107"></i>Entregas
                        </span>
                    </div>
                    <div class="chart-container-1" wire:ignore>
                        <canvas id="getSalesAndPurchasesByMonth"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5 col-xl-4 d-flex">
            <div class="card w-100 radius-10">
                <div class="card-body">
                    <div class="card radius-10 border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total Remitos (mes)</p>
                                    <h4 class="my-1">{{ $totalRemitos }} <small class="fs-6 text-muted">documentos</small></h4>
                                </div>
                                <div class="widgets-icons-2 bg-gradient-deepblue text-white ms-auto">
                                    <i class='bx bx-file-blank'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card radius-10 border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total Entregas (mes)</p>
                                    <h4 class="my-1">{{ $totalDeliveries }} <small class="fs-6 text-muted">documentos</small></h4>
                                </div>
                                <div class="widgets-icons-2 bg-gradient-orange text-white ms-auto">
                                    <i class='bx bx-truck'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card radius-10 mb-0 border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Items Despachados (mes)</p>
                                    <h4 class="my-1">{{ $totalRemitoItems }} <small class="fs-6 text-muted">unidades</small></h4>
                                </div>
                                <div class="widgets-icons-2 bg-gradient-scooter text-white ms-auto">
                                    <i class='bx bx-send'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8 d-flex">
            <div class="card radius-10 w-100">
                <div class="card-header px-3 py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0">Remitos por Día (Mes Seleccionado)</h6>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container-1" wire:ignore>
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4 d-flex">
            <div class="card radius-10 w-100">
                <div class="card-header px-3 py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0">Remitos por Tipo</h6>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container-1" wire:ignore>
                        <canvas id="categorySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-lg-3">
        <div class="col d-flex" x-data="{ view: 'remitos' }">
            <div class="card radius-10 w-100">
                <div class="card-header px-3 py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0">Top 5 Productos</h6>
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary"
                                :class="view === 'remitos' ? 'active' : ''"
                                @click="view = 'remitos'; toggleProductChart('remitos')">
                                <i class='bx bx-file-blank me-1'></i>Remitos
                            </button>
                            <button type="button" class="btn btn-outline-danger ms-2"
                                :class="view === 'entregas' ? 'active' : ''"
                                @click="view = 'entregas'; toggleProductChart('entregas')">
                                <i class='bx bx-truck me-1'></i>Entregas
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container-1" wire:ignore>
                        <canvas id="topProductosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col d-flex">
            <div class="card radius-10 w-100">
                <div class="card-header px-3 py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0">Top Trabajadores</h6>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container-1" wire:ignore>
                        <canvas id="topRankingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col d-flex">
            <div class="card radius-10 w-100">
                <div class="card-header px-3 py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0">Remitos vs Entregas</h6>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container-1" wire:ignore>
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("livewire:init", function() {
    let chartInstance = null;
    let topProductsChart = null;
    let topRankingChart = null;
    let dailySalesChart = null;
    let categorySalesChart = null;
    let incomeExpenseChart = null;

    let productosData = {
        remitos: [],
        entregas: []
    };

    let currentProductView = 'remitos';

    const updateChart = (data) => {
        const ctx = document.getElementById('getSalesAndPurchasesByMonth').getContext('2d');
        if (chartInstance) chartInstance.destroy();

        var gradientStroke1 = ctx.createLinearGradient(0, 0, 0, 300);
        gradientStroke1.addColorStop(0, '#a07fcf');
        gradientStroke1.addColorStop(0.5, '#9a78d9');
        gradientStroke1.addColorStop(1, '#7e43b8');

        var gradientStroke2 = ctx.createLinearGradient(0, 0, 0, 300);
        gradientStroke2.addColorStop(0, '#ffc078');
        gradientStroke2.addColorStop(0.5, '#ffb347');
        gradientStroke2.addColorStop(1, '#ffe066');

        chartInstance = new Chart(ctx, {
            type: "line",
            data: {
                labels: data.months,
                datasets: [{
                    label: 'Remitos',
                    data: data.remitosTotals,
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: gradientStroke1,
                    borderColor: gradientStroke1,
                    borderWidth: 3,
                    fill: false,
                    tooltipColor: '#9a78d9'
                }, {
                    label: 'Entregas',
                    data: data.deliveriesTotals,
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: gradientStroke2,
                    borderColor: gradientStroke2,
                    borderWidth: 3,
                    fill: false,
                    tooltipColor: '#ffb347'
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: { position: 'bottom', display: false },
                tooltips: {
                    displayColors: true,
                    mode: 'nearest',
                    intersect: false,
                    callbacks: {
                        label: function(tooltipItem, d) {
                            var label = d.datasets[tooltipItem.datasetIndex].label || '';
                            return label + ': ' + tooltipItem.yLabel + ' documentos';
                        },
                        labelColor: function(tooltipItem, chart) {
                            const dataset = chart.data.datasets[tooltipItem.datasetIndex];
                            return { borderColor: dataset.tooltipColor, backgroundColor: dataset.tooltipColor };
                        }
                    }
                },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, callback: function(value) { return value; } } }]
                }
            }
        });
    };

    const updateDailySalesChart = (data) => {
        const ctx = document.getElementById('dailySalesChart').getContext('2d');
        if (dailySalesChart) dailySalesChart.destroy();

        var gradientStroke = ctx.createLinearGradient(0, 0, 0, 300);
        gradientStroke.addColorStop(0, 'rgba(155, 120, 217, 0.8)');
        gradientStroke.addColorStop(1, 'rgba(155, 120, 217, 0.2)');

        dailySalesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Remitos del Día',
                    data: data.data,
                    backgroundColor: gradientStroke,
                    borderColor: '#9a78d9',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, callback: function(value) { return value; } } }],
                    xAxes: [{ gridLines: { display: false } }]
                },
                tooltips: {
                    callbacks: {
                        title: function(tooltipItems) { return 'Día ' + tooltipItems[0].xLabel; },
                        label: function(tooltipItem) { return 'Remitos: ' + tooltipItem.yLabel; }
                    }
                }
            }
        });
    };

    const updateCategorySalesChart = (data) => {
        const ctx = document.getElementById('categorySalesChart').getContext('2d');
        if (categorySalesChart) categorySalesChart.destroy();

        let nombres = [];
        let valores = [];
        const colors = ['#b83dba', '#ffc107', '#17c5ea', '#ff8042', '#28a745'];

        for (let i = 0; i < data.length; i++) {
            nombres.push(data[i].tipo);
            valores.push(data[i].total);
        }

        if (nombres.length === 0) {
            nombres = ['Sin datos'];
            valores = [1];
        }

        categorySalesChart = new Chart(ctx, {
            type: "pie",
            data: {
                labels: nombres,
                datasets: [{ backgroundColor: colors, data: valores, borderWidth: 1 }]
            },
            options: {
                maintainAspectRatio: false,
                legend: { position: "right", labels: { boxWidth: 12, fontSize: 11 } },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, dataObj) {
                            let label = dataObj.labels[tooltipItem.index];
                            let value = dataObj.datasets[0].data[tooltipItem.index];
                            return label + ': ' + value + ' remitos';
                        }
                    }
                }
            }
        });
    };

    const updateProductChart = (data, tipo) => {
        const ctx = document.getElementById('topProductosChart').getContext('2d');
        if (topProductsChart) topProductsChart.destroy();

        const colors = tipo === 'remitos' ?
            ['#fc4a1a', '#4776e6', '#ee0979', '#42e695', '#667eea'] :
            ['#ff6b6b', '#ffa726', '#42a5f5', '#ab47bc', '#66bb6a'];

        let nombres = [];
        let cantidades = [];
        let field = tipo === 'remitos' ? 'total_despachado' : 'total_entregado';

        for (let i = 0; i < data.length; i++) {
            let nombre = data[i].name;
            if (nombre.length > 15) nombre = nombre.substring(0, 15) + '...';
            nombres.push(nombre);
            cantidades.push(data[i][field]);
        }

        if (nombres.length === 0) {
            nombres = ['Sin datos'];
            cantidades = [0];
        }

        topProductsChart = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: nombres,
                datasets: [{ backgroundColor: colors, hoverBackgroundColor: colors, data: cantidades, borderWidth: 2, borderColor: '#fff' }]
            },
            options: {
                maintainAspectRatio: false,
                cutoutPercentage: 65,
                legend: { position: "right", display: true, labels: { boxWidth: 12, fontSize: 11, fontColor: '#6c757d', padding: 15 } },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, d) {
                            let label = d.labels[tooltipItem.index];
                            let value = d.datasets[0].data[tooltipItem.index];
                            return label + ': ' + value + ' unidades';
                        }
                    }
                }
            }
        });
    };

    const updateRankingChart = (data) => {
        const ctx = document.getElementById('topRankingChart').getContext('2d');
        if (topRankingChart) topRankingChart.destroy();

        let nombres = [];
        let valores = [];

        for (let i = 0; i < data.length; i++) {
            let nombre = data[i].name;
            if (nombre.length > 14) nombre = nombre.substring(0, 14) + '...';
            nombres.push(nombre);
            valores.push(data[i].total_entregas);
        }

        if (nombres.length === 0) {
            nombres = ['Sin datos'];
            valores = [0];
        }

        topRankingChart = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: nombres,
                datasets: [{
                    label: 'Entregas',
                    data: valores,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{ ticks: { beginAtZero: true, callback: function(value) { return value; } }, gridLines: { color: 'rgba(0,0,0,0.1)' } }],
                    yAxes: [{ gridLines: { display: false } }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem) { return 'Entregas: ' + tooltipItem.xLabel; }
                    }
                }
            }
        });
    };

    const updateIncomeExpenseChart = (data) => {
        const ctx = document.getElementById('incomeExpenseChart').getContext('2d');
        if (incomeExpenseChart) incomeExpenseChart.destroy();

        incomeExpenseChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Remitos', 'Entregas'],
                datasets: [{
                    data: [data.remitosTotals ? data.remitosTotals.reduce((a, b) => a + b, 0) : 0,
                           data.deliveriesTotals ? data.deliveriesTotals.reduce((a, b) => a + b, 0) : 0],
                    backgroundColor: ['#9a78d9', '#ffb347'],
                    hoverBackgroundColor: ['#7e43b8', '#ff9900'],
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutoutPercentage: 60,
                legend: { position: 'bottom', labels: { boxWidth: 12, fontSize: 11, fontColor: '#6c757d' } },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, dataObj) {
                            let label = dataObj.labels[tooltipItem.index];
                            let value = dataObj.datasets[0].data[tooltipItem.index];
                            return label + ': ' + value + ' documentos';
                        }
                    }
                }
            }
        });
    };

    window.toggleProductChart = function(tipo) {
        currentProductView = tipo;
        if (productosData[tipo] && productosData[tipo].length > 0) {
            updateProductChart(productosData[tipo], tipo);
        }
    };

    Livewire.on('dataUpdated', (eventData) => {
        updateChart(eventData[0]);
        updateIncomeExpenseChart(eventData[0]);
    });

    Livewire.on('dailyRemitosUpdated', (eventData) => {
        updateDailySalesChart(eventData[0]);
    });

    Livewire.on('remitosByTypeUpdated', (eventData) => {
        updateCategorySalesChart(eventData[0]);
    });

    Livewire.on('topProductsRemitoUpdated', (eventData) => {
        productosData.remitos = eventData[0];
        if (currentProductView === 'remitos') {
            updateProductChart(productosData.remitos, 'remitos');
        }
    });

    Livewire.on('topProductsDeliveryUpdated', (eventData) => {
        productosData.entregas = eventData[0];
        if (currentProductView === 'entregas') {
            updateProductChart(productosData.entregas, 'entregas');
        }
    });

    Livewire.on('topWorkersUpdated', (eventData) => {
        updateRankingChart(eventData[0]);
    });
});
</script>
