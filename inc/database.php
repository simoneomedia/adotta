<?php
if (!defined('ABSPATH')) {
    exit;
}

define('AGRI_SAAS_DB_VERSION', '12');

add_action('init', 'agri_saas_maybe_upgrade_db');
function agri_saas_maybe_upgrade_db(): void
{
    if (get_option('agri_saas_db_version') !== AGRI_SAAS_DB_VERSION) {
        agri_saas_install_tables();
        update_option('agri_saas_db_version', AGRI_SAAS_DB_VERSION);
    }
}

/**
 * Return all custom table names used by the application domain.
 */
function agri_saas_tables(): array
{
    global $wpdb;

    return [
        'farms'              => $wpdb->prefix . 'agri_farms',
        'trees'              => $wpdb->prefix . 'agri_trees',
        'updates'            => $wpdb->prefix . 'agri_updates',
        'adoptions'          => $wpdb->prefix . 'agri_adoptions',
        'farm_followers'     => $wpdb->prefix . 'agri_farm_followers',
        'update_reactions'   => $wpdb->prefix . 'agri_update_reactions',
        'push_subscriptions' => $wpdb->prefix . 'agri_push_subscriptions',
        'rewards'            => $wpdb->prefix . 'agri_rewards',
        'tree_rewards'       => $wpdb->prefix . 'agri_saas_tree_rewards',
        'products'           => $wpdb->prefix . 'agri_products',
        'baratti'            => $wpdb->prefix . 'agri_baratti',
        'farm_reviews'       => $wpdb->prefix . 'agri_farm_reviews',
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
        media_url TEXT NOT NULL DEFAULT '',
        is_verified TINYINT UNSIGNED NOT NULL DEFAULT 0,
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
        type VARCHAR(40) NOT NULL DEFAULT 'albero',
        status VARCHAR(40) NOT NULL DEFAULT 'available',
        planted_at DATE DEFAULT NULL,
        planted_display VARCHAR(20) DEFAULT NULL,
        carbon_estimate DECIMAL(10,2) DEFAULT 0,
        media_url TEXT DEFAULT NULL,
        description TEXT DEFAULT NULL,
        adopted_at DATETIME DEFAULT NULL,
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
        cancellation_requested_at DATETIME DEFAULT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'pending',
        is_gift TINYINT UNSIGNED NOT NULL DEFAULT 0,
        gift_token VARCHAR(64) DEFAULT NULL,
        gift_recipient_email VARCHAR(191) DEFAULT NULL,
        gift_message TEXT DEFAULT NULL,
        gift_claimed_at DATETIME DEFAULT NULL,
        milestone_sent VARCHAR(191) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        UNIQUE KEY tree_user (tree_id, adopter_user_id),
        KEY adopter_user_id (adopter_user_id),
        KEY status (status),
        KEY gift_token (gift_token)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['update_reactions']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        update_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        reaction VARCHAR(20) NOT NULL DEFAULT 'heart',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_update (update_id, user_id),
        KEY update_id (update_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['push_subscriptions']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        endpoint TEXT NOT NULL,
        p256dh TEXT NOT NULL,
        auth TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_endpoint (user_id, endpoint(191)),
        KEY user_id (user_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['rewards']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        farm_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(191) NOT NULL,
        description TEXT NOT NULL,
        reward_type VARCHAR(40) NOT NULL DEFAULT 'surprise',
        when_received VARCHAR(40) NOT NULL DEFAULT 'immediate',
        estimated_value VARCHAR(100) DEFAULT '',
        guidelines TEXT DEFAULT NULL,
        is_active TINYINT UNSIGNED NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY farm_id (farm_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['tree_rewards']} (
        tree_id BIGINT UNSIGNED NOT NULL,
        reward_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY  (tree_id, reward_id),
        KEY reward_id (reward_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['products']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        farm_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(191) NOT NULL,
        description TEXT DEFAULT NULL,
        price DECIMAL(10,2) DEFAULT NULL,
        unit VARCHAR(40) NOT NULL DEFAULT 'unità',
        price_note VARCHAR(191) NOT NULL DEFAULT '',
        media_url TEXT DEFAULT NULL,
        is_active TINYINT UNSIGNED NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY farm_id (farm_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['farm_reviews']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        farm_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        rating TINYINT UNSIGNED NOT NULL DEFAULT 1,
        comment TEXT NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY farm_user (farm_id, user_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE {$tables['baratti']} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        farm_id BIGINT UNSIGNED NOT NULL,
        offer_title VARCHAR(191) NOT NULL,
        offer_description TEXT DEFAULT NULL,
        wants_title VARCHAR(191) NOT NULL,
        wants_description TEXT DEFAULT NULL,
        media_url TEXT DEFAULT NULL,
        is_active TINYINT UNSIGNED NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY farm_id (farm_id)
    ) $charset_collate;");
}
