<?php
if (!defined('ABSPATH')) {
    exit;
}

if (is_user_logged_in()) {
    wp_safe_redirect(agri_saas_user_home_url());
    exit;
}

wp_redirect(wp_login_url(agri_saas_user_home_url()));
exit;
