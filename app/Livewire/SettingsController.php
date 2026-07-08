<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use App\Models\Branche;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Component
{
    use WithFileUploads, AuditLog;

    public $business, $owner, $nit, $email, $phone, $address, $message, $image, $image_preview;
    public $setting_id;
    public $branch_id;
    public bool $systemEnabled = true;

    public function mount()
    {
        $this->branch_id = session('branch_user_id') ?? auth()->user()->branch_id;

        if (!$this->branch_id && auth()->check()) {
            $this->branch_id = auth()->user()->branch_id;
        }

        $this->systemEnabled = !file_exists(storage_path('app/system_disabled.lock'));
        $this->loadSettings();
    }

    public function render()
    {
        return view('livewire.settings.settings')->extends('layouts.theme.app');
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

    public function toggleSystem()
    {
        if (auth()->id() !== 1) {
            return;
        }

        $lockFile = storage_path('app/system_disabled.lock');

        if ($this->systemEnabled) {
            file_put_contents($lockFile, now()->toDateTimeString());
            $this->systemEnabled = false;
            $this->logActivity('CONFIGURACION', 'EDITAR', 'Desactivó el sistema globalmente', null);
            $this->dispatch('settingsUpdate', 'SISTEMA DESACTIVADO', 'warning');
        } else {
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
            $this->systemEnabled = true;
            $this->logActivity('CONFIGURACION', 'EDITAR', 'Activó el sistema globalmente', null);
            $this->dispatch('settingsUpdate', 'SISTEMA ACTIVADO', 'success');
        }
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

            $this->logActivity(
                'CONFIGURACION', 'EDITAR',
                "Actualizó configuración de sucursal: {$this->business}",
                $this->branch_id,
                null,
                ['business' => $this->business, 'owner' => $this->owner, 'branch_id' => $this->branch_id]
            );

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
}
