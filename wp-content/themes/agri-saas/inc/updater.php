<?php
/**
 * GitHub-powered theme updates.
 */

if (!defined('ABSPATH')) {
    exit;
}

const AGRI_SAAS_GITHUB_OPTION_REPOSITORY = 'agri_saas_github_repository';
const AGRI_SAAS_GITHUB_OPTION_TOKEN = 'agri_saas_github_token';
const AGRI_SAAS_GITHUB_OPTION_BRANCH = 'agri_saas_github_branch';

add_action('admin_menu', 'agri_saas_register_update_settings_page');
add_action('admin_init', 'agri_saas_register_update_settings');
add_filter('site_transient_update_themes', 'agri_saas_check_github_theme_update');
add_filter('upgrader_pre_download', 'agri_saas_download_github_package', 10, 4);

function agri_saas_register_update_settings_page(): void
{
    add_theme_page(
        __('Agri SaaS GitHub Updates', 'agri-saas'),
        __('Agri SaaS Updates', 'agri-saas'),
        'manage_options',
        'agri-saas-updates',
        'agri_saas_render_update_settings_page'
    );
}

function agri_saas_register_update_settings(): void
{
    register_setting('agri_saas_updates', AGRI_SAAS_GITHUB_OPTION_REPOSITORY, [
        'type' => 'string',
        'sanitize_callback' => 'agri_saas_sanitize_github_repository',
        'default' => '',
    ]);

    register_setting('agri_saas_updates', AGRI_SAAS_GITHUB_OPTION_TOKEN, [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ]);

    register_setting('agri_saas_updates', AGRI_SAAS_GITHUB_OPTION_BRANCH, [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'main',
    ]);
}

