<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Branche;
use App\Models\Setting;
use App\Models\Payment;
use App\Models\Movement;
use App\Models\Inventorie;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Shuchkin\SimpleXLSXGen;

class GlobalEarningReportsController extends Component
{
    public $branches, $users;
    public $fromDate, $toDate;

    public $totalIngresos = 0;
    public $totalEgresos = 0;
    public $totalGanancia = 0;
    public $totalUtilidadBruta = 0;
    public $totalUtilidadNeta = 0;
    public $totalPatrimonio = 0;

    public $totalVentas = 0;
    public $totalCompras = 0;
    public $otrosIngresos = 0;
    public $otrosEgresos = 0;
    public $margenPromedio = 0;

    public $totalTesoreriaIngresos = 0;
    public $totalTesoreriaEgresos = 0;

    public $sucursalesData = [];
    public $metodosData = [];

    public function mount()
    {
        $this->branches = Branche::where('status', 1)->get();
        $this->users = User::where('status', 1)->get();
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $this->calculateGlobalEarnings();

        return view('livewire.reports.global_earnings_reports', [
            'sucursalesData' => $this->sucursalesData,
            'metodosData' => $this->metodosData,
        ])->extends('layouts.theme.app');
    }

    public function calculateGlobalEarnings()
    {
        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate = Carbon::parse($this->toDate ?? now())->endOfDay();

        $this->resetTotals();

        $branches = Branche::where('status', 1)->get();

        foreach ($branches as $branch) {
            $sucursalData = $this->calculateBranchEarnings($branch->id, $fromDate, $toDate);
            $sucursalData['name'] = $branch->name;

            $patrimonioSucursal = $this->calculateBranchEquity($branch->id);
            $sucursalData['patrimonio'] = $patrimonioSucursal;

            $this->sucursalesData[] = $sucursalData;

            $this->addToGlobalTotals($sucursalData);
        }

        $this->margenPromedio = $this->totalVentas > 0 ? ($this->totalUtilidadBruta / $this->totalVentas) * 100 : 0;

        $this->calculatePaymentMethods($fromDate, $toDate);
    }

    private function resetTotals()
    {
        $this->totalIngresos = 0;
        $this->totalEgresos = 0;
        $this->totalGanancia = 0;
        $this->totalUtilidadBruta = 0;
        $this->totalUtilidadNeta = 0;
        $this->totalPatrimonio = 0;
        $this->totalVentas = 0;
        $this->totalCompras = 0;
        $this->otrosIngresos = 0;
        $this->otrosEgresos = 0;
        $this->totalTesoreriaIngresos = 0;
        $this->totalTesoreriaEgresos = 0;
        $this->sucursalesData = [];
        $this->metodosData = [];
    }

    private function addToGlobalTotals($sucursalData)
    {
        $this->totalIngresos += $sucursalData['ingresos'];
        $this->totalEgresos += $sucursalData['egresos'];
        $this->totalGanancia += $sucursalData['ganancia'];
        $this->totalUtilidadBruta += $sucursalData['utilidad_bruta'];
        $this->totalUtilidadNeta += $sucursalData['utilidad_neta'];
        $this->totalVentas += $sucursalData['total_ventas'];
        $this->totalCompras += $sucursalData['total_compras'];
        $this->otrosIngresos += $sucursalData['otros_ingresos'];
        $this->otrosEgresos += $sucursalData['otros_egresos'];
        $this->totalPatrimonio += $sucursalData['patrimonio'];
        $this->totalTesoreriaIngresos += $sucursalData['tesoreria_ingresos'];
        $this->totalTesoreriaEgresos += $sucursalData['tesoreria_egresos'];
    }

