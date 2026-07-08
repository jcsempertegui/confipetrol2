@push('title', 'Ajustes')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item text-primary">Administración</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Ajustes</li>
            </ol>
        </div>
    </div>

    @if(auth()->id() == 1)
    <div class="card mb-3">
        <div class="card-body px-4 py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bx bx-power-off fs-5 {{ $systemEnabled ? 'text-success' : 'text-danger' }}"></i>
                        <span class="fw-semibold">Estado Global del Sistema</span>
                    </div>
                    <div class="text-muted small">
                        @if($systemEnabled)
                            El sistema está activo. Todos los usuarios tienen acceso normal.
                        @else
                            El sistema está desactivado. Solo el administrador puede acceder.
                        @endif
                    </div>
                </div>
                <button wire:click="toggleSystem"
                    wire:loading.attr="disabled"
                    wire:target="toggleSystem"
                    class="btn {{ $systemEnabled ? 'btn-outline-danger' : 'btn-success' }} btn-sm px-4">
                    <span wire:loading.remove wire:target="toggleSystem">
                        <i class="bx {{ $systemEnabled ? 'bx-lock' : 'bx-lock-open' }} me-1"></i>
                        {{ $systemEnabled ? 'Desactivar Sistema' : 'Activar Sistema' }}
                    </span>
                    <span wire:loading wire:target="toggleSystem">
                        <i class="bx bx-spin bx-loader me-1"></i> Procesando...
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body px-4 mt-2">
            <div class="card-header d-flex justify-content-between align-items-center px-3 py-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-building-house"></i>
                    <span class="fw-semibold">Ajustes del Negocio</span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Negocio <span class="text-danger">*</span></label>
                            <input type="text" wire:model="business" class="form-control" placeholder="Negocio">
                            @error('business')
                                <span class="text-danger er">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Razon Social <span class="text-danger">*</span></label>
                            <input type="text" wire:model="owner" class="form-control" placeholder="Razon Social">
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
                            <input type="email" wire:model="email" class="form-control" placeholder="Correo Electrónico">
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
                                <label for="message">Mensaje (Opcional)</label>
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
                                <span wire:loading.remove wire:target="updateSettings"><i class="bx bx-save"></i> Actualizar</span>
                                <span wire:loading wire:target="updateSettings"><i class="bx bx-spin bx-loader"></i> Procesando...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group" wire:ignore>
                        <label class="form-label fw-bold w-100 text-center">Logo del Negocio</label>
                        <div class="d-flex flex-column align-items-center justify-content-center p-3 border rounded bg-light">
                            <div class="image-container position-relative mb-3">
                                <img id="previewImage"
                                    src="{{ $image_preview ? asset('storage/' . $image_preview) : asset('assets/images/logo.png') }}"
                                    class="img-thumbnail shadow-sm"
                                    style="border-radius: 25px; width: 200px; height: 200px; object-fit: contain; object-position: center;"
                                    alt="Vista previa">

                                <div id="uploadOverlay" class="image-overlay"
                                    style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); border-radius: 25px; flex-direction: column; align-items: center; justify-content: center;">
                                    <i class="bx bx-spin bx-loader upload-spinner fs-1 text-danger"></i>
                                    <span id="uploadText" class="fw-bold mt-2 text-dark">Subiendo imagen...</span>
                                    <div class="progress-bar-custom w-75 mt-2"
                                        style="height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden;">
                                        <div id="progressFill" class="progress-fill bg-danger"
                                            style="width: 0%; height: 100%;"></div>
                                    </div>
                                    <small id="progressPercent" class="mt-1 fw-bold text-dark">0%</small>
                                </div>
                            </div>

                            <div class="w-100 text-center" style="max-width: 300px;">
                                <input type="file" id="imageInput" name="image"
                                    class="form-control d-none" accept=".jpg,.jpeg,.png,.webp">
                                <button type="button" id="selectImageBtn"
                                    class="btn btn-outline-primary w-100 cursor-pointer"
                                    onclick="document.getElementById('imageInput').click()">
                                    <i class="bx bx-cloud-upload"></i> Seleccionar Imagen
                                </button>
                                <small class="text-muted d-block mt-1">Formatos: PNG, JPG, WEBP (Máx. 20MB)</small>
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
