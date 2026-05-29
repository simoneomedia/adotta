(function () {
    const root = document.querySelector('[data-agri-endpoint]');
    if (!root || !window.AgriSaas) return;

    const apiFetch = async (path, options = {}) => {
        const response = await fetch(`${window.AgriSaas.apiBase}${path}`, {
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.AgriSaas.nonce,
                ...(options.headers || {}),
            },
            ...options,
        });
        if (!response.ok) throw new Error(`API request failed: ${response.status}`);
        return response.json();
    };

    const appUrl = (path) => new URL(path.replace(/^\//, ''), window.AgriSaas.homeUrl).toString();

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[char]));

    const statCard = (label, value, meta) => `
        <article class="card stat-card">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
            <small>${escapeHtml(meta)}</small>
        </article>`;

    const renderClientDashboard = (data) => {
        root.querySelector('[data-slot="stats"]').innerHTML = [
            statCard('Adopted trees', data.stats.adoptedTrees, 'In your adoption portfolio'),
            statCard('Active adoptions', data.stats.activeAdoptions, 'Currently active'),
            statCard('Carbon estimate', `${data.stats.estimatedCarbonKg} kg`, 'Estimated sequestration'),
        ].join('');
        root.querySelector('[data-slot="trees"]').innerHTML = data.trees.length ? data.trees.map((tree) => `
            <a class="tree-row" href="${appUrl(`trees/${tree.id}/`)}">
                <div><strong>${escapeHtml(tree.species)}</strong><br><small>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.location)}</small></div>
                <span class="badge">${escapeHtml(tree.code)}</span>
            </a>`).join('') : '<div class="card empty-state">No adopted trees yet.</div>';
    };

    const updateFarmOptions = (farms) => {
        document.querySelectorAll('[data-farm-options]').forEach((select) => {
            select.innerHTML = farms.length ? farms.map((farm) => `
                <option value="${escapeHtml(farm.id)}">${escapeHtml(farm.name)} · ${escapeHtml(farm.location)}</option>
            `).join('') : '<option value="">Create a farm first</option>';
            select.disabled = !farms.length;
        });
    };

    const renderFarmDashboard = (data) => {
        root.querySelector('[data-slot="stats"]').innerHTML = [
            statCard('Managed farms', data.stats.farms, 'Registered farms'),
            statCard('Available trees', data.stats.availableTrees, 'Ready for adoption'),
            statCard('Adopted trees', data.stats.adoptedTrees, 'Sponsored by clients'),
        ].join('');
        root.querySelector('[data-slot="farms"]').innerHTML = data.farms.length ? data.farms.map((farm) => `
            <div class="farm-row">
                <div><strong>${escapeHtml(farm.name)}</strong><br><small>${escapeHtml(farm.location)} · ${escapeHtml(farm.crop_focus)}</small></div>
                <span class="badge">${escapeHtml(farm.tree_count)} trees · ${escapeHtml(farm.health_score)} health</span>
            </div>`).join('') : '<div class="card empty-state">No farm records yet. Use Add farm before publishing trees.</div>';
        root.querySelector('[data-slot="farm-trees"]').innerHTML = data.trees.length ? data.trees.map((tree) => `
            <a class="tree-row" href="${appUrl(`trees/${tree.id}/`)}">
                <div><strong>${escapeHtml(tree.species)}</strong><br><small>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.planted_at || 'Planting date pending')}</small></div>
                <span class="badge">${escapeHtml(tree.code)} · ${escapeHtml(tree.status)}</span>
            </a>`).join('') : '<div class="card empty-state">No trees published yet. Use Add tree to make one available for adoption.</div>';
        updateFarmOptions(data.farms || []);
    };

    const renderTreeDetail = (data) => {
        const tree = data.tree;
        root.querySelector('[data-slot="tree"]').innerHTML = `
            <p class="eyebrow">${escapeHtml(tree.code)}</p>
            <h2>${escapeHtml(tree.species)}</h2>
            <p>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.location)} · ${escapeHtml(tree.crop_focus)}</p>
            <div class="stats-grid">
                ${statCard('Status', tree.status, 'Current lifecycle')}
                ${statCard('Carbon', `${tree.carbon_estimate} kg`, 'Estimated sequestration')}
                ${statCard('Planted', tree.planted_at || 'Pending', 'Planting date')}
            </div>`;
        renderUpdates(data.updates || []);
    };

    const renderUpdates = (updates) => {
        const slot = root.querySelector('[data-slot="updates"]');
        slot.innerHTML = updates.length ? updates.map((update) => `
            <article class="timeline-item">
                <p class="eyebrow">${escapeHtml(update.farm_name || update.tree_code || 'Field update')} · ${escapeHtml(update.created_at)}</p>
                <h3>${escapeHtml(update.title)}</h3>
                <p>${escapeHtml(update.body)}</p>
                ${update.media_url ? `<a class="button ghost" href="${escapeHtml(update.media_url)}">View media</a>` : ''}
            </article>`).join('') : '<div class="card empty-state">No updates have been published yet.</div>';
    };

    const renderers = {
        'client-dashboard': renderClientDashboard,
        'farm-dashboard': renderFarmDashboard,
        'tree-detail': renderTreeDetail,
        'updates-feed': (data) => renderUpdates(data.updates || []),
    };

    apiFetch(root.dataset.agriEndpoint)
        .then((data) => renderers[root.dataset.render]?.(data))
        .catch(() => root.insertAdjacentHTML('beforeend', '<div class="card empty-state">Unable to load dashboard data.</div>'));

    const showPanel = (selector) => {
        document.querySelector(selector)?.removeAttribute('hidden');
    };

    document.querySelector('[data-open-farm-form]')?.addEventListener('click', () => showPanel('[data-farm-form]'));
    document.querySelector('[data-open-tree-form]')?.addEventListener('click', () => showPanel('[data-tree-form]'));
    document.querySelector('[data-open-update-form]')?.addEventListener('click', () => showPanel('[data-update-form]'));

    document.querySelector('[data-agri-farm-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const payload = Object.fromEntries(new FormData(form).entries());
        await apiFetch('/farms', { method: 'POST', body: JSON.stringify(payload) });
        window.location.reload();
    });

    document.querySelector('[data-agri-tree-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const payload = Object.fromEntries(new FormData(form).entries());
        await apiFetch('/trees', { method: 'POST', body: JSON.stringify(payload) });
        window.location.reload();
    });

    document.querySelector('[data-agri-update-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const payload = Object.fromEntries(new FormData(form).entries());
        await apiFetch('/updates', { method: 'POST', body: JSON.stringify(payload) });
        form.reset();
        window.location.href = appUrl('updates/');
    });
}());
