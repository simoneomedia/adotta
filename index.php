<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="marketing-shell">
    <section class="hero card">
        <p class="eyebrow"><?php esc_html_e('Agri SaaS', 'agri-saas'); ?></p>
        <h1><?php esc_html_e('Agricultural adoption operations, without wp-admin.', 'agri-saas'); ?></h1>
        <p><?php esc_html_e('Sign in to manage farms, adopted trees, and field updates through a custom frontend dashboard.', 'agri-saas'); ?></p>
        <a class="button" href="<?php echo esc_url(wp_login_url(agri_saas_user_home_url())); ?>"><?php esc_html_e('Sign in', 'agri-saas'); ?></a>
    </section>
</main>
<?php
get_footer();
