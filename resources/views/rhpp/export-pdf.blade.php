<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RHPP Export - {{ $period->id }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
            font-size: 12px;
        }
        .period-info {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .period-info p {
            margin: 5px 0;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .metric-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }
        .metric-card .label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .metric-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .metric-card .currency {
            font-size: 14px;
            color: #27ae60;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #bdc3c7;
            font-size: 11px;
            color: #7f8c8d;
            text-align: center;
        }
        .highlight-positive {
            color: #27ae60;
            font-weight: bold;
        }
        .highlight-negative {
            color: #e74c3c;
            font-weight: bold;
        }
        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>DRAFT RHPP Export</h1>
            <p>Laporan Performa Hasil Produksi (RHPP)</p>
        </div>

        <!-- Period Information -->
        <div class="period-info">
            <p><strong>Period ID:</strong> {{ $period->id }}</p>
            <p><strong>Coop:</strong> {{ $period->floor?->coop?->name ?? 'N/A' }}</p>
            <p><strong>Farm:</strong> {{ $period->floor?->coop?->farm?->name ?? 'N/A' }}</p>
            <p><strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="label">Gross Revenue</div>
                <div class="value currency">Rp {{ number_format($metrics['gross_revenue'], 0, ',', '.') }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Total Cost</div>
                <div class="value currency">Rp {{ number_format($metrics['total_cost'], 0, ',', '.') }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Net Profit</div>
                <div class="value {{ $metrics['net_profit'] >= 0 ? 'highlight-positive' : 'highlight-negative' }}">
                    Rp {{ number_format($metrics['net_profit'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Technical Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="label">FCR</div>
                <div class="value">{{ number_format($metrics['fcr'], 4) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">IP (Index Performance)</div>
                <div class="value">{{ number_format($metrics['ip'], 2) }}</div>
            </div>
            <div class="metric-card">
                <div class="label">Profitability Margin</div>
                <div class="value">{{ number_format($metrics['profitability_margin'], 2) }}%</div>
            </div>
        </div>

        <!-- Detailed Summary -->
        <h2>Financial Summary</h2>
        <table>
            <tr>
                <th>Category</th>
                <th style="text-align: right;">Amount</th>
            </tr>
            <tr>
                <td>Gross Revenue</td>
                <td style="text-align: right;">Rp {{ number_format($metrics['gross_revenue'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Cost</td>
                <td style="text-align: right;">Rp {{ number_format($metrics['total_cost'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Net Profit</strong></td>
                <td style="text-align: right; {{ $metrics['net_profit'] >= 0 ? 'color: #27ae60;' : 'color: #e74c3c;' }} font-weight: bold;">
                    Rp {{ number_format($metrics['net_profit'], 0, ',', '.') }}
                </td>
            </tr>
        </table>

        <!-- Production Metrics -->
        <h2>Production Metrics</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th style="text-align: right;">Value</th>
            </tr>
            <tr>
                <td>Total Harvested Weight</td>
                <td style="text-align: right;">{{ number_format($metrics['total_harvested_weight'], 2) }} kg</td>
            </tr>
            <tr>
                <td>Feed Consumption</td>
                <td style="text-align: right;">{{ number_format($metrics['feed_consumption'], 2) }} kg</td>
            </tr>
            <tr>
                <td>FCR (Feed Conversion Ratio)</td>
                <td style="text-align: right;">{{ number_format($metrics['fcr'], 4) }}</td>
            </tr>
            <tr>
                <td>IP (Index Performance)</td>
                <td style="text-align: right;">{{ number_format($metrics['ip'], 2) }}</td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>Dokumen ini adalah DRAFT dan belum final. Silakan verifikasi dengan PIC atau manager sebelum publikasi.</p>
            <p>Generated at {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
