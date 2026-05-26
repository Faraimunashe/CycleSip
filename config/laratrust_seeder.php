<?php

return [
    /**
     * Control if the seeder should create a user per role while seeding the data.
     */
    'create_users' => false,

    /**
     * Control if all the laratrust tables should be truncated before running the seeder.
     */
    'truncate_tables' => true,

    'roles_permissions' => [
        'super-admin' => [
            'manage-orders',
            'manage-riders',
            'manage-stores',
            'manage-users',
            'manage-settings',
            'view-analytics',
            'manage-payments',
            'manage-zones',
            'approve-riders',
            'approve-stores',
            'manage-products',
            'manage-customers',
        ],
        'admin' => [
            'manage-orders',
            'manage-riders',
            'manage-stores',
            'manage-users',
            'view-analytics',
            'manage-payments',
            'manage-zones',
            'approve-riders',
            'approve-stores',
            'manage-products',
            'manage-customers',
        ],
        'operations-manager' => [
            'manage-orders',
            'manage-riders',
            'view-analytics',
            'manage-zones',
            'approve-riders',
        ],
        'support-staff' => [
            'manage-orders',
            'manage-customers',
        ],
        'rider' => [
            'manage-orders',
        ],
        'store-manager' => [
            'manage-stores',
            'manage-products',
        ],
        'finance-officer' => [
            'manage-payments',
            'view-analytics',
        ],
        'customer' => [],
    ],
];
