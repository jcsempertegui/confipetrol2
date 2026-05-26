<div wire:ignore.self class="modal fade" id="paymentsModal" tabindex="-1" aria-labelledby="paymentsModal"
    aria-hidden="true" data-bs-keyboard="false" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg" role="document" x-data="paymentLogic()">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">MÉTODOS DE PAGO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="payments-section">
                    <div class="payments-container">
                        <form action="#">
                            <div class="row gx-1 gy-2">
                                <div class="col-4 col-md-2">
                                    <input type="radio" name="payment" id="efectivo" value="EFECTIVO"
                                        x-model="paymentMethod" @change="calcularCambio()">
                                    <label for="efectivo" class="payment-method">
                                        <div class="imgName">
                                            <div class="imgContainer">
                                                <img src="{{ asset('assets/images/payments/payment_efectivo.jpg') }}"
                                                    alt="efectivo">
                                            </div>
                                            <span class="name">EFECTIVO</span>
                                        </div>
                                        <span class="check"><i class="bx bx-check"></i></span>
                                    </label>
                                </div>

                                <div class="col-4 col-md-2">
                                    <input type="radio" name="payment" id="tarjeta" value="TARJETA"
                                        x-model="paymentMethod" @change="calcularCambio()">
                                    <label for="tarjeta" class="payment-method">
                                        <div class="imgName">
                                            <div class="imgContainer">
                                                <img src="{{ asset('assets/images/payments/payment_tarjeta.png') }}"
                                                    alt="tarjeta">
                                            </div>
                                            <span class="name">TARJETA</span>
                                        </div>
                                        <span class="check"><i class="bx bx-check"></i></span>
                                    </label>
                                </div>

                                <div class="col-4 col-md-2">
                                    <input type="radio" name="payment" id="qr" value="QR" x-model="paymentMethod"
                                        @change="calcularCambio()">
                                    <label for="qr" class="payment-method">
                                        <div class="imgName">
                                            <div class="imgContainer">
                                                <img src="{{ asset('assets/images/payments/payment_qr.png') }}"
                                                    alt="qr">
                                            </div>
                                            <span class="name">QR</span>
                                        </div>
                                        <span class="check"><i class="bx bx-check"></i></span>
                                    </label>
                                </div>

                                <div class="col-4 col-md-2">
                                    <input type="radio" name="payment" id="credito" value="CREDITO"
                                        x-model="paymentMethod" @change="calcularCambio()">
                                    <label for="credito" class="payment-method">
                                        <div class="imgName">
                                            <div class="imgContainer">
                                                <img src="{{ asset('assets/images/payments/payment_credito.png') }}"
                                                    alt="credito">
                                            </div>
                                            <span class="name">CRÉDITO</span>
                                        </div>
                                        <span class="check"><i class="bx bx-check"></i></span>
                                    </label>
                                </div>

                                <div class="col-4 col-md-2">
                                    <input type="radio" name="payment" id="transferencia" value="TRANSFERENCIA"
                                        x-model="paymentMethod" @change="calcularCambio()">
                                    <label for="transferencia" class="payment-method">
                                        <div class="imgName">
                                            <div class="imgContainer">
                                                <img src="{{ asset('assets/images/payments/payment_transferencia.png') }}"
                                                    alt="transferencia">
                                            </div>
                                            <span class="name">TRANSFERENCIA</span>
                                        </div>
                                        <span class="check"><i class="bx bx-check"></i></span>
                                    </label>
                                </div>

                                <div class="col-4 col-md-2">
                                    <input type="radio" name="payment" id="multiple" value="MULTIPLE"
                                        x-model="paymentMethod" @change="calcularCambio()">
                                    <label for="multiple" class="payment-method">
                                        <div class="imgName">
                                            <div class="imgContainer">
                                                <img src="{{ asset('assets/images/payments/payment_convinado.png') }}"
                                                    alt="multiple">
                                            </div>
                                            <span class="name">MÚLTIPLE</span>
                                        </div>
                                        <span class="check"><i class="bx bx-check"></i></span>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12 col-sm-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-total">
                                    Bs. <span x-text="totalVenta.toFixed(2)"></span>
                                    <span class="card-total-literal">SON: {{ numtoletras($total_cart) }}</span>
                                </h5>

                                <div class="mb-3 px-3 px-md-4" x-show="['EFECTIVO', 'MULTIPLE'].includes(paymentMethod)"
                                    style="display: none;">
                                    <label for="iefectivo">Efectivo</label>
                                    <div class="position-relative input-icon">
                                        <input type="text" class="form-control text-end inputpayment price-decimal"
                                            id="iefectivo" wire:model="efectivo" placeholder="0.00" inputmode="decimal"
                                            autocomplete="off" x-on:input="calcularCambio()"
                                            onkeydown="if(event.key === 'Enter') event.preventDefault();">
                                        <span class="position-absolute top-50 translate-middle-y">Bs</span>
                                    </div>
                                    @error('efectivo') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3 px-3 px-md-4" x-show="paymentMethod === 'MULTIPLE'"
                                    style="display: none;">
                                    <label for="itarjeta">Tarjeta</label>
                                    <div class="position-relative input-icon">
                                        <input type="text" class="form-control text-end inputpayment price-decimal"
                                            id="itarjeta" wire:model="tarjeta" placeholder="0.00" inputmode="decimal"
                                            autocomplete="off" x-on:input="calcularCambio()"
                                            onkeydown="if(event.key === 'Enter') event.preventDefault();">
                                        <span class="position-absolute top-50 translate-middle-y">Bs</span>
                                    </div>
                                    @error('tarjeta') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3 px-3 px-md-4" x-show="paymentMethod === 'MULTIPLE'"
                                    style="display: none;">
                                    <label for="iqr">QR</label>
                                    <div class="position-relative input-icon">
                                        <input type="text" class="form-control text-end inputpayment price-decimal"
                                            id="iqr" wire:model="qr" placeholder="0.00" inputmode="decimal"
                                            autocomplete="off" x-on:input="calcularCambio()"
                                            onkeydown="if(event.key === 'Enter') event.preventDefault();">
                                        <span class="position-absolute top-50 translate-middle-y">Bs</span>
                                    </div>
                                    @error('qr') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3 px-3 px-md-4" x-show="paymentMethod === 'CREDITO'"
                                    style="display: none;">
                                    <label>Fecha Limite</label>
                                    <div class="position-relative input-icon">
                                        <input id="date_deu" class="form-control flatpickrtoday" type="text"
                                            wire:model="due_date" placeholder="Seleccione Fecha Inicial" readonly
                                            autocomplete="off">
                                        <span class="position-absolute top-50 translate-middle-y"><i
                                                class='bx bx-calendar'></i></span>
                                    </div>
                                    @error('due_date') <span class="text-danger er">{{ $message }}</span> @enderror
                                </div>

                                <div x-show="paymentMethod !== 'CREDITO'">
                                    <div class="col-span-2">
                                        <hr class="my-2">
                                    </div>
                                    <div
                                        class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 px-3 px-md-4">
                                        <h6 class="tittle-cambio" x-text="labelCambio">Cambio:</h6>
                                        <h6 class="tittle-cambio" :class="cambio < 0 ? 'text-danger' : 'text-success'">
                                            Bs. <span x-text="Math.abs(cambio).toFixed(2)">0.00</span>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                    wire:click="resetPayment">Cancelar</button>
                <button type="button" id="btnConfirmarPago"
                    x-on:click.prevent="if(isValid) { $wire.{{ isset($order_number) && $order_number ? 'updateSale' : 'confirmSale' }}() }"
                    class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="{{ isset($order_number) && $order_number ? 'updateSale' : 'confirmSale' }}"
                    :disabled="!isValid">
                    <span wire:loading.remove
                        wire:target="{{ isset($order_number) && $order_number ? 'updateSale' : 'confirmSale' }}">Aceptar</span>
                    <span wire:loading
                        wire:target="{{ isset($order_number) && $order_number ? 'updateSale' : 'confirmSale' }}"><i
                            class="bx bx-spin bx-loader"></i> Procesando...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('paymentLogic', () => ({
            paymentMethod: @entangle('selectedPayment'),
            totalVenta: @entangle('total_cart'),
            cambio: 0,
            labelCambio: 'Cambio:',
            isValid: true,

            init() {
                this.$watch('paymentMethod', value => {
                    if (value === 'EFECTIVO') {
                        this.$nextTick(() => {
                            let input = document.getElementById('iefectivo');
                            if (input) {
                                input.focus();
                                input.select();
                            }
                        });
                    }
                });

                this.$watch('totalVenta', () => this.calcularCambio());
                this.$watch('$wire.efectivo', () => this.calcularCambio());
                this.$watch('$wire.tarjeta', () => this.calcularCambio());
                this.$watch('$wire.qr', () => this.calcularCambio());

                window.addEventListener('recalculate-payment', () => {
                    this.calcularCambio();
                });

                let modalPago = document.getElementById('paymentsModal');
                if (modalPago) {
                    modalPago.addEventListener('shown.bs.modal', () => {
                        this.calcularCambio();
                        if (this.paymentMethod === 'EFECTIVO') {
                            let input = document.getElementById('iefectivo');
                            if (input) {
                                input.focus();
                                input.select();
                            }
                        }
                    });
                }

                this.$nextTick(() => this.calcularCambio());
            },

            calcularCambio() {
                let ef = parseFloat(this.$wire.efectivo) || 0;
                let ta = parseFloat(this.$wire.tarjeta) || 0;
                let qrr = parseFloat(this.$wire.qr) || 0;

                if (this.paymentMethod === 'EFECTIVO') {
                    this.cambio = ef - this.totalVenta;
                    this.isValid = ef >= this.totalVenta;
                } else if (this.paymentMethod === 'TARJETA' || this.paymentMethod === 'QR' || this.paymentMethod === 'TRANSFERENCIA') {
                    this.cambio = 0;
                    this.isValid = true;
                } else if (this.paymentMethod === 'MULTIPLE') {
                    let pagado = ef + ta + qrr;
                    this.cambio = pagado - this.totalVenta;
                    this.isValid = pagado >= this.totalVenta;
                } else if (this.paymentMethod === 'CREDITO') {
                    this.cambio = 0;
                    this.isValid = true;
                } else {
                    this.cambio = 0;
                    this.isValid = true;
                }

                if (this.cambio < 0) {
                    this.labelCambio = 'Falta:';
                } else {
                    this.labelCambio = 'Cambio:';
                }
            }
        }));
    });

    document.addEventListener('livewire:init', function () {
        const modalPago = document.getElementById('paymentsModal');

        if (modalPago) {
            modalPago.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const btnAceptar = this.querySelector('#btnConfirmarPago');
                    if (btnAceptar && !btnAceptar.disabled && !btnAceptar.hasAttribute('wire:loading')) {
                        btnAceptar.click();
                    }
                }
            });
        }
    });
</script>