@extends('documents.layout')
@section('title', 'Entrega '.($delivery->number ?: '#'.$delivery->id))
@section('content')
<div class="head"><div><h1>Entrega a trabajador</h1><div class="muted">CONFIPETROL</div></div><div><strong>{{ $delivery->number ?: 'BORRADOR #'.$delivery->id }}</strong><br>{{ $delivery->delivery_date->format('d/m/Y') }}</div></div>
<div class="grid"><div><strong>Trabajador:</strong> {{ $delivery->worker->full_name }}</div><div><strong>Documento:</strong> {{ $delivery->worker->document }}</div><div><strong>Área:</strong> {{ $delivery->worker->area ?: '—' }}</div><div><strong>Estado:</strong> {{ ['draft'=>'Borrador','confirmed'=>'Confirmada','annulled'=>'Anulada'][$delivery->status] }}</div><div><strong>Motivo:</strong> {{ $delivery->reason ?: '—' }}</div><div><strong>Registrado por:</strong> {{ $delivery->creator?->login }}</div><div><strong>Confirmado por:</strong> {{ $delivery->confirmer?->login ?: '—' }}</div><div><strong>Fecha confirmación:</strong> {{ $delivery->confirmed_at?->format('d/m/Y H:i') ?: '—' }}</div></div>
@if($delivery->correctedFrom)<div class="box"><strong>Documento corregido:</strong> esta versión sustituye la entrega {{ $delivery->correctedFrom->number }}.</div>@endif
@if($delivery->correction)<div class="box"><strong>Documento inactivo:</strong> fue sustituido por {{ $delivery->correction->number ?: 'el borrador #'.$delivery->correction->id }}.</div>@endif
@if($delivery->notes)<div class="box"><strong>Observaciones:</strong> {{ $delivery->notes }}</div>@endif
<table><thead><tr><th>#</th><th>Producto</th><th>SKU / variante</th><th>Series asignadas</th><th class="right">Cantidad</th></tr></thead><tbody>@foreach($delivery->items as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ $item->variant->product->name }}</td><td>{{ $item->variant->sku }}<br><span class="muted">{{ $item->variant->name }}</span></td><td>{{ $item->serializedItems->pluck('serial_number')->join(', ') ?: '—' }}</td><td class="right">{{ \App\Support\Quantity::format($item->quantity) }}</td></tr>@endforeach</tbody></table>
@if($delivery->annul_reason)<div class="box" style="margin-top:16px"><strong>Motivo de anulación:</strong> {{ $delivery->annul_reason }}</div>@endif
<div class="signatures"><div class="line">Responsable de almacén</div><div class="line">Firma del trabajador</div></div>
@endsection
