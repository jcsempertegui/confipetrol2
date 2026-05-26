<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Setting;
use App\Models\DocumentSetting;
use App\Models\Branche;
use App\Models\Printer;
use App\Models\ProductionArea;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $selected_tab = 'ajustes';

    public $business, $owner, $nit, $email, $phone, $address, $message, $image, $image_preview;
    public $setting_id;

    public $system_license_plan;
    public $system_payment_type;
    public $system_license_start_date;
    public $system_license_end_date;
    public $system_months_paid;
    public $system_years_paid;
    public $system_total_months_paid;
    public $system_license_status;
    public $system_license_status_text;
    public $system_days_remaining;

    public $pos_type, $has_production_areas, $requires_cashbox, $camera_barcode_enabled, $loyalty_program, $online_orders, $advanced_reports, $enable_size_color, $enable_product_gallery, $enable_staff_per_detail;
    
    public $invoice_type, $default_tax, $default_currency, $ambiente, $codigo_sistema, $token;
    public $email_notifications, $sms_notifications, $low_stock_alerts;

    public $branch_max_users;
    public $branch_max_sessions;

    public $printer_id, $printer_name, $printer_type = '', $printer_ip;
    public $connection_type = 'network', $print_behavior = 'none', $printer_copies = 1;
    public $printer_is_default = false;

    public $isEditModePrinter = false;
    public $searchTerm;

    public $production_area_id, $production_area_name, $production_area_printer_id;
    public $isEditModeProductionArea = false;

    public $branch_id;

    public $doc_type = 'nota_venta';
    public $doc_paper_size = '80';
    public $doc_show_logo = true;
    public $doc_show_business_name = true;
    public $doc_show_address = true;
    public $doc_show_phone = true;
    public $doc_show_client = true;
    public $doc_show_cashier = true;
    public $doc_show_unit_price = false;
    public $doc_custom_title = 'Nota de Venta';
    public $doc_footer_text = 'Generada a través de MASTEC DIGITAL.';

    protected $listeners = ['deletePrinter', 'deleteProductionArea'];

    public $perPage = 20;
    public $perPageOptions = [20, 50, 100];

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function refreshData($branchId = null)
    {
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
        $this->loadSettings();
        $this->loadSystemLicense();
        $this->loadBranchSettings();
        $this->loadDocumentSettings();
        $this->resetPage();
    }

    public function mount()
    {
        $this->branch_id = session('branch_user_id') ?? auth()->user()->branch_id;

        if (!$this->branch_id && auth()->check()) {
            $this->branch_id = auth()->user()->branch_id;
        }

        $this->loadSettings();
        $this->loadSystemLicense();
        $this->loadBranchSettings();
        $this->loadDocumentSettings();
    }

    public function render()
    {
        $printers = $this->getPaginatedPrinters();
        $productionAreas = $this->getPaginatedProductionAreas();
        $activePrinters = Printer::where('branch_id', $this->branch_id)->where('status', 1)->get();

        return view('livewire.settings.settings', [
            'printers' => $printers,
            'startCount' => $printers->total() - ($printers->currentPage() - 1) * $printers->perPage(),
            'productionAreas' => $productionAreas,
            'startCountPA' => $productionAreas->total() - ($productionAreas->currentPage() - 1) * $productionAreas->perPage(),
            'activePrinters' => $activePrinters
        ])->extends('layouts.theme.app');
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function loadSettings()
    {
        $setting = Setting::where('branch_id', $this->branch_id)->first();
        $branch = Branche::find($this->branch_id);

        if ($setting) {
            $this->setting_id = $setting->id;
            $this->business = $setting->business;
            $this->owner = $setting->owner;
            $this->nit = $setting->nit;
            $this->email = $setting->email;
            $this->message = $setting->message;
            $this->image_preview = $setting->image;
        } else {
            $this->resetSettingsFields();
        }

        if ($branch) {
            $this->phone = $branch->phone;
            $this->address = $branch->address;
        }
    }

    public function resetSettingsFields()
    {
        $this->setting_id = null;
        $this->business = '';
        $this->owner = '';
        $this->nit = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->message = '';
        $this->image = null;
        $this->image_preview = null;
    }

    public function updateSettings()
    {
        $this->validate([
            'business' => 'required|string|max:255',
            'owner' => 'required|string|max:255',
            'nit' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|numeric|digits_between:7,12',
            'address' => 'nullable|string|max:200',
            'image' => 'nullable|max:20480',
        ]);

        $currentSetting = Setting::where('branch_id', $this->branch_id)->first();
        $imagePath = $currentSetting ? $currentSetting->image : null;

        if ($this->image && $this->image instanceof \Illuminate\Http\UploadedFile) {
            ini_set('memory_limit', '1024M');
            set_time_limit(300);

            if ($currentSetting && $currentSetting->image && Storage::disk('public')->exists($currentSetting->image)) {
                Storage::disk('public')->delete($currentSetting->image);
            }
            try {
                if (!extension_loaded('gd')) {
                    $this->dispatch('settingsUpdate', 'ERROR: Librería GD no activa.', 'error');
                    return;
                }
                $manager = new ImageManager(new Driver());
                $filename = 'LOGO_' . time() . '.png';
                $img = $manager->read($this->image->getRealPath());
                $img->scaleDown(width: 1920);
                $encoded = (string) $img->toPng();
                Storage::disk('public')->put('settings/' . $filename, $encoded);
                $imagePath = 'settings/' . $filename;
                $this->image_preview = $imagePath;
                unset($img, $encoded);
            } catch (\Exception $e) {
                $this->dispatch('settingsUpdate', 'Error imagen: ' . $e->getMessage(), 'error');
                return;
            }
        }

        try {
            DB::beginTransaction();

            Setting::updateOrCreate(
                ['branch_id' => $this->branch_id],
                [
                    'business' => $this->business,
                    'owner' => $this->owner,
                    'nit' => $this->nit,
                    'email' => $this->email,
                    'message' => $this->message,
                    'image' => $imagePath,
                ]
            );

            $branch = Branche::find($this->branch_id);
            if ($branch) {
                $branch->update([
                    'phone' => $this->phone,
                    'address' => $this->address,
                ]);
            }

            DB::commit();
            Cache::forget('sidebar_branch_' . $this->branch_id);
            $this->image = null;
            $this->dispatch('settingsUpdate', 'DATOS DE NEGOCIO ACTUALIZADOS', 'success');

            $this->dispatch('update-sidebar', [
                'businessName' => $this->business,
                'logoImage' => $imagePath ? asset('storage/' . $imagePath) : asset('assets/images/login.png')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('settingsUpdate', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    public function loadSystemLicense()
    {
        $systemSetting = Setting::where('branch_id', $this->branch_id)->first();

        if ($systemSetting) {
            $this->system_license_plan = $systemSetting->license_plan ?? 'estandar';
            $this->system_payment_type = $systemSetting->payment_type ?? 'mensual';
            $this->system_license_start_date = $systemSetting->license_start_date;
            $this->system_months_paid = $systemSetting->months_paid ?? 1;
            $this->system_years_paid = $systemSetting->years_paid ?? 0;
            $this->calculateSystemLicense();
        } else {
            $this->resetSystemLicenseFields();
        }
    }

    public function resetSystemLicenseFields()
    {
        $this->system_license_plan = 'estandar';
        $this->system_payment_type = 'mensual';
        $this->system_license_start_date = now()->format('Y-m-d');
        $this->system_license_end_date = null;
        $this->system_months_paid = 1;
        $this->system_years_paid = 0;
        $this->system_total_months_paid = 1;
        $this->system_license_status = 'active';
        $this->system_license_status_text = 'Activa';
        $this->system_days_remaining = 30;
    }

    public function calculateSystemLicense()
    {
        if (!$this->system_license_start_date) {
            $this->system_license_start_date = now()->format('Y-m-d');
        }
        if ($this->system_payment_type === 'anual') {
            $this->system_total_months_paid = (int) $this->system_years_paid * 12;
        } else {
            $this->system_total_months_paid = (int) $this->system_months_paid;
        }
        $startDate = Carbon::parse($this->system_license_start_date);
        $this->system_license_end_date = $startDate->copy()->addMonths((int) $this->system_total_months_paid)->format('Y-m-d');
        $endDate = Carbon::parse($this->system_license_end_date);
        $today = Carbon::today();
        $this->system_days_remaining = $today->diffInDays($endDate, false);

        if ($this->system_days_remaining < 0) {
            $this->system_license_status = 'expired';
            $this->system_license_status_text = 'Expirada';
        } elseif ($this->system_days_remaining <= 30) {
            $this->system_license_status = 'expiring';
            $this->system_license_status_text = 'Por Vencer';
        } else {
            $this->system_license_status = 'active';
            $this->system_license_status_text = 'Activa';
        }
    }

    public function updatedSystemPaymentType()
    {
        $this->calculateSystemLicense();
    }
    public function updatedSystemMonthsPaid()
    {
        $this->calculateSystemLicense();
    }
    public function updatedSystemYearsPaid()
    {
        $this->calculateSystemLicense();
    }
    public function updatedSystemLicenseStartDate()
    {
        $this->calculateSystemLicense();
    }

    public function updateSystemLicense()
    {
        $this->validate([
            'system_license_plan' => 'required',
            'system_payment_type' => 'required',
            'system_license_start_date' => 'required|date',
        ]);

        try {
            Setting::updateOrCreate(
                ['branch_id' => $this->branch_id],
                [
                    'license_plan' => $this->system_license_plan,
                    'payment_type' => $this->system_payment_type,
                    'license_start_date' => $this->system_license_start_date,
                    'license_end_date' => $this->system_license_end_date,
                    'months_paid' => $this->system_payment_type === 'mensual' ? (int) $this->system_months_paid : 0,
                    'years_paid' => $this->system_payment_type === 'anual' ? (int) $this->system_years_paid : 0
                ]
            );

            $this->calculateSystemLicense();
            $this->dispatch('settingsUpdate', 'LICENCIA ACTUALIZADA', 'success');
        } catch (\Exception $e) {
            $this->dispatch('settingsUpdate', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    public function loadBranchSettings()
    {
        $branch = Branche::find($this->branch_id);

        if ($branch) {
            $this->pos_type = $branch->pos_type;
            $this->has_production_areas = $branch->has_production_areas == 1;
            $this->requires_cashbox = $branch->requires_cashbox == 1;
            $this->camera_barcode_enabled = $branch->camera_barcode_enabled == 1;
            $this->loyalty_program = $branch->loyalty_program == 1;
            $this->online_orders = $branch->online_orders == 1;
            $this->advanced_reports = $branch->advanced_reports == 1;
            $this->enable_size_color = $branch->enable_size_color == 1;
            $this->enable_product_gallery = $branch->enable_product_gallery == 1; 
            $this->enable_staff_per_detail = $branch->enable_staff_per_detail == 1;

            $this->invoice_type = $branch->invoice_type;
            $this->default_tax = $branch->default_tax ?? 0;
            $this->default_currency = $branch->default_currency ?? 'BOB';
            
            $this->ambiente = $branch->ambiente;
            $this->codigo_sistema = $branch->codigo_sistema;
            $this->token = $branch->token;

            $this->branch_max_users = $branch->max_users;

            $firstUser = User::where('branch_id', $this->branch_id)->first();
            $this->branch_max_sessions = $firstUser ? $firstUser->max_sessions : 1;
        }
    }

    public function updateAdvancedSettings()
    {
        $rules = [
            'pos_type' => 'required',
            'default_currency' => 'required',
            'branch_max_users' => 'required|integer|min:1',
            'branch_max_sessions' => 'required|integer|min:1',
        ];

        if (in_array($this->invoice_type, ['electronica', 'computarizada'])) {
            $rules['ambiente'] = 'required';
            $rules['codigo_sistema'] = 'required';
            $rules['token'] = 'required';
        }

        $this->validate($rules);

        try {
            DB::beginTransaction();

            $branch = Branche::find($this->branch_id);
            if (!$branch) {
                $this->dispatch('settingsUpdate', 'Sucursal no encontrada', 'error');
                return;
            }

            $branch->update([
                'pos_type' => $this->pos_type,
                'has_production_areas' => $this->has_production_areas ? 1 : 0,
                'requires_cashbox' => $this->requires_cashbox ? 1 : 0,
                'camera_barcode_enabled' => $this->camera_barcode_enabled ? 1 : 0,
                'loyalty_program' => $this->loyalty_program ? 1 : 0,
                'online_orders' => $this->online_orders ? 1 : 0,
                'advanced_reports' => $this->advanced_reports ? 1 : 0,
                'enable_size_color' => $this->enable_size_color ? 1 : 0,
                'enable_product_gallery' => $this->enable_product_gallery ? 1 : 0,
                'enable_staff_per_detail' => $this->enable_staff_per_detail ? 1 : 0, 
                'invoice_type' => $this->invoice_type,
                'default_tax' => $this->default_tax ?? 0,
                'default_currency' => $this->default_currency,
                'ambiente' => $this->ambiente,
                'codigo_sistema' => $this->codigo_sistema,
                'token' => $this->token,
                'max_users' => $this->branch_max_users,
            ]);

            User::where('branch_id', $this->branch_id)
                ->update(['max_sessions' => $this->branch_max_sessions]);

            DB::commit();
            Cache::forget('sidebar_branch_' . $this->branch_id);
            $this->dispatch('settingsUpdate', 'AJUSTES GUARDADOS CORRECTAMENTE', 'success');
            
            $this->dispatch('update-sidebar', [
                'enableSizeColor' => $this->enable_size_color ? 1 : 0,
                'invoiceType' => $this->invoice_type,
                'posType' => $this->pos_type
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('settingsUpdate', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    public function getPaginatedPrinters()
    {
        return Printer::where('branch_id', $this->branch_id)
            ->where(function ($query) {
                if (strlen($this->searchTerm) > 0) {
                    $query->where('name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('type', 'like', '%' . $this->searchTerm . '%');
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
    }

    public function resetPrinterFields()
    {
        $this->resetValidation();
        $this->printer_id = null;
        $this->printer_name = '';
        $this->printer_type = ''; 
        $this->printer_ip = '';
        $this->connection_type = 'network';
        $this->print_behavior = 'none';
        $this->printer_copies = 1;
        $this->printer_is_default = false;
        $this->isEditModePrinter = false;
    }

    public function updatedPrintBehavior($value)
    {
        if ($value === 'direct') {
            if (!in_array($this->connection_type, ['network', 'usb', 'bluetooth'])) {
                $this->connection_type = 'network';
            }
        }
    }

    public function storeOrUpdatePrinter()
    {
        $this->validate([
            'printer_name' => 'required|string|max:255',
            'printer_type' => 'required|in:ticket_comanda,ticket,kitchen,recibo',
            'print_behavior' => 'required',
        ]);

        $ip = null;
        $conn = 'none';
        $copies = 1;

        if ($this->print_behavior === 'direct') {
            $this->validate([
                'connection_type' => 'required',
                'printer_copies' => 'required|integer|min:1',
            ]);

            if ($this->connection_type === 'network') {
                $this->validate(['printer_ip' => 'required|string']);
                $ip = $this->printer_ip;
            }

            $conn = $this->connection_type;
            $copies = $this->printer_copies;
        }

        try {
            DB::beginTransaction();

            if ($this->printer_is_default) {
                $query = Printer::where('branch_id', $this->branch_id);
                if ($this->printer_id) {
                    $query->where('id', '!=', $this->printer_id);
                }
                $query->update(['is_default' => 0]);
            }

            Printer::updateOrCreate(
                ['id' => $this->printer_id],
                [
                    'name' => $this->printer_name,
                    'type' => $this->printer_type,
                    'ip_address' => $ip,
                    'connection_type' => $conn,
                    'print_behavior' => $this->print_behavior,
                    'copies' => $copies,
                    'is_default' => $this->printer_is_default ? 1 : 0,
                    'status' => 1,
                    'branch_id' => $this->branch_id,
                ]
            );

            DB::commit();
            $msg = $this->isEditModePrinter ? 'IMPRESORA ACTUALIZADA' : 'IMPRESORA CREADA';
            $this->resetPrinterFields();
            $this->dispatch('printerStoreOrUpdate', $msg, 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('settingsUpdate', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    public function editPrinter($id)
    {
        $printer = Printer::where('branch_id', $this->branch_id)->findOrFail($id);
        
        $this->printer_id = $printer->id;
        $this->printer_name = $printer->name;
        
        if (!in_array($printer->type, ['ticket_comanda', 'ticket', 'kitchen', 'recibo'])) {
            $this->printer_type = '';
        } else {
            $this->printer_type = $printer->type;
        }

        $this->printer_ip = $printer->ip_address;
        $this->connection_type = $printer->connection_type ?? 'network';
        $this->print_behavior = $printer->print_behavior ?? 'none';
        $this->printer_copies = $printer->copies ?? 1;

        $this->printer_is_default = $printer->is_default == 1;

        if ($this->print_behavior === 'direct' && !in_array($this->connection_type, ['network', 'usb', 'bluetooth'])) {
            $this->connection_type = 'network';
        }

        $this->isEditModePrinter = true;
    }

    public function deletePrinter($id)
    {
        try {
            $printer = Printer::where('branch_id', $this->branch_id)->find($id);
            if ($printer) {
                $newStatus = $printer->status == 1 ? 0 : 1;
                $printer->update(['status' => $newStatus]);
                $msg = $newStatus == 1 ? 'RESTAURADA' : 'ELIMINADA';
                $this->dispatch('printerDeleted', $msg, 'success');
            }
        } catch (\Exception $e) {
            $this->dispatch('settingsUpdate', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    public function getPaginatedProductionAreas()
    {
        return ProductionArea::with('printer')
            ->where(function ($query) {
                if (strlen($this->searchTerm) > 0) {
                    $query->where('name', 'like', '%' . $this->searchTerm . '%');
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
    }

    public function resetProductionAreaFields()
    {
        $this->resetValidation();
        $this->production_area_id = null;
        $this->production_area_name = '';
        $this->production_area_printer_id = null;
        $this->isEditModeProductionArea = false;
    }

    public function storeOrUpdateProductionArea()
    {
        $this->validate([
            'production_area_name' => 'required|string|max:100',
            'production_area_printer_id' => 'required|exists:printers,id'
        ]);

        try {
            DB::beginTransaction();

            ProductionArea::updateOrCreate(
                ['id' => $this->production_area_id],
                [
                    'name' => $this->production_area_name,
                    'printer_id' => $this->production_area_printer_id,
                    'status' => 1
                ]
            );

            DB::commit();
            $msg = $this->isEditModeProductionArea ? 'ÁREA DE PRODUCCIÓN ACTUALIZADA' : 'ÁREA DE PRODUCCIÓN CREADA';
            $this->resetProductionAreaFields();
            $this->dispatch('productionAreaStoreOrUpdate', $msg, 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('settingsUpdate', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    public function editProductionArea($id)
    {
        $area = ProductionArea::findOrFail($id);
        $this->production_area_id = $area->id;
        $this->production_area_name = $area->name;
        $this->production_area_printer_id = $area->printer_id;
        $this->isEditModeProductionArea = true;
    }

    public function deleteProductionArea($id)
    {
        try {
            $area = ProductionArea::find($id);
            if ($area) {
                $newStatus = $area->status == 1 ? 0 : 1;
                $area->update(['status' => $newStatus]);
                $msg = $newStatus == 1 ? 'RESTAURADA' : 'ELIMINADA';
                $this->dispatch('productionAreaDeleted', $msg, 'success');
            }
        } catch (\Exception $e) {
            $this->dispatch('settingsUpdate', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    public function selectTab($tab)
    {
        $this->selected_tab = $tab;
        $this->resetPage();

        if ($tab === 'ajustes')
            $this->loadSettings();
        elseif ($tab === 'licencia')
            $this->loadSystemLicense();
        elseif ($tab === 'adicionales')
            $this->loadBranchSettings();
        elseif ($tab === 'documentos')
            $this->loadDocumentSettings();
    }

    public function updatedDocType()
    {
        $this->loadDocumentSettings();
    }

    public function loadDocumentSettings()
    {
        $doc = DocumentSetting::where('branch_id', $this->branch_id)->where('document_type', $this->doc_type)->first();
        if ($doc) {
            $this->doc_paper_size = $doc->paper_size;
            $this->doc_show_logo = (bool)$doc->show_logo;
            $this->doc_show_business_name = (bool)$doc->show_business_name;
            $this->doc_show_address = (bool)$doc->show_address;
            $this->doc_show_phone = (bool)$doc->show_phone;
            $this->doc_show_client = (bool)$doc->show_client;
            $this->doc_show_cashier = (bool)$doc->show_cashier;
            $this->doc_show_unit_price = (bool)$doc->show_unit_price;
            $this->doc_custom_title = $doc->custom_title;
            $this->doc_footer_text = $doc->footer_text;
        } else {
            $this->resetDocumentSettingsFields();
        }
    }

    public function resetDocumentSettingsFields()
    {
        $this->doc_paper_size = '80';
        $this->doc_show_logo = true;
        $this->doc_show_business_name = true;
        $this->doc_show_address = true;
        $this->doc_show_phone = true;
        $this->doc_show_client = true;
        $this->doc_show_cashier = true;
        $this->doc_show_unit_price = false;
        
        if ($this->doc_type === 'nota_venta') {
            $this->doc_custom_title = 'Nota de Venta';
        } elseif ($this->doc_type === 'comanda') {
            $this->doc_custom_title = 'Orden';
            $this->doc_show_logo = false;
        } elseif ($this->doc_type === 'recibo') {
            $this->doc_custom_title = 'Recibo';
        } else {
            $this->doc_custom_title = 'Documento';
        }

        $this->doc_footer_text = 'Generada a través de MASTEC DIGITAL.';
    }

    public function saveDocumentSettings()
    {
        try {
            DocumentSetting::updateOrCreate(
                [
                    'branch_id' => $this->branch_id,
                    'document_type' => $this->doc_type
                ],
                [
                    'paper_size' => $this->doc_paper_size,
                    'show_logo' => ($this->doc_type === 'comanda') ? 0 : ($this->doc_show_logo ? 1 : 0),
                    'show_business_name' => $this->doc_show_business_name ? 1 : 0,
                    'show_address' => $this->doc_show_address ? 1 : 0,
                    'show_phone' => $this->doc_show_phone ? 1 : 0,
                    'show_client' => $this->doc_show_client ? 1 : 0,
                    'show_cashier' => $this->doc_show_cashier ? 1 : 0,
                    'show_unit_price' => ($this->doc_type === 'comanda') ? ($this->doc_show_unit_price ? 1 : 0) : 0,
                    'custom_title' => $this->doc_custom_title,
                    'footer_text' => $this->doc_footer_text,
                ]
            );
            $this->dispatch('settingsUpdate', 'DISEÑO DE DOCUMENTO ACTUALIZADO', 'success');
        } catch (\Exception $e) {
            $this->dispatch('settingsUpdate', 'Error: ' . $e->getMessage(), 'error');
        }
    }
}