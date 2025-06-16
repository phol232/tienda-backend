<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta Electrónica</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 0 0 4px 0; }
        .company, .client { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #888; padding: 4px 7px; }
        th { background: #e0e0e0; }
        .totals { margin-top: 10px; width: 320px; float: right; }
        .totals th, .totals td { border: none; padding: 3px 7px; text-align: right; }
        .hash { font-size: 10px; color: #555; margin-top: 30px; }
    </style>
</head>
<body>
<div class="header">
    <h2>BOLETA ELECTRÓNICA</h2>
    <div><b>{{ $boleta['company']['razonSocial'] ?? '-' }}</b></div>
    <div>RUC: {{ $boleta['company']['ruc'] ?? '-' }}</div>
    <div>{{ $boleta['company']['nombreComercial'] ?? '-' }}</div>
    <div>{{ $boleta['company']['address']['direccion'] ?? '-' }},
        {{ $boleta['company']['address']['distrito'] ?? '-' }},
        {{ $boleta['company']['address']['provincia'] ?? '-' }},
        {{ $boleta['company']['address']['departamento'] ?? '-' }}
    </div>
</div>

<div class="company">
    <strong>Serie:</strong> {{ $boleta['serie'] ?? '-' }}&nbsp;
    <strong>Correlativo:</strong> {{ $boleta['correlativo'] ?? '-' }}<br>
    <strong>Fecha Emisión:</strong>
    {{ \Carbon\Carbon::parse($boleta['fechaEmision'] ?? now())->format('d/m/Y H:i') }}
</div>

<div class="client">
    <strong>Cliente:</strong> {{ $boleta['client']['rznSocial'] ?? '-' }}<br>
    <strong>Documento:</strong> {{ $boleta['client']['tipoDoc'] ?? '-' }} - {{ $boleta['client']['numDoc'] ?? '-' }}<br>
    <strong>Dirección:</strong>
    {{ $boleta['client']['address']['direccion'] ?? '-' }},
    {{ $boleta['client']['address']['distrito'] ?? '' }},
    {{ $boleta['client']['address']['provincia'] ?? '' }},
    {{ $boleta['client']['address']['departamento'] ?? '' }}
</div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Producto</th>
        <th>Cant.</th>
        <th>Unidad</th>
        <th>P. Unitario</th>
        <th>Subtotal</th>
        <th>IGV</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($boleta['details'] as $i => $item)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $item['descripcion'] ?? '-' }}</td>
            <td style="text-align:center">{{ $item['cantidad'] ?? '-' }}</td>
            <td>{{ $item['unidad'] ?? '-' }}</td>
            <td style="text-align:right">{{ number_format($item['mtoValorUnitario'] ?? 0, 2) }}</td>
            <td style="text-align:right">{{ number_format($item['mtoValorVenta'] ?? 0, 2) }}</td>
            <td style="text-align:right">{{ number_format($item['igv'] ?? 0, 2) }}</td>
            <td style="text-align:right">{{ number_format(($item['mtoValorVenta'] ?? 0) + ($item['igv'] ?? 0), 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <th style="text-align:left">Op. Gravada:</th>
        <td>S/ {{ number_format($boleta['mtoOperGravadas'] ?? 0, 2) }}</td>
    </tr>
    <tr>
        <th style="text-align:left">IGV (18%):</th>
        <td>S/ {{ number_format($boleta['mtoIGV'] ?? 0, 2) }}</td>
    </tr>
    <tr>
        <th style="text-align:left">Total:</th>
        <td><strong>S/ {{ number_format($boleta['mtoImpVenta'] ?? $boleta['subTotal'] ?? $boleta['total'] ?? 0, 2) }}</strong></td>
    </tr>
</table>

<div style="clear: both"></div>
<br>
<div>
    <strong>SON: </strong>
    {{ $boleta['legends'][0]['value'] ?? '-' }}
</div>
<br>
<div class="hash">
    <strong>HASH (Referencia):</strong> {{ $boleta['hash'] ?? '-----' }}
</div>
</body>
</html>
