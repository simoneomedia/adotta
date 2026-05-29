<?php
/**
 * Agri SaaS theme bootstrap.
 *
 * WordPress is used for authentication and user management only. Application
 * routes render custom templates and business data is exposed through custom
 * REST endpoints backed by custom MySQL tables.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AGRI_SAAS_VERSION', '1.0.0');
define('AGRI_SAAS_PATH', get_template_directory());
define('AGRI_SAAS_URI', get_template_directory_uri());

require_once AGRI_SAAS_PATH . '/inc/setup.php';
require_once AGRI_SAAS_PATH . '/inc/database.php';
require_once AGRI_SAAS_PATH . '/inc/routes.php';
require_once AGRI_SAAS_PATH . '/inc/api.php';
require_once AGRI_SAAS_PATH . '/inc/auth.php';
