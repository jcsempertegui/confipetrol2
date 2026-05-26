
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
                        <h4 class="my-1">{{ $totalProduct }}</h4>
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
                            <i class='bx bx-user'></i>
                        </div>
                        <h4 class="my-1">{{ $totalCustomers }}</h4>
                        <p class="mb-0 text-secondary">Clientes</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-deepblue text-deepblue mb-2">
                            <i class='bx bx-shopping-bag'></i>
                        </div>
                        <h4 class="my-1">{{ $totalPurchase }}</h4>
                        <p class="mb-0 text-secondary">Compras</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-orange text-orange mb-2">
                            <i class='bx bx-cart'></i>
                        </div>
                        <h4 class="my-1">{{ $totalSales }}</h4>
                        <p class="mb-0 text-secondary">Ventas</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-info text-info mb-2">
                            <i class='bx bx-receipt'></i>
                        </div>
                        <h4 class="my-1">{{ $totalOrders }}</h4>
                        <p class="mb-0 text-secondary">Cotizaciones</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-success text-success mb-2">
                            <i class='bx bx-plus-circle'></i>
                        </div>
                        <h4 class="my-1">{{ $totalIncomes }}</h4>
                        <p class="mb-0 text-secondary">Ingresos</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-danger text-danger mb-2">
                            <i class='bx bx-minus-circle'></i>
                        </div>
                        <h4 class="my-1">{{ $totalExpenses }}</h4>
                        <p class="mb-0 text-secondary">Egresos</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="text-center">
                        <div class="widgets-icons rounded-circle mx-auto bg-light-warning text-warning mb-2">
                            <i class='bx bx-time-five'></i>
                        </div>
                        <h4 class="my-1">{{ $totalExpiredLots }}</h4>
                        <p class="mb-0 text-secondary">Vencidos</p>
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
                            <h6 class="mb-0">Transacciones Anuales (Mes a Mes)</h6>
                        </div>
                    </div>
                    <div class="d-flex align-items-center ms-auto font-13 gap-2 my-3">
                        <span class="border px-1 rounded cursor-pointer">
                            <i class="bx bxs-circle me-1" style="color: #b83dba"></i>Compras
                        </span>
                        <span class="border px-1 rounded cursor-pointer">
                            <i class="bx bxs-circle me-1" style="color: #ffc107"></i>Ventas
                        </span>
                        <span class="border px-1 rounded cursor-pointer">
                            <i class="bx bxs-circle me-1" style="color: #14abef"></i>Cotizaciones
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
                                    <p class="mb-0 text-secondary">Total Compras</p>
                                    <h4 class="my-1">Bs. {{ number_format($mountPurchase, 2) }}</h4>
                                </div>
                                <div class="widgets-icons-2 bg-gradient-deepblue text-white ms-auto">
                                    <i class='bx bx-shopping-bag'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card radius-10 border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total Ventas</p>
                                    <h4 class="my-1">Bs. {{ number_format($mountSales, 2) }}</h4>
                                </div>
                                <div class="widgets-icons-2 bg-gradient-orange text-white ms-auto">
                                    <i class='bx bx-cart'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card radius-10 mb-0 border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total Cotizacion</p>
                                    <h4 class="my-1">Bs. {{ number_format($mountOrders, 2) }}</h4>
                                </div>
                                <div class="widgets-icons-2 bg-gradient-scooter text-white ms-auto">
                                    <i class='bx bx-receipt'></i>
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
                            <h6 class="mb-0">Flujo de Ventas Diarias (Mes Seleccionado)</h6>
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
                            <h6 class="mb-0">Ventas por Categoría</h6>
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
        <div class="col d-flex" x-data="{ view: 'mas' }">
            <div class="card radius-10 w-100">
                <div class="card-header px-3 py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0">Top 5 Productos</h6>
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" 
                                :class="view === 'mas' ? 'active' : ''" 
                                @click="view = 'mas'; toggleProductChart('mas')">
                                <i class='bx bx-trending-up me-1'></i>Más
                            </button>
                            <button type="button" class="btn btn-outline-danger ms-2" 
                                :class="view === 'menos' ? 'active' : ''" 
                                @click="view = 'menos'; toggleProductChart('menos')">
                                <i class='bx bx-trending-down me-1'></i>Menos
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

        <div class="col d-flex" x-data="{ view: 'vendedores' }">
            <div class="card radius-10 w-100">
                <div class="card-header px-3 py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0">Top Rankings</h6>
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-success" 
                                :class="view === 'vendedores' ? 'active' : ''" 
                                @click="view = 'vendedores'; toggleRankingChart('vendedores')">
                                <i class='bx bx-user-check me-1'></i>Vend
                            </button>
                            <button type="button" class="btn btn-outline-info ms-2" 
                                :class="view === 'clientes' ? 'active' : ''" 
                                @click="view = 'clientes'; toggleRankingChart('clientes')">
                                <i class='bx bx-group me-1'></i>Cli
                            </button>
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
                            <h6 class="mb-0">Ingresos vs Egresos</h6>
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
        mas: [],
        menos: []
    };
    
    let rankingData = {
        vendedores: [],
        clientes: []
    };
    
    let currentProductView = 'mas';
    let currentRankingView = 'vendedores';

    const updateChart = (salesPurchases) => {
        const ctx = document.getElementById('getSalesAndPurchasesByMonth').getContext('2d');
        if (chartInstance) {
            chartInstance.destroy();
        }

        var gradientStroke1 = ctx.createLinearGradient(0, 0, 0, 300);
        gradientStroke1.addColorStop(0, '#a07fcf');
        gradientStroke1.addColorStop(0.5, '#9a78d9');
        gradientStroke1.addColorStop(1, '#7e43b8');

        var gradientStroke2 = ctx.createLinearGradient(0, 0, 0, 300);
        gradientStroke2.addColorStop(0, '#ffc078');
        gradientStroke2.addColorStop(0.5, '#ffb347');
        gradientStroke2.addColorStop(1, '#ffe066');

        var gradientStroke3 = ctx.createLinearGradient(0, 0, 0, 300);
        gradientStroke3.addColorStop(0, '#6078ea');
        gradientStroke3.addColorStop(1, '#17c5ea');

        const solidColors = {
            compras: '#9a78d9',
            ventas: '#ffb347',
            cotizacion: '#17c5ea'
        };

        chartInstance = new Chart(ctx, {
            type: "line",
            data: {
                labels: salesPurchases.months,
                datasets: [{
                    label: 'Compras',
                    data: salesPurchases.purchasesTotals,
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: gradientStroke1,
                    borderColor: gradientStroke1,
                    borderWidth: 3,
                    fill: false,
                    tooltipColor: solidColors.compras
                }, {
                    label: 'Ventas',
                    data: salesPurchases.salesTotal,
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: gradientStroke2,
                    borderColor: gradientStroke2,
                    borderWidth: 3,
                    fill: false,
                    tooltipColor: solidColors.ventas
                }, {
                    label: 'Cotizaciones',
                    data: salesPurchases.ordersTotals,
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: gradientStroke3,
                    borderColor: gradientStroke3,
                    borderWidth: 3,
                    fill: false,
                    tooltipColor: solidColors.cotizacion
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom',
                    display: false
                },
                tooltips: {
                    displayColors: true,
                    mode: 'nearest',
                    intersect: false,
                    position: 'nearest',
                    xPadding: 10,
                    yPadding: 10,
                    caretPadding: 10,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var label = data.datasets[tooltipItem.datasetIndex].label || '';
                            if (label) {
                                label += ': ';
                            }
                            var value = tooltipItem.yLabel;
                            label += 'Bs.' + value;
                            return label;
                        },
                        title: function(tooltipItems, data) {
                            return tooltipItems[0].xLabel;
                        },
                        labelColor: function(tooltipItem, chart) {
                            const dataset = chart.data.datasets[tooltipItem.datasetIndex];
                            return {
                                borderColor: dataset.tooltipColor,
                                backgroundColor: dataset.tooltipColor
                            };
                        }
                    }
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            callback: function(value) {
                                return 'Bs. ' + value;
                            }
                        }
                    }]
                }
            }
        });
    }

    const updateDailySalesChart = (data) => {
        const ctx = document.getElementById('dailySalesChart').getContext('2d');
        if (dailySalesChart) {
            dailySalesChart.destroy();
        }

        var gradientStroke = ctx.createLinearGradient(0, 0, 0, 300);
        gradientStroke.addColorStop(0, 'rgba(255, 179, 71, 0.8)');
        gradientStroke.addColorStop(1, 'rgba(255, 179, 71, 0.2)');

        dailySalesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Ventas del Día',
                    data: data.data,
                    backgroundColor: gradientStroke,
                    borderColor: '#ffb347',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) { return 'Bs.' + value; }
                        }
                    }],
                    xAxes: [{
                        gridLines: { display: false }
                    }]
                },
                tooltips: {
                    callbacks: {
                        title: function(tooltipItems) { return 'Día ' + tooltipItems[0].xLabel; },
                        label: function(tooltipItem) { return 'Total: Bs.' + tooltipItem.yLabel; }
                    }
                }
            }
        });
    }

    const updateCategorySalesChart = (data) => {
        const ctx = document.getElementById('categorySalesChart').getContext('2d');
        if (categorySalesChart) {
            categorySalesChart.destroy();
        }

        let nombres = [];
        let valores = [];
        const colors = ['#8884d8', '#82ca9d', '#ffc658', '#ff8042', '#0088FE'];

        for (let i = 0; i < data.length; i++) {
            nombres.push(data[i].name);
            valores.push(data[i].total);
        }

        categorySalesChart = new Chart(ctx, {
            type: "pie",
            data: {
                labels: nombres,
                datasets: [{
                    backgroundColor: colors,
                    data: valores,
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    position: "right",
                    labels: { boxWidth: 12, fontSize: 11 }
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, dataObj) {
                            let label = dataObj.labels[tooltipItem.index];
                            let value = dataObj.datasets[0].data[tooltipItem.index];
                            return label + ': Bs.' + value;
                        }
                    }
                }
            }
        });
    }

    const updateProductChart = (data, tipo) => {
        const ctx = document.getElementById('topProductosChart').getContext('2d');
        if (topProductsChart) {
            topProductsChart.destroy();
        }

        const colors = tipo === 'mas' ? 
            ['#fc4a1a', '#4776e6', '#ee0979', '#42e695', '#667eea'] :
            ['#ff6b6b', '#ffa726', '#42a5f5', '#ab47bc', '#66bb6a'];

        let nombres = [];
        let cantidades = [];

        for (let i = 0; i < data.length; i++) {
            let nombre = data[i].name;
            if (nombre.length > 15) {
                nombre = nombre.substring(0, 15) + '...';
            }
            nombres.push(nombre);
            cantidades.push(data[i].total_vendido);
        }

        topProductsChart = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: nombres,
                datasets: [{
                    backgroundColor: colors,
                    hoverBackgroundColor: colors,
                    data: cantidades,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutoutPercentage: 65,
                legend: {
                    position: "right",
                    display: true,
                    labels: {
                        boxWidth: 12,
                        fontSize: 11,
                        fontColor: '#6c757d',
                        padding: 15
                    }
                },
                tooltips: {
                    displayColors: true,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            let label = data.labels[tooltipItem.index];
                            let value = data.datasets[0].data[tooltipItem.index];
                            return label + ': ' + value + ' unidades';
                        }
                    }
                }
            }
        });
    }

    const updateRankingChart = (data, tipo) => {
        const ctx = document.getElementById('topRankingChart').getContext('2d');
        if (topRankingChart) {
            topRankingChart.destroy();
        }

        let nombres = [];
        let valores = [];
        let label = tipo === 'vendedores' ? 'Ventas' : 'Compras';
        let color = tipo === 'vendedores' ? 'rgba(40, 167, 69, 0.8)' : 'rgba(23, 162, 184, 0.8)';
        let borderColor = tipo === 'vendedores' ? 'rgba(40, 167, 69, 1)' : 'rgba(23, 162, 184, 1)';

        for (let i = 0; i < data.length; i++) {
            let nombre = data[i].name || data[i].nombre;
            if (nombre.length > 12) {
                nombre = nombre.substring(0, 12) + '...';
            }
            nombres.push(nombre);
            valores.push(data[i].total_ventas || data[i].total);
        }

        topRankingChart = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: nombres,
                datasets: [{
                    label: label,
                    data: valores,
                    backgroundColor: color,
                    borderColor: borderColor,
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'Bs.' + value;
                            }
                        },
                        gridLines: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }],
                    yAxes: [{
                        gridLines: {
                            display: false
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return label + ': Bs.' + tooltipItem.xLabel;
                        }
                    }
                }
            }
        });
    }

    const updateIncomeExpenseChart = (data) => {
        const ctx = document.getElementById('incomeExpenseChart').getContext('2d');
        if (incomeExpenseChart) {
            incomeExpenseChart.destroy();
        }

        incomeExpenseChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Ingresos', 'Egresos'],
                datasets: [{
                    data: [data.ingresos, data.egresos],
                    backgroundColor: ['#17a2b8', '#dc3545'],
                    hoverBackgroundColor: ['#138496', '#c82333'],
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutoutPercentage: 60,
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        fontSize: 11,
                        fontColor: '#6c757d'
                    }
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, dataObj) {
                            let label = dataObj.labels[tooltipItem.index];
                            let value = dataObj.datasets[0].data[tooltipItem.index];
                            return label + ': Bs. ' + parseFloat(value).toFixed(2);
                        }
                    }
                }
            }
        });
    }

    window.toggleProductChart = function(tipo) {
        currentProductView = tipo;
        if (productosData[tipo] && productosData[tipo].length > 0) {
            updateProductChart(productosData[tipo], tipo);
        }
    }

    window.toggleRankingChart = function(tipo) {
        currentRankingView = tipo;
        if (rankingData[tipo] && rankingData[tipo].length > 0) {
            updateRankingChart(rankingData[tipo], tipo);
        }
    }

    Livewire.on('dataUpdated', (eventData) => {
        updateChart(eventData[0]);
        if (productosData.mas && productosData.mas.length > 0) {
            updateProductChart(productosData[currentProductView], currentProductView);
        }
        if (rankingData.vendedores && rankingData.vendedores.length > 0) {
            updateRankingChart(rankingData[currentRankingView], currentRankingView);
        }
    });

    Livewire.on('dailySalesUpdated', (eventData) => {
        updateDailySalesChart(eventData[0]);
    });

    Livewire.on('categorySalesUpdated', (eventData) => {
        updateCategorySalesChart(eventData[0]);
    });

    Livewire.on('incomeExpenseUpdated', (eventData) => {
        updateIncomeExpenseChart(eventData[0]);
    });

    Livewire.on('topProductsUpdated', (eventData) => {
        productosData.mas = eventData[0];
        if (currentProductView === 'mas') {
            updateProductChart(productosData.mas, 'mas');
        }
    });

    Livewire.on('lowProductsUpdated', (eventData) => {
        productosData.menos = eventData[0];
        if (currentProductView === 'menos') {
            updateProductChart(productosData.menos, 'menos');
        }
    });

    Livewire.on('topSellersUpdated', (eventData) => {
        rankingData.vendedores = eventData[0];
        if (currentRankingView === 'vendedores') {
            updateRankingChart(rankingData.vendedores, 'vendedores');
        }
    });

    Livewire.on('topCustomersUpdated', (eventData) => {
        rankingData.clientes = eventData[0];
        if (currentRankingView === 'clientes') {
            updateRankingChart(rankingData.clientes, 'clientes');
        }
    });
});
</script>