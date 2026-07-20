<div class="table-responsive">
    <table class="table table-sm mb-0 audit-change-table">
        <thead>
            <tr><th>Campo modificado</th><th>Valor anterior</th><th>Valor nuevo</th></tr>
        </thead>
        <tbody>
            @foreach($changes as $change)
                <tr>
                    <td><strong>{{ ucfirst(str_replace('_', ' ', $change['field'])) }}</strong></td>
                    <td><span class="audit-old-value">{{ filled($change['before']) ? $change['before'] : 'Vacío' }}</span></td>
                    <td><span class="audit-new-value">{{ filled($change['after']) ? $change['after'] : 'Vacío' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
