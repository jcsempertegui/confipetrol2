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

                <li class="nav-item" role="presentation">
                    <a wire:click.prevent="selectTab('documentos')"
                        class="nav-link {{ $selected_tab === 'documentos' ? 'active' : '' }}" href="javascript:;"
                        role="tab">
                        <div class="d-flex align-items-center">
                            <div class="tab-icon"><i class='bx bx-receipt font-18 me-1'></i></div>
                            <div class="tab-title">Diseño Recibos</div>
                        </div>
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a wire:click.prevent="selectTab('impresoras')"
                        class="nav-link {{ $selected_tab === 'impresoras' ? 'active' : '' }}" href="javascript:;"
                        role="tab">
                        <div class="d-flex align-items-center">
                            <div class="tab-icon"><i class='bx bx-printer font-18 me-1'></i></div>
                            <div class="tab-title">Impresoras</div>
                        </div>
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a wire:click.prevent="selectTab('areas_produccion')"
                        class="nav-link {{ $selected_tab === 'areas_produccion' ? 'active' : '' }}" href="javascript:;"
                        role="tab">
                        <div class="d-flex align-items-center">
                            <div class="tab-icon"><i class='bx bx-restaurant font-18 me-1'></i></div>
                            <div class="tab-title">Áreas de Producción</div>
                        </div>
                    </a>
                </li>
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

                <div class="tab-pane fade {{ $selected_tab === 'documentos' ? 'show active' : '' }}" id="danger-pills-documentos" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-receipt"></i>
                                <span class="fw-semibold">Ajustes y Diseño de Documentos</span>
                            </div>
                        </div>
                        <div class="card-body px-4 py-3">
                            <div class="row">
                                <div class="col-md-6 border-end">
                                    <h6 class="fw-bold mb-3 text-danger">Controles de Diseño</h6>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Tipo de Documento</label>
                                            <select wire:model.live="doc_type" class="form-select">
                                                <option value="nota_venta">Nota de Venta</option>
                                                <option value="comanda">Orden</option>
                                                <option value="recibo">Recibo</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Tamaño de Papel</label>
                                            <select wire:model.live="doc_paper_size" class="form-select">
                                                <option value="80">Ticket 80mm</option>
                                                <option value="58">Ticket 58mm</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Título del Documento</label>
                                        <input type="text" wire:model.live="doc_custom_title" class="form-control" placeholder="EJ: NOTA DE VENTA">
                                    </div>

                                    <h6 class="fw-bold mt-4 mb-2 text-secondary">Elementos Visibles</h6>
                                    <div class="row">
                                        @if($doc_type !== 'comanda')
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.live="doc_show_logo" id="dshowlogo">
                                                <label class="form-check-label" for="dshowlogo">Imprimir Logo</label>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.live="doc_show_business_name" id="dshowname">
                                                <label class="form-check-label" for="dshowname">Nombre Empresa</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.live="doc_show_address" id="dshowaddress">
                                                <label class="form-check-label" for="dshowaddress">Dirección</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.live="doc_show_phone" id="dshowphone">
                                                <label class="form-check-label" for="dshowphone">Teléfono</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.live="doc_show_client" id="dshowclient">
                                                <label class="form-check-label" for="dshowclient">Datos del Cliente</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.live="doc_show_cashier" id="dshowcashier">
                                                <label class="form-check-label" for="dshowcashier">Nombre de Cajero</label>
                                            </div>
                                        </div>
                                        @if($doc_type === 'comanda')
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" wire:model.live="doc_show_unit_price" id="dshowunitprice">
                                                <label class="form-check-label" for="dshowunitprice">Mostrar Precio Unitario</label>
                                            </div>
                                        </div>
                                        @endif
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label fw-semibold">Texto de Pie de Página</label>
                                        <textarea wire:model.live="doc_footer_text" class="form-control" rows="2" placeholder="Ej: Gracias por su compra"></textarea>
                                    </div>

                                    <div class="text-end mt-4">
                                        <button type="button" wire:click.prevent="saveDocumentSettings" class="btn btn-primary px-4" wire:loading.attr="disabled" wire:target="saveDocumentSettings">
                                            <span wire:loading.remove wire:target="saveDocumentSettings"><i class="bx bx-save"></i> Guardar Diseño</span>
                                            <span wire:loading wire:target="saveDocumentSettings"><i class="bx bx-spin bx-loader"></i> Procesando...</span>
                                        </button>
                                    </div>

                                </div>

                                <div class="col-md-6 d-flex flex-column align-items-center bg-light rounded" style="background-color: #525659 !important; padding: 20px;">
                                    <h6 class="text-white fw-bold mb-3">Vista Previa en Vivo</h6>
                                    
                                    <div class="bg-white shadow" style="width: {{ $doc_paper_size == '80' ? '300px' : '220px' }}; min-height: 350px; padding: 15px; font-family: monospace; color: #000; transition: width 0.3s ease;">
                                        
                                        <div class="text-center mb-2">
                                            @if($doc_show_logo && $doc_type !== 'comanda')
                                                @if($image_preview)
                                                    <img src="{{ asset('storage/' . $image_preview) }}" style="max-width: 80px; margin-bottom: 5px;">
                                                @else
                                                    <div style="width: 80px; height: 40px; border: 1px solid #ccc; display: inline-block; margin-bottom: 5px; line-height: 40px; font-size: 10px;">LOGO</div>
                                                @endif
                                            @endif

                                            @if($doc_show_business_name)
                                                <div style="font-weight: bold; font-size: 14px;">{{ strtoupper($business ?: 'MASTEC DIGITAL') }}</div>
                                            @endif
                                            @if($doc_show_address)
                                                <div style="font-size: 11px;">{{ strtoupper($address ?: 'AV. SIEMPRE VIVA 123, LA PAZ') }}</div>
                                            @endif
                                            @if($doc_show_phone)
                                                <div style="font-size: 11px;">TEL: {{ $phone ?: '77777777' }}</div>
                                            @endif
                                        </div>

                                        <div class="text-center font-weight-bold" style="font-size: 15px; border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px;">
                                            {{ strtoupper($doc_custom_title ?: 'DOCUMENTO') }}
                                            <div style="font-size: 12px; font-weight: normal;">Orden #32</div>
                                        </div>

                                        @if($doc_show_client)
                                        <div style="font-size: 11px; margin-bottom: 5px;">
                                            <div>Cliente: PUBLICO GENERAL</div>
                                            <div>Documento: 000000</div>
                                            <div>Fecha: {{ date('d/m/Y - H:i:s') }}</div>
                                        </div>
                                        @endif

                                        <div style="border-bottom: 1px dashed #000; margin-bottom: 5px;"></div>

                                        <table style="width: 100%; font-size: 11px;">
                                            <tr>
                                                <th class="text-start">CANT. DETALLE</th>
                                                @if($doc_type === 'comanda' && $doc_show_unit_price)
                                                <th class="text-center">P.U.</th>
                                                @endif
                                                <th class="text-end">SUBT.</th>
                                            </tr>
                                            <tr>
                                                <td>1 FRESAS CON DURAZNO 500ML<br><small>* NUTELLA, CREMA *</small></td>
                                                @if($doc_type === 'comanda' && $doc_show_unit_price)
                                                <td class="text-center" style="vertical-align: top;">45.00</td>
                                                @endif
                                                <td class="text-end" style="vertical-align: top;">45.00</td>
                                            </tr>
                                        </table>

                                        <div style="border-top: 1px dashed #000; margin-top: 5px; padding-top: 5px;" class="text-end">
                                            <div style="font-size: 11px; font-weight: bold;">TOTAL: 45.00</div>
                                            <div style="font-size: 11px;">QR: 45.00</div>
                                        </div>

                                        @if($doc_show_cashier)
                                        <div style="font-size: 11px; margin-top: 10px;">
                                            Cajero: CAJA.SOPOCACHI
                                        </div>
                                        @endif

                                        <div class="text-center mt-3" style="font-size: 11px;">
                                            {!! nl2br(e($doc_footer_text ?: 'Generada a través de MASTEC DIGITAL.')) !!}
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $selected_tab === 'impresoras' ? 'show active' : '' }}"
                    id="danger-pills-impresoras" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-printer"></i>
                                <span class="fw-semibold">Listar Impresoras</span>
                            </div>
                            <button class="btn btn-secondary btnIcon" data-bs-toggle="modal"
                                data-bs-target="#printerModal" wire:click="resetPrinterFields">
                                <i class="bx bx-plus-circle"></i> NUEVO
                            </button>
                        </div>

                        <div class="card-body px-3">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">Mostrar</span>
                                    <select wire:model.live="perPage" class="form-select form-select-sm"
                                        style="width: auto;">
                                        @foreach ($perPageOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted">registros</span>
                                </div>
                                @include('components.tools.searchbox')
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle table-striped table-hover" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>NOMBRE</th>
                                            <th>TIPO</th>
                                            <th>CONEXIÓN</th>
                                            <th>IP/RECURSO</th>
                                            <th>ESTADO</th>
                                            <th>ACCIONES</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($printers->isEmpty())
                                            <tr>
                                                <td colspan="7" class="text-center">No se encontraron registros.</td>
                                            </tr>
                                        @else
                                            @foreach ($printers as $index => $printer)
                                                <tr>
                                                    <td>{{ $startCount - $index }}</td>
                                                    <td>
                                                        {{ $printer->name ?: 'S/N' }}
                                                        @if ($printer->is_default)
                                                            <span class="badge bg-primary">Default</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ strtoupper($printer->type) ?: 'S/N' }}</td>
                                                    <td>
                                                        @if($printer->print_behavior == 'direct')
                                                            {{ strtoupper($printer->connection_type) ?: 'S/N' }}
                                                        @else
                                                            {{ strtoupper($printer->print_behavior) ?: 'NONE' }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $printer->ip_address ?: '-' }}</td>
                                                    <td>
                                                        @if ($printer->status == 1)
                                                            <div
                                                                class="badge rounded-pill text-success bg-light-success text-uppercase">
                                                                ACTIVO
                                                            </div>
                                                        @else
                                                            <div
                                                                class="badge rounded-pill text-danger bg-light-danger text-uppercase">
                                                                INACTIVO
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex order-actions">
                                                            <a href="javascript:;" wire:click="editPrinter({{ $printer->id }})"
                                                                data-bs-toggle="modal" data-bs-target="#printerModal"
                                                                class="btn-action-primary"><i class="bx bxs-edit-alt"></i></a>
                                                            @if ($printer->status == 1)
                                                                <a href="javascript:;"
                                                                    onclick="confirmDeletePrinter({{ $printer->id }}, 'deletePrinter')"
                                                                    class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                                            @else
                                                                <a href="javascript:;"
                                                                    onclick="confirmDeletePrinter({{ $printer->id }}, 'deletePrinter')"
                                                                    class="btn-action-warning ms-1"><i
                                                                        class="bx bx-refresh"></i></a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            {{ $printers->links() }}
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $selected_tab === 'areas_produccion' ? 'show active' : '' }}"
                    id="danger-pills-areas-produccion" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center px-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-restaurant"></i>
                                <span class="fw-semibold">Listar Áreas de Producción</span>
                            </div>
                            <button class="btn btn-secondary btnIcon" data-bs-toggle="modal"
                                data-bs-target="#productionAreaModal" wire:click="resetProductionAreaFields">
                                <i class="bx bx-plus-circle"></i> NUEVO
                            </button>
                        </div>

                        <div class="card-body px-3">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">Mostrar</span>
                                    <select wire:model.live="perPage" class="form-select form-select-sm"
                                        style="width: auto;">
                                        @foreach ($perPageOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted">registros</span>
                                </div>
                                @include('components.tools.searchbox')
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle table-striped table-hover" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>NOMBRE</th>
                                            <th>IMPRESORA ASIGNADA</th>
                                            <th>ESTADO</th>
                                            <th>ACCIONES</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($productionAreas->isEmpty())
                                            <tr>
                                                <td colspan="5" class="text-center">No se encontraron registros.</td>
                                            </tr>
                                        @else
                                            @foreach ($productionAreas as $index => $area)
                                                <tr>
                                                    <td>{{ $startCountPA + $index }}</td>
                                                    <td>{{ $area->name }}</td>
                                                    <td>{{ $area->printer ? $area->printer->name : 'NINGUNA' }}</td>
                                                    <td>
                                                        @if ($area->status == 1)
                                                            <div
                                                                class="badge rounded-pill text-success bg-light-success text-uppercase">
                                                                ACTIVO
                                                            </div>
                                                        @else
                                                            <div
                                                                class="badge rounded-pill text-danger bg-light-danger text-uppercase">
                                                                INACTIVO
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex order-actions">
                                                            <a href="javascript:;"
                                                                wire:click="editProductionArea({{ $area->id }})"
                                                                data-bs-toggle="modal" data-bs-target="#productionAreaModal"
                                                                class="btn-action-primary"><i class="bx bxs-edit-alt"></i></a>
                                                            @if ($area->status == 1)
                                                                <a href="javascript:;"
                                                                    onclick="confirmDeleteProductionArea({{ $area->id }}, 'deleteProductionArea')"
                                                                    class="btn-action-danger ms-1"><i class="bx bxs-trash"></i></a>
                                                            @else
                                                                <a href="javascript:;"
                                                                    onclick="confirmDeleteProductionArea({{ $area->id }}, 'deleteProductionArea')"
                                                                    class="btn-action-warning ms-1"><i
                                                                        class="bx bx-refresh"></i></a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            {{ $productionAreas->links() }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="printerModal" tabindex="-1" aria-labelledby="printerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printerModalLabel">
                        <i class="bx bx-printer"></i>
                        {{ $isEditModePrinter ? 'Editar Impresora' : 'Nueva Impresora' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" wire:model="printer_name" class="form-control"
                                placeholder="Ej: Cocina Principal">
                            @error('printer_name')
                                <span class="text-danger er small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Uso <span class="text-danger">*</span></label>
                            <select wire:model.live="printer_type" class="form-select">
                                <option value="">Seleccione un Tipo</option>
                                <option value="ticket_comanda">Nota de Venta / Orden</option>
                                <option value="ticket">Nota de Venta</option>
                                <option value="kitchen">Cocina (Orden)</option>
                                <option value="recibo">Recibo de Venta</option>
                            </select>
                            @error('printer_type')
                                <span class="text-danger er small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Comportamiento <span class="text-danger">*</span></label>
                            <select wire:model.live="print_behavior" class="form-select">
                                <option value="none">No hacer nada (ni PDF)</option>
                                <option value="pdf">Ventana PDF</option>
                                <option value="popup">Ventana Emergente (Pop-up)</option>
                                <option value="direct">Impresión Directa</option>
                            </select>
                        </div>

                        @if($print_behavior == 'direct')
                            <div class="col-md-6">
                                <label class="form-label">Conexión <span class="text-danger">*</span></label>
                                <select wire:model.live="connection_type" class="form-select">
                                    <option value="network">Red (Ethernet/Wifi)</option>
                                    <option value="usb">USB Directo</option>
                                    <option value="bluetooth">Bluetooth</option>
                                </select>
                            </div>

                            @if($connection_type == 'network')
                                <div class="col-md-6">
                                    <label class="form-label">IP de Impresora <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="printer_ip" class="form-control" placeholder="192.168.1.200">
                                    @error('printer_ip')
                                        <span class="text-danger er small">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label">Copias</label>
                                <input type="number" wire:model="printer_copies" class="form-control" min="1">
                            </div>

                        @endif

                        <div class="col-12">
                            <hr>
                        </div>

                        <div class="col-md-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="printer_is_default"
                                    id="chkDefault">
                                <label class="form-check-label fw-bold text-primary" for="chkDefault">Es Impresora por
                                    Defecto</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" wire:click.prevent="storeOrUpdatePrinter" class="btn btn-primary"
                        wire:loading.attr="disabled" wire:target="storeOrUpdatePrinter">
                        <span wire:loading.remove wire:target="storeOrUpdatePrinter">
                            {{ $isEditModePrinter ? 'Actualizar' : 'Guardar' }}
                        </span>
                        <span wire:loading wire:target="storeOrUpdatePrinter">
                            <i class="bx bx-spin bx-loader"></i> Procesando...
                        </span>
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                        wire:click="resetPrinterFields">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="productionAreaModal" tabindex="-1"
        aria-labelledby="productionAreaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productionAreaModalLabel">
                        <i class="bx bx-restaurant"></i>
                        {{ $isEditModeProductionArea ? 'Editar Área de Producción' : 'Nueva Área de Producción' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" wire:model="production_area_name" class="form-control"
                                placeholder="Ej: SALA, COCINA, BAR">
                            @error('production_area_name')
                                <span class="text-danger er small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Impresora Asignada <span class="text-danger">*</span></label>
                            <select wire:model="production_area_printer_id" class="form-select">
                                <option value="">Seleccionar Impresora</option>
                                @foreach($activePrinters as $printer)
                                    <option value="{{ $printer->id }}">{{ $printer->name }}</option>
                                @endforeach
                            </select>
                            @error('production_area_printer_id')
                                <span class="text-danger er small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" wire:click.prevent="storeOrUpdateProductionArea" class="btn btn-primary"
                        wire:loading.attr="disabled" wire:target="storeOrUpdateProductionArea">
                        <span wire:loading.remove wire:target="storeOrUpdateProductionArea">
                            {{ $isEditModeProductionArea ? 'Actualizar' : 'Guardar' }}
                        </span>
                        <span wire:loading wire:target="storeOrUpdateProductionArea">
                            <i class="bx bx-spin bx-loader"></i> Procesando...
                        </span>
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                        wire:click="resetProductionAreaFields">
                        Cancelar
                    </button>
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

        Livewire.on('printerStoreOrUpdate', (data) => {
            const [msg, type] = data;
            $('#printerModal').modal('hide');
            toast(msg, type);
        });

        Livewire.on('printerDeleted', (data) => {
            const [msg, type] = data;
            toast(msg, type);
        });

        Livewire.on('productionAreaStoreOrUpdate', (data) => {
            const [msg, type] = data;
            $('#productionAreaModal').modal('hide');
            toast(msg, type);
        });

        Livewire.on('productionAreaDeleted', (data) => {
            const [msg, type] = data;
            toast(msg, type);
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

    function confirmDeletePrinter(id, action) {
        Swal.fire({
            title: action === 'deletePrinter' ? "¿Está seguro de cambiar el estado?" :
                "¿Está seguro de restaurar?",
            text: action === 'deletePrinter' ?
                "El registro cambiará de estado!" : "El registro será restaurado!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si, Continuar!",
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('deletePrinter', id);
            }
        });
    }

    function confirmDeleteProductionArea(id, action) {
        Swal.fire({
            title: action === 'deleteProductionArea' ? "¿Está seguro de cambiar el estado?" :
                "¿Está seguro de restaurar?",
            text: action === 'deleteProductionArea' ?
                "El registro cambiará de estado!" : "El registro será restaurado!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si, Continuar!",
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('deleteProductionArea', id);
            }
        });
    }
</script>