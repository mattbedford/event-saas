<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registration Confirmation</title>
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
            background: #667eea;
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
        .detail {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid #667eea;
        }
        .detail strong {
            display: block;
            margin-bottom: 5px;
            color: #667eea;
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
        <h1>Registration Confirmed!</h1>
    </div>

    <div class="content">
        <p>Hi {{ $registration->name }},</p>

        <p>Thank you for registering for <strong>{{ $event->name }}</strong>!</p>

        <div class="detail">
            <strong>Event Details</strong>
            {{ $event->name }}<br>
            {{ $event->event_date->format('l, F j, Y \a\t g:i A') }}
        </div>

        <div class="detail">
            <strong>Your Information</strong>
            Name: {{ $registration->full_name }}<br>
            Email: {{ $registration->email }}<br>
            @if($registration->company)
            Company: {{ $registration->company }}<br>
            @endif
            Registration ID: #{{ $registration->id }}
        </div>

        <div class="detail">
            <strong>Payment</strong>
            Amount Paid: ${{ number_format($registration->paid_amount, 2) }}<br>
            @if($registration->discount_amount > 0)
            Discount Applied: ${{ number_format($registration->discount_amount, 2) }} ({{ $registration->coupon_code }})<br>
            @endif
            Status: {{ ucfirst($registration->payment_status) }}
        </div>

        @if($registration->badge_generated)
        <p><strong>Your event badge is attached to this email.</strong> Please bring it with you to the event!</p>
        @endif

        <p>We look forward to seeing you at the event!</p>

        <p>If you have any questions, please don't hesitate to reach out.</p>

        <p>Best regards,<br>
        The {{ $event->name }} Team</p>
    </div>

    <div class="footer">
        <p>This is an automated confirmation email. Please do not reply.</p>
    </div>
</body>
</html>
