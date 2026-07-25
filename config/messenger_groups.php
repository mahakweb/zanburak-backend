<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */

    'roles' => [
        'group' => ['owner', 'admin', 'moderator', 'member', 'guest'],
        'channel' => ['owner', 'admin', 'subscriber'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions (independently configurable per role / conversation)
    |--------------------------------------------------------------------------
    */

    'permissions' => [
        'send_messages',
        'edit_own_messages',
        'delete_own_messages',
        'delete_others_messages',
        'pin_messages',
        'manage_pins',
        'invite_members',
        'create_invite_links',
        'manage_invite_links',
        'ban_members',
        'unban_members',
        'kick_members',
        'mute_members',
        'manage_roles',
        'manage_group_info',
        'manage_avatar',
        'manage_cover',
        'manage_username',
        'manage_slow_mode',
        'manage_join_requests',
        'manage_approval_requests',
        'mention_everyone',
        'mention_admins',
        'create_poll',
        'view_audit_logs',
        'transfer_ownership',
        'publish_posts', // channels
    ],

    /*
    |--------------------------------------------------------------------------
    | Default permission matrix (role => permission => allowed)
    | Overridable per conversation via conversation_role_permissions.
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'group' => [
            'owner' => '*',
            'admin' => [
                'send_messages', 'edit_own_messages', 'delete_own_messages', 'delete_others_messages',
                'pin_messages', 'manage_pins', 'invite_members', 'create_invite_links', 'manage_invite_links',
                'ban_members', 'unban_members', 'kick_members', 'mute_members', 'manage_roles',
                'manage_group_info', 'manage_avatar', 'manage_cover', 'manage_username', 'manage_slow_mode',
                'manage_join_requests', 'manage_approval_requests', 'mention_everyone', 'mention_admins',
                'create_poll', 'view_audit_logs',
            ],
            'moderator' => [
                'send_messages', 'edit_own_messages', 'delete_own_messages', 'delete_others_messages',
                'pin_messages', 'manage_pins', 'invite_members', 'kick_members', 'mute_members',
                'manage_join_requests', 'mention_admins', 'create_poll', 'view_audit_logs',
            ],
            'member' => [
                'send_messages', 'edit_own_messages', 'delete_own_messages', 'invite_members',
                'mention_admins', 'create_poll',
            ],
            'guest' => [
                'send_messages', 'edit_own_messages', 'delete_own_messages',
            ],
        ],
        'channel' => [
            'owner' => '*',
            'admin' => [
                'publish_posts', 'edit_own_messages', 'delete_own_messages', 'delete_others_messages',
                'pin_messages', 'manage_pins', 'invite_members', 'create_invite_links', 'manage_invite_links',
                'ban_members', 'unban_members', 'kick_members', 'mute_members', 'manage_roles',
                'manage_group_info', 'manage_avatar', 'manage_cover', 'manage_username',
                'manage_join_requests', 'manage_approval_requests', 'view_audit_logs',
            ],
            'subscriber' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Slow mode presets (seconds)
    |--------------------------------------------------------------------------
    */

    'slow_mode_presets' => [5, 10, 30, 60, 300],

    /*
    |--------------------------------------------------------------------------
    | Mute presets (seconds); null = forever
    |--------------------------------------------------------------------------
    */

    'mute_presets' => [
        '10m' => 600,
        '1h' => 3600,
        '8h' => 28800,
        '1d' => 86400,
        '1w' => 604800,
        'forever' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Anti-spam
    |--------------------------------------------------------------------------
    */

    'anti_spam' => [
        'max_messages_per_window' => (int) env('MESSENGER_SPAM_MAX', 8),
        'window_seconds' => (int) env('MESSENGER_SPAM_WINDOW', 10),
        'repeat_threshold' => (int) env('MESSENGER_SPAM_REPEAT', 3),
        'auto_mute_seconds' => (int) env('MESSENGER_SPAM_MUTE', 600),
    ],

    'max_title_length' => 128,
    'max_description_length' => 2000,
    'max_members_per_create' => 200,
    'invite_code_length' => 16,
];