    private function calculateBranchEquity($branchId)
    {
        $inventories = Inventorie::join('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
            ->where('warehouses.branch_id', $branchId)
            ->select(
                'inventories.product_id',
                'inventories.warehouse_id',
                'inventories.stock_lot',
                'inventories.stock_nolot',
                'inventories.purchase_price'
            )
            ->get();

        $totalEquity = 0;

        foreach ($inventories as $inv) {
            $stockActual = $inv->stock_lot + $inv->stock_nolot;

            if ($stockActual <= 0) {
                continue;
            }

            $cppRows = DB::table('purchase_details')
                ->where('product_id', $inv->product_id)
                ->where('warehouse_id', $inv->warehouse_id)
                ->where('remaining_quantity', '>', 0)
                ->select('price_compra', 'unit_id', 'remaining_quantity')
                ->get();

            if ($cppRows->isEmpty()) {
                $totalEquity += $stockActual * $inv->purchase_price;
                continue;
            }

            $totalCosto = 0;
            $totalUnidades = 0;

            foreach ($cppRows as $row) {
                $factor = 1;
                if ($row->unit_id) {
                    $unit = DB::table('units')->where('id', $row->unit_id)->first();
                    if ($unit && $unit->factor > 0) {
                        $factor = $unit->factor;
                    }
                }
                $costoBase = $factor > 0 ? ($row->price_compra / $factor) : $row->price_compra;
                $totalCosto += $row->remaining_quantity * $costoBase;
                $totalUnidades += $row->remaining_quantity;
            }

            if ($totalUnidades > 0) {
                $cpp = $totalCosto / $totalUnidades;
            } else {
                $cpp = $inv->purchase_price;
            }

            $totalEquity += $stockActual * $cpp;
        }

        return $totalEquity;
    }

    private function calculateBranchEarnings($branchId, $fromDate, $toDate)
    {
        $tiposOperativos = ['VENTA', 'COMPRA', 'ANTICIPO RESERVA'];
        $tiposIgnorar   = ['APERTURA DE CAJA', 'CIERRE DE CAJA'];

        $movimientos = Movement::where('branch_id', $branchId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $totalVentas = Sale::where('branch_id', $branchId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->where('status', 1)
            ->sum('total');

        $totalCompras = $movimientos
            ->where('type', 'EGRESO')
            ->where('type_movements', 'COMPRA')
            ->sum('amount');

        $anticipoIngresos = $movimientos
            ->where('type', 'INGRESO')
            ->where('type_movements', 'ANTICIPO RESERVA')
            ->sum('amount');

        $tesoreriaIngresos = $movimientos
            ->where('type', 'INGRESO')
            ->filter(function ($m) use ($tiposOperativos, $tiposIgnorar) {
                return !in_array($m->type_movements, array_merge($tiposOperativos, $tiposIgnorar));
            })
            ->sum('amount');

        $tesoreriaEgresos = $movimientos
            ->where('type', 'EGRESO')
            ->filter(function ($m) use ($tiposOperativos, $tiposIgnorar) {
                return !in_array($m->type_movements, array_merge($tiposOperativos, $tiposIgnorar));
            })
            ->sum('amount');

        $totalIngresos = $totalVentas + $anticipoIngresos;
        $totalEgresos  = $totalCompras;

        $utilidadBruta = SaleDetail::select(DB::raw('SUM((sale_price - purchase_price) * quantity) as utilidad_total'))
            ->whereHas('sale', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)->where('status', 1);
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->first()->utilidad_total ?? 0;

        $ventasStats = SaleDetail::select(DB::raw('COUNT(*) as productos_count, SUM(quantity) as cantidad_total'))
            ->whereHas('sale', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)->where('status', 1);
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->first();

        $utilidadNeta = $utilidadBruta;

        return [
            'branch_id'         => $branchId,
            'ingresos'          => $totalIngresos,
            'egresos'           => $totalEgresos,
            'ganancia'          => $totalIngresos - $totalEgresos,
            'utilidad_bruta'    => $utilidadBruta,
            'utilidad_neta'     => $utilidadNeta,
            'ventas_cantidad'   => $ventasStats->cantidad_total ?? 0,
            'ventas_productos'  => $ventasStats->productos_count ?? 0,
            'total_ventas'      => $totalVentas,
            'total_compras'     => $totalCompras,
            'otros_ingresos'    => $anticipoIngresos,
            'otros_egresos'     => 0,
            'tesoreria_ingresos'=> $tesoreriaIngresos,
            'tesoreria_egresos' => $tesoreriaEgresos,
            'flujo_operativo'   => $totalVentas - $totalCompras,
            'margen_porcentual' => $totalVentas > 0 ? ($utilidadBruta / $totalVentas) * 100 : 0,
        ];
    }

    private function calculatePaymentMethods($fromDate, $toDate)
    {
        $pagosData = Payment::where('transaction_type', 'sales')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->whereHas('sale', function ($q) {
                $q->where('status', 1);
            })
            ->select('description', DB::raw('SUM(amount) as total'))
            ->groupBy('description')
            ->get()
            ->pluck('total', 'description')
            ->toArray();

        $this->metodosData = [
            'EFECTIVO' => $pagosData['EFECTIVO'] ?? 0,
            'QR'       => $pagosData['QR'] ?? 0,
            'TARJETA'  => $pagosData['TARJETA'] ?? 0,
        ];

        foreach ($pagosData as $metodo => $total) {
            if (!in_array($metodo, ['EFECTIVO', 'QR', 'TARJETA'])) {
                $this->metodosData[$metodo] = $total;
            }
        }
    }

    public function consultarGanancias()
    {
        $this->calculateGlobalEarnings();
        $this->dispatch('earnings-updated');
    }

    public function exportToPdf($fromDate, $toDate)
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(600);

        $this->fromDate = $fromDate;
        $this->toDate = $toDate;

        $settings = Setting::first();
        $fromDateParsed = Carbon::parse($fromDate);
        $toDateParsed   = Carbon::parse($toDate);

        $this->calculateGlobalEarnings();

        $pdf = PDF::loadView('rooms.reports.global_earnings_pdf', [
            'settings'              => $settings,
            'fromDate'              => $fromDateParsed->format('d/m/Y'),
            'toDate'                => $toDateParsed->format('d/m/Y'),
            'totalIngresos'         => $this->totalIngresos,
            'totalEgresos'          => $this->totalEgresos,
            'totalGanancia'         => $this->totalGanancia,
            'totalUtilidadBruta'    => $this->totalUtilidadBruta,
            'totalUtilidadNeta'     => $this->totalUtilidadNeta,
            'totalPatrimonio'       => $this->totalPatrimonio,
            'totalVentas'           => $this->totalVentas,
            'totalCompras'          => $this->totalCompras,
            'margenPromedio'        => $this->margenPromedio,
            'sucursalesData'        => $this->sucursalesData,
            'metodosData'           => $this->metodosData,
            'totalTesoreriaIngresos'=> $this->totalTesoreriaIngresos,
            'totalTesoreriaEgresos' => $this->totalTesoreriaEgresos,
        ])
            ->setOption('defaultFont', 'sans-serif')
            ->setPaper('letter', 'portrait')
            ->setWarnings(false);

        return $pdf->stream('ganancias_global.pdf');
    }

    public function exportToExcel()
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(600);

        $this->calculateGlobalEarnings();
        $settings = Setting::first();
        $date = date('d/m/Y H:i');

        $businessName = $settings ? mb_strtoupper($settings->business) : 'EMPRESA';
        $borderColor = '#d0d0d0';

        $data = [];
        $data[] = ['<center><style font-size="16"><b>' . $businessName . '</b></style></center>', null, null, null, null, null, null];
        $data[] = ['<center><style font-size="12" bgcolor="#EFEFEF"><b>REPORTE DE GANANCIAS GLOBAL</b></style></center>', null, null, null, null, null, null];
        $data[] = ['Desde: ' . Carbon::parse($this->fromDate)->format('d/m/Y') . ' Hasta: ' . Carbon::parse($this->toDate)->format('d/m/Y'), null, null, null, null, null, null];
        $data[] = ['Generado: ' . $date, null, null, null, null, null, null];
        $data[] = [''];

        $data[] = ['<style bgcolor="#f8f9fa"><b>RESUMEN GENERAL</b></style>', null, null, null, null, null, null];
        $data[] = ['Total Ingresos:', number_format($this->totalIngresos, 2), '', 'Utilidad Bruta:', number_format($this->totalUtilidadBruta, 2), '', ''];
        $data[] = ['Total Egresos:', number_format($this->totalEgresos, 2), '', 'Utilidad Neta:', number_format($this->totalUtilidadNeta, 2), '', ''];
        $data[] = ['Flujo de Caja:', number_format($this->totalGanancia, 2), '', 'Total Patrimonio:', number_format($this->totalPatrimonio, 2), '', ''];
        $data[] = [''];

        $hStyle = '<style bgcolor="#FC0038" color="#FFFFFF" border="1" border-color="' . $borderColor . '" font-size="10"><b>';
        $hEnd = '</b></style>';
        
        $data[] = [
            $hStyle . 'SUCURSAL' . $hEnd,
            $hStyle . 'INGRESOS' . $hEnd,
            $hStyle . 'EGRESOS' . $hEnd,
            $hStyle . 'FLUJO CAJA' . $hEnd,
            $hStyle . 'PATRIMONIO' . $hEnd,
            $hStyle . 'UTILIDAD BRUTA' . $hEnd,
            $hStyle . 'UTILIDAD NETA' . $hEnd
        ];

        $bodyStyle = '<style font-size="10" border="1" border-color="' . $borderColor . '">';
        $bodyEnd = '</style>';

        foreach ($this->sucursalesData as $sucursal) {
            $data[] = [
                $bodyStyle . $sucursal['name'] . $bodyEnd,
                $bodyStyle . '<right>' . number_format($sucursal['ingresos'], 2) . '</right>' . $bodyEnd,
                $bodyStyle . '<right>' . number_format($sucursal['egresos'], 2) . '</right>' . $bodyEnd,
                $bodyStyle . '<right>' . number_format($sucursal['ganancia'], 2) . '</right>' . $bodyEnd,
                $bodyStyle . '<right>' . number_format($sucursal['patrimonio'], 2) . '</right>' . $bodyEnd,
                $bodyStyle . '<right>' . number_format($sucursal['utilidad_bruta'], 2) . '</right>' . $bodyEnd,
                $bodyStyle . '<right>' . number_format($sucursal['utilidad_neta'], 2) . '</right>' . $bodyEnd
            ];
        }

        $data[] = [''];
        $data[] = ['<style bgcolor="#f8f9fa"><b>METODOS DE PAGO</b></style>', null, null, null, null, null, null];
        foreach ($this->metodosData as $metodo => $total) {
            $data[] = [$metodo, number_format($total, 2), '', '', '', '', ''];
        }

        $xlsx = SimpleXLSXGen::fromArray($data);
        $xlsx->mergeCells('A1:G1');
        $xlsx->mergeCells('A2:G2');
        $xlsx->mergeCells('A3:G3');
        $xlsx->mergeCells('A4:G4');
        $xlsx->mergeCells('A6:G6');
        $xlsx->mergeCells('A12:G12');

        $fileName = 'ganancias_global_' . time() . '.xlsx';
        $path = storage_path('app/public/' . $fileName);
        $xlsx->saveAs($path);

        return response()->download($path)->deleteFileAfterSend();
    }

    public function updatedFromDate()
    {
        $this->calculateGlobalEarnings();
    }

    public function updatedToDate()
    {
        $this->calculateGlobalEarnings();
    }
}