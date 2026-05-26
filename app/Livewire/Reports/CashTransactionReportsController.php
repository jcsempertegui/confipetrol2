<?php
namespace App\Livewire\Reports;

use App\Models\User;
use App\Models\Movement;
use App\Models\Branche;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CashTransactionReportsController extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $searchTerm;
    public $branches, $branch_id, $users, $user_id;
    public $fromDate, $toDate;
    public $type = '';
    public $totalTransactions, $totalAmount, $totalIngresos, $totalEgresos;

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->users = User::where('status', 1)->get();
        $this->branches = Branche::where('status', 1)->get();
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $transactions = $this->TransactionsByDate();
        $this->totales();

        return view('livewire.reports.cash_transaction_reports', [
            'transactions' => $transactions,
            'startCount' => $transactions->total() - ($transactions->currentPage() - 1) * $transactions->perPage()
        ])
            ->extends('layouts.theme.app');
    }

    public function TransactionsByDate()
    {
        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate = Carbon::parse($this->toDate ?? now())->endOfDay();
        $branch_id = $this->branch_id;
        $user_id = $this->user_id;
        $type = $this->type;

        $query = Movement::with(['user', 'branch'])
            ->where('branch_id', $branch_id)
            ->where('status', 1)
            ->whereIn('type_movements', ['MOVIMIENTO DE CAJA', 'TESORERIA'])
            ->when($user_id, function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('amount', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('type', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('user', function ($q) {
                            $q->where('login', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return $query;
    }

    public function totales()
    {
        $fromDate = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $toDate = Carbon::parse($this->toDate ?? now())->endOfDay();
        $branch_id = $this->branch_id;
        $user_id = $this->user_id;
        $type = $this->type;

        $transactions = Movement::where('branch_id', $branch_id)
            ->where('status', 1)
            ->whereIn('type_movements', ['MOVIMIENTO DE CAJA', 'TESORERIA'])
            ->when($user_id, function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            })
            ->get();

        $this->totalTransactions = $transactions->count();
        $this->totalIngresos = $transactions->where('type', 'INGRESO')->sum('amount');
        $this->totalEgresos = $transactions->where('type', 'EGRESO')->sum('amount');
        $this->totalAmount = $this->totalIngresos - $this->totalEgresos;
    }

    public function cashTransactionReportPdf($fromDate, $toDate, $branch_id, $user_id = null, $type = null)
    {
        $settings = Setting::first();
        $users = User::first();
        
        if ($user_id == '0') {
            $user_id = null;
        }
        
        if ($type == 'all') {
            $type = null;
        }

        $transactions = Movement::with(['user', 'branch'])
            ->where('branch_id', $branch_id)
            ->where('status', 1)
            ->whereIn('type_movements', ['MOVIMIENTO DE CAJA', 'TESORERIA'])
            ->when($user_id, function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [
                    Carbon::parse($fromDate)->startOfDay(),
                    Carbon::parse($toDate)->endOfDay()
                ]);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $totalTransactions = $transactions->count();
        $totalIngresos = $transactions->where('type', 'INGRESO')->sum('amount');
        $totalEgresos = $transactions->where('type', 'EGRESO')->sum('amount');
        $totalAmount = $totalIngresos - $totalEgresos;

        $pdf = PDF::loadView('rooms.reports.reportCashTransactionPdf', [
            'transactions' => $transactions,
            'users' => $users,
            'settings' => $settings,
            'totalTransactions' => $totalTransactions,
            'totalIngresos' => $totalIngresos,
            'totalEgresos' => $totalEgresos,
            'totalAmount' => $totalAmount,
        ])
            ->setOption('defaultFont', 'sans-serif')
            ->setPaper('letter', 'landscape')
            ->setWarnings(false);

        return $pdf->stream('cashTransactionPdf.pdf');
    }
    
    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }
}