<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Log Digest Alert</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 20px; }
        .container { max-width: 680px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background-color: #0f172a; color: #ffffff; padding: 20px 24px; }
        .header h2 { margin: 0; font-size: 18px; font-weight: 700; }
        .badge { display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 600; border-radius: 4px; text-transform: uppercase; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-info { background-color: #dbeafe; color: #1e40af; }
        .content { padding: 24px; }
        .summary { background-color: #f1f5f9; padding: 12px 16px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; }
        .log-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
        .log-table th { text-align: left; padding: 10px 12px; background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 11px; text-transform: uppercase; }
        .log-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .log-msg { font-family: monospace; font-size: 12px; color: #0f172a; word-break: break-all; }
        .log-time { font-family: monospace; font-size: 11px; color: #64748b; }
        .footer { text-align: center; padding: 16px; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Laravel Log Viewer — Digest Alert</h2>
        </div>
        <div class="content">
            <div class="summary">
                <strong>Environment:</strong> {{ strtoupper($environment) }} |
                <strong>Timeframe:</strong> Last {{ $intervalMinutes }} minutes |
                <strong>Total Entries:</strong> {{ count($entries) }}
            </div>

            <p style="font-size: 14px; margin-bottom: 16px;">The following logs occurred in the last {{ $intervalMinutes }} minutes:</p>

            <table class="log-table">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>Timestamp</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        <tr>
                            <td>
                                @php
                                    $lvl = strtoupper($entry['level']);
                                    $badgeClass = match($lvl) {
                                        'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'badge-danger',
                                        'WARNING' => 'badge-warning',
                                        default => 'badge-info',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $entry['level'] }}</span>
                            </td>
                            <td class="log-time">{{ $entry['timestamp'] }}</td>
                            <td class="log-msg">
                                {{ $entry['message'] }}
                                @if(!empty($entry['context']))
                                    <pre style="margin: 6px 0 0 0; padding: 6px; background: #0f172a; color: #34d399; font-size: 11px; border-radius: 4px; overflow-x: auto;">{{ is_array($entry['context']) ? json_encode($entry['context'], JSON_PRETTY_PRINT) : $entry['context'] }}</pre>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="footer">
            Sent automatically by <a href="https://www.solvercircle.com">SolverCircle</a> Laravel Log Viewer.
        </div>
    </div>
</body>
</html>
