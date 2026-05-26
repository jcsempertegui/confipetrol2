<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;
use Carbon\Carbon;

class HeaderNotifications extends Component
{
    public $notifications = [];
    public $has_notifications = false;
    public $days_remaining = null;
    public $branch_id;

    public function mount()
    {
        $this->checkLicenseStatus();
    }
    public function refreshData($branchId = null)
    {
        $this->branch_id = session('branch_user_id', auth()->user()->branch_id);
    }

    public function checkLicenseStatus()
{
    $setting = Setting::first();

    if ($setting && $setting->license_end_date) {
        $endDate = Carbon::parse($setting->license_end_date);
        $today = Carbon::today();
        
        // false para que devuelva negativo si ya pasó
        $this->days_remaining = $today->diffInDays($endDate, false); 

        // Lógica: Entrar aquí si faltan 5 días o menos, O SI YA VENCIÓ (negativo)
        if ($this->days_remaining <= 5) {
            
            $isExpired = false;

            // 1. Definir mensajes y estado
            if ($this->days_remaining < 0) {
                // CASO EXPIRADO
                $msg = "Tu licencia ha expirado hace " . abs($this->days_remaining) . " días.";
                $icon = 'bx-error-circle';
                $color = 'danger';
                $isExpired = true; 
            } elseif ($this->days_remaining == 0) {
                // CASO VENCE HOY
                $msg = "Tu licencia vence HOY.";
                $icon = 'bx-error';
                $color = 'danger';
            } else {
                // CASO ADVERTENCIA
                $msg = "Tu licencia vencerá en " . $this->days_remaining . " días.";
                $icon = 'bx-time-five';
                $color = 'warning';
            }

            // 2. Agregar a la campanita (siempre visible)
            $this->notifications[] = [
                'message' => $msg,
                'icon' => $icon,
                'color' => $color,
                'time' => 'Ahora'
            ];
            $this->has_notifications = true;

            // 3. LOGICA DEL MODAL CORREGIDA
            // Mostramos el modal SI:
            // A) La licencia YA expiró ($isExpired == true) -> SIEMPRE MOSTRAR
            // B) O si es advertencia Y no se ha mostrado en esta sesión (!session...)
            
            if ($isExpired || !session()->has('license_alert_shown')) {
                
                $this->dispatch('show-license-modal', [
                    'days' => $this->days_remaining,
                    'message' => $msg,
                    'is_expired' => $isExpired
                ]);
                
                // Solo marcamos "visto" si es una advertencia.
                // Si está expirado, NO marcamos (o da igual), para que la condición 
                // $isExpired de arriba vuelva a disparar el modal al recargar la página.
                if (!$isExpired) {
                    session()->put('license_alert_shown', true);
                }
            }
        }
    }
}

    public function render()
    {
        return view('livewire.header-notifications');
    }
}