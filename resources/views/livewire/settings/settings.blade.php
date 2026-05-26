@push('title', 'Ajustes')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-danger">Administración</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Ajustes</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-pills nav-pills-danger mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <a wire:click.prevent="selectTab('ajustes')"
                        class="nav-link {{ $selected_tab === 'ajustes' ? 'active' : '' }}" href="javascript:;"
                        role="tab">
                        <div class="d-flex align-items-center">
                            <div class="tab-icon"><i class='bx bx-building-house font-18 me-1'></i></div>
                            <div class="tab-title">Ajustes Negocio</div>
                        </div>
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a wire:click.prevent="selectTab('licencia')"
                        class="nav-link {{ $selected_tab === 'licencia' ? 'active' : '' }}" href="javascript:;"
                        role="tab">
                        <div class="d-flex align-items-center">
                            <div class="tab-icon"><i class='bx bx-shield-alt-2 font-18 me-1'></i></div>
                            <div class="tab-title">Licencia/Sistema</div>
                        </div>
                    </a>
                </li>

                @can('ver-ajustesadicionales')
                    <li class="nav-item" role="presentation">
                        <a wire:click.prevent="selectTab('adicionales')"
                            class="nav-link {{ $selected_tab === 'adicionales' ? 'active' : '' }}" href="javascript:;"
                            role="tab">
                            <div class="d-flex align-items-center">
                                <div class="tab-icon"><i class='bx bx-cog font-18 me-1'></i></div>
                                <div class="tab-title">Ajustes Adicionales</div>
                            </div>
                        </a>
                    </li>
                @endcan
            </ul>

            <div class="tab-content" id="danger-pills-tabContent">

                <div class="tab-pane fade {{ $selected_tab === 'ajustes' ? 'show active' : '' }}" id="danger-pills-home"
                    role="tabpanel">
                    <div class="card">
                        <div class="card-body px-4 mt-2">
                            <div class="card-header d-flex justify-content-between align-items-center px-3 py-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bx bx-box"></i>
                                    <span class="fw-semibold">Ajustes del Negocio </span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label>Negocio <span class="text-danger">*</span></label>
                                            <input type="text" wire:model="business" class="form-control"
                                                placeholder="Negocio">
                                            @error('business')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Razon Social <span class="text-danger">*</span></label>
                                            <input type="text" wire:model="owner" class="form-control"
                                                placeholder="Razon Social">
                                            @error('owner')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Nit <span class="text-danger">*</span></label>
                                            <input type="text" wire:model="nit" class="form-control" placeholder="Nit">
                                            @error('nit')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Correo Electrónico <span class="text-danger">*</span></label>
                                            <input type="email" wire:model="email" class="form-control"
                                                placeholder="Correo Electrónico">
                                            @error('email')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label>Teléfono</label>
                                            <input type="text" wire:model="phone" class="form-control"
                                                inputmode="decimal" maxlength="12"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                placeholder="Teléfono">
                                            @error('phone')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label>Dirección</label>
                                            <textarea class="form-control" wire:model="address" maxlength="200" rows="2"
                                                placeholder="Dirección del Negocio"></textarea>
                                            @error('address')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <div class="form-group" wire:ignore>
                                                <label for="message">Mensaje (Opcional) </label>
                                                <textarea id="editor" class="form-control" name="message" rows="3"
                                                    placeholder="Mensaje de Agradecimiento"
                                                    wire:model="message"></textarea>
                                            </div>
                                            @error('message')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 text-end mt-2">
                                            <button type="button" id="saveSettingsBtn"
                                                wire:click.prevent="updateSettings" class="btn btn-primary px-4"
                                                wire:loading.attr="disabled" wire:target="updateSettings">
                                                <span wire:loading.remove wire:target="updateSettings"><i
                                                        class="bx bx-save"></i> Actualizar</span>
                                                <span wire:loading wire:target="updateSettings"><i
                                                        class="bx bx-spin bx-loader"></i> Procesando...</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group" wire:ignore>
                                        <label class="form-label fw-bold w-100 text-center">Logo del Negocio</label>
                                        <div
                                            class="d-flex flex-column align-items-center justify-content-center p-3 border rounded bg-light">
                                            <div class="image-container position-relative mb-3">
                                                <img id="previewImage"
                                                    src="{{ $image_preview ? asset('storage/' . $image_preview) : asset('assets/images/logo.png') }}"
                                                    class="img-thumbnail shadow-sm"
                                                    style="border-radius: 25px; width: 200px; height: 200px; object-fit: contain; object-position: center;"
                                                    alt="Vista previa">

                                                <div id="uploadOverlay" class="image-overlay"
                                                    style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); border-radius: 25px; flex-direction: column; align-items: center; justify-content: center;">
                                                    <i class="bx bx-spin bx-loader upload-spinner fs-1 text-danger"></i>
                                                    <span id="uploadText" class="fw-bold mt-2 text-dark">Subiendo
                                                        imagen...</span>
                                                    <div class="progress-bar-custom w-75 mt-2"
                                                        style="height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden;">
                                                        <div id="progressFill" class="progress-fill bg-danger"
                                                            style="width: 0%; height: 100%;"></div>
                                                    </div>
                                                    <small id="progressPercent"
                                                        class="mt-1 fw-bold text-dark">0%</small>
                                                </div>
                                            </div>

                                            <div class="w-100 text-center" style="max-width: 300px;">
                                                <input type="file" id="imageInput" name="image"
                                                    class="form-control d-none" accept=".jpg,.jpeg,.png,.webp">
                                                <button type="button" id="selectImageBtn"
                                                    class="btn btn-outline-danger w-100 cursor-pointer"
                                                    onclick="document.getElementById('imageInput').click()">
                                                    <i class="bx bx-cloud-upload"></i> Seleccionar Imagen
                                                </button>
                                                <small class="text-muted d-block mt-1">Formatos: PNG, JPG, WEBP (Máx.
                                                    20MB)</small>
                                            </div>
                                        </div>
                                    </div>
                                    @error('image')
                                        <span class="text-danger small fw-bold mt-1 d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $selected_tab === 'licencia' ? 'show active' : '' }}"
                    id="danger-pills-licencia" role="tabpanel">
                    <div class="card">
                        <div class="card-body px-4 mt-2">
                            <div class="card-header d-flex justify-content-between align-items-center px-3 py-2 mb-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bx bx-shield-alt-2"></i>
                                    <span class="fw-semibold">Licencia del Sistema (General)</span>
                                </div>
                            </div>
                            <div class="p-2">
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="bx bx-info-circle"></i> Esta configuración es
                                            <strong>GENERAL</strong> y aplica a toda la empresa.
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-danger mb-3"><i class="bx bx-package"></i> Plan
                                            Contratado</h6>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-2">
                                        <label>Plan de Licencia <span class="text-danger">*</span></label>
                                        <select wire:model.lazy="system_license_plan" class="form-select">
                                            <option value="emprendedor">EMPRENDEDOR</option>
                                            <option value="estandar">ESTÁNDAR</option>
                                            <option value="pyme">PYME</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-2">
                                        <label>Tipo de Pago <span class="text-danger">*</span></label>
                                        <select wire:model.live="system_payment_type" class="form-select">
                                            <option value="mensual">Mensual</option>
                                            <option value="anual">Anual</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-2">
                                        <label>Fecha de Activación <span class="text-danger">*</span></label>
                                        <input type="date" wire:model.live="system_license_start_date"
                                            class="form-control">
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-danger mb-3"><i class="bx bx-calendar"></i> Tiempo
                                            Pagado</h6>
                                    </div>
                                    @if ($system_payment_type === 'mensual')
                                        <div class="col-lg-6 col-sm-6 mb-2">
                                            <label>Meses Pagados</label>
                                            <input type="number" wire:model.live="system_months_paid" class="form-control"
                                                min="1" max="12">
                                        </div>
                                    @else
                                        <div class="col-lg-6 col-sm-6 mb-2">
                                            <label>Años Pagados</label>
                                            <input type="number" wire:model.live="system_years_paid" class="form-control"
                                                min="1" max="10">
                                        </div>
                                    @endif
                                    <div class="col-lg-6 col-sm-6 mb-2">
                                        <label>Total Meses</label>
                                        <input type="text" class="form-control fw-bold"
                                            value="{{ $system_total_months_paid ?? 0 }} meses" readonly>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-danger mb-3"><i class="bx bx-check-shield"></i> Estado
                                        </h6>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-2">
                                        <label>Vencimiento</label>
                                        <input type="text" class="form-control fw-bold"
                                            value="{{ $system_license_end_date ? \Carbon\Carbon::parse($system_license_end_date)->format('d/m/Y') : 'Sin calcular' }}"
                                            readonly>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-2">
                                        <label>Estado</label>
                                        <input type="text" class="form-control fw-bold"
                                            value="{{ $system_license_status_text ?? 'Sin calcular' }}" readonly>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-2">
                                        <label>Días Restantes</label>
                                        <input type="text" class="form-control fw-bold"
                                            value="{{ $system_days_remaining !== null ? abs($system_days_remaining) . ' días' : 'Sin calcular' }}"
                                            readonly>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="button" wire:click.prevent="updateSystemLicense"
                                        class="btn btn-primary px-4" wire:loading.attr="disabled"
                                        wire:target="updateSystemLicense">
                                        <span wire:loading.remove wire:target="updateSystemLicense"><i
                                                class="bx bx-save"></i> Guardar Licencia</span>
                                        <span wire:loading wire:target="updateSystemLicense"><i
                                                class="bx bx-spin bx-loader"></i> Procesando...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $selected_tab === 'adicionales' ? 'show active' : '' }}"
                    id="danger-pills-profile" role="tabpanel">
                    <div class="card">
                        <div class="card-body px-4 mt-2">
                            <div class="p-2">

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-danger mb-3"><i class="bx bx-lock-alt"></i>
                                            Seguridad y Autenticación</h6>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-2">
                                        <label>Límite de Usuarios (Cuentas) <span class="text-danger">*</span></label>
                                        <input type="number" wire:model.defer="branch_max_users" class="form-control"
                                            min="1">
                                        <small class="text-muted">Cantidad máxima de usuarios que se pueden
                                            crear.</small>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-2">
                                        <label>Sesiones Simultáneas por Usuario <span
                                                class="text-danger">*</span></label>
                                        <input type="number" wire:model.defer="branch_max_sessions" class="form-control"
                                            min="1">
                                        <small class="text-muted">Cantidad de dispositivos donde un usuario puede estar
                                            activo a la vez.</small>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <hr>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-danger mb-3"><i class="bx bx-store"></i>
                                            Configuración de Punto de Venta</h6>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-2">
                                        <label>Tipo de POS <span class="text-danger">*</span></label>
                                        <select wire:model.live="pos_type" class="form-select">
                                            <option value="">Seleccionar Tipo</option>
                                            <option value="1">Punto de Venta Normal</option>
                                            <option value="2">Punto de Venta con Interfaz</option>
                                            <option value="0">Punto de Venta Restaurante</option>
                                            <option value="4">Punto de Venta Restaurante Mesas</option>
                                        </select>
                                        @error('pos_type')
                                            <span class="text-danger er">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-lg-6 col-sm-6 mb-2 d-flex align-items-center">
                                        <div class="form-check form-switch mt-3">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="requires_cashbox" id="requiresCashbox">
                                            <label class="form-check-label" for="requiresCashbox"><i
                                                    class="bx bx-money"></i> ¿Necesita caja abierta para vender?</label>
                                        </div>
                                    </div>

                                    @if($pos_type == '0' || $pos_type == '4')
                                        <div class="col-lg-6 col-sm-6 mb-2 d-flex align-items-center">
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox"
                                                    wire:model="has_production_areas" id="hasProductionAreas">
                                                <label class="form-check-label" for="hasProductionAreas"><i
                                                        class="bx bx-restaurant"></i> ¿Tendrá Áreas de Producción?</label>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-danger mb-3"><i class="bx bx-cog"></i> Funcionalidades
                                        </h6>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="camera_barcode_enabled" id="cameraBarcode">
                                            <label class="form-check-label" for="cameraBarcode"><i
                                                    class="bx bx-camera"></i> Lector Cámara</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" wire:model="loyalty_program"
                                                id="loyaltyProgram">
                                            <label class="form-check-label" for="loyaltyProgram"><i
                                                    class="bx bx-gift"></i> Fidelización</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" wire:model="online_orders"
                                                id="onlineOrders">
                                            <label class="form-check-label" for="onlineOrders"><i
                                                    class="bx bx-shopping-bag"></i> Pedidos Online</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="advanced_reports" id="advancedReports">
                                            <label class="form-check-label" for="advancedReports"><i
                                                    class="bx bx-chart"></i> Reportes Avanzados</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="enable_size_color" id="enableSizeColor">
                                            <label class="form-check-label"
                                                for="enableSizeColor"><i class="bx bx-purchase-tag-alt"></i> Módulo
                                                Tallas y Colores </label>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="enable_product_gallery" id="enableProductGallery">
                                            <label class="form-check-label"
                                                for="enableProductGallery"><i class="bx bx-images"></i> Galería de Imágenes (Máx 3) </label>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                wire:model="enable_staff_per_detail" id="enableStaffPerDetail">
                                            <label class="form-check-label"
                                                for="enableStaffPerDetail"><i class="bx bx-user-pin"></i> Asignar Empleado por Servicio </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold text-danger mb-3"><i class="bx bx-receipt"></i> Facturación
                                        </h6>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-2">
                                        <label>Tipo</label>
                                        <select wire:model.live="invoice_type" class="form-select">
                                            <option value="ninguno">Ninguno</option>
                                            <option value="electronica">Facturación Electrónica</option>
                                            <option value="computarizada">Facturación Computarizada</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-2">
                                        <label>Impuesto Defecto (%)</label>
                                        <input type="number" wire:model="default_tax" class="form-control" step="0.01">
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-2">
                                        <label>Moneda</label>
                                        <select wire:model.lazy="default_currency" class="form-select">
                                            <option value="BOB">Boliviano (BOB)</option>
                                            <option value="USD">Dólar (USD)</option>
                                            <option value="EUR">Euro (EUR)</option>
                                        </select>
                                    </div>

                                    @if($invoice_type === 'electronica' || $invoice_type === 'computarizada')
                                        <div class="col-lg-4 col-sm-6 mb-2 mt-2">
                                            <label>Ambiente <span class="text-danger">*</span></label>
                                            <select wire:model="ambiente" class="form-select">
                                                <option value="">Seleccione...</option>
                                                <option value="1">Producción</option>
                                                <option value="2">Piloto/Pruebas</option>
                                            </select>
                                            @error('ambiente')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-lg-4 col-sm-6 mb-2 mt-2">
                                            <label>Código de Sistema <span class="text-danger">*</span></label>
                                            <input type="text" wire:model="codigo_sistema" class="form-control" placeholder="Código de Sistema">
                                            @error('codigo_sistema')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-lg-12 mb-2 mt-2">
                                            <label>Token <span class="text-danger">*</span></label>
                                            <textarea wire:model="token" class="form-control" rows="3" placeholder="Ingrese el Token"></textarea>
                                            @error('token')
                                                <span class="text-danger er">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    @endif
                                </div>

                                <div class="text-end">
                                    <button type="button" wire:click.prevent="updateAdvancedSettings"
                                        class="btn btn-primary px-4" wire:loading.attr="disabled"
                                        wire:target="updateAdvancedSettings">
                                        <span wire:loading.remove wire:target="updateAdvancedSettings"><i
                                                class="bx bx-save"></i> Guardar</span>
                                        <span wire:loading wire:target="updateAdvancedSettings"><i
                                                class="bx bx-spin bx-loader"></i> Procesando...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<script type="text/javascript"
    src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const imageInput = document.getElementById('imageInput');
        const uploadOverlay = document.getElementById('uploadOverlay');
        const progressFill = document.getElementById('progressFill');
        const progressPercent = document.getElementById('progressPercent');
        const uploadText = document.getElementById('uploadText');
        const selectImageBtn = document.getElementById('selectImageBtn');
        const saveSettingsBtn = document.getElementById('saveSettingsBtn');
        let isUploading = false;

        function showUploadLoader() {
            isUploading = true;
            uploadOverlay.style.display = 'flex';
            if (selectImageBtn) { selectImageBtn.disabled = true; selectImageBtn.classList.add('disabled'); }
            if (saveSettingsBtn) { saveSettingsBtn.disabled = true; saveSettingsBtn.classList.add('disabled'); }
        }
        function hideUploadLoader() {
            isUploading = false;
            uploadOverlay.style.display = 'none';
            if (selectImageBtn) { selectImageBtn.disabled = false; selectImageBtn.classList.remove('disabled'); }
            if (saveSettingsBtn) { saveSettingsBtn.disabled = false; saveSettingsBtn.classList.remove('disabled'); }
            progressFill.style.width = '0%';
            progressPercent.textContent = '0%';
        }
        function updateProgress(percent) {
            progressFill.style.width = percent + '%';
            progressPercent.textContent = percent + '%';
        }

        if (imageInput) {
            imageInput.addEventListener('change', async function (e) {
                const file = e.target.files[0];
                const preview = document.getElementById('previewImage');
                if (file) {
                    showUploadLoader();
                    const reader = new FileReader();
                    reader.onload = function (e) { preview.src = e.target.result; };
                    reader.readAsDataURL(file);
                    uploadText.textContent = "Optimizando a JPG...";
                    let fileToUpload = file;
                    const options = { maxSizeMB: 5, maxWidthOrHeight: 1920, useWebWorker: true, fileType: 'image/jpeg', initialQuality: 0.9 };
                    try {
                        const compressedBlob = await imageCompression(file, options);
                        fileToUpload = new File([compressedBlob], file.name.replace(/\.[^/.]+$/, ".jpg"), { type: 'image/jpeg', lastModified: new Date().getTime() });
                    } catch (error) { console.warn(error); }
                    uploadText.textContent = "Subiendo al servidor...";
                    @this.upload('image', fileToUpload,
                        (uploadedFilename) => { uploadText.textContent = "¡Listo!"; updateProgress(100); setTimeout(() => { hideUploadLoader(); }, 500); },
                        (error) => { uploadText.textContent = "Error"; hideUploadLoader(); alert('Error al subir. Intente de nuevo.'); },
                        (event) => { const progress = Math.round(event.detail.progress); updateProgress(progress); uploadText.textContent = `Cargando... ${progress}%`; }
                    );
                }
            });
        }

        if (saveSettingsBtn) {
            saveSettingsBtn.addEventListener('click', function (e) {
                if (isUploading) { e.preventDefault(); e.stopPropagation(); alert('Por favor espera a que termine de subir la imagen.'); return false; }
            });
        }
    });

    document.addEventListener('livewire:init', function () {
        Livewire.on('settingsUpdate', (data) => {
            const [msg, type] = data;
            toast(msg, type);
            if (type === 'success') {
                const input = document.getElementById('imageInput');
                if (input) input.value = '';
            }
        });

        if (document.querySelector('#editor')) {
            ClassicEditor.create(document.querySelector('#editor'), {
                toolbar: {
                    items: ['selectAll', '|', 'heading', '|', 'bold', 'italic', 'outdent', 'indent',
                        '|',
                        'undo', 'redo', 'link', 'blockQuote', 'insertTable', 'mediaEmbed'
                    ],
                    shouldNotGroupWhenFull: true
                },
            })
                .then(editor => {
                    editor.setData(@this.get('message'));
                    editor.model.document.on('change:data', () => {
                        @this.set('message', editor.getData());
                    });
                })
                .catch(error => {
                    console.error(error);
                });
        }
    });
</script>
