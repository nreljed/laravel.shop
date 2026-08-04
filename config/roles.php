<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    */

    'default_role' => 'customer',

    /*
    |--------------------------------------------------------------------------
    | Core Permission Catalog
    |--------------------------------------------------------------------------
    |
    | Merges the base package permissions with Shop-specific permissions.
    | Feature providers (ShopServiceProvider) also declare their own
    | permissions via permissions() — the seeder merges everything.
    |
    */

    'permissions' => [
        // General
        ['name' => 'View Dashboard', 'slug' => 'view-dashboard', 'description' => 'Access the main dashboard', 'group' => 'General'],
        ['name' => 'View Profile', 'slug' => 'view-profile', 'description' => 'View own profile', 'group' => 'General'],
        ['name' => 'Edit Profile', 'slug' => 'edit-profile', 'description' => 'Edit own name, email, password', 'group' => 'General'],
        ['name' => 'Delete Account', 'slug' => 'delete-account', 'description' => 'Delete own account', 'group' => 'General'],
        ['name' => 'View Notifications', 'slug' => 'view-notifications', 'description' => 'View notifications', 'group' => 'General'],
        ['name' => 'Manage Notifications', 'slug' => 'manage-notifications', 'description' => 'Mark read, delete notifications', 'group' => 'General'],
        // Blog
        ['name' => 'Manage Categories', 'slug' => 'manage-categories', 'description' => 'Manage post categories', 'group' => 'Blog'],
        ['name' => 'Manage Tags', 'slug' => 'manage-tags', 'description' => 'Manage post tags', 'group' => 'Blog'],
        // Administration
        ['name' => 'Manage Users', 'slug' => 'manage-users', 'description' => 'Create, edit, delete users', 'group' => 'Administration'],
        ['name' => 'Impersonate Users', 'slug' => 'impersonate-users', 'description' => 'Sign in as another user to troubleshoot', 'group' => 'Administration'],
        ['name' => 'Assign Roles', 'slug' => 'assign-roles', 'description' => 'Assign and remove roles on users', 'group' => 'Administration'],
        ['name' => 'Manage Settings', 'slug' => 'manage-settings', 'description' => 'Change application settings', 'group' => 'Administration'],
        ['name' => 'Manage Roles', 'slug' => 'manage-roles', 'description' => 'Create, edit, delete roles and permissions', 'group' => 'Administration'],
        ['name' => 'View System Health', 'slug' => 'view-system-health', 'description' => 'View system diagnostics', 'group' => 'Administration'],
        ['name' => 'Manage Options', 'slug' => 'manage-options', 'description' => 'Feature-level settings and system operations (admin only)', 'group' => 'Administration'],
        ['name' => 'Manage Options', 'slug' => 'manage_options', 'description' => 'Feature-level settings and system operations (admin only) — legacy alias', 'group' => 'Administration'],
        ['name' => 'Manage Audit Logs', 'slug' => 'manage-audit-logs', 'description' => 'View system audit logs', 'group' => 'Administration'],
        ['name' => 'Export Audit Logs', 'slug' => 'export-audit-logs', 'description' => 'Export audit log data as CSV', 'group' => 'Administration'],
        ['name' => 'Manage Email Settings', 'slug' => 'manage-email-settings', 'description' => 'Configure email settings', 'group' => 'Administration'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Catalog
    |--------------------------------------------------------------------------
    |
    | Uses * wildcard with ! exclusions for future-proof role definitions.
    | Shop-specific permissions are auto-included via * unless excluded.
    |
    */

    'roles' => [
        'user' => [
            'name' => 'User',
            'description' => 'Can manage own account and browse the store.',
            'sort_order' => 3,
            'permissions' => [
                'view-dashboard',
                'view-profile',
                'edit-profile',
                'delete-account',
                'view-notifications',
                'manage-notifications',
                'shop.view',
                'posts.view',
                'posts.create',
                'posts.edit',
                'posts.delete',
            ],
        ],

        'author' => [
            'name' => 'Author',
            'description' => 'Can add content and manage own account. Limited shop access.',
            'sort_order' => 2,
            'permissions' => [
                '*',
                '!posts.manage',
                '!manage-users',
                '!impersonate-users',
                '!assign-roles',
                '!manage-settings',
                '!manage-roles',
                '!view-system-health',
                '!manage-options',
                '!manage_options',
                '!manage-audit-logs',
                '!export-audit-logs',
                '!manage-email-settings',
                '!manage-categories',
                '!manage-tags',
                '!shop.orders.refund',
                '!shop.webhooks.manage',
                '!shop.currencies.manage',
            ],
        ],

        'editor' => [
            'name' => 'Editor',
            'description' => 'Every permission except the admin panel. Full shop and content management.',
            'sort_order' => 1,
            'permissions' => [
                '*',
                '!manage-users',
                '!impersonate-users',
                '!assign-roles',
                '!manage-settings',
                '!manage-roles',
                '!view-system-health',
                '!manage-options',
                '!manage_options',
                '!manage-audit-logs',
                '!export-audit-logs',
                '!manage-email-settings',
            ],
        ],

        'admin' => [
            'name' => 'Admin',
            'description' => 'Full access to everything including admin panel',
            'sort_order' => 0,
            'permissions' => '*',
        ],

        'shop_owner' => [
            'name' => 'Shop Owner',
            'description' => 'Full shop control: products, orders, categories, reviews, etc.',
            'sort_order' => 2,
            'permissions' => [
                'view-dashboard',
                'view-profile',
                'edit-profile',
                'delete-account',
                'view-notifications',
                'manage-notifications',
            ],
        ],

        'shop_manager' => [
            'name' => 'Shop Manager',
            'description' => 'Manage existing shop content: edit products, view orders',
            'sort_order' => 3,
            'permissions' => [
                'view-dashboard',
                'view-profile',
                'edit-profile',
                'delete-account',
                'view-notifications',
                'manage-notifications',
            ],
        ],

        'customer' => [
            'name' => 'Customer',
            'description' => 'Account management: profile, notifications, account settings',
            'sort_order' => 4,
            'permissions' => [
                'view-dashboard',
                'view-profile',
                'edit-profile',
                'delete-account',
                'view-notifications',
                'manage-notifications',
            ],
        ],
    ],

];
