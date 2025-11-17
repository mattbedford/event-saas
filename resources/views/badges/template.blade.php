<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Badge</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            width: 288pt;  /* 4 inches */
            height: 216pt; /* 3 inches */
            padding: 20pt;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .badge {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .header {
            text-align: center;
            border-bottom: 2pt solid white;
            padding-bottom: 10pt;
        }
        .event-name {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1pt;
        }
        .event-date {
            font-size: 9pt;
            margin-top: 4pt;
            opacity: 0.9;
        }
        .attendee {
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .name {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 8pt;
        }
        .company {
            font-size: 12pt;
            opacity: 0.9;
        }
        .footer {
            text-align: center;
            font-size: 8pt;
            opacity: 0.8;
            border-top: 1pt solid white;
            padding-top: 8pt;
        }
    </style>
</head>
<body>
    <div class="badge">
        <div class="header">
            <div class="event-name">{{ $event->name }}</div>
            <div class="event-date">{{ $event->event_date->format('F j, Y') }}</div>
        </div>

        <div class="attendee">
            <div class="name">{{ $registration->full_name }}</div>
            @if($registration->company)
                <div class="company">{{ $registration->company }}</div>
            @endif
        </div>

        <div class="footer">
            Registration ID: #{{ $registration->id }}
        </div>
    </div>
</body>
</html>
