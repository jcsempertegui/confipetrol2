@push('title', 'Importar Clientes')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item">Clientes</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Importar</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 mx-auto">
            <h6 class="mb-0 text-uppercase">Importación Masiva de Clientes</h6>
            <hr />
            <div class="card">
                <div class="card-body p-4">
                    <div class="row mb-2 p-2">
                        <div class="col-lg-12 col-sm-12 mb-3">
                            <label>Seleccionar Archivo (Excel/CSV)</label>
                            <input type="file" class="form-control" wire:model="file" accept=".xlsx, .csv">
                            @error('file') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>

                        <div wire:loading wire:target="file" class="text-center w-100">
                            <div class="progress mb-3" style="height:15px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    style="width: 100%">Analizando archivo...</div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <a href="{{ asset('assets/formats/formato_importacion_clientes.xlsx') }}" download
                                class="text-primary d-block mb-2">
                                <i class="bx bx-download"></i> Descargar formato de ejemplo
                            </a>
                        </div>
                    </div>

                    <button type="button" wire:click.prevent="storeImport()" class="btn btn-primary"
                        wire:loading.attr="disabled" wire:target="storeImport" @if(!$file || count($customersData) == 0) disabled @endif>
                        <span wire:loading.remove wire:target="storeImport">
                            <i class="bx bx-upload"></i> Importar Clientes
                        </span>
                        <span wire:loading wire:target="storeImport">
                            <i class="bx bx-spin bx-loader"></i> Guardando en Base de Datos...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(count($customersData) > 0)
        <div class="card">
            <div class="card-body px-4 mt-2">
                <div class="table-responsive">
                    <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>TIPO DOC.</th>
                                <th>DOCUMENTO (CI/NIT)</th>
                                <th>RAZÓN SOCIAL</th>
                                <th>TELÉFONO</th>
                                <th>CORREO</th>
                                <th>DIRECCIÓN</th>
                                <th>ESTADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customersPaginated as $index => $customer)
                                <tr class="{{ isset($customer['has_errors']) && $customer['has_errors'] ? 'table-danger' : '' }}">
                                    <td>{{ $startCount + $loop->index }}</td>
                                    <td>{{ $customer[0] ?? '' }}</td>
                                    <td>{{ $customer[1] ?? '' }}</td>
                                    <td>{{ $customer[2] ?? '' }}</td>
                                    <td>{{ $customer[3] ?? '' }}</td>
                                    <td>{{ $customer[4] ?? '' }}</td>
                                    <td>{{ $customer[5] ?? '' }}</td>
                                    <td>
                                        @if(isset($customer['has_errors']) && $customer['has_errors'])
                                            <span class="text-danger" title="{{ implode(', ', $customer['row_errors']) }}">
                                                <i class="bx bx-error-circle"></i> {{ count($customer['row_errors']) }} Error(es)
                                            </span>
                                        @elseif(isset($customer['exists']) && $customer['exists'])
                                            <span class="text-warning"><i class="bx bx-info-circle"></i> Se Actualizará</span>
                                        @else
                                            <span class="text-success"><i class="bx bx-check-circle"></i> Nuevo Registro</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        {{ $customersPaginated->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('alert', (data) => {
            const [msg, type] = data;
            toast(msg, type); // Asegúrate de tener tu función toast global definida
        });
    });
</script>