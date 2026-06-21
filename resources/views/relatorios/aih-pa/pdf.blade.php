<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Relatório Procedimentos AIH' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1   { font-size: 14px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #e2e8f0; font-weight: bold; }
        tr:nth-child(even) { background: #f8fafc; }
        .totals { margin-top: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Relatório Procedimentos AIH' }}</h1>

    @if (!empty($data) && $data->isNotEmpty())
        @php $headers = array_keys((array) $data->first()); @endphp
        <table>
            <thead>
                <tr>
                    @foreach ($headers as $h)
                        <th>{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $row)
                    <tr>
                        @foreach ((array) $row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (!empty($totals))
            <div class="totals">
                @foreach ($totals as $label => $value)
                    <p>{{ $label }}: {{ $value }}</p>
                @endforeach
            </div>
        @endif
    @else
        <p>Nenhum dado para exibir.</p>
    @endif
</body>
</html>
