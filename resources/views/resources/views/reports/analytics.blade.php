<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 24px; margin-bottom: 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .meta { font-size: 11px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background-color: #2d3748; color: white; padding: 6px 8px; text-align: left; font-size: 11px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        tr:nth-child(even) td { background-color: #f7fafc; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-low      { background: #c6f6d5; color: #276749; }
        .badge-medium   { background: #fefcbf; color: #744210; }
        .badge-high     { background: #fed7d7; color: #9b2335; }
        .badge-critical { background: #feb2b2; color: #742a2a; }
        .summary-box { background: #f7fafc; border: 1px solid #e2e8f0; padding: 10px 14px; margin-bottom: 16px; border-radius: 4px; }
        .summary-box p { margin: 4px 0; }
        .footer { margin-top: 32px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>

    <h1>Laporan Analytics Enrollment</h1>
    <div class="meta">
        Periode: <strong>{{ $session->periode_name }}</strong> &nbsp;|&nbsp;
        {{ \Carbon\Carbon::parse($session->start_date)->format('d M Y') }} —
        {{ \Carbon\Carbon::parse($session->end_date)->format('d M Y') }}
        <br>
        Diekspor pada: {{ now()->format('d M Y, H:i') }} WIB
    </div>

    {{-- Overview --}}
    <div class="summary-box">
        <p>Total mahasiswa masuk: <strong>{{ $overview['total_mahasiswa'] }}</strong></p>
        <p>Total program studi: <strong>{{ $overview['total_programs'] }}</strong></p>
        <p>Total dropout: <strong>{{ $overview['total_dropout'] }}</strong></p>
    </div>

    {{-- Funnel --}}
    <h2>Analisis Funnel Enrollment</h2>
    <div class="summary-box">
        <p>Total mulai: <strong>{{ $funnel['total_start'] }}</strong> mahasiswa</p>
        <p>Total selesai: <strong>{{ $funnel['total_finish'] }}</strong> mahasiswa</p>
        <p>Overall conversion: <strong>{{ $funnel['overall_conversion'] }}%</strong></p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Tahap</th>
                <th>Total Prospects</th>
                <th>Conversion Rate</th>
                <th>Dropoff Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($funnel['stages'] as $stage)
            <tr>
                <td>{{ $stage['stage'] }}</td>
                <td>{{ $stage['total_prospects'] }}</td>
                <td>{{ $stage['conversion_rate'] }}%</td>
                <td>{{ $stage['dropoff_rate'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Attrition --}}
    <h2>Analisis Attrition</h2>
    <div class="summary-box">
        <p>Rata-rata attrition: <strong>{{ $attrition['avg_attrition'] }}%</strong></p>
        <p>Stage paling berisiko: <strong>{{ $attrition['highest_risk']['stage'] ?? '-' }}</strong>
            ({{ $attrition['highest_risk']['attrition_rate'] ?? 0 }}%)</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Tahap</th>
                <th>Attrition Rate</th>
                <th>Risk Level</th>
                <th>Alasan Utama</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attrition['stages'] as $stage)
            <tr>
                <td>{{ $stage['stage'] }}</td>
                <td>{{ $stage['attrition_rate'] }}%</td>
                <td>
                    <span class="badge badge-{{ $stage['risk_level'] }}">
                        {{ strtoupper($stage['risk_level']) }}
                    </span>
                </td>
                <td>{{ $stage['dropoff_reason'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Retention --}}
    <h2>Analisis Retention</h2>
    <div class="summary-box">
        <p>Retention rate: <strong>{{ $retention['retention_rate'] }}%</strong>
            (target: 70%)</p>
        <p>Mahasiswa aktif: <strong>{{ $retention['active_students'] }}</strong></p>
        <p>Mahasiswa tidak aktif: <strong>{{ $retention['inactive_students'] }}</strong></p>
    </div>

    {{-- Insights --}}
    <h2>Rekomendasi & Insight</h2>
    @forelse($insights as $insight)
    <div class="summary-box">
        <p><strong>[{{ strtoupper($insight['type']) }}]</strong> {{ $insight['description'] }}</p>
        @if($insight['recommendation'])
        <p>Rekomendasi: {{ $insight['recommendation'] }}</p>
        @endif
    </div>
    @empty
    <p>Tidak ada insight untuk periode ini.</p>
    @endforelse

    <div class="footer">
        Laporan ini digenerate otomatis oleh sistem Analytics Enrollment &mdash; {{ now()->format('Y') }}
    </div>

</body>
</html>