function agri_saas_render_update_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Agri SaaS GitHub Updates', 'agri-saas'); ?></h1>
        <p><?php esc_html_e('Connect this theme to a GitHub repository so WordPress can detect new release versions and install them from the Updates screen.', 'agri-saas'); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields('agri_saas_updates'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="agri-saas-github-repository"><?php esc_html_e('Repository', 'agri-saas'); ?></label></th>
                    <td>
                        <input
                            id="agri-saas-github-repository"
                            class="regular-text"
                            name="<?php echo esc_attr(AGRI_SAAS_GITHUB_OPTION_REPOSITORY); ?>"
                            placeholder="owner/repository"
                            value="<?php echo esc_attr(agri_saas_get_github_repository()); ?>"
                        >
                        <p class="description"><?php esc_html_e('Use the GitHub slug, for example: your-company/agri-saas-theme.', 'agri-saas'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="agri-saas-github-token"><?php esc_html_e('GitHub token', 'agri-saas'); ?></label></th>
                    <td>
                        <input
                            id="agri-saas-github-token"
                            class="regular-text"
                            name="<?php echo esc_attr(AGRI_SAAS_GITHUB_OPTION_TOKEN); ?>"
                            type="password"
                            autocomplete="off"
                            value="<?php echo esc_attr(agri_saas_get_github_token()); ?>"
                        >
                        <p class="description"><?php esc_html_e('Optional for public repositories. Required for private repositories; create a fine-grained token with repository contents read access.', 'agri-saas'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="agri-saas-github-branch"><?php esc_html_e('Fallback branch', 'agri-saas'); ?></label></th>
                    <td>
                        <input
                            id="agri-saas-github-branch"
                            class="regular-text"
                            name="<?php echo esc_attr(AGRI_SAAS_GITHUB_OPTION_BRANCH); ?>"
                            value="<?php echo esc_attr(agri_saas_get_github_branch()); ?>"
                        >
                        <p class="description"><?php esc_html_e('Used only when the repository has no GitHub releases. Releases with semantic version tags are recommended.', 'agri-saas'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function agri_saas_sanitize_github_repository(string $repository): string
{
    $repository = trim($repository);
    $repository = preg_replace('#^https?://github\.com/#i', '', $repository) ?? '';
    $repository = trim($repository, "/ \t\n\r\0\x0B");

    if (!preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repository)) {
        return '';
    }

    return $repository;
}

function agri_saas_get_github_repository(): string
{
    if (defined('AGRI_SAAS_GITHUB_REPOSITORY') && AGRI_SAAS_GITHUB_REPOSITORY) {
        return agri_saas_sanitize_github_repository((string) AGRI_SAAS_GITHUB_REPOSITORY);
    }

    return agri_saas_sanitize_github_repository((string) get_option(AGRI_SAAS_GITHUB_OPTION_REPOSITORY, ''));
}

function agri_saas_get_github_token(): string
{
    if (defined('AGRI_SAAS_GITHUB_TOKEN') && AGRI_SAAS_GITHUB_TOKEN) {
        return (string) AGRI_SAAS_GITHUB_TOKEN;
    }

    return (string) get_option(AGRI_SAAS_GITHUB_OPTION_TOKEN, '');
}

function agri_saas_get_github_branch(): string
{
    if (defined('AGRI_SAAS_GITHUB_BRANCH') && AGRI_SAAS_GITHUB_BRANCH) {
        return sanitize_text_field((string) AGRI_SAAS_GITHUB_BRANCH);
    }

    $branch = sanitize_text_field((string) get_option(AGRI_SAAS_GITHUB_OPTION_BRANCH, 'main'));

    return $branch ?: 'main';
}

function agri_saas_check_github_theme_update(object $transient): object
{
    if (empty($transient->checked) || !isset($transient->checked[AGRI_SAAS_THEME_SLUG])) {
        return $transient;
    }

    $release = agri_saas_get_latest_github_release();
    if (!$release || empty($release['version']) || empty($release['package'])) {
        return $transient;
    }

    if (version_compare($release['version'], AGRI_SAAS_VERSION, '<=')) {
        return $transient;
    }

    $transient->response[AGRI_SAAS_THEME_SLUG] = [
        'theme' => AGRI_SAAS_THEME_SLUG,
        'new_version' => $release['version'],
        'url' => $release['url'],
        'package' => $release['package'],
        'requires' => '6.0',
        'requires_php' => '8.0',
    ];

    return $transient;
}

/**
 * @return array{version:string, package:string, url:string}|null
 */
function agri_saas_get_latest_github_release(): ?array
{
    $repository = agri_saas_get_github_repository();
    if (!$repository) {
        return null;
    }

    $release_response = agri_saas_github_request("https://api.github.com/repos/{$repository}/releases/latest");
    if (!is_wp_error($release_response) && wp_remote_retrieve_response_code($release_response) === 200) {
        $release = json_decode(wp_remote_retrieve_body($release_response), true);
        if (is_array($release) && !empty($release['tag_name']) && !empty($release['zipball_url'])) {
            return [
                'version' => agri_saas_normalize_version((string) $release['tag_name']),
                'package' => esc_url_raw((string) $release['zipball_url']),
                'url' => esc_url_raw((string) ($release['html_url'] ?? "https://github.com/{$repository}/releases")),
            ];
        }
    }

    $branch = agri_saas_get_github_branch();
    $branch_version = agri_saas_get_branch_theme_version($repository, $branch);
    if (!$branch_version) {
        return null;
    }

    $encoded_branch = rawurlencode($branch);

    return [
        'version' => $branch_version,
        'package' => "https://api.github.com/repos/{$repository}/zipball/{$encoded_branch}",
        'url' => "https://github.com/{$repository}/tree/{$encoded_branch}",
    ];
}

function agri_saas_get_branch_theme_version(string $repository, string $branch): ?string
{
    $encoded_branch = rawurlencode($branch);
    $contents_response = agri_saas_github_request("https://api.github.com/repos/{$repository}/contents/style.css?ref={$encoded_branch}");

    if (is_wp_error($contents_response) || wp_remote_retrieve_response_code($contents_response) !== 200) {
        return null;
    }

    $contents = json_decode(wp_remote_retrieve_body($contents_response), true);
    if (!is_array($contents) || empty($contents['content'])) {
        return null;
    }

    $encoded_content = preg_replace('/\s+/', '', (string) $contents['content']) ?? '';
    $style_css = base64_decode($encoded_content, true);
    if (!$style_css || !preg_match('/^Version:\s*(.+)$/mi', $style_css, $matches)) {
        return null;
    }

    return agri_saas_normalize_version($matches[1]);
}

function agri_saas_normalize_version(string $version): string
{
    $version = ltrim(trim($version), 'vV');
    preg_match('/\d+(?:\.\d+){0,3}(?:[-+][A-Za-z0-9.-]+)?/', $version, $matches);

    return $matches[0] ?? $version;
}

function agri_saas_github_request(string $url)
{
    $headers = [
        'Accept' => 'application/vnd.github+json',
        'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
        'X-GitHub-Api-Version' => '2022-11-28',
    ];

    $token = agri_saas_get_github_token();
    if ($token) {
        $headers['Authorization'] = 'Bearer ' . $token;
    }

    return wp_remote_get($url, [
        'headers' => $headers,
        'timeout' => 15,
    ]);
}

function agri_saas_download_github_package($reply, string $package, WP_Upgrader $upgrader, array $hook_extra)
{
    if (!isset($hook_extra['theme']) || $hook_extra['theme'] !== AGRI_SAAS_THEME_SLUG) {
        return $reply;
    }

    if (!str_starts_with($package, 'https://api.github.com/repos/')) {
        return $reply;
    }

    $temporary_file = wp_tempnam($package);
    if (!$temporary_file) {
        return new WP_Error('agri_saas_temp_file_failed', __('Could not create a temporary file for the GitHub theme package.', 'agri-saas'));
    }

    $headers = [
        'Accept' => 'application/vnd.github+json',
        'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
        'X-GitHub-Api-Version' => '2022-11-28',
    ];

    $token = agri_saas_get_github_token();
    if ($token) {
        $headers['Authorization'] = 'Bearer ' . $token;
    }

    $response = wp_safe_remote_get($package, [
        'headers' => $headers,
        'stream' => true,
        'filename' => $temporary_file,
        'timeout' => 300,
    ]);

    if (is_wp_error($response)) {
        @unlink($temporary_file);
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code < 200 || $response_code >= 300) {
        @unlink($temporary_file);
        return new WP_Error('agri_saas_github_download_failed', sprintf(
            /* translators: %d: HTTP response code. */
            __('GitHub package download failed with HTTP status %d.', 'agri-saas'),
            $response_code
        ));
    }

    return $temporary_file;
}
