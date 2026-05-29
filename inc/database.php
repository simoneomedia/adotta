<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return all custom table names used by the application domain.
 */
function agri_saas_tables(): array
{
    global $wpdb;

    return [
        'farms' => $wpdb->prefix . 'agri_farms',
        'trees' => $wpdb->prefix . 'agri_trees',
        'updates' => $wpdb->prefix . 'agri_updates',
        'adoptions' => $wpdb->prefix . 'agri_adoptions',
        'farm_followers' => $wpdb->prefix . 'agri_farm_followers',
    ];
}

register_activation_hook(AGRI_SAAS_PATH . '/functions.php', 'agri_saas_install_tables');
add_action('after_switch_theme', 'agri_saas_install_tables');
function agri_saas_install_tables(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $tables = agri_saas_tables();

    dbDelta("CREATE TABLE {$tables['farms']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        owner_user_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(191) NOT NULL,
        location VARCHAR(191) NOT NULL,
        acreage DECIMAL(10,2) DEFAULT 0,
        crop_focus VARCHAR(191) DEFAULT '',
        health_score TINYINT UNSIGNED DEFAULT 0,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        contact_email VARCHAR(191) DEFAULT '',
        contact_whatsapp VARCHAR(40) DEFAULT '',
        contact_phone VARCHAR(40) DEFAULT '',
        description TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY owner_user_id (owner_user_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['trees']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        farm_id BIGINT UNSIGNED NOT NULL,
        adopter_user_id BIGINT UNSIGNED DEFAULT NULL,
        species VARCHAR(191) NOT NULL,
        code VARCHAR(64) NOT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'available',
        planted_at DATE DEFAULT NULL,
        carbon_estimate DECIMAL(10,2) DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY code (code),
        KEY farm_id (farm_id),
        KEY adopter_user_id (adopter_user_id),
        KEY status (status),
        KEY status_created (status, created_at)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['updates']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        farm_id BIGINT UNSIGNED DEFAULT NULL,
        tree_id BIGINT UNSIGNED DEFAULT NULL,
        author_user_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(191) NOT NULL,
        body TEXT NOT NULL,
        media_url TEXT DEFAULT NULL,
        visibility VARCHAR(40) NOT NULL DEFAULT 'public',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY farm_id (farm_id),
        KEY tree_id (tree_id),
        KEY author_user_id (author_user_id),
        KEY visibility (visibility),
        KEY created_at (created_at),
        KEY vis_created (visibility, created_at)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['farm_followers']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        farm_id BIGINT UNSIGNED NOT NULL,
        follower_user_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY farm_follower (farm_id, follower_user_id),
        KEY follower_user_id (follower_user_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['adoptions']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        tree_id BIGINT UNSIGNED NOT NULL,
        adopter_user_id BIGINT UNSIGNED NOT NULL,
        starts_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        decided_at DATETIME DEFAULT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'pending',
        PRIMARY KEY  (id),
        UNIQUE KEY tree_user (tree_id, adopter_user_id),
        KEY adopter_user_id (adopter_user_id),
        KEY status (status)
    ) $charset_collate;");
}
