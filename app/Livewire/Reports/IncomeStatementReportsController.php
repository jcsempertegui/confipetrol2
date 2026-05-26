<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Models\Branche;
use App\Models\Setting;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\CashTransaction;
use App\Models\Movement;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class IncomeStatementReportsController extends Component
{
    // Filtros
    public $branches, $branch_id;
    public $fromDate, $toDate;

    // === INGRESOS ===
    public $totalSales;           // Ventas Totales (de sales)
    public $otherIncome;          // Otros Ingresos (cash_transactions INGRESO)
    public $totalIncome;          // Total Ingresos

    // === COSTOS Y GASTOS ===
    public $costOfSales;          // Costo de Ventas (de sales)
    public $operatingExpenses;    // Gastos de Operación (cash_transactions EGRESO)
    public $totalCostsExpenses;   // Total Costos y Gastos

    // === UTILIDAD OPERATIVA ===
    public $operatingProfit;      // Utilidad Operativa (Ingresos - Costos)

    // === OTROS RESULTADOS ===
    public $financialIncome;      // Ingresos Financieros
    public $financialExpenses;    // Gastos Financieros
    public $netOtherResults;      // Resultado Neto Otros

    // === UTILIDAD ANTES DE IMPUESTOS ===
    public $incomeBeforeTax;      // Utilidad Antes de Impuestos

    // === IMPUESTOS ===
    public $taxes;                // Impuestos (IUE 25% en Bolivia)

    // === UTILIDAD NETA ===
    public $netIncome;            // Utilidad Neta del Ejercicio

    public function mount()
    {
        $this->branches = Branche::where('status', 1)->get();
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function render()
    {
        // Calcular todos los valores
        $this->calculateIncomeStatement();

        return view('livewire.reports.income_statement_reports')
            ->extends('layouts.theme.app');
    }

    public function generateReport()
    {
        // El método render se encarga de refrescar la data automáticamente
    }

    // --- LÓGICA CENTRALIZADA ---

    private function calculateIncomeStatement()
    {
        $fromDate = Carbon::parse($this->fromDate)->startOfDay();
        $toDate = Carbon::parse($this->toDate)->endOfDay();
        $branch_id = $this->branch_id;

        // ========================================
        // 1. INGRESOS
        // ========================================

        // 1.1 Ventas (Total de ventas del período)
        $this->totalSales = Sale::where('branch_id', $branch_id)
            ->where('status', 1)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('total');

        // 1.2 Otros Ingresos (Ingresos extras registrados en cash_transactions)
        $this->otherIncome = CashTransaction::where('type', 'INGRESO')
            ->where('branch_id', $branch_id)
            ->where('status', 1)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('amount');

        // 1.3 Total Ingresos
        $this->totalIncome = $this->totalSales + $this->otherIncome;

        // ========================================
        // 2. COSTOS Y GASTOS
        // ========================================

        // 2.1 Costo de Ventas (Costo de productos vendidos)
        $this->costOfSales = SaleDetail::whereHas('sale', function ($query) use ($branch_id, $fromDate, $toDate) {
            $query->where('branch_id', $branch_id)
                  ->where('status', 1)
                  ->whereBetween('created_at', [$fromDate, $toDate]);
        })->get()->sum(function ($detail) {
            return $detail->quantity * $detail->purchase_price;
        });

        // 2.2 Gastos de Operación (Egresos extras)
        $this->operatingExpenses = CashTransaction::where('type', 'EGRESO')
            ->where('branch_id', $branch_id)
            ->where('status', 1)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('amount');

        // 2.3 Total Costos y Gastos
        $this->totalCostsExpenses = $this->costOfSales + $this->operatingExpenses;

        // ========================================
        // 3. UTILIDAD OPERATIVA
        // ========================================
        $this->operatingProfit = $this->totalIncome - $this->totalCostsExpenses;

        // ========================================
        // 4. OTROS RESULTADOS (Por ahora en 0 - se pueden agregar después)
        // ========================================
        $this->financialIncome = 0;      // Ingresos por intereses bancarios
        $this->financialExpenses = 0;    // Gastos por intereses, comisiones bancarias
        $this->netOtherResults = $this->financialIncome - $this->financialExpenses;

        // ========================================
        // 5. UTILIDAD ANTES DE IMPUESTOS
        // ========================================
        $this->incomeBeforeTax = $this->operatingProfit + $this->netOtherResults;

        // ========================================
        // 6. IMPUESTOS (IUE 25% en Bolivia)
        // ========================================
        // Solo aplicamos impuesto si hay utilidad positiva
        $this->taxes = $this->incomeBeforeTax > 0 ? ($this->incomeBeforeTax * 0.25) : 0;

        // ========================================
        // 7. UTILIDAD NETA DEL EJERCICIO
        // ========================================
        $this->netIncome = $this->incomeBeforeTax - $this->taxes;
    }

    // --- PDF ---

    public function incomeStatementPdf($fromDate, $toDate, $branch_id)
    {
        // Actualizamos propiedades temporales
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->branch_id = $branch_id;

        $settings = Setting::first();
        $user = auth()->user();
        $branch = Branche::find($branch_id);

        // Recalculamos datos
        $this->calculateIncomeStatement();

        $pdf = PDF::loadView('rooms.reports.reportIncomeStatementPdf', [
            'settings' => $settings,
            'users' => $user,
            'branch' => $branch,
            'fromDate' => Carbon::parse($fromDate)->format('d/m/Y'),
            'toDate' => Carbon::parse($toDate)->format('d/m/Y'),
            
            // Ingresos
            'totalSales' => $this->totalSales,
            'otherIncome' => $this->otherIncome,
            'totalIncome' => $this->totalIncome,
            
            // Costos y Gastos
            'costOfSales' => $this->costOfSales,
            'operatingExpenses' => $this->operatingExpenses,
            'totalCostsExpenses' => $this->totalCostsExpenses,
            
            // Utilidad Operativa
            'operatingProfit' => $this->operatingProfit,
            
            // Otros Resultados
            'financialIncome' => $this->financialIncome,
            'financialExpenses' => $this->financialExpenses,
            'netOtherResults' => $this->netOtherResults,
            
            // Utilidad Antes de Impuestos
            'incomeBeforeTax' => $this->incomeBeforeTax,
            
            // Impuestos
            'taxes' => $this->taxes,
            
            // Utilidad Neta
            'netIncome' => $this->netIncome,
        ])
        ->setOption('defaultFont', 'sans-serif')
        ->setPaper('letter', 'portrait')
        ->setWarnings(false);

        return $pdf->stream('EstadoDeResultados.pdf');
    }
}