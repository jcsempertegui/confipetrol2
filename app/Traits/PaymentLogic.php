<?php

namespace App\Traits;

trait PaymentLogic
{
    public $selectedPayment = 'EFECTIVO';
    public $isCombined = false;
    public $isconfirm = false;
    public $efectivo = '', $tarjeta = '', $qr = '', $transferencia = '', $due_date;
    public $cambio = 0;

    public function updatedSelectedPayment($value)
    {
        $this->customPayment($value);
    }

    public function updatedEfectivo($value)
    {
        $this->efectivo = $this->sanitizeDecimal($value);
        $this->calculatePayment();
    }

    public function updatedTarjeta($value)
    {
        $this->tarjeta = $this->sanitizeDecimal($value);
        $this->calculatePayment();
    }

    public function updatedQr($value)
    {
        $this->qr = $this->sanitizeDecimal($value);
        $this->calculatePayment();
    }

    private function sanitizeDecimal($value)
    {
        return preg_replace('/[^0-9.]/', '', $value);
    }

    public function customPayment($payment)
    {
        $this->resetPaymentInputs();
        $this->selectedPayment = $payment;
        $this->isCombined = $payment === 'MULTIPLE';

        $pagosSinCambio = ['QR', 'TARJETA', 'TRANSFERENCIA', 'CREDITO'];
        $this->isconfirm = in_array($payment, $pagosSinCambio);

        if ($this->selectedPayment === 'EFECTIVO') {
            $this->efectivo = number_format((float) $this->total_cart, 2, '.', '');
            $this->calculatePayment();
        }
    }

    public function calculatePayment()
    {
        $valEfectivo = floatval($this->efectivo ?: 0);
        $valTarjeta = floatval($this->tarjeta ?: 0);
        $valQr = floatval($this->qr ?: 0);

        if ($this->selectedPayment === 'EFECTIVO') {
            $this->cambio = $valEfectivo - $this->total_cart;
            $this->isconfirm = ($valEfectivo >= $this->total_cart);
        } elseif ($this->selectedPayment === 'MULTIPLE') {
            $totalPagado = $valEfectivo + $valTarjeta + $valQr;
            $this->cambio = $totalPagado - $this->total_cart;
            $this->isconfirm = ($totalPagado >= $this->total_cart);
        }
    }

    public function resetPayment()
    {
        $this->resetPaymentInputs();
        $this->selectedPayment = 'EFECTIVO';
        $this->isCombined = false;
        $this->isconfirm = false;
        $this->efectivo = '';
    }

    public function resetPaymentInputs()
    {
        $this->resetValidation();
        $this->efectivo = '';
        $this->tarjeta = '';
        $this->qr = '';
        $this->transferencia = '';
        $this->due_date = '';
        $this->cambio = 0;
    }
}
?>