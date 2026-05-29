(function () {
    if (!window.AgriSaas) return;

    const root = document.querySelector('[data-agri-endpoint]');
    const coordinateMaps = new Map();
    let adoptableLeafletMap = null;
    let adoptableMarkers = null;

    const apiFetch = async (path, options = {}) => {
        const { headers: optionHeaders = {}, ...fetchOptions } = options;
        const headers = options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' };
        const response = await fetch(`${window.AgriSaas.apiBase}${path}`, {
            credentials: 'same-origin',
            ...fetchOptions,
            headers: {
                ...headers,
                'X-WP-Nonce': window.AgriSaas.nonce,
                ...optionHeaders,
            },
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `API request failed: ${response.status}`);
        return payload;
    };

    const appUrl = (path) => new URL(path.replace(/^\//, ''), window.AgriSaas.homeUrl).toString();

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[char]));

    const hasLeaflet = () => Boolean(window.L);

    const getCoordinateInputs = (container) => {
        const scope = container.closest('form') || container.parentElement || document;
        return {
            lat: scope.querySelector('[data-marker-lat]'),
            lng: scope.querySelector('[data-marker-lng]'),
        };
    };

    const defaultLatLng = () => [41.9028, 12.4964];

    const readLatLng = (container) => {
        const inputs = getCoordinateInputs(container);
        const lat = Number(inputs.lat?.value);
        const lng = Number(inputs.lng?.value);
        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            return [lat, lng];
        }
        return defaultLatLng();
    };

    const setCoordinateMarker = (container, lat, lng, zoom = 13) => {
        const state = coordinateMaps.get(container);
        if (!state) return;

        const inputs = getCoordinateInputs(container);
        if (inputs.lat) inputs.lat.value = Number(lat).toFixed(7);
        if (inputs.lng) inputs.lng.value = Number(lng).toFixed(7);

        if (!state.marker) {
            state.marker = window.L.marker([lat, lng], { draggable: true }).addTo(state.map);
            state.marker.on('dragend', () => {
                const position = state.marker.getLatLng();
                setCoordinateMarker(container, position.lat, position.lng, state.map.getZoom());
            });
        } else {
            state.marker.setLatLng([lat, lng]);
        }
        state.map.setView([lat, lng], zoom);
    };

    const initCoordinateMaps = () => {
        if (!hasLeaflet()) return;

        document.querySelectorAll('[data-coordinate-map]').forEach((container) => {
            if (coordinateMaps.has(container)) return;

            const map = window.L.map(container).setView(readLatLng(container), 6);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            coordinateMaps.set(container, { map, marker: null });
            map.on('click', (event) => setCoordinateMarker(container, event.latlng.lat, event.latlng.lng, map.getZoom()));

            const [lat, lng] = readLatLng(container);
            const inputs = getCoordinateInputs(container);
            if (inputs.lat?.value && inputs.lng?.value) {
                setCoordinateMarker(container, lat, lng);
            }
        });
    };

    const refreshCoordinateMaps = () => {
        coordinateMaps.forEach(({ map }) => setTimeout(() => map.invalidateSize(), 80));
    };

    const bindCoordinateButtons = () => {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-set-marker]');
            if (!button) return;

            const form = button.closest('form');
            const mapContainer = form?.querySelector('[data-coordinate-map]');
            if (!mapContainer) return;

            initCoordinateMaps();
            refreshCoordinateMaps();
            const [lat, lng] = readLatLng(mapContainer);
            setCoordinateMarker(mapContainer, lat, lng);
        });
    };

    const statCard = (label, value, meta) => `
        <article class="card stat-card">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
            <small>${escapeHtml(meta)}</small>
        </article>`;

    const treeMeta = (tree) => [tree.farm_name, tree.location, tree.crop_focus].filter(Boolean).join(' · ');

    const renderAdoptableMap = (trees) => {
        const slot = root?.querySelector('[data-slot="adoptable-map"]');
        if (!slot) return;

        const mappedTrees = trees.filter((tree) => tree.map_latitude && tree.map_longitude);
        if (!hasLeaflet()) {
            slot.innerHTML = '<div class="map-placeholder">◎<small>Map library unavailable</small></div>';
            return;
        }

        if (!mappedTrees.length) {
            slot.innerHTML = '<div class="map-placeholder">◎<small>No coordinates yet</small></div>';
            return;
        }

        if (!slot.querySelector('.leaflet-map')) {
            slot.innerHTML = '<div class="leaflet-map" data-leaflet-adoptable-map></div><p class="map-note">Pins use tree coordinates when available, otherwise farm coordinates. Zoom and drag the OpenStreetMap view.</p>';
            adoptableLeafletMap = window.L.map(slot.querySelector('[data-leaflet-adoptable-map]'));
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(adoptableLeafletMap);
            adoptableMarkers = window.L.layerGroup().addTo(adoptableLeafletMap);
        }

        adoptableMarkers.clearLayers();
        const bounds = [];
        mappedTrees.forEach((tree) => {
            const lat = Number(tree.map_latitude);
            const lng = Number(tree.map_longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            bounds.push([lat, lng]);
            window.L.marker([lat, lng], { title: `${tree.species} · ${tree.farm_name}` })
                .bindPopup(`<strong>${escapeHtml(tree.species)}</strong><br>${escapeHtml(tree.farm_name)}<br><a href="${appUrl(`trees/${tree.id}/`)}">View tree</a>`)
                .addTo(adoptableMarkers);
        });

        if (bounds.length === 1) {
            adoptableLeafletMap.setView(bounds[0], 13);
        } else {
            adoptableLeafletMap.fitBounds(bounds, { padding: [28, 28], maxZoom: 14 });
        }
        setTimeout(() => adoptableLeafletMap.invalidateSize(), 80);
    };

    const renderAdoptableTrees = (trees) => {
        const slot = root?.querySelector('[data-slot="adoptable-trees"]');
        if (!slot) return;

        slot.innerHTML = trees.length ? trees.map((tree) => `
            <article class="tree-row catalog-row">
                <a href="${appUrl(`trees/${tree.id}/`)}">
                    <strong>${escapeHtml(tree.species)}</strong><br>
                    <small>${escapeHtml(treeMeta(tree))}</small><br>
                    <small>${tree.map_latitude && tree.map_longitude ? `${escapeHtml(tree.map_latitude)}, ${escapeHtml(tree.map_longitude)} · ${escapeHtml(tree.coordinate_source)} coordinates` : 'Coordinates pending'}</small>
                </a>
                <div class="row-actions">
                    <span class="badge">${escapeHtml(tree.code)}</span>
                    <button class="button" type="button" data-request-adoption="${escapeHtml(tree.id)}" ${tree.request_status === 'pending' ? 'disabled' : ''}>${tree.request_status === 'pending' ? 'Pending' : 'Request adoption'}</button>
                </div>
            </article>`).join('') : '<div class="card empty-state">No adoptable trees are available right now.</div>';
        renderAdoptableMap(trees);
    };

    const loadAdoptableTrees = async () => {
        if (!root?.querySelector('[data-slot="adoptable-trees"]')) return;
        const data = await apiFetch('/catalog/trees');
        renderAdoptableTrees(data.trees || []);
    };

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
        loadAdoptableTrees().catch(() => root.querySelector('[data-slot="adoptable-trees"]')?.insertAdjacentHTML('beforeend', '<div class="card empty-state">Unable to load adoptable trees.</div>'));
    };

    const updateFarmOptions = (farms) => {
        document.querySelectorAll('[data-farm-options]').forEach((select) => {
            select.innerHTML = farms.length ? farms.map((farm) => `
                <option value="${escapeHtml(farm.id)}">${escapeHtml(farm.name)} · ${escapeHtml(farm.location)}</option>
            `).join('') : '<option value="">Create a farm first</option>';
            select.disabled = !farms.length;
        });
    };

    const renderAdoptionRequests = (requests) => {
        const slot = root.querySelector('[data-slot="adoption-requests"]');
        if (!slot) return;

        slot.innerHTML = requests.length ? requests.map((request) => `
            <article class="tree-row request-row">
                <div>
                    <strong>${escapeHtml(request.species)} · ${escapeHtml(request.code)}</strong><br>
                    <small>${escapeHtml(request.farm_name)} · requested by ${escapeHtml(request.adopter_name || request.adopter_email || `User #${request.adopter_user_id}`)} · ${escapeHtml(request.requested_at)}</small>
                </div>
                <div class="row-actions">
                    <button class="button" type="button" data-adoption-decision="accept" data-request-id="${escapeHtml(request.id)}">Accept</button>
                    <button class="button ghost" type="button" data-adoption-decision="reject" data-request-id="${escapeHtml(request.id)}">Reject</button>
                </div>
            </article>`).join('') : '<div class="card empty-state">No pending adoption requests.</div>';
    };

    const renderFarmDashboard = (data) => {
        root.querySelector('[data-slot="stats"]').innerHTML = [
            statCard('Managed farms', data.stats.farms, 'Registered farms'),
            statCard('Available trees', data.stats.availableTrees, 'Ready for adoption'),
            statCard('Adopted trees', data.stats.adoptedTrees, 'Sponsored by clients'),
        ].join('');
        root.querySelector('[data-slot="farms"]').innerHTML = data.farms.length ? data.farms.map((farm) => `
            <div class="farm-row">
                <div><strong>${escapeHtml(farm.name)}</strong><br><small>${escapeHtml(farm.location)} · ${escapeHtml(farm.crop_focus)}${farm.latitude && farm.longitude ? ` · ${escapeHtml(farm.latitude)}, ${escapeHtml(farm.longitude)}` : ''}</small></div>
                <span class="badge">${escapeHtml(farm.tree_count)} trees · ${escapeHtml(farm.health_score)} health</span>
            </div>`).join('') : '<div class="card empty-state">No farm records yet. Use Add farm before publishing trees.</div>';
        root.querySelector('[data-slot="farm-trees"]').innerHTML = data.trees.length ? data.trees.map((tree) => `
            <a class="tree-row" href="${appUrl(`trees/${tree.id}/`)}">
                <div><strong>${escapeHtml(tree.species)}</strong><br><small>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.planted_at || 'Planting date pending')}</small></div>
                <span class="badge">${escapeHtml(tree.code)} · ${escapeHtml(tree.status)}</span>
            </a>`).join('') : '<div class="card empty-state">No trees published yet. Use Add tree to make one available for adoption.</div>';
        renderAdoptionRequests(data.requests || []);
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
                ${update.media_url ? `<img class="update-media" src="${escapeHtml(update.media_url)}" alt="${escapeHtml(update.title)}" loading="lazy">` : ''}
                ${update.media_url ? `<a class="button ghost" href="${escapeHtml(update.media_url)}">View media</a>` : ''}
            </article>`).join('') : '<div class="card empty-state">No updates have been published yet.</div>';
    };

    const renderers = {
        'client-dashboard': renderClientDashboard,
        'farm-dashboard': renderFarmDashboard,
        'tree-detail': renderTreeDetail,
        'updates-feed': (data) => renderUpdates(data.updates || []),
    };

    const loadRoot = () => {
        if (!root) return Promise.resolve();
        return apiFetch(root.dataset.agriEndpoint)
            .then((data) => renderers[root.dataset.render]?.(data))
            .catch(() => root.insertAdjacentHTML('beforeend', '<div class="card empty-state">Unable to load dashboard data.</div>'));
    };

    const showPanel = (selector) => {
        document.querySelector(selector)?.removeAttribute('hidden');
        initCoordinateMaps();
        refreshCoordinateMaps();
    };

    const bindDashboardActions = () => {
        if (!root) return;

        document.querySelector('[data-open-farm-form]')?.addEventListener('click', () => showPanel('[data-farm-form]'));
        document.querySelector('[data-open-tree-form]')?.addEventListener('click', () => showPanel('[data-tree-form]'));
        document.querySelector('[data-open-update-form]')?.addEventListener('click', () => showPanel('[data-update-form]'));

        document.addEventListener('click', async (event) => {
            const requestButton = event.target.closest('[data-request-adoption]');
            if (requestButton) {
                requestButton.disabled = true;
                await apiFetch('/adoption-requests', { method: 'POST', body: JSON.stringify({ tree_id: requestButton.dataset.requestAdoption }) });
                requestButton.textContent = 'Pending';
                loadAdoptableTrees();
                return;
            }

            const decisionButton = event.target.closest('[data-adoption-decision]');
            if (decisionButton) {
                decisionButton.disabled = true;
                await apiFetch(`/adoption-requests/${decisionButton.dataset.requestId}/${decisionButton.dataset.adoptionDecision}`, { method: 'POST', body: JSON.stringify({}) });
                loadRoot();
            }
        });

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
            const fileInput = form.querySelector('[data-photo-input]');
            const status = form.querySelector('[data-upload-status]');
            const mediaUrl = form.querySelector('[data-media-url]');

            if (fileInput?.files?.length) {
                if (status) status.textContent = 'Optimizing photo to 100 KB and saving to WordPress media…';
                const uploadData = new FormData();
                uploadData.append('photo', fileInput.files[0]);
                const upload = await apiFetch('/media/photo', { method: 'POST', body: uploadData });
                if (mediaUrl) mediaUrl.value = upload.url;
                if (status) status.textContent = `Photo optimized and uploaded (${Math.round(upload.size / 1024)} KB).`;
            }

            const payload = Object.fromEntries(new FormData(form).entries());
            delete payload.photo;
            await apiFetch('/updates', { method: 'POST', body: JSON.stringify(payload) });
            form.reset();
            window.location.href = appUrl('updates/');
        });
    };

    const bindRegistration = () => {
        const panels = document.querySelectorAll('[data-registration-panel]');
        if (!panels.length) return;

        document.querySelectorAll('[data-registration-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                const type = tab.dataset.registrationTab;
                panels.forEach((panel) => {
                    panel.hidden = panel.dataset.registrationPanel !== type;
                });
                document.querySelectorAll('[data-registration-tab]').forEach((button) => button.classList.toggle('ghost', button !== tab));
                initCoordinateMaps();
                refreshCoordinateMaps();
            });
        });

        document.querySelectorAll('[data-registration-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const status = form.querySelector('[data-form-status]');
                if (status) status.textContent = 'Creazione account in corso…';
                const payload = Object.fromEntries(new FormData(form).entries());
                payload.account_type = form.dataset.registrationForm;
                try {
                    const response = await apiFetch('/register', { method: 'POST', body: JSON.stringify(payload) });
                    if (status) status.textContent = 'Account creato. Reindirizzamento…';
                    window.location.href = response.redirect;
                } catch (error) {
                    if (status) status.textContent = error.message;
                }
            });
        });
    };

    bindCoordinateButtons();
    bindRegistration();
    bindDashboardActions();
    initCoordinateMaps();
    loadRoot();
}());
