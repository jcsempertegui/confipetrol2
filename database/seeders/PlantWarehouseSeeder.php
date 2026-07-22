<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\DispatchNote;
use App\Models\Log;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\SerializedItem;
use App\Models\SerializedItemAttributeValue;
use App\Models\User;
use App\Models\VariantAttributeValue;
use App\Models\Worker;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PlantWarehouseSeeder extends Seeder
{
    private const MARKER = 'CARGA_PLANTA_V1';

    private const LOCK_NAME = 'confipetrol2:carga-planta-v1';

    private const LOAD_DATE = '2026-07-16';

    private InventoryService $inventory;

    private User $actor;

    /** @var array<string, Category> */
    private array $categories = [];

    /** @var array<string, ProductAttribute> */
    private array $attributes = [];

    /** @var array<string, ProductVariant> */
    private array $variants = [];

    /** @var array<string, SerializedItem> */
    private array $serials = [];

    /** @var array<string, Worker> */
    private array $workers = [];

    /** @var list<string> */
    private array $productCodes = [];

    /** @var list<string> */
    private array $variantSkus = [];

    /** @var list<string> */
    private array $serialNumbers = [];

    /** @var list<string> */
    private array $workerDocuments = [];

    /** @var list<string> */
    private array $userLogins = [];

    /** @var list<string> */
    private array $dispatchNumbers = [
        'ING101160726',
        'ING102160726',
        'ING103160726',
        'ING104160726',
        'SAL101160726',
    ];

    /** @var list<string> */
    private array $deliveryNumbers = [
        'ENT101160726',
        'ENT102160726',
        'ENT103160726',
        'ENT104160726',
        'ENT105160726',
        'ENT106160726',
    ];

    public function run(): void
    {
        $this->inventory = app(InventoryService::class);

        $this->withExclusiveLock(function (): void {
            if (Log::query()->where('accion', self::MARKER)->exists()) {
                $this->assertCompletedManifest();
                $this->command?->info('La carga CARGA_PLANTA_V1 ya estaba completa. No se modificó ningún registro.');

                return;
            }

            $this->actor = User::query()
                ->where('status', true)
                ->whereHas('roles', fn ($query) => $query
                    ->where('name', 'SUPER ADMIN')
                    ->where('guard_name', 'web'))
                ->orderBy('id')
                ->firstOrFail();

            DB::transaction(function (): void {
                $roles = $this->seedRoles();
                $this->seedUsers($roles);
                $this->seedCategoriesAndAttributes();
                $this->seedProducts();
                $this->seedWorkers();
                $this->seedDispatchNotes();
                $this->seedDeliveries();
                $this->assertCompletedManifest(false);
                $this->auditFinalLoad();
            }, 3);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->command?->info('Carga CARGA_PLANTA_V1 completada: 25 productos, 10 trabajadores, 5 remitos y 6 entregas.');
        });
    }

    /** @return array<string, Role> */
    private function seedRoles(): array
    {
        $definitions = [
            'JEFE DE ALMACÉN' => [
                'ver-categoria', 'crear-categoria', 'editar-categoria', 'gestionar-atributos',
                'ver-producto', 'crear-producto', 'editar-producto',
                'ver-trabajador', 'crear-trabajador', 'editar-trabajador', 'restaurar-trabajador',
                'ver-remito', 'crear-remito', 'editar-remito', 'eliminar-remito', 'confirmar-remito', 'anular-remito',
                'ver-entrega', 'crear-entrega', 'editar-entrega', 'eliminar-entrega', 'confirmar-entrega', 'anular-entrega',
                'ver-inventario', 'ver-kardex', 'ver-reporte', 'exportar-reporte',
                'ver-log', 'exportar-log', 'ver-backup', 'crear-backup',
            ],
            'ALMACENERO' => [
                'ver-categoria', 'ver-producto', 'ver-trabajador',
                'ver-remito', 'crear-remito', 'eliminar-remito', 'confirmar-remito',
                'ver-entrega', 'crear-entrega', 'eliminar-entrega', 'confirmar-entrega',
                'ver-inventario', 'ver-kardex', 'ver-reporte',
                'ver-backup', 'crear-backup',
            ],
            'AUDITOR DE INVENTARIO' => [
                'ver-categoria', 'ver-producto', 'ver-trabajador', 'ver-remito', 'ver-entrega',
                'ver-inventario', 'ver-kardex', 'ver-reporte', 'exportar-reporte', 'ver-log', 'exportar-log',
            ],
            'HSE CONSULTA' => [
                'ver-categoria', 'ver-producto', 'ver-trabajador', 'ver-entrega',
                'ver-inventario', 'ver-kardex', 'ver-reporte',
            ],
        ];

        $missing = collect($definitions)->flatten()->unique()
            ->diff(Permission::query()->where('guard_name', 'web')->pluck('name'));
        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Faltan permisos requeridos para la carga: '.$missing->implode(', ').'. Ejecute primero PermissionSeeder.');
        }

        $roles = [];
        foreach ($definitions as $name => $permissionNames) {
            $role = Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions($permissionNames);
            } else {
                $current = $role->permissions()->orderBy('name')->pluck('name')->all();
                $expected = collect($permissionNames)->sort()->values()->all();
                if ($current !== $expected) {
                    throw new RuntimeException("El rol {$name} ya existe con permisos diferentes; no se modificó.");
                }
            }

            $roles[$name] = $role;
            $this->audit('ROLES', 'CARGA_INICIAL', $role->id, "Rol operativo incorporado: {$name}.", [
                'name' => $name,
                'permissions' => $permissionNames,
                'restaurar_backup' => false,
            ]);
        }

        return $roles;
    }

    /** @param array<string, Role> $roles */
    private function seedUsers(array $roles): void
    {
        $definitions = [
            ['login' => 'jefe.almacen.psl', 'name' => 'Paola Fernanda', 'lastname' => 'Gutiérrez Salas', 'document' => 'PSL-USR-001', 'email' => 'jefe.almacen@planta-liquidos.test', 'role' => 'JEFE DE ALMACÉN'],
            ['login' => 'almacenero.psl', 'name' => 'Óscar Daniel', 'lastname' => 'López Vargas', 'document' => 'PSL-USR-002', 'email' => 'almacenero@planta-liquidos.test', 'role' => 'ALMACENERO'],
            ['login' => 'auditor.inventario.psl', 'name' => 'Valeria Andrea', 'lastname' => 'Ríos Méndez', 'document' => 'PSL-USR-003', 'email' => 'auditoria.inventario@planta-liquidos.test', 'role' => 'AUDITOR DE INVENTARIO'],
            ['login' => 'hse.consulta.psl', 'name' => 'Mariela Soledad', 'lastname' => 'Rojas Pérez', 'document' => 'PSL-USR-004', 'email' => 'hse.consulta@planta-liquidos.test', 'role' => 'HSE CONSULTA'],
        ];

        foreach ($definitions as $definition) {
            $roleName = $definition['role'];
            unset($definition['role']);
            $login = $definition['login'];
            $expected = $definition + ['phone' => null, 'status' => false, 'max_sessions' => 1];
            $user = User::query()->firstOrCreate(['login' => $login], $expected + [
                'password' => Hash::make(Str::random(64)),
            ]);

            if (! $user->wasRecentlyCreated) {
                $this->assertModelValues($user, $expected, "usuario {$login}");
                $currentRoles = $user->roles()->orderBy('name')->pluck('name')->all();
                if ($currentRoles !== [$roleName]) {
                    throw new RuntimeException("El usuario {$login} ya existe con un rol diferente; no se modificó.");
                }
            } else {
                $user->assignRole($roles[$roleName]);
            }

            $this->userLogins[] = $login;
            $this->audit('USUARIOS', 'CARGA_INICIAL', $user->id, "Usuario operativo inactivo incorporado: {$login}.", [
                'login' => $login,
                'name' => $user->name,
                'lastname' => $user->lastname,
                'document' => $user->document,
                'email' => $user->email,
                'status' => false,
                'role' => $roleName,
            ]);
        }
    }

    private function seedCategoriesAndAttributes(): void
    {
        $definitions = [
            'EQP' => ['name' => 'Equipos y activos serializados', 'description' => 'Equipos individuales de operación, inspección y soporte con trazabilidad por número de serie.'],
            'EPP' => ['name' => 'Equipos de protección personal', 'description' => 'Dotación de seguridad para el personal de la planta separadora de líquidos.'],
            'CON' => ['name' => 'Consumibles de operación', 'description' => 'Materiales de uso recurrente para operación y mantenimiento.'],
            'HER' => ['name' => 'Herramientas', 'description' => 'Herramientas técnicas de uso controlado en mantenimiento.'],
            'REP' => ['name' => 'Repuestos', 'description' => 'Repuestos críticos para equipos y sistemas de proceso.'],
        ];

        foreach ($definitions as $code => $definition) {
            $category = Category::query()->firstOrCreate(['code' => $code], $definition + ['status' => true]);
            if (! $category->status) {
                throw new RuntimeException("La categoría {$code} existe pero está inactiva; no se modificó.");
            }
            if ($category->wasRecentlyCreated || in_array($code, ['EQP', 'HER', 'REP'], true)) {
                $this->assertModelValues($category, ['code' => $code, 'name' => $definition['name'], 'status' => true], "categoría {$code}");
            }
            $this->categories[$code] = $category;
            $this->audit('CATEGORÍAS', 'CARGA_INICIAL', $category->id, "Categoría de almacén incorporada: {$code}.", [
                'code' => $code,
                'name' => $category->name,
                'status' => (bool) $category->status,
            ]);
        }

        $this->attributes['eqp-marca'] = $this->ensureAttribute('EQP', [
            'name' => 'Marca', 'code' => 'eqp-marca', 'type' => 'text', 'scope' => 'product', 'options' => null,
        ], true, 1);
        $this->attributes['eqp-modelo'] = $this->ensureAttribute('EQP', [
            'name' => 'Modelo', 'code' => 'eqp-modelo', 'type' => 'text', 'scope' => 'product', 'options' => null,
        ], true, 2);
        $this->attributes['eqp-serie'] = $this->ensureAttribute('EQP', [
            'name' => 'Número de serie', 'code' => 'eqp-serie', 'type' => 'text', 'scope' => 'unit', 'options' => null,
        ], true, 3);

        $eppSize = ProductAttribute::query()->where('code', 'epp-talla')->first();
        if ($eppSize && ($eppSize->scope !== 'variant' || $eppSize->type !== 'select')) {
            throw new RuntimeException('El atributo existente epp-talla no es compatible con la carga.');
        }
        $this->attributes['epp-talla'] = $eppSize ?: $this->ensureAttribute('EPP', [
            'name' => 'Talla', 'code' => 'epp-talla', 'type' => 'select', 'scope' => 'variant',
            'options' => ['Única', '7', '8', '9', '10', 'M', 'L', 'XL', '2XL', '39', '40', '41', '42', '43', '44'],
        ], true, 1);
        $this->ensurePivot($this->categories['EPP'], $this->attributes['epp-talla'], true, 1, false);

        $eppBrand = ProductAttribute::query()->where('code', 'epp-marca')->first();
        if ($eppBrand && $eppBrand->scope !== 'variant') {
            throw new RuntimeException('El atributo existente epp-marca no es compatible con la carga.');
        }
        $this->attributes['epp-marca'] = $eppBrand ?: $this->ensureAttribute('EPP', [
            'name' => 'Marca', 'code' => 'epp-marca', 'type' => 'text', 'scope' => 'variant', 'options' => null,
        ], false, 2);
        $this->ensurePivot($this->categories['EPP'], $this->attributes['epp-marca'], false, 2, false);

        $this->attributes['con-presentacion'] = $this->ensureAttribute('CON', [
            'name' => 'Presentación', 'code' => 'psl-con-presentacion', 'type' => 'text', 'scope' => 'product', 'options' => null,
        ], true, 1);
        $this->attributes['her-medida'] = $this->ensureAttribute('HER', [
            'name' => 'Medida o rango', 'code' => 'psl-her-medida', 'type' => 'text', 'scope' => 'product', 'options' => null,
        ], true, 1);
        $this->attributes['rep-especificacion'] = $this->ensureAttribute('REP', [
            'name' => 'Especificación técnica', 'code' => 'psl-rep-especificacion', 'type' => 'text', 'scope' => 'product', 'options' => null,
        ], true, 1);
    }

    private function seedProducts(): void
    {
        foreach ($this->productDefinitions() as $definition) {
            $categoryCode = $definition['category'];
            $productValues = $definition['attributes'];
            $variants = $definition['variants'];
            unset($definition['category'], $definition['attributes'], $definition['variants']);

            $code = $definition['code'];
            $expected = $definition + ['category_id' => $this->categories[$categoryCode]->id, 'status' => true];
            $product = Product::query()->firstOrCreate(['code' => $code], $expected);
            $this->assertModelValues($product, $expected, "producto {$code}");

            foreach ($productValues as $attributeKey => $value) {
                $this->ensureProductValue($product, $this->attributes[$attributeKey], $value);
            }

            foreach ($variants as $variantDefinition) {
                $variantValues = $variantDefinition['attributes'] ?? [];
                $serials = $variantDefinition['serials'] ?? [];
                unset($variantDefinition['attributes'], $variantDefinition['serials']);

                $sku = $variantDefinition['sku'];
                $variantExpected = $variantDefinition + ['product_id' => $product->id, 'status' => true];
                $variant = ProductVariant::query()->firstOrCreate(['sku' => $sku], $variantExpected);
                $this->assertModelValues($variant, $variantExpected, "variante {$sku}");

                foreach ($variantValues as $attributeKey => $value) {
                    $this->ensureVariantValue($variant, $this->attributes[$attributeKey], $value);
                }

                foreach ($serials as $serialNumber) {
                    $serial = SerializedItem::query()->firstOrCreate(
                        ['serial_number' => $serialNumber],
                        ['product_variant_id' => $variant->id, 'status' => 'available']
                    );
                    if ((int) $serial->product_variant_id !== (int) $variant->id || $serial->status === 'inactive') {
                        throw new RuntimeException("La serie {$serialNumber} ya existe asociada a otro equipo o está inactiva.");
                    }
                    $this->ensureSerializedValue($serial, $this->attributes['eqp-serie'], $serialNumber);
                    $this->serials[$serialNumber] = $serial;
                    $this->serialNumbers[] = $serialNumber;
                }

                $this->variants[$sku] = $variant;
                $this->variantSkus[] = $sku;
            }

            $this->productCodes[] = $code;
            $this->audit('PRODUCTOS', 'CARGA_INICIAL', $product->id, "Producto de planta incorporado: {$code} - {$product->name}.", [
                'code' => $code,
                'name' => $product->name,
                'category' => $categoryCode,
                'unit' => $product->unit,
                'tracking_type' => $product->tracking_type,
                'variants' => collect($variants)->pluck('sku')->all(),
            ]);
        }
    }

    /** @return list<array<string, mixed>> */
    private function productDefinitions(): array
    {
        $single = fn (string $sku, string $name, float $minimum, array $attributes = [], array $serials = []): array => [
            'sku' => $sku, 'name' => $name, 'minimum_stock' => $minimum, 'attributes' => $attributes, 'serials' => $serials,
        ];
        $epp = fn (string $sku, string $name, string $brand, string $size, float $minimum): array => $single(
            $sku,
            $name,
            $minimum,
            ['epp-marca' => $brand, 'epp-talla' => $size]
        );

        return [
            [
                'category' => 'EQP', 'code' => 'PSL-EQP-001', 'name' => 'Detector multigás portátil',
                'description' => 'Detector para O₂, H₂S, CO y gases combustibles en áreas de proceso.', 'unit' => 'equipo', 'tracking_type' => 'serialized',
                'attributes' => ['eqp-marca' => 'MSA', 'eqp-modelo' => 'ALTAIR 4XR'],
                'variants' => [$single('PSL-EQP-001-STD', 'Configuración 4 gases', 0, [], ['MSA4XR-PSL-26001'])],
            ],
            [
                'category' => 'EQP', 'code' => 'PSL-EQP-002', 'name' => 'Radio portátil ATEX',
                'description' => 'Radio intrínsecamente segura para comunicaciones en áreas clasificadas.', 'unit' => 'equipo', 'tracking_type' => 'serialized',
                'attributes' => ['eqp-marca' => 'Motorola', 'eqp-modelo' => 'DP4801 Ex'],
                'variants' => [$single('PSL-EQP-002-STD', 'VHF ATEX', 0, [], ['MOT-DP48-PSL-26001'])],
            ],
            [
                'category' => 'EQP', 'code' => 'PSL-EQP-003', 'name' => 'Laptop industrial',
                'description' => 'Computadora robusta para diagnóstico y configuración de equipos de planta.', 'unit' => 'equipo', 'tracking_type' => 'serialized',
                'attributes' => ['eqp-marca' => 'Panasonic', 'eqp-modelo' => 'Toughbook 55'],
                'variants' => [$single('PSL-EQP-003-STD', 'Toughbook industrial', 0, [], ['PAN-CF55-PSL-26001'])],
            ],
            [
                'category' => 'EQP', 'code' => 'PSL-EQP-004', 'name' => 'Cámara termográfica',
                'description' => 'Equipo para inspección térmica de tableros, motores y líneas de proceso.', 'unit' => 'equipo', 'tracking_type' => 'serialized',
                'attributes' => ['eqp-marca' => 'FLIR', 'eqp-modelo' => 'E8-XT'],
                'variants' => [$single('PSL-EQP-004-STD', 'Resolución 320 × 240', 0, [], ['FLIR-E8XT-PSL-26001'])],
            ],
            [
                'category' => 'EQP', 'code' => 'PSL-EQP-005', 'name' => 'Calibrador documentador de procesos',
                'description' => 'Calibrador para pruebas y mantenimiento de instrumentación de proceso.', 'unit' => 'equipo', 'tracking_type' => 'serialized',
                'attributes' => ['eqp-marca' => 'Fluke', 'eqp-modelo' => '754'],
                'variants' => [$single('PSL-EQP-005-STD', 'Calibrador multifunción', 0, [], ['FLK-754-PSL-26001'])],
            ],
            [
                'category' => 'EQP', 'code' => 'PSL-EQP-006', 'name' => 'Tablet industrial',
                'description' => 'Tablet robusta para rondas operativas, inspecciones y listas de verificación.', 'unit' => 'equipo', 'tracking_type' => 'serialized',
                'attributes' => ['eqp-marca' => 'Samsung', 'eqp-modelo' => 'Galaxy Tab Active4 Pro'],
                'variants' => [$single('PSL-EQP-006-STD', '128 GB / Wi-Fi', 0, [], ['SAM-TABA4-PSL-26001'])],
            ],
            [
                'category' => 'EPP', 'code' => 'PSL-EPP-001', 'name' => 'Casco de seguridad industrial',
                'description' => 'Casco con suspensión de seis puntos para áreas operativas.', 'unit' => 'unidad', 'tracking_type' => 'bulk', 'attributes' => [],
                'variants' => [$epp('PSL-EPP-001-UNI', 'Color blanco / talla ajustable', 'MSA V-Gard', 'Única', 8)],
            ],
            [
                'category' => 'EPP', 'code' => 'PSL-EPP-002', 'name' => 'Lentes de seguridad',
                'description' => 'Lentes transparentes antiempañantes para protección ocular.', 'unit' => 'unidad', 'tracking_type' => 'bulk', 'attributes' => [],
                'variants' => [$epp('PSL-EPP-002-UNI', 'Lente transparente', '3M SecureFit', 'Única', 12)],
            ],
            [
                'category' => 'EPP', 'code' => 'PSL-EPP-003', 'name' => 'Guante resistente a hidrocarburos',
                'description' => 'Guante de nitrilo para manipulación de aceites, condensados y químicos.', 'unit' => 'par', 'tracking_type' => 'bulk', 'attributes' => [],
                'variants' => [
                    $epp('PSL-EPP-003-T07', 'Talla 7', 'Ansell AlphaTec 58-535', '7', 6),
                    $epp('PSL-EPP-003-T08', 'Talla 8', 'Ansell AlphaTec 58-535', '8', 10),
                    $epp('PSL-EPP-003-T09', 'Talla 9', 'Ansell AlphaTec 58-535', '9', 10),
                    $epp('PSL-EPP-003-T10', 'Talla 10', 'Ansell AlphaTec 58-535', '10', 6),
                ],
            ],
            [
                'category' => 'EPP', 'code' => 'PSL-EPP-004', 'name' => 'Overol ignífugo',
                'description' => 'Overol de trabajo resistente a la llama para áreas de proceso.', 'unit' => 'unidad', 'tracking_type' => 'bulk', 'attributes' => [],
                'variants' => [
                    $epp('PSL-EPP-004-TM', 'Talla M', 'Nomex IIIA', 'M', 4),
                    $epp('PSL-EPP-004-TL', 'Talla L', 'Nomex IIIA', 'L', 6),
                    $epp('PSL-EPP-004-TXL', 'Talla XL', 'Nomex IIIA', 'XL', 4),
                    $epp('PSL-EPP-004-T2XL', 'Talla 2XL', 'Nomex IIIA', '2XL', 2),
                ],
            ],
            [
                'category' => 'EPP', 'code' => 'PSL-EPP-005', 'name' => 'Botín de seguridad dieléctrico',
                'description' => 'Botín con puntera de composite y suela antideslizante.', 'unit' => 'par', 'tracking_type' => 'bulk', 'attributes' => [],
                'variants' => collect(range(39, 44))->map(fn (int $size): array => $epp(
                    "PSL-EPP-005-T{$size}", "Talla {$size}", 'Funcional Titan', (string) $size, 2
                ))->all(),
            ],
            [
                'category' => 'EPP', 'code' => 'PSL-EPP-006', 'name' => 'Respirador reutilizable media cara',
                'description' => 'Respirador de silicona para cartuchos de vapores y gases.', 'unit' => 'unidad', 'tracking_type' => 'bulk', 'attributes' => [],
                'variants' => [
                    $epp('PSL-EPP-006-TM', 'Talla M', '3M 7502', 'M', 4),
                    $epp('PSL-EPP-006-TL', 'Talla L', '3M 7503', 'L', 3),
                ],
            ],
            [
                'category' => 'EPP', 'code' => 'PSL-EPP-007', 'name' => 'Cartucho para vapores orgánicos',
                'description' => 'Cartucho reemplazable para respiradores de media cara.', 'unit' => 'par', 'tracking_type' => 'bulk', 'attributes' => [],
                'variants' => [$epp('PSL-EPP-007-UNI', 'Cartucho vapores orgánicos', '3M 6001', 'Única', 8)],
            ],
            [
                'category' => 'EPP', 'code' => 'PSL-EPP-008', 'name' => 'Protector auditivo tipo copa',
                'description' => 'Protección auditiva para zonas con niveles elevados de ruido.', 'unit' => 'unidad', 'tracking_type' => 'bulk', 'attributes' => [],
                'variants' => [$epp('PSL-EPP-008-UNI', 'Atenuación 30 dB', '3M Peltor', 'Única', 12)],
            ],
            [
                'category' => 'CON', 'code' => 'PSL-CON-001', 'name' => 'Paño absorbente para hidrocarburos',
                'description' => 'Paño para control de derrames y limpieza operativa.', 'unit' => 'unidad', 'tracking_type' => 'bulk',
                'attributes' => ['con-presentacion' => 'Paño 40 × 50 cm'], 'variants' => [$single('PSL-CON-001-STD', 'Paño blanco oleofílico', 30)],
            ],
            [
                'category' => 'CON', 'code' => 'PSL-CON-002', 'name' => 'Cinta de PTFE',
                'description' => 'Sellador para uniones roscadas de servicios auxiliares.', 'unit' => 'rollo', 'tracking_type' => 'bulk',
                'attributes' => ['con-presentacion' => '12 mm × 12 m'], 'variants' => [$single('PSL-CON-002-STD', 'Rollo estándar', 15)],
            ],
            [
                'category' => 'CON', 'code' => 'PSL-CON-003', 'name' => 'Limpiador dieléctrico',
                'description' => 'Aerosol de secado rápido para tableros y componentes eléctricos.', 'unit' => 'lata', 'tracking_type' => 'bulk',
                'attributes' => ['con-presentacion' => 'Aerosol 400 ml'], 'variants' => [$single('PSL-CON-003-STD', 'Aerosol 400 ml', 8)],
            ],
            [
                'category' => 'CON', 'code' => 'PSL-CON-004', 'name' => 'Grasa multipropósito',
                'description' => 'Lubricante para rodamientos y mecanismos de equipos auxiliares.', 'unit' => 'cartucho', 'tracking_type' => 'bulk',
                'attributes' => ['con-presentacion' => 'Cartucho 400 g'], 'variants' => [$single('PSL-CON-004-STD', 'Grado NLGI 2', 6)],
            ],
            [
                'category' => 'CON', 'code' => 'PSL-CON-005', 'name' => 'Tarjeta de bloqueo LOTO',
                'description' => 'Tarjeta de advertencia para aislamiento seguro de energías.', 'unit' => 'unidad', 'tracking_type' => 'bulk',
                'attributes' => ['con-presentacion' => 'PVC resistente a intemperie'], 'variants' => [$single('PSL-CON-005-STD', 'No operar', 30)],
            ],
            [
                'category' => 'HER', 'code' => 'PSL-HER-001', 'name' => 'Juego de llaves combinadas antichispa',
                'description' => 'Juego para trabajos mecánicos en zonas con riesgo de atmósfera inflamable.', 'unit' => 'juego', 'tracking_type' => 'bulk',
                'attributes' => ['her-medida' => '8 a 32 mm'], 'variants' => [$single('PSL-HER-001-STD', 'Aleación cobre-berilio', 1)],
            ],
            [
                'category' => 'HER', 'code' => 'PSL-HER-002', 'name' => 'Torquímetro',
                'description' => 'Herramienta de precisión para apriete controlado de pernos.', 'unit' => 'unidad', 'tracking_type' => 'bulk',
                'attributes' => ['her-medida' => '40 a 200 N·m / encastre 1/2"'], 'variants' => [$single('PSL-HER-002-STD', 'Encastre 1/2"', 1)],
            ],
            [
                'category' => 'HER', 'code' => 'PSL-HER-003', 'name' => 'Bomba manual de calibración neumática',
                'description' => 'Generador de presión para calibración de transmisores e indicadores.', 'unit' => 'unidad', 'tracking_type' => 'bulk',
                'attributes' => ['her-medida' => '-0,95 a 40 bar'], 'variants' => [$single('PSL-HER-003-STD', 'Kit con mangueras y adaptadores', 1)],
            ],
            [
                'category' => 'REP', 'code' => 'PSL-REP-001', 'name' => 'Elemento de filtro coalescente',
                'description' => 'Elemento de reemplazo para separación de aerosoles en gas de instrumentos.', 'unit' => 'unidad', 'tracking_type' => 'bulk',
                'attributes' => ['rep-especificacion' => 'Grado 0,01 μm / carcasa PSL-FC-01'], 'variants' => [$single('PSL-REP-001-STD', 'Elemento coalescente', 3)],
            ],
            [
                'category' => 'REP', 'code' => 'PSL-REP-002', 'name' => 'Fusible de control 2 A',
                'description' => 'Fusible de protección para circuitos de control e instrumentación.', 'unit' => 'unidad', 'tracking_type' => 'bulk',
                'attributes' => ['rep-especificacion' => '2 A / 250 VAC / 5 × 20 mm / acción rápida'], 'variants' => [$single('PSL-REP-002-STD', '5 × 20 mm', 12)],
            ],
            [
                'category' => 'REP', 'code' => 'PSL-REP-003', 'name' => 'Bobina para válvula solenoide',
                'description' => 'Bobina de reemplazo para actuadores de servicios auxiliares.', 'unit' => 'unidad', 'tracking_type' => 'bulk',
                'attributes' => ['rep-especificacion' => '24 VDC / conector DIN 43650-A'], 'variants' => [$single('PSL-REP-003-STD', '24 VDC', 2)],
            ],
        ];
    }

    private function seedWorkers(): void
    {
        $definitions = [
            ['code' => 'OPER-PLAN-01-RGD', 'document' => 'PSL-CI-0001', 'name' => 'Ana María', 'lastname' => 'Quispe Rojas', 'position' => 'Operadora de Planta', 'area' => 'Operaciones', 'email' => 'ana.quispe@personal-planta.test', 'start_date' => '2021-03-15'],
            ['code' => 'OPER-TURN-01-RGD', 'document' => 'PSL-CI-0002', 'name' => 'Luis Fernando', 'lastname' => 'Mamani Choque', 'position' => 'Supervisor de Turno', 'area' => 'Operaciones', 'email' => 'luis.mamani@personal-planta.test', 'start_date' => '2018-09-10'],
            ['code' => 'PROC-INGE-01-RGD', 'document' => 'PSL-CI-0003', 'name' => 'Carla Andrea', 'lastname' => 'Vargas Flores', 'position' => 'Ingeniera de Procesos', 'area' => 'Procesos', 'email' => 'carla.vargas@personal-planta.test', 'start_date' => '2020-01-20'],
            ['code' => 'INST-TECN-01-RGD', 'document' => 'PSL-CI-0004', 'name' => 'Diego Alejandro', 'lastname' => 'Flores Lima', 'position' => 'Técnico de Instrumentación', 'area' => 'Instrumentación', 'email' => 'diego.flores@personal-planta.test', 'start_date' => '2019-06-03'],
            ['code' => 'SEGU-HSE-01-RGD', 'document' => 'PSL-CI-0005', 'name' => 'Mariela Soledad', 'lastname' => 'Rojas Pérez', 'position' => 'Inspectora HSE', 'area' => 'Seguridad, Salud y Medio Ambiente', 'email' => 'mariela.rojas@personal-planta.test', 'start_date' => '2022-02-14'],
            ['code' => 'MTTO-MECA-01-RGD', 'document' => 'PSL-CI-0006', 'name' => 'José Miguel', 'lastname' => 'Condori Nina', 'position' => 'Técnico Mecánico', 'area' => 'Mantenimiento Mecánico', 'email' => 'jose.condori@personal-planta.test', 'start_date' => '2017-11-06'],
            ['code' => 'MTTO-ELEC-01-RGD', 'document' => 'PSL-CI-0007', 'name' => 'Sandra Paola', 'lastname' => 'López Cruz', 'position' => 'Técnica Electricista', 'area' => 'Mantenimiento Eléctrico', 'email' => 'sandra.lopez@personal-planta.test', 'start_date' => '2023-04-17'],
            ['code' => 'LABO-ANAL-01-RGD', 'document' => 'PSL-CI-0008', 'name' => 'Rodrigo Javier', 'lastname' => 'Arce Mendoza', 'position' => 'Analista de Laboratorio', 'area' => 'Laboratorio', 'email' => 'rodrigo.arce@personal-planta.test', 'start_date' => '2020-08-24'],
            ['code' => 'ALMA-ENCA-01-RGD', 'document' => 'PSL-CI-0009', 'name' => 'Paola Fernanda', 'lastname' => 'Gutiérrez Salas', 'position' => 'Encargada de Almacén', 'area' => 'Almacén', 'email' => 'paola.gutierrez@personal-planta.test', 'start_date' => '2019-10-07'],
            ['code' => 'OPER-DESP-01-RGD', 'document' => 'PSL-CI-0010', 'name' => 'Edwin Marcelo', 'lastname' => 'Ticona Ramos', 'position' => 'Operador de Despacho', 'area' => 'Operaciones', 'email' => 'edwin.ticona@personal-planta.test', 'start_date' => '2021-07-12'],
        ];

        foreach ($definitions as $definition) {
            $document = $definition['document'];
            $expected = $definition + [
                'phone' => null,
                'notes' => 'Registro sintético de la carga inicial para la planta separadora de líquidos.',
                'status' => true,
            ];
            $worker = Worker::query()->firstOrCreate(['document' => $document], $expected);
            $this->assertModelValues($worker, $expected, "trabajador {$document}");

            $this->workers[$definition['code']] = $worker;
            $this->workerDocuments[] = $document;
            $this->audit('TRABAJADORES', 'CARGA_INICIAL', $worker->id, "Trabajador de planta incorporado: {$worker->full_name}.", [
                'code' => $worker->code,
                'document' => $worker->document,
                'name' => $worker->full_name,
                'position' => $worker->position,
                'area' => $worker->area,
                'status' => true,
            ]);
        }
    }

    private function seedDispatchNotes(): void
    {
        $entries = [
            [
                'number' => 'ING101160726', 'counterparty' => 'TecnoSeg Industrial Bolivia S.R.L.',
                'reason' => 'Ingreso inicial de equipos serializados',
                'notes' => 'Equipos revisados por Almacén e Instrumentación. Certificados y accesorios archivados.',
                'items' => [
                    ['sku' => 'PSL-EQP-001-STD', 'quantity' => 1, 'serials' => ['MSA4XR-PSL-26001']],
                    ['sku' => 'PSL-EQP-002-STD', 'quantity' => 1, 'serials' => ['MOT-DP48-PSL-26001']],
                    ['sku' => 'PSL-EQP-003-STD', 'quantity' => 1, 'serials' => ['PAN-CF55-PSL-26001']],
                    ['sku' => 'PSL-EQP-004-STD', 'quantity' => 1, 'serials' => ['FLIR-E8XT-PSL-26001']],
                    ['sku' => 'PSL-EQP-005-STD', 'quantity' => 1, 'serials' => ['FLK-754-PSL-26001']],
                    ['sku' => 'PSL-EQP-006-STD', 'quantity' => 1, 'serials' => ['SAM-TABA4-PSL-26001']],
                ],
            ],
            [
                'number' => 'ING102160726', 'counterparty' => 'Seguridad Industrial del Oriente S.A.',
                'reason' => 'Ingreso inicial de EPP',
                'notes' => 'Dotación recibida con certificados de conformidad y control de tallas.',
                'items' => [
                    ['sku' => 'PSL-EPP-001-UNI', 'quantity' => 24],
                    ['sku' => 'PSL-EPP-002-UNI', 'quantity' => 36],
                    ['sku' => 'PSL-EPP-003-T07', 'quantity' => 20], ['sku' => 'PSL-EPP-003-T08', 'quantity' => 36],
                    ['sku' => 'PSL-EPP-003-T09', 'quantity' => 36], ['sku' => 'PSL-EPP-003-T10', 'quantity' => 18],
                    ['sku' => 'PSL-EPP-004-TM', 'quantity' => 12], ['sku' => 'PSL-EPP-004-TL', 'quantity' => 18],
                    ['sku' => 'PSL-EPP-004-TXL', 'quantity' => 12], ['sku' => 'PSL-EPP-004-T2XL', 'quantity' => 6],
                    ['sku' => 'PSL-EPP-005-T39', 'quantity' => 6], ['sku' => 'PSL-EPP-005-T40', 'quantity' => 6],
                    ['sku' => 'PSL-EPP-005-T41', 'quantity' => 6], ['sku' => 'PSL-EPP-005-T42', 'quantity' => 6],
                    ['sku' => 'PSL-EPP-005-T43', 'quantity' => 6], ['sku' => 'PSL-EPP-005-T44', 'quantity' => 6],
                    ['sku' => 'PSL-EPP-006-TM', 'quantity' => 12], ['sku' => 'PSL-EPP-006-TL', 'quantity' => 8],
                    ['sku' => 'PSL-EPP-007-UNI', 'quantity' => 24], ['sku' => 'PSL-EPP-008-UNI', 'quantity' => 48],
                ],
            ],
            [
                'number' => 'ING103160726', 'counterparty' => 'Suministros Técnicos Andinos Ltda.',
                'reason' => 'Ingreso inicial de consumibles y herramientas',
                'notes' => 'Material recibido contra orden de abastecimiento de arranque.',
                'items' => [
                    ['sku' => 'PSL-CON-001-STD', 'quantity' => 100], ['sku' => 'PSL-CON-002-STD', 'quantity' => 60],
                    ['sku' => 'PSL-CON-003-STD', 'quantity' => 24], ['sku' => 'PSL-CON-004-STD', 'quantity' => 18],
                    ['sku' => 'PSL-CON-005-STD', 'quantity' => 100], ['sku' => 'PSL-HER-001-STD', 'quantity' => 2],
                    ['sku' => 'PSL-HER-002-STD', 'quantity' => 2], ['sku' => 'PSL-HER-003-STD', 'quantity' => 1],
                ],
            ],
            [
                'number' => 'ING104160726', 'counterparty' => 'Control y Procesos Bolivia S.A.',
                'reason' => 'Ingreso inicial de repuestos críticos',
                'notes' => 'Repuestos identificados para gas de instrumentos y sistemas de control.',
                'items' => [
                    ['sku' => 'PSL-REP-001-STD', 'quantity' => 8],
                    ['sku' => 'PSL-REP-002-STD', 'quantity' => 40],
                    ['sku' => 'PSL-REP-003-STD', 'quantity' => 6],
                ],
            ],
        ];

        foreach ($entries as $definition) {
            $this->ensureDispatch('entry', $definition);
        }

        $this->ensureDispatch('exit', [
            'number' => 'SAL101160726',
            'counterparty' => 'Gestor Ambiental Autorizado EcoResiduos S.R.L.',
            'reason' => 'Baja controlada de material contaminado o no conforme',
            'notes' => 'Salida para disposición ambiental según acta interna PSL-HSE-BAJA-001.',
            'items' => [
                ['sku' => 'PSL-CON-001-STD', 'quantity' => 2, 'notes' => 'Paños usados en contención de hidrocarburos.'],
                ['sku' => 'PSL-CON-003-STD', 'quantity' => 3, 'notes' => 'Latas dañadas, segregadas como residuo peligroso.'],
                ['sku' => 'PSL-EPP-007-UNI', 'quantity' => 2, 'notes' => 'Pares de cartuchos con empaque comprometido.'],
            ],
        ]);
    }

    private function seedDeliveries(): void
    {
        $definitions = [
            [
                'number' => 'ENT101160726', 'worker' => 'OPER-PLAN-01-RGD', 'reason' => 'Dotación operativa y asignación de detector multigás',
                'items' => [
                    ['sku' => 'PSL-EQP-001-STD', 'quantity' => 1, 'serials' => ['MSA4XR-PSL-26001']],
                    ['sku' => 'PSL-EPP-001-UNI', 'quantity' => 1], ['sku' => 'PSL-EPP-002-UNI', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-003-T08', 'quantity' => 1], ['sku' => 'PSL-EPP-004-TL', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-005-T42', 'quantity' => 1], ['sku' => 'PSL-EPP-006-TM', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-008-UNI', 'quantity' => 1],
                ],
            ],
            [
                'number' => 'ENT102160726', 'worker' => 'OPER-TURN-01-RGD', 'reason' => 'Dotación de supervisor y asignación de radio ATEX',
                'items' => [
                    ['sku' => 'PSL-EQP-002-STD', 'quantity' => 1, 'serials' => ['MOT-DP48-PSL-26001']],
                    ['sku' => 'PSL-EPP-001-UNI', 'quantity' => 1], ['sku' => 'PSL-EPP-002-UNI', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-003-T09', 'quantity' => 1], ['sku' => 'PSL-EPP-004-TL', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-005-T43', 'quantity' => 1], ['sku' => 'PSL-EPP-008-UNI', 'quantity' => 1],
                ],
            ],
            [
                'number' => 'ENT103160726', 'worker' => 'PROC-INGE-01-RGD', 'reason' => 'Asignación de laptop para ingeniería de procesos',
                'items' => [
                    ['sku' => 'PSL-EQP-003-STD', 'quantity' => 1, 'serials' => ['PAN-CF55-PSL-26001']],
                    ['sku' => 'PSL-EPP-001-UNI', 'quantity' => 1], ['sku' => 'PSL-EPP-002-UNI', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-003-T07', 'quantity' => 1], ['sku' => 'PSL-EPP-004-TM', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-005-T39', 'quantity' => 1],
                ],
            ],
            [
                'number' => 'ENT104160726', 'worker' => 'INST-TECN-01-RGD', 'reason' => 'Asignación de cámara termográfica y dotación de instrumentación',
                'items' => [
                    ['sku' => 'PSL-EQP-004-STD', 'quantity' => 1, 'serials' => ['FLIR-E8XT-PSL-26001']],
                    ['sku' => 'PSL-EPP-001-UNI', 'quantity' => 1], ['sku' => 'PSL-EPP-002-UNI', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-003-T08', 'quantity' => 1], ['sku' => 'PSL-EPP-004-TM', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-005-T40', 'quantity' => 1], ['sku' => 'PSL-EPP-008-UNI', 'quantity' => 1],
                ],
            ],
            [
                'number' => 'ENT105160726', 'worker' => 'SEGU-HSE-01-RGD', 'reason' => 'Asignación de tablet para inspecciones HSE',
                'items' => [
                    ['sku' => 'PSL-EQP-006-STD', 'quantity' => 1, 'serials' => ['SAM-TABA4-PSL-26001']],
                    ['sku' => 'PSL-EPP-001-UNI', 'quantity' => 1], ['sku' => 'PSL-EPP-002-UNI', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-003-T07', 'quantity' => 1], ['sku' => 'PSL-EPP-004-TM', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-005-T39', 'quantity' => 1], ['sku' => 'PSL-EPP-006-TM', 'quantity' => 1],
                ],
            ],
            [
                'number' => 'ENT106160726', 'worker' => 'MTTO-MECA-01-RGD', 'reason' => 'Dotación de mantenimiento mecánico',
                'items' => [
                    ['sku' => 'PSL-EPP-001-UNI', 'quantity' => 1], ['sku' => 'PSL-EPP-002-UNI', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-003-T10', 'quantity' => 1], ['sku' => 'PSL-EPP-004-TXL', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-005-T44', 'quantity' => 1], ['sku' => 'PSL-EPP-006-TL', 'quantity' => 1],
                    ['sku' => 'PSL-EPP-008-UNI', 'quantity' => 1],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $this->ensureDelivery($definition);
        }
    }

    /** @param array<string, mixed> $definition */
    private function ensureDispatch(string $type, array $definition): DispatchNote
    {
        $items = $definition['items'];
        unset($definition['items']);
        $number = $definition['number'];
        $expected = $definition + [
            'type' => $type,
            'document_date' => self::LOAD_DATE,
            'status' => 'draft',
            'created_by' => $this->actor->id,
        ];
        $note = DispatchNote::query()->firstOrCreate(['number' => $number], $expected);

        $this->assertDocumentHeader($note, $expected, "remito {$number}");
        if ($note->status === 'draft') {
            $this->seedDispatchItems($note, $items);
            $note = $this->inventory->confirm($note, $this->actor->id);
        } else {
            if ($note->status !== 'confirmed') {
                throw new RuntimeException("El remito {$number} ya existe anulado; no se modificó.");
            }
            $this->assertDispatchItems($note, $items);
        }

        $this->audit('REMITOS', 'CARGA_INICIAL', $note->id, "Remito {$number} confirmado por la carga inicial.", [
            'number' => $number,
            'type' => $type,
            'document_date' => self::LOAD_DATE,
            'counterparty' => $note->counterparty,
            'reason' => $note->reason,
            'status' => $note->status,
            'items' => $items,
        ]);

        return $note;
    }

    /** @param list<array<string, mixed>> $items */
    private function seedDispatchItems(DispatchNote $note, array $items): void
    {
        $expectedVariantIds = collect($items)->map(fn (array $item): int => $this->variants[$item['sku']]->id)->all();
        if ($note->items()->whereNotIn('product_variant_id', $expectedVariantIds)->exists()) {
            throw new RuntimeException("El borrador {$note->number} contiene productos ajenos a la carga; no se modificó.");
        }

        foreach ($items as $definition) {
            $variant = $this->variants[$definition['sku']];
            $serialIds = collect($definition['serials'] ?? [])->map(fn (string $number): int => $this->serials[$number]->id)->all();
            $item = $note->items()->firstOrCreate(['product_variant_id' => $variant->id], [
                'quantity' => $definition['quantity'],
                'notes' => $definition['notes'] ?? null,
            ]);
            $this->assertDecimal((float) $item->quantity, (float) $definition['quantity'], "cantidad de {$definition['sku']} en {$note->number}");
            $currentSerials = $item->serializedItems()->pluck('serialized_items.id')->sort()->values()->all();
            sort($serialIds);
            if ($currentSerials === [] && $serialIds !== []) {
                $item->serializedItems()->sync($serialIds);
            } elseif ($currentSerials !== $serialIds) {
                throw new RuntimeException("Las series de {$definition['sku']} en {$note->number} no coinciden; no se modificó.");
            }
        }
    }

    /** @param list<array<string, mixed>> $items */
    private function assertDispatchItems(DispatchNote $note, array $items): void
    {
        $actual = $note->items()->with('serializedItems')->get()->keyBy('product_variant_id');
        if ($actual->count() !== count($items)) {
            throw new RuntimeException("El detalle confirmado de {$note->number} no coincide con la carga.");
        }
        foreach ($items as $definition) {
            $variant = $this->variants[$definition['sku']];
            $item = $actual->get($variant->id);
            if (! $item) {
                throw new RuntimeException("Falta {$definition['sku']} en el remito confirmado {$note->number}.");
            }
            $this->assertDecimal((float) $item->quantity, (float) $definition['quantity'], "cantidad confirmada de {$definition['sku']}");
            $expectedSerials = collect($definition['serials'] ?? [])->sort()->values()->all();
            $actualSerials = $item->serializedItems->pluck('serial_number')->sort()->values()->all();
            if ($actualSerials !== $expectedSerials) {
                throw new RuntimeException("Las series confirmadas de {$definition['sku']} no coinciden.");
            }
        }
    }

    /** @param array<string, mixed> $definition */
    private function ensureDelivery(array $definition): Delivery
    {
        $items = $definition['items'];
        $workerCode = $definition['worker'];
        unset($definition['items'], $definition['worker']);
        $number = $definition['number'];
        $expected = $definition + [
            'worker_id' => $this->workers[$workerCode]->id,
            'delivery_date' => self::LOAD_DATE,
            'notes' => 'Entrega operativa registrada durante la carga inicial de la planta.',
            'status' => 'draft',
            'created_by' => $this->actor->id,
        ];
        $delivery = Delivery::query()->firstOrCreate(['number' => $number], $expected);
        $this->assertDocumentHeader($delivery, $expected, "entrega {$number}");

        if ($delivery->status === 'draft') {
            $this->seedDeliveryItems($delivery, $items);
            $delivery = $this->inventory->confirmDelivery($delivery, $this->actor->id);
        } else {
            if ($delivery->status !== 'confirmed') {
                throw new RuntimeException("La entrega {$number} ya existe anulada; no se modificó.");
            }
            $this->assertDeliveryItems($delivery, $items);
        }

        $this->audit('ENTREGAS', 'CARGA_INICIAL', $delivery->id, "Entrega {$number} confirmada por la carga inicial.", [
            'number' => $number,
            'delivery_date' => self::LOAD_DATE,
            'worker' => [
                'code' => $this->workers[$workerCode]->code,
                'name' => $this->workers[$workerCode]->full_name,
                'area' => $this->workers[$workerCode]->area,
            ],
            'reason' => $delivery->reason,
            'status' => $delivery->status,
            'items' => $items,
        ]);

        return $delivery;
    }

    /** @param list<array<string, mixed>> $items */
    private function seedDeliveryItems(Delivery $delivery, array $items): void
    {
        $expectedVariantIds = collect($items)->map(fn (array $item): int => $this->variants[$item['sku']]->id)->all();
        if ($delivery->items()->whereNotIn('product_variant_id', $expectedVariantIds)->exists()) {
            throw new RuntimeException("El borrador {$delivery->number} contiene productos ajenos a la carga; no se modificó.");
        }

        foreach ($items as $definition) {
            $variant = $this->variants[$definition['sku']];
            $serialIds = collect($definition['serials'] ?? [])->map(fn (string $number): int => $this->serials[$number]->id)->all();
            $item = $delivery->items()->firstOrCreate(['product_variant_id' => $variant->id], [
                'quantity' => $definition['quantity'],
                'notes' => $definition['notes'] ?? null,
            ]);
            $this->assertDecimal((float) $item->quantity, (float) $definition['quantity'], "cantidad de {$definition['sku']} en {$delivery->number}");
            $currentSerials = $item->serializedItems()->pluck('serialized_items.id')->sort()->values()->all();
            sort($serialIds);
            if ($currentSerials === [] && $serialIds !== []) {
                $item->serializedItems()->sync($serialIds);
            } elseif ($currentSerials !== $serialIds) {
                throw new RuntimeException("Las series de {$definition['sku']} en {$delivery->number} no coinciden; no se modificó.");
            }
        }
    }

    /** @param list<array<string, mixed>> $items */
    private function assertDeliveryItems(Delivery $delivery, array $items): void
    {
        $actual = $delivery->items()->with('serializedItems')->get()->keyBy('product_variant_id');
        if ($actual->count() !== count($items)) {
            throw new RuntimeException("El detalle confirmado de {$delivery->number} no coincide con la carga.");
        }
        foreach ($items as $definition) {
            $variant = $this->variants[$definition['sku']];
            $item = $actual->get($variant->id);
            if (! $item) {
                throw new RuntimeException("Falta {$definition['sku']} en la entrega confirmada {$delivery->number}.");
            }
            $this->assertDecimal((float) $item->quantity, (float) $definition['quantity'], "cantidad confirmada de {$definition['sku']}");
            $expectedSerials = collect($definition['serials'] ?? [])->sort()->values()->all();
            $actualSerials = $item->serializedItems->pluck('serial_number')->sort()->values()->all();
            if ($actualSerials !== $expectedSerials) {
                throw new RuntimeException("Las series confirmadas de {$definition['sku']} no coinciden.");
            }
        }
    }

    /** @param array<string, mixed> $definition */
    private function ensureAttribute(string $categoryCode, array $definition, bool $required, int $position): ProductAttribute
    {
        $code = $definition['code'];
        $attribute = ProductAttribute::query()->firstOrCreate(['code' => $code], $definition + ['status' => true]);
        $this->assertModelValues($attribute, [
            'code' => $code,
            'type' => $definition['type'],
            'scope' => $definition['scope'],
            'status' => true,
        ], "atributo {$code}");
        $this->ensurePivot($this->categories[$categoryCode], $attribute, $required, $position);

        return $attribute;
    }

    private function ensurePivot(Category $category, ProductAttribute $attribute, bool $required, int $position, bool $strict = true): void
    {
        if ($attribute->scope === 'unit') {
            $otherUnit = $category->attributes()->where('scope', 'unit')->whereKeyNot($attribute->id)->first();
            if ($otherUnit) {
                throw new RuntimeException("La categoría {$category->code} ya tiene otro atributo unitario ({$otherUnit->code}).");
            }
        }

        $existing = $category->attributes()->whereKey($attribute->id)->first();
        if (! $existing) {
            $category->attributes()->attach($attribute->id, ['required' => $required, 'position' => $position]);

            return;
        }

        if ($strict && ((bool) $existing->pivot->required !== $required || (int) $existing->pivot->position !== $position)) {
            throw new RuntimeException("La configuración del atributo {$attribute->code} en {$category->code} es diferente; no se modificó.");
        }
    }

    private function ensureProductValue(Product $product, ProductAttribute $attribute, string $value): void
    {
        $record = ProductAttributeValue::query()->firstOrCreate([
            'product_id' => $product->id,
            'product_attribute_id' => $attribute->id,
        ], ['value' => $value]);
        if ((string) $record->value !== $value) {
            throw new RuntimeException("El valor {$attribute->code} de {$product->code} ya existe con otro contenido.");
        }
    }

    private function ensureVariantValue(ProductVariant $variant, ProductAttribute $attribute, string $value): void
    {
        $record = VariantAttributeValue::query()->firstOrCreate([
            'product_variant_id' => $variant->id,
            'product_attribute_id' => $attribute->id,
        ], ['value' => $value]);
        if ((string) $record->value !== $value) {
            throw new RuntimeException("El valor {$attribute->code} de {$variant->sku} ya existe con otro contenido.");
        }
    }

    private function ensureSerializedValue(SerializedItem $serial, ProductAttribute $attribute, string $value): void
    {
        $record = SerializedItemAttributeValue::query()->firstOrCreate([
            'serialized_item_id' => $serial->id,
            'product_attribute_id' => $attribute->id,
        ], ['value' => $value]);
        if ((string) $record->value !== $value) {
            throw new RuntimeException("El valor unitario de la serie {$serial->serial_number} ya existe con otro contenido.");
        }
    }

    /** @param array<string, mixed> $expected */
    private function assertModelValues(object $model, array $expected, string $label): void
    {
        foreach ($expected as $field => $value) {
            $actual = $model->{$field};
            if ($actual instanceof \DateTimeInterface) {
                $actual = $actual->format('Y-m-d');
            } elseif (is_bool($value)) {
                $actual = (bool) $actual;
            } elseif (is_int($value)) {
                $actual = (int) $actual;
            } elseif (is_float($value)) {
                $actual = (float) $actual;
            } elseif ($value !== null) {
                $actual = (string) $actual;
            }

            if ($actual !== $value) {
                throw new RuntimeException("El {$label} ya existe con un valor diferente en {$field}; no se modificó.");
            }
        }
    }

    /** @param array<string, mixed> $expected */
    private function assertDocumentHeader(object $document, array $expected, string $label): void
    {
        foreach ($expected as $field => $value) {
            if ($field === 'status') {
                continue;
            }
            $actual = $document->{$field};
            if ($actual instanceof \DateTimeInterface) {
                $actual = $actual->format('Y-m-d');
            } elseif (is_int($value)) {
                $actual = (int) $actual;
            } elseif ($value !== null) {
                $actual = (string) $actual;
            }
            if ($actual !== $value) {
                throw new RuntimeException("El {$label} ya existe con datos diferentes en {$field}; no se modificó.");
            }
        }
    }

    private function assertDecimal(float $actual, float $expected, string $label): void
    {
        if (abs($actual - $expected) > 0.0005) {
            throw new RuntimeException("No coincide la {$label}; no se modificó.");
        }
    }

    private function audit(string $module, string $action, ?int $modelId, string $description, array $newValues): void
    {
        Log::query()->create([
            'user_id' => $this->actor->id,
            'actor_login' => $this->actor->login,
            'modulo' => $module,
            'accion' => $action,
            'descripcion' => $description,
            'modelo_id' => $modelId,
            'valores_anteriores' => null,
            'valores_nuevos' => $newValues,
            'ip' => '127.0.0.1',
        ]);
    }

    private function auditFinalLoad(): void
    {
        $this->audit('SISTEMA', self::MARKER, null, 'Carga inicial coherente del almacén de la planta separadora de líquidos.', [
            'identifier' => self::MARKER,
            'load_date' => self::LOAD_DATE,
            'products' => $this->productCodes,
            'variants' => $this->variantSkus,
            'serialized_items' => $this->serialNumbers,
            'workers' => $this->workerDocuments,
            'inactive_users' => $this->userLogins,
            'dispatch_notes' => $this->dispatchNumbers,
            'deliveries' => $this->deliveryNumbers,
            'summary' => [
                'products' => 25,
                'variants' => 37,
                'serialized_items' => 6,
                'workers' => 10,
                'users' => 4,
                'dispatch_notes' => 5,
                'deliveries' => 6,
            ],
        ]);
    }

    private function assertCompletedManifest(bool $expectMarker = true): void
    {
        $productCodes = collect($this->productDefinitions())->pluck('code')->all();
        $variantSkus = collect($this->productDefinitions())
            ->flatMap(fn (array $product): array => collect($product['variants'])->pluck('sku')->all())
            ->all();
        $serialNumbers = collect($this->productDefinitions())
            ->flatMap(fn (array $product): array => collect($product['variants'])->flatMap(fn (array $variant): array => $variant['serials'] ?? [])->all())
            ->all();
        $workerDocuments = collect(range(1, 10))->map(fn (int $number): string => 'PSL-CI-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT))->all();
        $userLogins = ['jefe.almacen.psl', 'almacenero.psl', 'auditor.inventario.psl', 'hse.consulta.psl'];

        $checks = [
            'productos' => Product::query()->whereIn('code', $productCodes)->count() === 25,
            'variantes' => ProductVariant::query()->whereIn('sku', $variantSkus)->count() === 37,
            'series' => SerializedItem::query()->whereIn('serial_number', $serialNumbers)->count() === 6,
            'trabajadores' => Worker::query()->whereIn('document', $workerDocuments)->count() === 10,
            'usuarios inactivos' => User::query()->whereIn('login', $userLogins)->where('status', false)->count() === 4,
            'remitos confirmados' => DispatchNote::query()->whereIn('number', $this->dispatchNumbers)->where('status', 'confirmed')->count() === 5,
            'entregas confirmadas' => Delivery::query()->whereIn('number', $this->deliveryNumbers)->where('status', 'confirmed')->count() === 6,
            'series asignadas' => SerializedItem::query()->whereIn('serial_number', $serialNumbers)->where('status', 'assigned')->count() === 5,
            'serie disponible' => SerializedItem::query()->whereIn('serial_number', $serialNumbers)->where('status', 'available')->count() === 1,
        ];
        if ($expectMarker) {
            $checks['marcador único'] = Log::query()->where('accion', self::MARKER)->count() === 1;
        }

        $failed = collect($checks)->filter(fn (bool $passed): bool => ! $passed)->keys();
        if ($failed->isNotEmpty()) {
            throw new RuntimeException('La carga CARGA_PLANTA_V1 está marcada pero su manifiesto no coincide: '.$failed->implode(', ').'.');
        }

        $plantVariantIds = ProductVariant::query()->whereIn('sku', $variantSkus)->pluck('id');
        $negative = DB::table('inventory_movements')
            ->whereIn('product_variant_id', $plantVariantIds)
            ->select('product_variant_id')
            ->groupBy('product_variant_id')
            ->havingRaw('SUM(quantity) < -0.0005')
            ->exists();
        if ($negative) {
            throw new RuntimeException('La carga contiene existencias negativas; se canceló la operación.');
        }
    }

    private function withExclusiveLock(callable $callback): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $callback();

            return;
        }

        $result = DB::selectOne('SELECT GET_LOCK(?, 15) AS acquired', [self::LOCK_NAME]);
        if ((int) ($result->acquired ?? 0) !== 1) {
            throw new RuntimeException('No se pudo obtener el bloqueo exclusivo para CARGA_PLANTA_V1.');
        }

        try {
            $callback();
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [self::LOCK_NAME]);
        }
    }
}
