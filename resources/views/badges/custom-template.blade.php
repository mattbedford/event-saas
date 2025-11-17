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
            width: {{ $template['width'] }}px;
            height: {{ $template['height'] }}px;
            position: relative;
            overflow: hidden;
            @if(!empty($template['background_pdf']))
                background: url('{{ Storage::disk('public')->path($template['background_pdf']) }}') no-repeat center center;
                background-size: cover;
            @else
                background: {{ $template['background_color'] ?? '#667eea' }};
            @endif
        }
        .field {
            position: absolute;
            white-space: nowrap;
        }
        .logo {
            position: absolute;
        }
    </style>
</head>
<body>
    {{-- Logo --}}
    @if(!empty($template['logo']))
        <img src="{{ Storage::disk('public')->path($template['logo']) }}"
             alt="Logo"
             class="logo"
             style="
                left: {{ $template['logo_position']['x'] ?? 50 }}px;
                top: {{ $template['logo_position']['y'] ?? 20 }}px;
                width: {{ $template['logo_size']['width'] ?? 100 }}px;
                height: {{ $template['logo_size']['height'] ?? 50 }}px;
             ">
    @endif

    {{-- Dynamic Fields --}}
    @foreach($template['fields'] as $field)
        @php
            // Get the value from the registration based on field name
            $value = match($field['name']) {
                'full_name' => $registration->full_name,
                'name' => $registration->name,
                'surname' => $registration->surname,
                'company' => $registration->company,
                'email' => $registration->email,
                'event_name' => $event->name,
                'event_date' => $event->event_date->format('F j, Y'),
                'registration_id' => '#' . $registration->id,
                default => $field['label'],
            };
        @endphp

        @if($value)
            <div class="field" style="
                left: {{ $field['position']['x'] ?? 0 }}px;
                top: {{ $field['position']['y'] ?? 0 }}px;
                font-size: {{ $field['font_size'] ?? 14 }}px;
                font-weight: {{ $field['font_weight'] ?? 'normal' }};
                color: {{ $field['color'] ?? '#ffffff' }};
                text-align: {{ $field['align'] ?? 'center' }};
            ">
                {{ $value }}
            </div>
        @endif
    @endforeach
</body>
</html>
