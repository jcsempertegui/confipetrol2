<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Log;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\VariantAttributeValue;
use App\Services\CodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RequestedProductCatalogSeeder extends Seeder
{
    private int $created = 0;

    private int $skipped = 0;

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->catalog() as $categoryCode => $products) {
                $category = Category::query()
                    ->with('attributes')
                    ->where('code', $categoryCode)
                    ->where('status', true)
                    ->first();

                if (! $category) {
                    throw new RuntimeException("La categoría activa {$categoryCode} no existe.");
                }

                foreach ($products as $definition) {
                    $this->createProduct($category, $definition);
                }
            }
        }, 3);

        $this->command?->info("Catálogo procesado: {$this->created} productos creados y {$this->skipped} existentes omitidos.");
    }

    private function createProduct(Category $category, array $definition): void
    {
        $exists = Product::query()
            ->where('category_id', $category->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($definition['name'])])
            ->exists();

        if ($exists) {
            $this->skipped++;

            return;
        }

        $lockedCategory = Category::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();
        $sequence = max(1, (int) $lockedCategory->next_product_number);
        $codes = app(CodeGenerator::class);

        do {
            $code = $codes->productCode($lockedCategory, $sequence++);
        } while (Product::query()->where('code', $code)->exists());

        $lockedCategory->update(['next_product_number' => $sequence]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'code' => $code,
            'name' => $definition['name'],
            'description' => $definition['description'],
            'unit' => $definition['unit'],
            'tracking_type' => $definition['tracking_type'],
            'status' => true,
        ]);

        $attributes = $category->attributes->keyBy('code');
        foreach ($definition['attributes'] as $attributeCode => $value) {
            $attribute = $attributes->get($attributeCode);
            if (! $attribute || $attribute->scope !== 'product') {
                throw new RuntimeException("El atributo de producto {$attributeCode} no pertenece a {$category->code}.");
            }

            ProductAttributeValue::query()->create([
                'product_id' => $product->id,
                'product_attribute_id' => $attribute->id,
                'value' => $value,
            ]);
        }

        foreach ($definition['variants'] as $index => $variantDefinition) {
            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'sku' => $codes->variantSku($product->code, $index + 1),
                'name' => $variantDefinition['name'],
                'minimum_stock' => $product->tracking_type === 'serialized' ? 0 : $variantDefinition['minimum_stock'],
                'status' => true,
            ]);

            foreach ($variantDefinition['attributes'] as $attributeCode => $value) {
                $attribute = $attributes->get($attributeCode);
                if (! $attribute || $attribute->scope !== 'variant') {
                    throw new RuntimeException("El atributo de variante {$attributeCode} no pertenece a {$category->code}.");
                }

                VariantAttributeValue::query()->create([
                    'product_variant_id' => $variant->id,
                    'product_attribute_id' => $attribute->id,
                    'value' => $value,
                ]);
            }
        }

        $actor = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'SUPER ADMIN'))->first();
        Log::query()->create([
            'user_id' => $actor?->id,
            'actor_login' => $actor?->login ?? 'sistema',
            'modulo' => 'PRODUCTOS',
            'accion' => 'CARGA_CATALOGO',
            'descripcion' => "Producto incorporado al catálogo solicitado: {$product->code} - {$product->name}.",
            'modelo_id' => $product->id,
            'valores_anteriores' => [],
            'valores_nuevos' => [
                'categoría' => $category->name,
                'código' => $product->code,
                'nombre' => $product->name,
                'unidad' => $product->unit,
                'seguimiento' => $product->tracking_type,
                'variantes' => $product->variants()->pluck('sku')->all(),
                'atributos' => $definition['attributes'],
            ],
            'ip' => '127.0.0.1',
        ]);

        $this->created++;
    }

    private function catalog(): array
    {
        $single = fn (string $name = 'Presentación estándar', float $minimum = 5, array $attributes = []): array => [
            'name' => $name,
            'minimum_stock' => $minimum,
            'attributes' => $attributes,
        ];
        $sizes = fn (array $values, float $minimum = 5): array => array_map(
            fn (string $size): array => $single("Talla {$size}", $minimum, ['epp-talla' => $size]),
            $values
        );
        $doses = fn (array $values, float $minimum = 10): array => array_map(
            fn (string $dose): array => $single($dose, $minimum, ['med-dosis' => $dose]),
            $values
        );
        $product = fn (
            string $name,
            string $description,
            string $unit,
            string $trackingType,
            array $attributes,
            array $variants
        ): array => compact('name', 'description', 'unit', 'attributes', 'variants') + [
            'tracking_type' => $trackingType,
        ];

        return [
            'ACT' => [
                $product('Computadora portátil industrial', 'Equipo portátil para operación y mantenimiento en planta.', 'unidad', 'serialized', ['act-marca' => 'Dell'], [$single('Unidad estándar', 0)]),
                $product('Computadora de escritorio', 'Estación de trabajo para oficinas técnicas y almacén.', 'unidad', 'serialized', ['act-marca' => 'Lenovo'], [$single('Unidad estándar', 0)]),
                $product('Pinza amperimétrica', 'Instrumento digital para mediciones eléctricas en mantenimiento.', 'unidad', 'serialized', ['act-marca' => 'Fluke'], [$single('Unidad estándar', 0)]),
                $product('Detector multigás portátil', 'Detector portátil para atmósferas peligrosas.', 'unidad', 'serialized', ['act-marca' => 'MSA'], [$single('Unidad estándar', 0)]),
                $product('Detector monogás H2S', 'Detector personal para presencia de sulfuro de hidrógeno.', 'unidad', 'serialized', ['act-marca' => 'Honeywell'], [$single('Unidad estándar', 0)]),
                $product('Radio portátil intrínsecamente segura', 'Radio de comunicación para áreas operativas clasificadas.', 'unidad', 'serialized', ['act-marca' => 'Motorola'], [$single('Unidad estándar', 0)]),
                $product('Cámara termográfica', 'Equipo de inspección térmica para mantenimiento predictivo.', 'unidad', 'serialized', ['act-marca' => 'FLIR'], [$single('Unidad estándar', 0)]),
                $product('Megóhmetro digital', 'Instrumento para comprobar resistencia de aislamiento.', 'unidad', 'serialized', ['act-marca' => 'Fluke'], [$single('Unidad estándar', 0)]),
                $product('Calibrador de procesos', 'Instrumento portátil para calibración de señales de proceso.', 'unidad', 'serialized', ['act-marca' => 'Fluke'], [$single('Unidad estándar', 0)]),
                $product('Tablet industrial', 'Dispositivo móvil reforzado para inspecciones de campo.', 'unidad', 'serialized', ['act-marca' => 'Samsung'], [$single('Unidad estándar', 0)]),
                $product('Impresora multifuncional', 'Equipo de impresión, copiado y digitalización para almacén.', 'unidad', 'serialized', ['act-marca' => 'HP'], [$single('Unidad estándar', 0)]),
            ],
            'CONSU' => [
                $product('Papel bond tamaño carta', 'Papel blanco tamaño carta de uso administrativo.', 'resma', 'bulk', [], [$single('Resma de 500 hojas', 10)]),
                $product('Papel bond tamaño oficio', 'Papel blanco tamaño oficio de uso administrativo.', 'resma', 'bulk', [], [$single('Resma de 500 hojas', 10)]),
                $product('Bolígrafo azul', 'Bolígrafo de tinta azul para oficina.', 'unidad', 'bulk', [], [$single('Unidad', 20)]),
                $product('Bolígrafo negro', 'Bolígrafo de tinta negra para oficina.', 'unidad', 'bulk', [], [$single('Unidad', 20)]),
                $product('Bolígrafo rojo', 'Bolígrafo de tinta roja para marcación documental.', 'unidad', 'bulk', [], [$single('Unidad', 10)]),
                $product('Marcador permanente negro', 'Marcador permanente de punta gruesa.', 'unidad', 'bulk', [], [$single('Unidad', 10)]),
                $product('Marcador permanente azul', 'Marcador permanente de punta gruesa.', 'unidad', 'bulk', [], [$single('Unidad', 10)]),
                $product('Marcador para pizarra', 'Marcador borrable para pizarra acrílica.', 'unidad', 'bulk', [], [$single('Unidad', 10)]),
                $product('Cuaderno espiral tamaño carta', 'Cuaderno de notas para actividades operativas.', 'unidad', 'bulk', [], [$single('100 hojas', 10)]),
                $product('Cinta adhesiva transparente', 'Cinta transparente para embalaje y oficina.', 'rollo', 'bulk', [], [$single('Rollo', 10)]),
                $product('Grapas 26/6', 'Grapas estándar para documentos.', 'caja', 'bulk', [], [$single('Caja', 5)]),
                $product('Tóner para impresora', 'Consumible de impresión para equipos administrativos.', 'unidad', 'bulk', [], [$single('Unidad', 2)]),
                $product('Corrector líquido', 'Corrector blanco de secado rápido.', 'unidad', 'bulk', [], [$single('Unidad', 5)]),
            ],
            'EPP' => [
                $product('Gafas de seguridad claras', 'Protección ocular transparente para uso general.', 'unidad', 'bulk', ['epp-marca' => '3M'], $sizes(['UNICA'], 10)),
                $product('Gafas de seguridad oscuras', 'Protección ocular oscura para trabajos en exteriores.', 'unidad', 'bulk', ['epp-marca' => '3M'], $sizes(['UNICA'], 10)),
                $product('Pantalón resistente al fuego', 'Pantalón RF para exposición a riesgos térmicos.', 'unidad', 'bulk', ['epp-marca' => 'Portwest'], $sizes(['S', 'M', 'L', 'XL', 'XXL', '3XL'])),
                $product('Botín de seguridad', 'Calzado de seguridad con puntera de protección.', 'par', 'bulk', ['epp-marca' => 'Delta Plus'], $sizes(['39', '40', '41', '42', '43', '44', '45', '46'])),
                $product('Bota industrial de PVC', 'Bota impermeable para áreas húmedas y limpieza industrial.', 'par', 'bulk', ['epp-marca' => 'Delta Plus'], $sizes(['39', '40', '41', '42', '43', '44', '45', '46'])),
                $product('Guante de nitrilo industrial', 'Guante resistente a aceites e hidrocarburos.', 'par', 'bulk', ['epp-marca' => 'Ansell'], $sizes(['7', '8', '9', '10'], 10)),
                $product('Guante de vaqueta', 'Guante de cuero para trabajo mecánico general.', 'par', 'bulk', ['epp-marca' => 'Steelpro'], $sizes(['7', '8', '9', '10'], 10)),
                $product('Casco de seguridad industrial', 'Casco con suspensión para protección de cabeza.', 'unidad', 'bulk', ['epp-marca' => '3M'], $sizes(['UNICA'], 10)),
                $product('Protector auditivo tipo copa', 'Protección auditiva ajustable para ambientes ruidosos.', 'unidad', 'bulk', ['epp-marca' => '3M'], $sizes(['UNICA'], 5)),
                $product('Tapón auditivo reutilizable', 'Protector auditivo reutilizable con cordón.', 'par', 'bulk', ['epp-marca' => '3M'], $sizes(['UNICA'], 20)),
                $product('Respirador reutilizable de media cara', 'Respirador compatible con filtros reemplazables.', 'unidad', 'bulk', ['epp-marca' => '3M'], $sizes(['M', 'L'], 5)),
                $product('Filtro para vapores orgánicos', 'Filtro reemplazable para respirador de media cara.', 'par', 'bulk', ['epp-marca' => '3M'], $sizes(['UNICA'], 10)),
                $product('Chaleco reflectivo', 'Prenda de alta visibilidad para áreas operativas.', 'unidad', 'bulk', ['epp-marca' => 'Steelpro'], $sizes(['M', 'L', 'XL', 'XXL'], 10)),
                $product('Arnés de cuerpo completo', 'Sistema personal para detención de caídas.', 'unidad', 'bulk', ['epp-marca' => '3M'], $sizes(['UNICA'], 3)),
                $product('Careta facial transparente', 'Protección facial contra salpicaduras y partículas.', 'unidad', 'bulk', ['epp-marca' => 'Steelpro'], $sizes(['UNICA'], 5)),
            ],
            'MED' => [
                $product('Paracetamol', 'Analgésico y antipirético en tabletas.', 'caja', 'bulk', ['med-vencimiento' => '2028-06-30', 'med-marca' => 'Genérico'], $doses(['500 mg', '1000 mg'])),
                $product('Diclofenaco sódico', 'Antiinflamatorio en tabletas; administrar únicamente bajo protocolo médico.', 'caja', 'bulk', ['med-vencimiento' => '2028-03-31', 'med-marca' => 'Genérico'], $doses(['50 mg', '75 mg', '100 mg'])),
                $product('Ibuprofeno', 'Antiinflamatorio y analgésico en tabletas.', 'caja', 'bulk', ['med-vencimiento' => '2028-09-30', 'med-marca' => 'Genérico'], $doses(['400 mg', '600 mg'])),
                $product('Omeprazol', 'Cápsulas de uso gastrointestinal.', 'caja', 'bulk', ['med-vencimiento' => '2028-12-31', 'med-marca' => 'Genérico'], $doses(['20 mg', '40 mg'])),
                $product('Loratadina', 'Antihistamínico en tabletas.', 'caja', 'bulk', ['med-vencimiento' => '2029-03-31', 'med-marca' => 'Genérico'], $doses(['10 mg'])),
                $product('Sales de rehidratación oral', 'Polvo para solución de rehidratación oral.', 'sobre', 'bulk', ['med-vencimiento' => '2027-12-31', 'med-marca' => 'Genérico'], $doses(['27,9 g'], 20)),
                $product('Suero fisiológico 0,9 %', 'Solución estéril de cloruro de sodio.', 'frasco', 'bulk', ['med-vencimiento' => '2028-08-31', 'med-marca' => 'Genérico'], $doses(['500 mL', '1000 mL'])),
                $product('Alcohol medicinal 70 %', 'Solución antiséptica de uso externo.', 'frasco', 'bulk', ['med-vencimiento' => '2029-12-31', 'med-marca' => 'Genérico'], $doses(['250 mL', '1000 mL'])),
                $product('Povidona yodada 10 %', 'Solución antiséptica de uso externo.', 'frasco', 'bulk', ['med-vencimiento' => '2028-10-31', 'med-marca' => 'Genérico'], $doses(['120 mL', '1000 mL'])),
                $product('Agua oxigenada 3 %', 'Solución de peróxido de hidrógeno de uso externo.', 'frasco', 'bulk', ['med-vencimiento' => '2027-10-31', 'med-marca' => 'Genérico'], $doses(['120 mL', '1000 mL'])),
                $product('Clorhexidina 2 %', 'Solución antiséptica para uso clínico según protocolo.', 'frasco', 'bulk', ['med-vencimiento' => '2028-11-30', 'med-marca' => 'Genérico'], $doses(['500 mL'])),
            ],
        ];
    }
}
