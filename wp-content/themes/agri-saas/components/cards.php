<?php
if (!defined('ABSPATH')) {
    exit;
}

function agri_saas_stat_card(string $label, string $value, string $meta = ''): void
{
    ?>
    <article class="card stat-card">
        <span><?php echo esc_html($label); ?></span>
        <strong><?php echo esc_html($value); ?></strong>
        <?php if ($meta) : ?><small><?php echo esc_html($meta); ?></small><?php endif; ?>
    </article>
    <?php
}

function agri_saas_empty_state(string $message): void
{
    ?>
    <div class="card empty-state">
        <span class="empty-icon">🌿</span>
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php
}
