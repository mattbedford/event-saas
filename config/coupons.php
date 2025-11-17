<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Coupon Type Defaults
    |--------------------------------------------------------------------------
    |
    | Define default usage limits for each coupon type. These are applied
    | automatically when creating new coupons, but can be overridden per coupon.
    |
    | max_uses_per_event: Maximum uses for a single event (null = unlimited)
    | max_uses_global: Maximum uses across all events in the year (null = unlimited)
    |
    */

    'types' => [
        'staff' => [
            'label' => 'Staff',
            'max_uses_per_event' => 1,
            'max_uses_global' => 1,
            'description' => 'Staff members - 1 use per event, 1 use globally per year',
        ],

        'staff_guest' => [
            'label' => 'Staff Guest',
            'max_uses_per_event' => 100,
            'max_uses_global' => null, // Unlimited globally
            'description' => 'Staff guests - 100 uses per event, unlimited annually',
        ],

        'member' => [
            'label' => 'Member',
            'max_uses_per_event' => 1,
            'max_uses_global' => 6,
            'description' => 'Members - 1 use per event, 6 uses globally per year',
        ],

        'member_guest' => [
            'label' => 'Member Guest',
            'max_uses_per_event' => 5,
            'max_uses_global' => null, // Unlimited globally
            'description' => 'Member guests - 5 uses per event, unlimited annually',
        ],

        'custom' => [
            'label' => 'Custom',
            'max_uses_per_event' => null,
            'max_uses_global' => null,
            'description' => 'Custom configuration - set your own limits',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cancellation Policy
    |--------------------------------------------------------------------------
    |
    | Deadline for cancellations that reaccredit coupon uses.
    | Cancellations after this deadline are treated as no-shows.
    |
    */

    'cancellation_deadline_hours' => 24,
];
