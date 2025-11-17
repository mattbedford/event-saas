<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #764ba2;
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px 20px;
            border-radius: 0 0 8px 8px;
        }
        .event-details {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            border-left: 4px solid #764ba2;
            font-size: 18px;
            text-align: center;
        }
        .event-details .date {
            font-size: 24px;
            font-weight: bold;
            color: #764ba2;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Event Reminder</h1>
    </div>

    <div class="content">
        <p>Hi {{ $registration->name }},</p>

        <p>This is a friendly reminder that <strong>{{ $event->name }}</strong> is coming up soon!</p>

        <div class="event-details">
            <div>{{ $event->name }}</div>
            <div class="date">{{ $event->event_date->format('l, F j, Y') }}</div>
            <div>{{ $event->event_date->format('g:i A') }}</div>
        </div>

        <p><strong>Important Reminders:</strong></p>
        <ul>
            <li>Don't forget to bring your event badge (attached to your confirmation email)</li>
            <li>Please arrive 15 minutes early for registration</li>
            <li>Your registration ID is: #{{ $registration->id }}</li>
        </ul>

        <p>We're looking forward to seeing you there!</p>

        <p>Best regards,<br>
        The {{ $event->name }} Team</p>
    </div>

    <div class="footer">
        <p>This is an automated reminder email. Please do not reply.</p>
    </div>
</body>
</html>
