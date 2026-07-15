@if($attribute->type === 'select')
<select wire:model="{{ $model }}" class="form-select"><option value="">Seleccione...</option>@foreach($attribute->options ?? [] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select>
@elseif($attribute->type === 'boolean')
<select wire:model="{{ $model }}" class="form-select"><option value="">Seleccione...</option><option value="1">Sí</option><option value="0">No</option></select>
@else
<input wire:model="{{ $model }}" type="{{ $attribute->type === 'number' ? 'number' : ($attribute->type === 'date' ? 'date' : 'text') }}" class="form-control">
@endif
