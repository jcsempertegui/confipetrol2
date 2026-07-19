<?php

namespace App\Services;

use App\Models\Category;
use DateTimeInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CodeGenerator
{
    public function siteCode(): string
    {
        $code = Str::upper(trim((string) config('warehouse.site_code', 'RGD')));

        if (! preg_match('/^[A-Z0-9]{2,10}$/', $code)) {
            throw new InvalidArgumentException('El código configurado para la sede no es válido.');
        }

        return $code;
    }

    public function productCode(Category $category, int $sequence): string
    {
        if ($sequence < 1 || $sequence > 999999) {
            throw new InvalidArgumentException('La secuencia de producto no es válida.');
        }

        return Str::upper($category->code).'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT).'-'.$this->siteCode();
    }

    public function variantSku(string $productCode, int $sequence): string
    {
        if ($sequence < 1 || $sequence > 999999) {
            throw new InvalidArgumentException('La secuencia de variante no es válida.');
        }

        return $this->withoutSiteSuffix($productCode).'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'-'.$this->siteCode();
    }

    public function workerPrefix(string $position, ?string $area = null): string
    {
        $positionTokens = $this->codeTokens($position);
        if ($positionTokens === []) {
            throw new InvalidArgumentException('El cargo es obligatorio para generar el código del trabajador.');
        }

        $areaTokens = $this->codeTokens((string) $area);
        $familyTokens = $areaTokens === [] ? $positionTokens : $areaTokens;
        $primary = collect($familyTokens)->contains(fn (string $token) => in_array($token, ['MANTENIMIENTO', 'MTTO'], true))
            ? 'MTTO'
            : mb_substr($familyTokens[0], 0, 4);

        $specialtyTokens = collect($positionTokens)
            ->reject(fn (string $token) => in_array($token, ['MANTENIMIENTO', 'MTTO'], true))
            ->values();
        $specialty = mb_substr((string) ($specialtyTokens->last() ?: 'GRAL'), 0, 4);
        if ($specialty === $primary && $specialtyTokens->count() > 1) {
            $specialty = mb_substr((string) $specialtyTokens->first(), 0, 4);
        }

        return $primary.'-'.$specialty;
    }

    public function workerCode(string $position, ?string $area, int $sequence): string
    {
        if ($sequence < 1 || $sequence > 999999) {
            throw new InvalidArgumentException('La secuencia del trabajador no es válida.');
        }

        $code = $this->workerPrefix($position, $area).'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'-'.$this->siteCode();
        if (mb_strlen($code) > 20) {
            throw new InvalidArgumentException('El código generado para el trabajador excede los 20 caracteres permitidos.');
        }

        return $code;
    }

    public function workerSequenceKey(string $position, ?string $area = null): string
    {
        return 'worker_'.Str::lower(str_replace('-', '_', $this->workerPrefix($position, $area))).'_'.Str::lower($this->siteCode());
    }

    public function normalizeSiteSuffix(string $code): string
    {
        $code = Str::upper(trim($code));
        if ($code === '') {
            return '';
        }

        if ($this->siteCode() === 'RGD' && Str::endsWith($code, '-RDG')) {
            $code = Str::beforeLast($code, '-RDG');
        }

        return Str::endsWith($code, '-'.$this->siteCode()) ? $code : $code.'-'.$this->siteCode();
    }

    public function documentCode(string $prefix, int $sequence, DateTimeInterface $date): string
    {
        if ($sequence < 1 || $sequence > 999) {
            throw new InvalidArgumentException('La secuencia documental debe estar entre 1 y 999.');
        }

        return Str::upper($prefix).'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'-'.$date->format('dmY').'-'.$this->siteCode();
    }

    public function sequenceKey(string $document, DateTimeInterface $date): string
    {
        return Str::lower($document).'_'.Str::lower($this->siteCode()).'_'.$date->format('Ymd');
    }

    private function withoutSiteSuffix(string $code): string
    {
        $normalized = Str::upper(trim($code));
        $suffix = '-'.$this->siteCode();

        return Str::endsWith($normalized, $suffix)
            ? Str::beforeLast($normalized, $suffix)
            : $normalized;
    }

    /** @return list<string> */
    private function codeTokens(string $value): array
    {
        $normalized = Str::upper(Str::ascii(trim($value)));
        $ignored = ['DE', 'DEL', 'LA', 'LAS', 'EL', 'LOS', 'Y', 'E', 'EN', 'PARA', 'POR'];

        return collect(preg_split('/[^A-Z0-9]+/', $normalized) ?: [])
            ->filter()
            ->reject(fn (string $token) => in_array($token, $ignored, true))
            ->values()
            ->all();
    }
}
