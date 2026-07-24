@if($attribute->type === 'select')
@php($usedOptions = isset($variantIndex) ? collect($variants)->except($variantIndex)->map(fn($row) => data_get($row, 'values.'.$attribute->id))->filter(fn($value) => filled($value))->map(fn($value) => mb_strtoupper(trim((string) $value)))->all() : [])
<select wire:model.live="{{ $model }}" class="form-select @if($errors->has($model)) is-invalid @endif"><option value="">Seleccione...</option>@foreach($attribute->options ?? [] as $option)@php($optionUsed = in_array(mb_strtoupper(trim((string) $option)), $usedOptions, true))<option value="{{ $option }}" @disabled($optionUsed)>{{ $option }}{{ $optionUsed ? ' — ya seleccionada' : '' }}</option>@endforeach</select>
@elseif($attribute->type === 'boolean')
<select wire:model.live="{{ $model }}" class="form-select @if($errors->has($model)) is-invalid @endif"><option value="">Seleccione...</option><option value="1">Sí</option><option value="0">No</option></select>
@else
<input wire:model.blur="{{ $model }}" type="{{ $attribute->type === 'number' ? 'number' : ($attribute->type === 'date' ? 'date' : 'text') }}" class="form-control @if($errors->has($model)) is-invalid @endif">
@endif
