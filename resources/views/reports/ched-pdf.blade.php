<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin-top: 18px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 6px; vertical-align: top; }
        .label { color: #6b7280; width: 160px; }
        ul { margin: 0; padding-left: 18px; }
    </style>
</head>
<body>
    <h1>CHED Activity Report</h1>
    <p>{{ $club_name }}</p>

    <table>
        <tr><td class="label">Adviser</td><td>{{ $adviser_name }}</td></tr>
        <tr><td class="label">Activity Title</td><td>{{ $activity_title }}</td></tr>
        <tr><td class="label">Activity Type</td><td>{{ ucfirst(str_replace('_', ' ', $activity_type)) }}</td></tr>
        <tr><td class="label">Date</td><td>{{ $date }}</td></tr>
        <tr><td class="label">Time</td><td>{{ $time }}</td></tr>
        <tr><td class="label">Venue</td><td>{{ $venue }}</td></tr>
        <tr><td class="label">Officer-in-Charge</td><td>{{ $officer_in_charge }}</td></tr>
        <tr><td class="label">Number of Participants</td><td>{{ $participant_count }}</td></tr>
    </table>

    <h2>Objectives</h2>
    <p>{{ $objectives }}</p>

    <h2>Description</h2>
    <p>{{ $description }}</p>

    <h2>Participants</h2>
    <ul>
        @foreach($participants as $name)
            <li>{{ $name }}</li>
        @endforeach
    </ul>
</body>
</html>
