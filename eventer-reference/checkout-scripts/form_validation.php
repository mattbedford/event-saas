<?php

function validate_the_form($data) {
    $errors = [];

    // Anti-bot / honeypot check
    if (!empty($data['website_fake_field'])) {
        $errors['honeypot'] = 'Suspicious activity detected.';
    }

    // Email check
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // First name
    if (empty($data['fname']) || strlen(trim($data['fname'])) < 2) {
        $errors['fname'] = 'First name is required.';
    }

    // Last name
    if (empty($data['lname']) || strlen(trim($data['lname'])) < 2) {
        $errors['lname'] = 'Last name is required.';
    }

    // Company
    if (empty($data['company']) || strlen(trim($data['company'])) < 2) {
        $errors['company'] = 'Company name is required.';
    }

    // Website (optional but format-checked)
    if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
        $errors['website'] = 'Please enter a valid website URL.';
    }

    // Return with errors if any
    if (!empty($errors)) {
        return [
            'status' => 'error',
            'errors' => $errors,
            'old' => $data
        ];
    }

    // All good
    return [
        'status' => 'ok',
        'old' => $data
    ];
}
