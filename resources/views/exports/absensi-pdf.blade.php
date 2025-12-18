<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Absensi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0ea5e9;
        }

        .header h1 {
            color: #0f172a;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .header p {
            color: #64748b;
            font-size: 10px;
        }

        .filter-info {
            background: #f1f5f9;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .filter-info p {
            margin: 3px 0;
            color: #334155;
        }

        .filter-info strong {
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead {
            background: #0ea5e9;
            color: white;
        }

        thead th {
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #0284c7;
        }

        tbody td {
            padding: 6px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            color: #334155;
        }

        tbody tr:nth-child(odd) {
            background-color: #f8fafc;
        }

        tbody tr:nth-child(even) {
            background-color: #eef2ff;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Absensi</h1>
        <p>HCIS Holding Perkebunan</p>
    </div>

    <div class="filter-info">
        <p><strong>PTPN:</strong> {{ $filters['ptpn'] }}</p>
        <p><strong>PSA:</strong> {{ $filters['psa'] }}</p>
        <p><strong>Regional:</strong> {{ $filters['regional'] }}</p>
        <p><strong>Periode:</strong> {{ $filters['dari_tanggal'] }} s/d {{ $filters['sampai_tanggal'] }}</p>
        <p><strong>Total Records:</strong> {{ count($data) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($formattedHeaders as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    @foreach($headers as $header)
                        <td>{{ $row[$header] ?? '-' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ date('d/m/Y H:i:s') }}</p>
    </div>
</body>

</html>
