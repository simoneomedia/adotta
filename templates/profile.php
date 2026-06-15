<?php
if (!defined('ABSPATH')) { exit; }
require_once AGRI_SAAS_PATH . '/components/layout.php';
agri_saas_render_shell(__('Il mio profilo', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/profile" data-render="profile">
        <div class="span-3" data-slot="level-badge"></div>
        <article class="card span-2" data-slot="profile-info">
            <p class="eyebrow">Account</p>
            <h2>Caricamento…</h2>
        </article>
        <aside class="card" data-slot="profile-stats"></aside>
        <article class="card span-3" data-slot="profile-adoptions">
            <p class="eyebrow">Le mie adozioni</p>
            <p>Caricamento…</p>
        </article>
    </section>
    <?php
}, '👤 Profilo');
