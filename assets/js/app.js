(function () {
    if (!window.AgriSaas) return;

    const root = document.querySelector('[data-agri-endpoint]');
    const coordinateLeafletMaps = new Map();
    let adoptableLeafletMap = null;
    let farmProfileLeafletMap = null;

    const makeTileLayer = () => L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    });

    // COORDINATE PICKER (usato nei form di registrazione / aggiunta)
    const initCoordinateMaps = () => {
        document.querySelectorAll('[data-coordinate-map]').forEach((container) => {
            if (coordinateLeafletMaps.has(container)) return;

            const scope = container.closest('form') || container.parentElement || document;
            const latInput = scope.querySelector('[data-marker-lat]');
            const lngInput = scope.querySelector('[data-marker-lng]');

            const hasCoords = latInput?.value && lngInput?.value;
            const lat = Number(latInput?.value) || 41.9028;
            const lng = Number(lngInput?.value) || 12.4964;

            const map = L.map(container).setView([lat, lng], hasCoords ? 13 : 6);
            makeTileLayer().addTo(map);

            let marker = null;

            const updateInputs = (lt, lg) => {
                if (latInput) latInput.value = Number(lt).toFixed(7);
                if (lngInput) lngInput.value = Number(lg).toFixed(7);
            };

            const placeMarker = (lt, lg) => {
                if (!marker) {
                    marker = L.marker([lt, lg], { draggable: true }).addTo(map);
                    marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        updateInputs(pos.lat, pos.lng);
                    });
                } else {
                    marker.setLatLng([lt, lg]);
                }
                map.setView([lt, lg], Math.max(map.getZoom(), 13));
            };

            if (hasCoords) placeMarker(lat, lng);

            map.on('click', (e) => {
                const { lat: lt, lng: lg } = e.latlng;
                updateInputs(lt, lg);
                placeMarker(lt, lg);
            });

            coordinateLeafletMaps.set(container, { map, setMarker: placeMarker });
        });
    };

    const refreshCoordinateMaps = () => {
        coordinateLeafletMaps.forEach(({ map }) => setTimeout(() => map.invalidateSize(), 80));
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
            const state = coordinateLeafletMaps.get(mapContainer);
            if (!state) return;
            const lt = Number(form.querySelector('[data-marker-lat]')?.value) || 41.9028;
            const lg = Number(form.querySelector('[data-marker-lng]')?.value) || 12.4964;
            state.setMarker(lt, lg);
        });
    };

    // API
    const apiFetch = async (path, options = {}) => {
        const { headers: optionHeaders = {}, ...fetchOptions } = options;
        const headers = options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' };
        const response = await fetch(`${window.AgriSaas.apiBase}${path}`, {
            credentials: 'same-origin',
            ...fetchOptions,
            headers: { ...headers, 'X-WP-Nonce': window.AgriSaas.nonce, ...optionHeaders },
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `Errore API: ${response.status}`);
        return payload;
    };

    const appUrl = (path) => new URL(path.replace(/^\//, ''), window.AgriSaas.homeUrl).toString();

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;',
    }[char]));

    // BOTTONI DI CONDIVISIONE
    const shareButtons = (url, title) => {
        const eu = encodeURIComponent(url);
        const et = encodeURIComponent(title);
        return `<div class="share-bar">
            <a class="share-btn share-wa" href="https://wa.me/?text=${encodeURIComponent(title + '\n' + url)}" target="_blank" rel="noopener noreferrer" title="Condividi su WhatsApp">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                <span>WhatsApp</span></a>
            <a class="share-btn share-fb" href="https://www.facebook.com/sharer/sharer.php?u=${eu}" target="_blank" rel="noopener noreferrer" title="Condividi su Facebook">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <span>Facebook</span></a>
            <a class="share-btn share-tw" href="https://twitter.com/intent/tweet?url=${eu}&text=${et}" target="_blank" rel="noopener noreferrer" title="Condividi su X">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                <span>X</span></a>
            <button class="share-btn share-copy" type="button" data-copy-url="${escapeHtml(url)}" title="Copia link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                <span>Copia</span></button>
        </div>`;
    };

    // UTILITIES
    const statCard = (label, value, meta) => `
        <article class="card stat-card">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
            <small>${escapeHtml(meta)}</small>
        </article>`;

    const treeMeta = (tree) => [tree.farm_name, tree.location, tree.crop_focus].filter(Boolean).join(' · ');

    const plantedDisplay = (tree) => {
        const d = tree.planted_display || tree.planted_at;
        if (!d) return null;
        // Return as-is (could be "1920", "1920-03", "1920-03-15")
        return d.substring(0, 10); // trim time if present
    };

    const treeAgeYears = (tree) => {
        const d = tree.planted_display || tree.planted_at;
        if (!d) return null;
        const year = parseInt(d.substring(0, 4), 10);
        if (!year) return null;
        return new Date().getFullYear() - year;
    };

    const secolareBadge = (tree) => {
        const age = treeAgeYears(tree);
        return age !== null && age >= 100
            ? `<span class="badge-secolare">🏛️ Albero Secolare (${age} anni)</span>`
            : '';
    };

    const rewardChips = (rewards) => {
        if (!rewards || !rewards.length) return '';
        const whenLabel = (w) => ({ immediate: 'Immediato', '6m': '6 mesi', '1y': '1 anno', harvest: 'Al raccolto', annually: 'Annuale' }[w] || w);
        return `<div class="tree-rewards">
            ${rewards.map((r) => `<div class="reward-chip">
                <strong>🎁 ${escapeHtml(r.name)}</strong>
                <small>${escapeHtml(r.description.substring(0, 60))}${r.description.length > 60 ? '…' : ''}</small>
                <small>⏱ ${whenLabel(r.when_received)}${r.estimated_value ? ` · ${escapeHtml(r.estimated_value)}` : ''}</small>
            </div>`).join('')}
        </div>`;
    };

    const timeAgo = (dateStr) => {
        if (!dateStr) return '';
        const diff = Date.now() - new Date(dateStr).getTime();
        const m = Math.floor(diff / 60000);
        if (m < 1)  return 'adesso';
        if (m < 60) return `${m} min fa`;
        const h = Math.floor(m / 60);
        if (h < 24) return `${h} ore fa`;
        const d = Math.floor(h / 24);
        if (d < 30) return `${d} giorni fa`;
        const mo = Math.floor(d / 30);
        if (mo < 12) return `${mo} mesi fa`;
        return `${Math.floor(mo / 12)} anni fa`;
    };

    const visibilityLabel = (v) => ({ public: '🌐 Pubblico', followers: '👥 Follower', adopters: '🌱 Adottanti', tree_adopter: '🌳 Adottante albero' }[v] || v);

    // CLUSTER MAP HELPER
    const makeClusterMap = (containerId) => {
        const map = L.map(containerId).setView([41.9028, 12.4964], 6);
        makeTileLayer().addTo(map);
        return map;
    };

    const clearMapLayers = (map) => {
        map.eachLayer((layer) => { if (!(layer instanceof L.TileLayer)) map.removeLayer(layer); });
    };

    const makeClusterGroup = () =>
        (typeof L.markerClusterGroup === 'function')
            ? L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 60 })
            : L.layerGroup();

    // MAPPA ALBERI ADOTTABILI
    const renderAdoptableMap = (trees) => {
        const slot = root?.querySelector('[data-slot="adoptable-map"]');
        if (!slot) return;
        const mapped = trees.filter((t) => Number.isFinite(Number(t.map_latitude)) && Number.isFinite(Number(t.map_longitude)));
        if (!mapped.length) {
            slot.innerHTML = '<div class="map-placeholder">&#9678;<small>Nessuna coordinata disponibile</small></div>';
            adoptableLeafletMap = null;
            return;
        }
        if (!adoptableLeafletMap) {
            slot.innerHTML = '<div id="adoptable-leaflet-map" class="leaflet-map"></div><p class="map-note">I numeri nei cerchi indicano quanti alberi si trovano nell\'area — clicca per espandere.</p>';
            adoptableLeafletMap = makeClusterMap('adoptable-leaflet-map');
        } else {
            clearMapLayers(adoptableLeafletMap);
        }
        const cluster = makeClusterGroup();
        mapped.forEach((tree) => {
            L.marker([Number(tree.map_latitude), Number(tree.map_longitude)])
                .bindPopup(`<strong>${escapeHtml(tree.species)}</strong><br>${escapeHtml(tree.farm_name)}<br><a href="${appUrl(`trees/${tree.id}/`)}">Vedi albero →</a>`)
                .addTo(cluster);
        });
        adoptableLeafletMap.addLayer(cluster);
        try {
            mapped.length === 1
                ? adoptableLeafletMap.setView([Number(mapped[0].map_latitude), Number(mapped[0].map_longitude)], 13)
                : adoptableLeafletMap.fitBounds(cluster.getBounds ? cluster.getBounds() : mapped.map((t) => [Number(t.map_latitude), Number(t.map_longitude)]), { padding: [28, 28], maxZoom: 14 });
        } catch (_) {}
        setTimeout(() => adoptableLeafletMap.invalidateSize(), 80);
    };

    const renderAdoptableTrees = (trees) => {
        const slot = root?.querySelector('[data-slot="adoptable-trees"]');
        if (!slot) return;
        slot.innerHTML = trees.length
            ? trees.map((tree) => `
                <article class="tree-row catalog-row">
                    <a href="${appUrl(`trees/${tree.id}/`)}">
                        <strong>${escapeHtml(tree.species)}</strong><br>
                        <small>${escapeHtml(treeMeta(tree))}</small><br>
                        <small>${tree.map_latitude && tree.map_longitude ? `📍 ${escapeHtml(tree.map_latitude)}, ${escapeHtml(tree.map_longitude)}` : 'Coordinate non ancora disponibili'}</small>
                    </a>
                    <div class="row-actions">
                        <span class="badge">${escapeHtml(tree.code)}</span>
                        <button class="button" type="button" data-request-adoption="${escapeHtml(tree.id)}" ${tree.request_status === 'pending' ? 'disabled' : ''}>${tree.request_status === 'pending' ? 'In attesa' : 'Adotta'}</button>
                    </div>
                </article>`).join('')
            : '<div class="card empty-state">Nessun albero disponibile per l\'adozione al momento.</div>';
        renderAdoptableMap(trees);
    };

    const loadAdoptableTrees = async () => {
        if (!root?.querySelector('[data-slot="adoptable-trees"]')) return;
        const data = await apiFetch('/catalog/trees');
        renderAdoptableTrees(data.trees || []);
    };

    // DASHBOARD CLIENTE
    const renderClientDashboard = (data) => {
        root.querySelector('[data-slot="stats"]').innerHTML = [
            statCard('Alberi adottati', data.stats.adoptedTrees, 'Nel tuo portfolio'),
            statCard('Adozioni attive', data.stats.activeAdoptions, 'Attualmente attive'),
        ].join('');
        root.querySelector('[data-slot="trees"]').innerHTML = data.trees.length
            ? data.trees.map((tree) => {
                const planted = plantedDisplay(tree);
                const isCancelRequested = tree.adoption_status === 'cancel_requested';
                const isActive = tree.adoption_status === 'active';
                return `
                <article class="tree-row tree-row--portfolio">
                    <div class="tree-row-top">
                        <div>
                            <strong>${escapeHtml(tree.species)}</strong>${secolareBadge(tree)}<br>
                            <small>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.location)}</small>
                            ${planted ? `<br><small>🌱 Piantato: ${escapeHtml(planted)}</small>` : ''}
                        </div>
                        <span class="badge">${escapeHtml(tree.code)}</span>
                    </div>
                    ${rewardChips(tree.rewards)}
                    ${tree.adoption_id ? `<div class="adoption-cancel-row">
                        ${isCancelRequested
                            ? `<span class="badge-warning">⏳ Cancellazione in attesa di conferma dall'azienda</span>`
                            : isActive
                                ? `<button class="button ghost" type="button" style="font-size:.78rem;padding:6px 12px;" data-request-cancel="${escapeHtml(tree.adoption_id)}">Richiedi cancellazione</button>`
                                : ''}
                    </div>` : ''}
                </article>`;
            }).join('')
            : '<div class="card empty-state">Nessun albero adottato ancora.</div>';
        loadAdoptableTrees().catch(() => {
            root.querySelector('[data-slot="adoptable-trees"]')?.insertAdjacentHTML('beforeend', '<div class="card empty-state">Impossibile caricare gli alberi adottabili.</div>');
        });
    };

    let _farmsData = [];

    const updateTreeRewardOptions = (farmId) => {
        const slot = document.querySelector('[data-slot="tree-reward-options"]');
        const box  = document.querySelector('[data-tree-reward-checkboxes]');
        if (!slot || !box) return;
        const farm = _farmsData.find((f) => String(f.id) === String(farmId));
        const rewards = farm?.rewards || [];
        if (!rewards.length) { slot.style.display = 'none'; return; }
        slot.style.display = '';
        box.innerHTML = rewards.map((r) => `
            <label style="flex-direction:row;align-items:center;gap:8px;font-weight:normal;">
                <input type="checkbox" name="reward_ids[]" value="${escapeHtml(r.id)}">
                <span><strong>${escapeHtml(r.name)}</strong>${r.description ? ` — ${escapeHtml(r.description)}` : ''}</span>
            </label>`).join('');
    };

    const updateFarmOptions = (farms) => {
        _farmsData = farms;
        document.querySelectorAll('[data-farm-options]').forEach((select) => {
            select.innerHTML = farms.length
                ? farms.map((farm) => `<option value="${escapeHtml(farm.id)}">${escapeHtml(farm.name)} · ${escapeHtml(farm.location)}</option>`).join('')
                : '<option value="">Crea prima un\'azienda</option>';
            select.disabled = !farms.length;
        });
        const treeFormFarmSelect = document.querySelector('[data-agri-tree-form] [data-farm-options]');
        if (treeFormFarmSelect) {
            updateTreeRewardOptions(treeFormFarmSelect.value);
            treeFormFarmSelect.addEventListener('change', (e) => updateTreeRewardOptions(e.target.value));
        }
    };

    const renderAdoptionRequests = (requests) => {
        const slot = root.querySelector('[data-slot="adoption-requests"]');
        if (!slot) return;
        const pending = requests.filter(r => r.status === 'pending');
        const cancelRequests = requests.filter(r => r.status === 'cancel_requested');

        let html = '';

        if (cancelRequests.length) {
            html += cancelRequests.map((req) => `
                <article class="tree-row request-row" style="border-color: #ffcc02;">
                    <div>
                        <span class="badge-warning" style="margin-bottom:6px;display:inline-block;">⚠️ Richiesta di cancellazione</span><br>
                        <strong>${escapeHtml(req.species)} · ${escapeHtml(req.code)}</strong><br>
                        <small>${escapeHtml(req.farm_name)} · ${escapeHtml(req.adopter_name || req.adopter_email || `Utente #${req.adopter_user_id}`)}</small>
                    </div>
                    <div class="row-actions">
                        <button class="button" type="button" data-adoption-decision="confirm-cancel" data-request-id="${escapeHtml(req.id)}">Conferma cancellazione</button>
                        <button class="button ghost" type="button" data-adoption-decision="reject-cancel" data-request-id="${escapeHtml(req.id)}">Rifiuta cancellazione</button>
                    </div>
                </article>`).join('');
        }

        if (pending.length) {
            html += pending.map((req) => `
                <article class="tree-row request-row">
                    <div>
                        <strong>${escapeHtml(req.species)} · ${escapeHtml(req.code)}</strong><br>
                        <small>${escapeHtml(req.farm_name)} · richiesta da ${escapeHtml(req.adopter_name || req.adopter_email || `Utente #${req.adopter_user_id}`)} · ${escapeHtml(req.requested_at)}</small><br>
                        <small>${escapeHtml([req.adopter_email, req.adopter_whatsapp ? `WhatsApp ${req.adopter_whatsapp}` : '', req.adopter_phone ? `Tel ${req.adopter_phone}` : ''].filter(Boolean).join(' · '))}</small>
                    </div>
                    <div class="row-actions">
                        <button class="button" type="button" data-adoption-decision="accept" data-request-id="${escapeHtml(req.id)}">Accetta</button>
                        <button class="button ghost" type="button" data-adoption-decision="reject" data-request-id="${escapeHtml(req.id)}">Rifiuta</button>
                    </div>
                </article>`).join('');
        }

        slot.innerHTML = html || '<div class="card empty-state">Nessuna richiesta in sospeso.</div>';
    };

    // DASHBOARD AZIENDA
    const renderFarmDashboard = (data) => {
        root.querySelector('[data-slot="stats"]').innerHTML = [
            statCard('Aziende gestite', data.stats.farms, 'Aziende registrate'),
            statCard('Alberi disponibili', data.stats.availableTrees, "Pronti per l'adozione"),
            statCard('Alberi adottati', data.stats.adoptedTrees, 'Sponsorizzati dai clienti'),
        ].join('');
        root.querySelector('[data-slot="farms"]').innerHTML = data.farms.length
            ? data.farms.map((farm) => `
                <div class="farm-row">
                    <div>
                        <strong><a href="${appUrl(`farms/${farm.id}/`)}">${escapeHtml(farm.name)}</a></strong><br>
                        <small>${escapeHtml(farm.location)} · ${escapeHtml(farm.crop_focus)}${farm.latitude && farm.longitude ? ` · 📍 ${escapeHtml(farm.latitude)}, ${escapeHtml(farm.longitude)}` : ''}</small>
                    </div>
                    <div class="farm-row-end">
                        <span class="badge">${escapeHtml(farm.tree_count)} alberi · ${escapeHtml(farm.health_score)} salute</span>
                        ${shareButtons(appUrl(`farms/${farm.id}/`), `🌾 ${farm.name} — Adotta un albero!`)}
                    </div>
                </div>`).join('')
            : '<div class="card empty-state">Nessuna azienda registrata. Aggiungi un\'azienda prima di pubblicare alberi.</div>';
        root.querySelector('[data-slot="farm-trees"]').innerHTML = data.trees.length
            ? data.trees.map((tree) => `
                <a class="tree-row" href="${appUrl(`trees/${tree.id}/`)}">
                    <div><strong>${escapeHtml(tree.species)}</strong><br><small>${escapeHtml(tree.farm_name)} · ${escapeHtml(plantedDisplay(tree) || 'Data di messa a dimora non disponibile')}</small></div>
                    <span class="badge">${escapeHtml(tree.code)} · ${escapeHtml(tree.status)}</span>
                </a>`).join('')
            : '<div class="card empty-state">Nessun albero pubblicato. Usa "+ Albero" per renderlo disponibile.</div>';
        renderAdoptionRequests(data.requests || []);
        const rewardsByFarm = {};
        (data.rewards || []).forEach((r) => {
            if (!rewardsByFarm[r.farm_id]) rewardsByFarm[r.farm_id] = [];
            rewardsByFarm[r.farm_id].push(r);
        });
        const farmsWithRewards = (data.farms || []).map((f) => ({ ...f, rewards: rewardsByFarm[f.id] || [] }));
        updateFarmOptions(farmsWithRewards);
    };

    // DETTAGLIO ALBERO
    const renderTreeDetail = (data) => {
        const tree = data.tree;
        const treeUrl = appUrl(`trees/${tree.id}/`);
        root.querySelector('[data-slot="tree"]').innerHTML = `
            <p class="eyebrow">${escapeHtml(tree.code)}</p>
            <h2>${escapeHtml(tree.species)}</h2>
            <p>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.location)} · ${escapeHtml(tree.crop_focus)}</p>
            <div class="stats-grid">
                ${statCard('Stato', tree.status, 'Ciclo di vita')}
                ${statCard('Messo a dimora', plantedDisplay(tree) || 'N/D', 'Data di messa a dimora')}
            </div>
            ${secolareBadge(tree) ? `<p style="margin-top:12px;">${secolareBadge(tree)}</p>` : ''}
            <div class="share-section">
                <span class="share-label">Condividi questo albero:</span>
                ${shareButtons(treeUrl, `🌳 ${tree.species} — Adotta un albero su Adotta!`)}
            </div>
            ${data.rewards && data.rewards.length ? `
            <div class="share-section" style="flex-direction:column;align-items:flex-start;">
                <span class="share-label">🎁 Premi inclusi in questa adozione:</span>
                ${rewardChips(data.rewards)}
            </div>` : ''}`;
        renderUpdates(data.updates || [], tree.farm_id);
    };

    // FEED STILE INSTAGRAM
    const renderUpdates = (updates, contextFarmId = null) => {
        const slot = root?.querySelector('[data-slot="updates"]');
        if (!slot) return;
        if (!updates.length) {
            slot.className = 'timeline';
            slot.innerHTML = '<div class="card empty-state">Nessun aggiornamento pubblicato ancora.</div>';
            return;
        }
        slot.className = 'ig-feed';
        slot.innerHTML = updates.map((update) => {
            const farmId = update.farm_id || contextFarmId;
            const updateUrl = farmId
                ? appUrl(`farms/${farmId}/`) + `#update-${update.id}`
                : appUrl('updates/') + `#update-${update.id}`;
            return `
            <article class="ig-card" id="update-${escapeHtml(update.id)}">
                <div class="ig-card-header">
                    <div class="ig-avatar">🌿</div>
                    <div class="ig-card-meta">
                        <strong class="ig-farm-name">${escapeHtml(update.farm_name || 'Azienda')}</strong>
                        <span class="ig-timestamp">${timeAgo(update.created_at)}</span>
                    </div>
                    <span class="ig-visibility">${visibilityLabel(update.visibility)}</span>
                </div>
                ${update.media_url ? `<div class="ig-card-img-wrap"><img class="ig-card-img" src="${escapeHtml(update.media_url)}" alt="${escapeHtml(update.title)}" loading="lazy"></div>` : ''}
                <div class="ig-card-body">
                    <p class="ig-card-title">${escapeHtml(update.title)}</p>
                    <p class="ig-card-text">${escapeHtml(update.body)}</p>
                    ${update.tree_code ? `<span class="ig-tag">🌱 ${escapeHtml(update.tree_code)}</span>` : ''}
                </div>
                <div class="ig-card-footer">
                    ${shareButtons(updateUrl, `📰 ${update.title} — Adotta un albero su Adotta!`)}
                </div>
            </article>`;
        }).join('');
    };

    // MAPPA PROFILO AZIENDA (cluster)
    const renderFarmProfileMap = (trees) => {
        const slot = root?.querySelector('[data-slot="farm-profile-map"]');
        if (!slot) return;
        const mapped = trees.filter((t) => Number.isFinite(Number(t.map_latitude)) && Number.isFinite(Number(t.map_longitude)));
        if (!mapped.length) {
            slot.innerHTML = '<div class="map-placeholder">&#9678;<small>Nessuna coordinata per gli alberi</small></div>';
            farmProfileLeafletMap = null;
            return;
        }
        if (!farmProfileLeafletMap) {
            slot.innerHTML = '<div id="farm-profile-leaflet-map" class="leaflet-map"></div><p class="map-note">I numeri nei cerchi indicano cluster — clicca per espandere e vedere i singoli alberi.</p>';
            farmProfileLeafletMap = makeClusterMap('farm-profile-leaflet-map');
        } else {
            clearMapLayers(farmProfileLeafletMap);
        }
        const cluster = makeClusterGroup();
        mapped.forEach((tree) => {
            L.marker([Number(tree.map_latitude), Number(tree.map_longitude)])
                .bindPopup(`<strong>${escapeHtml(tree.species)}</strong><br>${escapeHtml(tree.code)} · ${escapeHtml(tree.status)}<br><a href="${appUrl(`trees/${tree.id}/`)}">Vedi albero →</a>`)
                .addTo(cluster);
        });
        farmProfileLeafletMap.addLayer(cluster);
        try {
            mapped.length === 1
                ? farmProfileLeafletMap.setView([Number(mapped[0].map_latitude), Number(mapped[0].map_longitude)], 14)
                : farmProfileLeafletMap.fitBounds(cluster.getBounds ? cluster.getBounds() : mapped.map((t) => [Number(t.map_latitude), Number(t.map_longitude)]), { padding: [28, 28], maxZoom: 15 });
        } catch (_) {}
        setTimeout(() => farmProfileLeafletMap.invalidateSize(), 80);
    };

    const contactButton = (href, label) => href ? `<a class="button ghost" href="${escapeHtml(href)}">${escapeHtml(label)}</a>` : '';

    // PROFILO AZIENDA
    const renderFarmProfile = (data) => {
        const farm = data.farm;
        const farmUrl = appUrl(`farms/${farm.id}/`);
        root.querySelector('[data-slot="farm-title"]').textContent = farm.name;
        root.querySelector('[data-slot="farm-summary"]').innerHTML =
            `${escapeHtml(farm.location)} · ${escapeHtml(farm.crop_focus || 'Produzione mista')}<br>${escapeHtml(farm.description || "Questa azienda usa il suo profilo come vetrina pubblica per alberi, foto e aggiornamenti dal campo.")}`;
        root.querySelector('[data-slot="farm-contacts"]').innerHTML = [
            contactButton(farm.contact_email  ? `mailto:${farm.contact_email}` : '', '📧 Email'),
            contactButton(farm.contact_whatsapp ? `https://wa.me/${String(farm.contact_whatsapp).replace(/\D/g, '')}` : '', '💬 WhatsApp'),
            contactButton(farm.contact_phone  ? `tel:${farm.contact_phone}` : '', '📞 Telefono'),
        ].join('') || '<span class="badge">Contatti in arrivo</span>';

        const followButton = root.querySelector('[data-follow-farm]');
        if (followButton) {
            followButton.hidden = false;
            followButton.dataset.farmId = farm.id;
            followButton.dataset.following = data.isFollowing ? '1' : '0';
            followButton.textContent = data.canFollow
                ? (data.isFollowing ? 'Stai seguendo ✓' : 'Segui azienda')
                : 'Accedi per seguire';
            followButton.classList.toggle('ghost', Boolean(data.isFollowing));
            followButton.dataset.loginUrl = data.loginUrl || '';
        }

        const heroActions = root.querySelector('.farm-hero-actions');
        if (heroActions && !heroActions.querySelector('.share-bar')) {
            heroActions.insertAdjacentHTML('beforeend', shareButtons(farmUrl, `🌾 ${farm.name} — Adotta un albero su Adotta!`));
        }

        root.querySelector('[data-slot="farm-profile-stats"]').innerHTML = [
            statCard('Alberi', data.stats.trees, 'Tutti visibili nella vetrina'),
            statCard('Adottati', data.stats.adoptedTrees, 'Già sponsorizzati'),
            statCard('Follower', data.stats.followers, 'Clienti che seguono gli aggiornamenti'),
        ].join('');

        root.querySelector('[data-slot="farm-profile-trees"]').innerHTML = data.trees.length
            ? data.trees.map((tree) => `
                <a class="tree-row" href="${appUrl(`trees/${tree.id}/`)}">
                    <div>
                        <strong>${escapeHtml(tree.species)}</strong><br>
                        <small>${escapeHtml(tree.code)} · ${escapeHtml(tree.planted_at || 'Data N/D')} · coord. ${escapeHtml(tree.coordinate_source || 'azienda')}</small>
                    </div>
                    <span class="badge">${escapeHtml(tree.status)}${tree.adopter_name ? ` · ${escapeHtml(tree.adopter_name)}` : ''}</span>
                </a>`).join('')
            : '<div class="card empty-state">Nessun albero pubblicato da questa azienda.</div>';

        root.querySelector('[data-slot="farm-photos"]').innerHTML = data.photos.length
            ? data.photos.slice(0, 6).map((url) => `<a href="${escapeHtml(url)}"><img src="${escapeHtml(url)}" alt="Foto dell'azienda" loading="lazy"></a>`).join('')
            : '<div class="card empty-state">Nessuna foto ancora.</div>';

        renderFarmProfileMap(data.trees || []);
        renderUpdates(data.updates || [], farm.id);
    };

    // HANDLER COPIA LINK
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-copy-url]');
        if (!btn) return;
        navigator.clipboard.writeText(btn.dataset.copyUrl).then(() => {
            const svgHtml = btn.querySelector('svg')?.outerHTML || '';
            const original = btn.innerHTML;
            btn.innerHTML = svgHtml + '<span>✓ Copiato!</span>';
            btn.classList.add('share-btn--copied');
            setTimeout(() => { btn.innerHTML = original; btn.classList.remove('share-btn--copied'); }, 2200);
        });
    });

    const renderers = {
        'client-dashboard': renderClientDashboard,
        'farm-dashboard':   renderFarmDashboard,
        'tree-detail':      renderTreeDetail,
        'updates-feed':     (data) => renderUpdates(data.updates || []),
        'farm-profile':     renderFarmProfile,
    };

    const loadRoot = () => {
        if (!root) return Promise.resolve();
        return apiFetch(root.dataset.agriEndpoint)
            .then((data) => renderers[root.dataset.render]?.(data))
            .catch(() => root.insertAdjacentHTML('beforeend', '<div class="card empty-state">Impossibile caricare i dati. Ricarica la pagina.</div>'));
    };

    const showPanel = (selector) => {
        document.querySelector(selector)?.removeAttribute('hidden');
        initCoordinateMaps();
        refreshCoordinateMaps();
    };

    const bindDashboardActions = () => {
        if (!root) return;
        document.querySelector('[data-open-farm-form]')?.addEventListener('click',   () => showPanel('[data-farm-form]'));
        document.querySelector('[data-open-tree-form]')?.addEventListener('click',   () => showPanel('[data-tree-form]'));
        document.querySelector('[data-open-update-form]')?.addEventListener('click', () => showPanel('[data-update-form]'));

        document.addEventListener('click', async (event) => {
            const requestButton = event.target.closest('[data-request-adoption]');
            if (requestButton) {
                requestButton.disabled = true;
                await apiFetch('/adoption-requests', { method: 'POST', body: JSON.stringify({ tree_id: requestButton.dataset.requestAdoption }) });
                requestButton.textContent = 'In attesa';
                loadAdoptableTrees();
                return;
            }
            const followButton = event.target.closest('[data-follow-farm]');
            if (followButton) {
                if (!window.AgriSaas.userId) { window.location.href = followButton.dataset.loginUrl || appUrl(''); return; }
                followButton.disabled = true;
                const method = followButton.dataset.following === '1' ? 'DELETE' : 'POST';
                await apiFetch(`/farms/${followButton.dataset.farmId}/follow`, { method, body: JSON.stringify({}) });
                await loadRoot();
                followButton.disabled = false;
                return;
            }
            const cancelButton = event.target.closest('[data-request-cancel]');
            if (cancelButton) {
                if (!confirm('Sei sicuro di voler richiedere la cancellazione di questa adozione? Il gestore dell\'azienda dovrà confermare.')) return;
                cancelButton.disabled = true;
                await apiFetch(`/adoption-requests/${cancelButton.dataset.requestCancel}/request-cancel`, { method: 'POST', body: JSON.stringify({}) });
                loadRoot();
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
            const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
            await apiFetch('/farms', { method: 'POST', body: JSON.stringify(payload) });
            window.location.reload();
        });

        document.querySelector('[data-agri-tree-form]')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const statusEl    = form.querySelector('[data-tree-form-status]');
            const uploadStatus = form.querySelector('[data-tree-upload-status]');
            const photoInput  = form.querySelector('[data-tree-photo-input]');
            const mediaUrlEl  = form.querySelector('[data-tree-media-url]');
            const submitBtn   = form.querySelector('[type="submit"]');

            if (statusEl) statusEl.textContent = '';
            submitBtn.disabled = true;

            try {
                if (photoInput?.files?.length) {
                    if (uploadStatus) uploadStatus.textContent = 'Ottimizzazione foto a 100 KB…';
                    const uploadData = new FormData();
                    uploadData.append('photo', photoInput.files[0]);
                    const upload = await apiFetch('/media/photo', { method: 'POST', body: uploadData });
                    if (mediaUrlEl) mediaUrlEl.value = upload.url;
                    if (uploadStatus) uploadStatus.textContent = `Foto caricata (${Math.round(upload.size / 1024)} KB).`;
                }

                const formData = new FormData(form);
                const payload = Object.fromEntries(formData.entries());
                delete payload.tree_photo;
                const rewardIds = formData.getAll('reward_ids[]').map(Number).filter(Boolean);
                if (rewardIds.length) payload.reward_ids = rewardIds;

                await apiFetch('/trees', { method: 'POST', body: JSON.stringify(payload) });
                window.location.reload();
            } catch (err) {
                if (statusEl) statusEl.textContent = err.message || 'Errore durante la pubblicazione. Riprova.';
                submitBtn.disabled = false;
            }
        });

        document.querySelector('[data-agri-update-form]')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const fileInput = form.querySelector('[data-photo-input]');
            const status   = form.querySelector('[data-upload-status]');
            const mediaUrl = form.querySelector('[data-media-url]');
            if (fileInput?.files?.length) {
                if (status) status.textContent = 'Ottimizzazione foto a 100 KB e salvataggio nella libreria media…';
                const uploadData = new FormData();
                uploadData.append('photo', fileInput.files[0]);
                const upload = await apiFetch('/media/photo', { method: 'POST', body: uploadData });
                if (mediaUrl) mediaUrl.value = upload.url;
                if (status) status.textContent = `Foto ottimizzata e caricata (${Math.round(upload.size / 1024)} KB).`;
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
                panels.forEach((panel) => { panel.hidden = panel.dataset.registrationPanel !== type; });
                document.querySelectorAll('[data-registration-tab]').forEach((btn) => btn.classList.toggle('ghost', btn !== tab));
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
