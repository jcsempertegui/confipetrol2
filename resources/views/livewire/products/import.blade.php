@push('title', 'Importar Productos')

<div class="page-content">
    <div class="row align-items-center mb-3 px-2">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item">Productos</li>
                <li class="breadcrumb-item" style="font-weight: 500; font-size: 18px;">Importar</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 mx-auto">
            <h6 class="mb-0 text-uppercase">Importación Masiva de Productos</h6>
            <hr />
            <div class="card">
                <div class="card-body p-4">
                    <div class="row mb-2 p-2">
                        <div class="col-lg-6 col-sm-6 mb-3">
                            <label>Seleccionar Almacén</label>
                            <select class="form-select" wire:model.lazy="warehouse_id" wire:change="verifiryProduct">
                                <option value="">Seleccionar</option>
                                @foreach ($warehousesList as $wh)
                                    <option value="{{ $wh['id'] }}">{{ $wh['display_name'] }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-6 col-sm-6 mb-3">
                            <label>Seleccionar Archivo</label>
                            <input type="file" class="form-control" wire:model="file" accept=".xlsx, .csv">
                            @error('file') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>

                        <div wire:loading wire:target="file" class="text-center w-100">
                            <div class="progress mb-3" style="height:15px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    style="width: 100%">Cargando...</div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <a href="{{ asset('assets/formats/formato_importacion_productos.xlsx') }}" download
                                class="text-primary d-block mb-2">
                                <i class="bx bx-download"></i> Descargar formato de ejemplo
                            </a>
                        </div>
                    </div>

                    <button type="button" wire:click.prevent="storeImport()" class="btn btn-primary"
                        wire:loading.attr="disabled" wire:target="storeImport" @if(!$warehouse_id || !$file || count($products) == 0) disabled @endif>
                        <span wire:loading.remove wire:target="storeImport">
                            <i class="bx bx-upload"></i> Importar Productos
                        </span>
                        <span wire:loading wire:target="storeImport">
                            <i class="bx bx-spin bx-loader"></i> Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(count($products) > 0)
        <div class="card">
            <div class="card-body px-4 mt-2">
                <div class="table-responsive">
                    <table class="table align-middle table-striped table-hover nowrap" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>CODIGO</th>
                                <th>PRODUCTO</th>
                                <th>P. COMPRA</th>
                                <th>P. VENTA</th>
                                <th>CATEGORIA</th>
                                <th>MARCA</th>
                                <th>UNIDAD</th>
                                <th>STOCK</th>
                                @if($enable_size_color == 1)
                                <th>TALLA</th>
                                <th>COLOR</th>
                                @endif
                                <th>ESTADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($productsPaginated as $index => $product)
                                <tr class="{{ isset($product['has_errors']) && $product['has_errors'] ? 'table-danger' : '' }}">
                                    <td>{{ $startCount + $loop->index }}</td>
                                    <td>{{ $product[0] ?? '' }}</td>
                                    <td>{{ $product[1] ?? '' }}</td>
                                    <td>
                                        {{ is_numeric(str_replace(',', '.', $product[2] ?? 0)) ? number_format((float) str_replace(',', '.', $product[2]), 2) : 'Auto' }}
                                    </td>
                                    <td>
                                        {{ is_numeric(str_replace(',', '.', $product[3] ?? 0)) ? number_format((float) str_replace(',', '.', $product[3]), 2) : '0.00' }}
                                    </td>
                                    <td>{{ $product[4] ?? 'Sin Categoría' }}</td>
                                    <td>{{ $product[5] ?? 'Sin Marca' }}</td>
                                    <td>{{ $product[6] ?? 'Unidad' }}</td>
                                    <td>{{ $product[7] ?? '0' }}</td>
                                    @if($enable_size_color == 1)
                                    <td>{{ $product[8] ?? '-' }}</td>
                                    <td>{{ $product[9] ?? '-' }}</td>
                                    @endif
                                    <td>
                                        @if(isset($product['has_errors']) && $product['has_errors'])
                                            <span class="text-danger" title="{{ implode(', ', $product['row_errors']) }}">
                                                <i class="bx bx-error-circle"></i> {{ count($product['row_errors']) }} Error
                                            </span>
                                        @elseif(isset($product['exists']) && $product['exists'])
                                            <span class="text-warning"><i class="bx bx-info-circle"></i> Actualizar</span>
                                        @else
                                            <span class="text-success"><i class="bx bx-check-circle"></i> Nuevo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        {{ $productsPaginated->links() }}
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
            toast(msg, type);
        });
    });
</script>