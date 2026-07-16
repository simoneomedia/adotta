(function () {
    if (!window.AgriSaas) return;

    // ── Auth modal (for unauthenticated actions) ─────────────────
    const _injectAuthModal = () => {
        if (document.getElementById('auth-modal')) return;
        const loginUrl = `${window.AgriSaas.homeUrl}login/`;
        const logoUrl  = `${window.AgriSaas.homeUrl}wp-content/uploads/2026/06/icon-light.png`;
        document.body.insertAdjacentHTML('beforeend', `
        <dialog class="agri-modal auth-modal" id="auth-modal">
            <div class="auth-modal-inner">
                <button class="auth-modal-close" data-close-modal aria-label="Chiudi">✕</button>
                <div class="auth-modal-brand">
                    <img src="${logoUrl}" alt="wido" width="36" height="36" style="border-radius:9px;">
                    <span>wido.</span>
                </div>
                <h2 class="auth-modal-title">Il tuo pezzo di terra.</h2>
                <p class="auth-modal-sub">Scegli come vuoi registrarti, oppure accedi al tuo account</p>
                <div class="auth-modal-options">
                    <a class="auth-option" href="${window.AgriSaas.homeUrl}?type=client">
                        <span class="auth-option-icon">🌱</span>
                        <div class="auth-option-text">
                            <strong>Sono un Utente</strong>
                            <small>Adotta elementi, segui il loro percorso, ricevi prodotti dal produttore</small>
                        </div>
                        <span class="auth-option-arrow">→</span>
                    </a>
                    <a class="auth-option" href="${window.AgriSaas.homeUrl}?type=farm">
                        <span class="auth-option-icon">🚜</span>
                        <div class="auth-option-text">
                            <strong>Sono un Agricoltore</strong>
                            <small>Pubblica elementi adottabili, condividi aggiornamenti, connettiti con i sostenitori</small>
                        </div>
                        <span class="auth-option-arrow">→</span>
                    </a>
                </div>
                <div class="auth-modal-login">
                    <a href="${loginUrl}">Ho già un account — Accedi →</a>
                </div>
            </div>
        </dialog>`);
    };
    const showAuthModal = () => {
        _injectAuthModal();
        const modal = document.getElementById('auth-modal');
        if (modal && typeof modal.showModal === 'function') modal.showModal();
    };

    const root = document.querySelector('[data-agri-endpoint]');
    const coordinateLeafletMaps = new Map();
    let adoptableLeafletMap = null;
    let farmProfileLeafletMap = null;

    // ── Geolocation ───────────────────────────────────────────────────
    let _userLat = null;
    let _userLng = null;
    const _geoCallbacks = [];
    const _onGeo = (cb) => { if (_userLat !== null) cb(_userLat, _userLng); else _geoCallbacks.push(cb); };
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                _userLat = pos.coords.latitude;
                _userLng = pos.coords.longitude;
                _geoCallbacks.forEach((cb) => cb(_userLat, _userLng));
                _geoCallbacks.length = 0;
            },
            () => {},
            { enableHighAccuracy: false, timeout: 8000 }
        );
    }
    const _haversineKm = (lat1, lng1, lat2, lng2) => {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    };
    const _distLabel = (itemLat, itemLng) => {
        if (_userLat === null || !itemLat || !itemLng) return '';
        const km = _haversineKm(_userLat, _userLng, Number(itemLat), Number(itemLng));
        return `<small class="dist-label">📍 ${km < 1 ? `${Math.round(km * 1000)} m` : `${km.toFixed(1)} km`} da te</small>`;
    };

    // ── Tile layers ───────────────────────────────────────────────────
    const makeSatelliteTileLayer = () => L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { attribution: 'Tiles &copy; Esri', maxZoom: 19 }
    );
    const makeStreetTileLayer = () => L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    });
    const makeTileLayer = makeSatelliteTileLayer;

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

            const _sat = makeSatelliteTileLayer();
            const _str = makeStreetTileLayer();
            const map = L.map(container, { layers: [_sat] }).setView([lat, lng], hasCoords ? 13 : 6);
            L.control.layers({ '🛰 Satellite': _sat, '🗺 Mappa': _str }, {}, { position: 'topright' }).addTo(map);

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
    const bust = (path) => `${path}${path.includes('?') ? '&' : '?'}_ts=${Date.now()}`;

    const apiFetch = async (path, options = {}) => {
        const { headers: optionHeaders = {}, ...fetchOptions } = options;
        const headers = options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' };
        const response = await fetch(`${window.AgriSaas.apiBase}${bust(path)}`, {
            cache: 'no-store',
            credentials: 'same-origin',
            ...fetchOptions,
            headers: { ...headers, 'X-WP-Nonce': window.AgriSaas.nonce, ...optionHeaders },
        });
        const rawText = await response.text();
        let payload = null;
        try { payload = JSON.parse(rawText); } catch(_) {
            // Salvage JSON preceded by PHP warnings/notices printed before the body
            const start = Math.min(...['{', '['].map((c) => { const i = rawText.indexOf(c); return i === -1 ? Infinity : i; }));
            if (start !== Infinity) {
                try { payload = JSON.parse(rawText.slice(start)); payload.__salvaged = rawText.slice(0, start).slice(0, 300); console.warn('[AgriSaas] JSON salvaged for', path, '— leading garbage:', payload.__salvaged); } catch(_) {}
            }
        }
        if (payload === null) {
            console.error('[AgriSaas] JSON parse failed for', path, '— raw response:', rawText.slice(0, 500));
            if (response.ok) throw new Error(`Risposta API non valida (non-JSON): ${rawText.slice(0, 200)}`);
            payload = {};
        }
        if (!response.ok) throw new Error(payload.message || `Errore API: ${response.status}`);
        if (!payload || typeof payload !== 'object') throw new Error(`Risposta non valida dall'API: ${rawText.slice(0, 120)}`);
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
    let _statCardIdx = 0;
    const _statPalettes = [
        'background:linear-gradient(135deg,#e8f5e9,#c8e6c9);color:#1b5e20;',
        'background:linear-gradient(135deg,#fffde7,#fff9c4);color:#5c4200;',
        'background:linear-gradient(135deg,#e3f2fd,#bbdefb);color:#0d47a1;',
        'background:linear-gradient(135deg,#fce4ec,#f8bbd0);color:#880e4f;',
    ];
    const statCard = (label, value, meta) => {
        const style = _statPalettes[_statCardIdx++ % _statPalettes.length];
        return `
        <article class="card stat-card" style="${style}">
            <span style="color:currentColor;opacity:.7">${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
            <small style="opacity:.7">${escapeHtml(meta)}</small>
        </article>`;
    };

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
            ? `<span class="badge-secolare">🏛️ Elemento Secolare (${age} anni)</span>`
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

    const visibilityLabel = (v) => ({ public: '🌐 Pubblico', followers: '👥 Follower', adopters: '🌱 Adottanti', tree_adopter: '🌳 Adottante elemento' }[v] || v);

    // TYPE-SPECIFIC MAP ICONS
    const _TYPE_ICONS = {
        albero:   '🌳',
        olivo:    '🫒',
        vite:     '🍇',
        alveare:  '🐝',
        animale:  '🐄',
        bosco:    '🌲',
        terreno:  '🌾',
        orto:     '🥦',
        _shop:    '🛒',
        _barter:  '🤝',
        _farm:    '🏡',
    };
    const _makeTypeIcon = (type) => {
        const emoji = _TYPE_ICONS[type] || '🌿';
        return L.divIcon({
            html: `<div style="font-size:22px;line-height:1;filter:drop-shadow(0 1px 2px rgba(0,0,0,.4));">${emoji}</div>`,
            className: '',
            iconSize: [30, 30],
            iconAnchor: [15, 28],
            popupAnchor: [0, -28],
        });
    };

    // CLUSTER MAP HELPER
    const makeClusterMap = (containerId) => {
        const satellite = makeSatelliteTileLayer();
        const street    = makeStreetTileLayer();
        const map = L.map(containerId, { layers: [satellite] }).setView([41.9028, 12.4964], 6);
        L.control.layers({ '🛰 Satellite': satellite, '🗺 Mappa': street }, {}, { position: 'topright' }).addTo(map);
        // User location marker
        _onGeo((lat, lng) => {
            L.circleMarker([lat, lng], { radius: 8, color: '#2563EB', fillColor: '#3B82F6', fillOpacity: 0.9, weight: 2 })
                .bindPopup('📍 La tua posizione')
                .addTo(map);
        });
        return map;
    };

    const clearMapLayers = (map) => {
        map.eachLayer((layer) => { if (!(layer instanceof L.TileLayer)) map.removeLayer(layer); });
    };

    const makeClusterGroup = () =>
        (typeof L.markerClusterGroup === 'function')
            ? L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 60 })
            : L.layerGroup();

    let _adoptableMapTrees = [];

    const _initAdoptableLeafletMap = () => {
        const slot = root?.querySelector('[data-slot="adoptable-map"]');
        if (!slot) return;
        const mapped = _adoptableMapTrees;
        if (!mapped.length) return;
        if (!adoptableLeafletMap) {
            slot.innerHTML = '<div id="adoptable-leaflet-map" class="leaflet-map"></div><p class="map-note">I numeri nei cerchi indicano quanti elementi si trovano nell\'area — clicca per espandere.</p>';
            adoptableLeafletMap = makeClusterMap('adoptable-leaflet-map');
        } else {
            clearMapLayers(adoptableLeafletMap);
        }
        const cluster = makeClusterGroup();
        mapped.forEach((tree) => {
            L.marker([Number(tree.map_latitude), Number(tree.map_longitude)], { icon: _makeTypeIcon(tree.type) })
                .bindPopup(`<strong>${escapeHtml(tree.species)}</strong><br>${escapeHtml(tree.farm_name)}<br><a href="${appUrl(`trees/${tree.id}/`)}">Vedi elemento →</a>`)
                .addTo(cluster);
        });
        adoptableLeafletMap.addLayer(cluster);
        setTimeout(() => {
            adoptableLeafletMap.invalidateSize();
            try {
                mapped.length === 1
                    ? adoptableLeafletMap.setView([Number(mapped[0].map_latitude), Number(mapped[0].map_longitude)], 13)
                    : adoptableLeafletMap.fitBounds(cluster.getBounds ? cluster.getBounds() : mapped.map((t) => [Number(t.map_latitude), Number(t.map_longitude)]), { padding: [28, 28], maxZoom: 14 });
            } catch (_) {}
        }, 150);
    };

    // MAPPA ALBERI ADOTTABILI
    const renderAdoptableMap = (trees) => {
        const slot = root?.querySelector('[data-slot="adoptable-map"]');
        if (!slot) return;
        const mapped = trees.filter((t) => Number.isFinite(Number(t.map_latitude)) && Number.isFinite(Number(t.map_longitude)));
        _adoptableMapTrees = mapped;
        if (!mapped.length) {
            slot.innerHTML = '<div class="map-placeholder">&#9678;<small>Nessuna coordinata disponibile</small></div>';
            adoptableLeafletMap = null;
            return;
        }
        // If the map panel is currently visible, initialize immediately; otherwise defer to toggle click
        const panel = slot.closest('[data-catalog-panel]');
        if (!panel || !panel.hidden) {
            _initAdoptableLeafletMap();
        } else {
            slot.innerHTML = '<div class="map-placeholder">&#9678;</div>'; // placeholder until panel shown
        }
    };

    const renderAdoptableTrees = (trees) => {
        const slot = root?.querySelector('[data-slot="adoptable-trees"]');
        if (!slot) return;
        slot.innerHTML = trees.length
            ? trees.map((tree) => `
                <article class="tree-row catalog-row catalog-row--card">
                    <div class="catalog-row-img"><img src="${escapeHtml(tree.media_url || tree.farm_photo || 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png')}" alt="${escapeHtml(tree.species)}" loading="lazy" onerror="this.onerror=null;this.src='https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';this.style.objectFit='contain';this.style.padding='8px';this.style.background='var(--surface-soft)'"></div>
                    <div class="catalog-row-body">
                        <strong>${escapeHtml(tree.species)}</strong>
                        <small>${escapeHtml(treeMeta(tree))}</small>
                        ${tree.map_latitude && tree.map_longitude ? `<small class="catalog-row-loc">📍 ${escapeHtml(tree.location || tree.farm_name)}</small>` : ''}
                        ${_distLabel(tree.map_latitude, tree.map_longitude)}
                    </div>
                    <div class="row-actions">
                        <span class="badge">${escapeHtml(tree.code)}</span>
                        <a class="button" href="${appUrl(`trees/${tree.id}/`)}">Info</a>
                    </div>
                </article>`).join('')
            : '<div class="card empty-state">Nessun elemento disponibile per l\'adozione al momento.</div>';
        renderAdoptableMap(trees);
    };

    // ── Catalogo filtri ───────────────────────────────────────────────
    const _CATALOG_TYPES = [
        { type: null,      label: 'Tutti',    icon: '🌿' },
        { type: 'albero',  label: 'Elementi',   icon: '🌳' },
        { type: 'olivo',   label: 'Olivi',    icon: '🫒' },
        { type: 'vite',    label: 'Viti',     icon: '🍇' },
        { type: 'orto',    label: 'Orti',     icon: '🥦' },
        { type: 'alveare', label: 'Alveari',  icon: '🐝' },
        { type: 'bosco',   label: 'Boschi',   icon: '🌲' },
        { type: 'terreno', label: 'Terreni',  icon: '🏡' },
        { type: 'animale', label: 'Animali',  icon: '🐄' },
        { type: 'altro',   label: 'Altro',    icon: '✨' },
    ];
    let _activeTypeFilter = null;

    const renderCatalogFilter = (trees) => {
        const slot = root?.querySelector('[data-slot="catalog-filter"]');
        if (!slot) return;
        // Only show types that actually exist in the data
        const presentTypes = new Set(trees.map((t) => t.type || 'altro'));
        const visible = _CATALOG_TYPES.filter((c) => c.type === null || presentTypes.has(c.type));
        if (visible.length <= 2) { slot.innerHTML = ''; return; } // no point showing just "Tutti" + 1 type
        slot.innerHTML = visible.map((c) => `
            <button class="catalog-filter-chip${_activeTypeFilter === c.type ? ' active' : ''}"
                    data-filter-type="${c.type ?? ''}" type="button">
                <span>${c.icon}</span> ${c.label}
                ${c.type !== null ? `<span class="filter-count">${trees.filter((t) => (t.type || 'altro') === c.type).length}</span>` : ''}
            </button>`).join('');
    };

    let _lastAdoptableTrees = null;
    const _filteredTrees = () => {
        if (!_lastAdoptableTrees) return [];
        if (_activeTypeFilter === null) return _lastAdoptableTrees;
        return _lastAdoptableTrees.filter((t) => (t.type || 'altro') === _activeTypeFilter);
    };

    const loadAdoptableTrees = async () => {
        if (!root?.querySelector('[data-slot="adoptable-trees"]') && !root?.querySelector('[data-slot="adoptable-map"]')) return;
        const data = await apiFetch('/catalog/trees');
        _lastAdoptableTrees = data.trees || [];
        renderCatalogFilter(_lastAdoptableTrees);
        renderAdoptableTrees(_filteredTrees());
        // Re-render when geolocation arrives to show distances
        _onGeo(() => { if (_lastAdoptableTrees) renderAdoptableTrees(_filteredTrees()); });
    };

    // Filter chip click
    document.addEventListener('click', (e) => {
        const chip = e.target.closest('[data-filter-type]');
        if (!chip || !chip.classList.contains('catalog-filter-chip')) return;
        _activeTypeFilter = chip.dataset.filterType || null;
        renderCatalogFilter(_lastAdoptableTrees || []);
        renderAdoptableTrees(_filteredTrees());
    });

    // ── Sistema livelli "Radici" ──────────────────────────────────────
    const _LEVELS = [
        { min: 0,  name: 'Seme',               icon: '🌱', color: '#A8D5A2', text: '#1B5E20' },
        { min: 1,  name: 'Germoglio',           icon: '🌿', color: '#B2DFDB', text: '#00695C' },
        { min: 3,  name: 'Custode della terra', icon: '🌳', color: '#C8E6C9', text: '#2E7D32' },
        { min: 6,  name: 'Eroe della campagna', icon: '🌾', color: '#FFF9C4', text: '#F57F17' },
        { min: 10, name: 'Anima del bosco',     icon: '🏡', color: '#D7CCC8', text: '#4E342E' },
    ];
    const _getLevel = (active) => {
        let lvl = _LEVELS[0];
        for (const l of _LEVELS) { if (active >= l.min) lvl = l; }
        return lvl;
    };
    const _getNextLevel = (active) => _LEVELS.find((l) => l.min > active) || null;
    const renderLevelBadge = (activeAdoptions) => {
        const slot = root?.querySelector('[data-slot="level-badge"]');
        if (!slot) return;
        const lvl  = _getLevel(activeAdoptions);
        const next = _getNextLevel(activeAdoptions);
        const idx  = _LEVELS.indexOf(lvl) + 1;
        const pct  = next ? Math.round(((activeAdoptions - lvl.min) / (next.min - lvl.min)) * 100) : 100;
        slot.innerHTML = `
        <div class="level-badge" style="background:${lvl.color};color:${lvl.text};">
            <span class="level-icon">${lvl.icon}</span>
            <div class="level-info">
                <span class="level-label">Livello ${idx} · ${lvl.name}</span>
                ${next
                    ? `<div class="level-progress-wrap">
                        <div class="level-progress-bar" style="width:${pct}%;background:${lvl.text};opacity:.6;"></div>
                       </div>
                       <span class="level-next">ancora ${next.min - activeAdoptions} adozione${next.min - activeAdoptions !== 1 ? 'i' : ''} per diventare <strong>${next.icon} ${next.name}</strong></span>`
                    : `<span class="level-next">🏆 Livello massimo raggiunto!</span>`
                }
            </div>
        </div>`;
    };

    const _dashInlineMap = (containerId, items, makeMarker) => {
        // reuse makeClusterMap
        const map = makeClusterMap(containerId);
        const cluster = makeClusterGroup();
        items.forEach((item) => {
            if (Number.isFinite(Number(item.map_latitude)) && Number.isFinite(Number(item.map_longitude))) {
                makeMarker(item).addTo(cluster);
            }
        });
        map.addLayer(cluster);
        setTimeout(() => map.invalidateSize(), 80);
        return map;
    };

    const renderMercatoInline = (products, mapSlot, listSlot) => {
        const WIDO_PH = 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';
        if (!products.length) {
            if (mapSlot) mapSlot.innerHTML = '<div class="map-placeholder"><small>Nessun prodotto disponibile</small></div>';
            return;
        }
        if (mapSlot) {
            mapSlot.innerHTML = '<div id="dash-mercato-map" class="leaflet-map"></div>';
            _dashInlineMap('dash-mercato-map', products, (p) =>
                L.marker([Number(p.map_latitude), Number(p.map_longitude)], { icon: _makeTypeIcon('_shop') })
                 .bindPopup(`<strong>${escapeHtml(p.name)}</strong><br>${escapeHtml(p.farm_name)}<br><a href="${appUrl('mercato/')}">Vai al mercato →</a>`)
            );
        }
        if (listSlot) {
            listSlot.innerHTML = products.map((p) => `
                <a class="tree-row tree-row--link" href="${appUrl('mercato/')}">
                    <img class="product-img product-img--small" src="${escapeHtml(p.media_url || WIDO_PH)}" alt="${escapeHtml(p.name)}" loading="lazy" onerror="this.onerror=null;this.src='${WIDO_PH}'">
                    <div class="tree-row-top">
                        <div><strong>${escapeHtml(p.name)}</strong><br><small>${escapeHtml(p.farm_name)} · ${escapeHtml(p.location)}</small></div>
                        <span class="badge">${p.price ? `€${Number(p.price).toFixed(2)}` : (p.price_note || '—')}</span>
                    </div>
                </a>`).join('');
        }
    };

    const renderBarattoInline = (baratti, mapSlot, listSlot) => {
        const WIDO_PH = 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';
        if (!baratti.length) {
            if (mapSlot) mapSlot.innerHTML = '<div class="map-placeholder"><small>Nessun baratto disponibile</small></div>';
            return;
        }
        if (mapSlot) {
            mapSlot.innerHTML = '<div id="dash-baratto-map" class="leaflet-map"></div>';
            _dashInlineMap('dash-baratto-map', baratti, (b) =>
                L.marker([Number(b.map_latitude), Number(b.map_longitude)], { icon: _makeTypeIcon('_barter') })
                 .bindPopup(`<strong>${escapeHtml(b.offer_title)}</strong><br>Cerca: ${escapeHtml(b.wants_title)}<br><a href="${appUrl('baratto/')}">Vai al baratto →</a>`)
            );
        }
        if (listSlot) {
            listSlot.innerHTML = baratti.map((b) => `
                <a class="tree-row tree-row--link" href="${appUrl('baratto/')}">
                    <img class="product-img product-img--small" src="${escapeHtml(b.media_url || WIDO_PH)}" alt="${escapeHtml(b.offer_title)}" loading="lazy" onerror="this.onerror=null;this.src='${WIDO_PH}'">
                    <div class="tree-row-top">
                        <div><strong>${escapeHtml(b.offer_title)}</strong><br><small>Cerco: ${escapeHtml(b.wants_title)}</small><br><small>${escapeHtml(b.farm_name)}</small></div>
                        <span class="badge">🤝</span>
                    </div>
                </a>`).join('');
        }
    };

    // DASHBOARD UTENTE
    const initExplore = () => {
        // ── Esplora unificato: tutto | adozioni | mercato | baratto | produttori ──
        const WIDO_PH = 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';
        const _exploreCache = {};
        const _fetchExplore = async (kind) => {
            if (_exploreCache[kind]) return _exploreCache[kind];
            if (kind === 'adoptions') {
                if (!_lastAdoptableTrees) { try { const d = await apiFetch('/catalog/trees'); _lastAdoptableTrees = d.trees || []; renderCatalogFilter(_lastAdoptableTrees); } catch (_) { _lastAdoptableTrees = []; } }
                _exploreCache.adoptions = _lastAdoptableTrees;
            } else if (kind === 'mercato') {
                _exploreCache.mercato = (await apiFetch('/mercato')).products || [];
            } else if (kind === 'baratto') {
                _exploreCache.baratto = (await apiFetch('/baratto')).baratti || [];
            } else if (kind === 'farms') {
                _exploreCache.farms = (await apiFetch('/farms/map')).farms || [];
            }
            return _exploreCache[kind] || [];
        };

        const _exploreMarkerSpec = (kind, it) => {
            const lat = Number(kind === 'farms' ? it.latitude : it.map_latitude);
            const lng = Number(kind === 'farms' ? it.longitude : it.map_longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
            if (kind === 'adoptions') return { lat, lng, type: it.type, popup: `<strong>${escapeHtml(it.species)}</strong><br>${escapeHtml(it.farm_name)}<br><a href="${appUrl(`trees/${it.id}/`)}">Vedi elemento →</a>` };
            if (kind === 'mercato')   return { lat, lng, type: '_shop', popup: `<strong>${escapeHtml(it.name)}</strong><br>${escapeHtml(it.farm_name)}<br><a href="${appUrl('mercato/')}">Vai al mercato →</a>` };
            if (kind === 'baratto')   return { lat, lng, type: '_barter', popup: `<strong>${escapeHtml(it.offer_title)}</strong><br>Cerca: ${escapeHtml(it.wants_title)}<br><a href="${appUrl('baratto/')}">Vai al baratto →</a>` };
            return { lat, lng, type: '_farm', popup: `<strong>${escapeHtml(it.name)}</strong><br>${escapeHtml(it.location || '')}<br><a href="${appUrl(`farms/${it.id}/`)}">Vedi vetrina →</a>` };
        };

        const _exploreRow = (kind, it) => {
            const img = (src, alt) => `<img class="product-img product-img--small" src="${escapeHtml(src || WIDO_PH)}" alt="${escapeHtml(alt)}" loading="lazy" onerror="this.onerror=null;this.src='${WIDO_PH}'">`;
            if (kind === 'adoptions') return `
                <a class="tree-row tree-row--link" href="${appUrl(`trees/${it.id}/`)}">
                    ${img(it.media_url || it.farm_photo, it.species)}
                    <div class="tree-row-top">
                        <div><strong>${escapeHtml(it.species)}</strong><br><small>${escapeHtml(it.farm_name)} · ${escapeHtml(it.location || '')}</small></div>
                        <span class="badge">${escapeHtml(it.code || '🌱')}</span>
                    </div>
                </a>`;
            const waBtn = (subject, it2) => {
                if (!window.AgriSaas.userId) return `<button class="button ghost" type="button" data-auth-contact onclick="event.stopPropagation()">🔒</button>`;
                if (!it2.contact_whatsapp) return '';
                return `<a class="button" href="https://wa.me/${String(it2.contact_whatsapp).replace(/\D/g, '')}?text=${encodeURIComponent(subject)}" target="_blank" rel="noopener" onclick="event.stopPropagation()">💬 Contatta</a>`;
            };
            if (kind === 'mercato') return `
                <div class="tree-row tree-row--link" onclick="location.href='${appUrl('mercato/')}'" role="link" tabindex="0">
                    ${img(it.media_url, it.name)}
                    <div class="tree-row-top">
                        <div><strong>${escapeHtml(it.name)}</strong><br><small>${escapeHtml(it.farm_name)} · ${escapeHtml(it.location || '')}</small></div>
                        <span class="badge">${it.price ? `€${Number(it.price).toFixed(2)}` : '🛒'}</span>
                    </div>
                    <div class="row-actions">${waBtn(`Ciao, sono interessato al prodotto "${it.name}" di ${it.farm_name}`, it)}</div>
                </div>`;
            if (kind === 'baratto') return `
                <div class="tree-row tree-row--link" onclick="location.href='${appUrl('baratto/')}'" role="link" tabindex="0">
                    ${img(it.media_url, it.offer_title)}
                    <div class="tree-row-top">
                        <div><strong>${escapeHtml(it.offer_title)}</strong><br><small>Cerco: ${escapeHtml(it.wants_title)}</small><br><small>${escapeHtml(it.farm_name)}</small></div>
                        <span class="badge">🤝</span>
                    </div>
                    <div class="row-actions">${waBtn(`Ciao! Ho visto la tua offerta di baratto su wido: offri "${it.offer_title}" in cambio di "${it.wants_title}", e sono interessato.`, it)}</div>
                </div>`;
            return `
                <a class="tree-row tree-row--link" href="${appUrl(`farms/${it.id}/`)}">
                    ${img(it.media_url, it.name)}
                    <div class="tree-row-top">
                        <div><strong>${escapeHtml(it.name)}</strong>${Number(it.is_verified) === 1 ? ' ✅' : ''}<br><small>${escapeHtml(it.location || '')}${it.crop_focus ? ` · ${escapeHtml(it.crop_focus)}` : ''}</small></div>
                        <span class="badge">🏡</span>
                    </div>
                </a>`;
        };

        const EXPLORE_LABELS = { adoptions: '🌱 Adozioni', mercato: '🛒 Mercato', baratto: '🤝 Baratto', farms: '🏡 Produttori' };

        const renderExploreTab = async (tab) => {
            const mapSlot  = root.querySelector('[data-slot="adoptable-map"]');
            const listSlot = root.querySelector('[data-slot="adoptable-trees"]');
            if (!mapSlot || !listSlot) return;
            mapSlot.innerHTML = '<div class="map-placeholder"><small>Caricamento…</small></div>';
            const kinds = tab === 'all' ? ['adoptions', 'mercato', 'baratto', 'farms'] : [tab];
            const datasets = await Promise.all(kinds.map((k) => _fetchExplore(k).catch(() => [])));
            adoptableLeafletMap = null;
            const markers = [];
            let listHtml = '';
            kinds.forEach((k, i) => {
                const items = datasets[i] || [];
                items.forEach((it) => { const m = _exploreMarkerSpec(k, it); if (m) markers.push(m); });
                if (tab === 'all' && items.length) listHtml += `<p class="eyebrow" style="margin:12px 0 4px;">${EXPLORE_LABELS[k]}</p>`;
                listHtml += items.map((it) => _exploreRow(k, it)).join('');
            });
            listSlot.innerHTML = listHtml || '<div class="card empty-state">Niente da mostrare al momento.</div>';
            if (!markers.length) {
                mapSlot.innerHTML = '<div class="map-placeholder">&#9678;<small>Nessuna coordinata disponibile</small></div>';
                return;
            }
            mapSlot.innerHTML = '<div id="explore-leaflet-map" class="leaflet-map"></div>';
            const map = makeClusterMap('explore-leaflet-map');
            adoptableLeafletMap = null; // explore map is standalone; adoptions tab rebuilds its own
            const cluster = makeClusterGroup();
            markers.forEach((m) => L.marker([m.lat, m.lng], { icon: _makeTypeIcon(m.type) }).bindPopup(m.popup).addTo(cluster));
            map.addLayer(cluster);
            setTimeout(() => {
                map.invalidateSize();
                try {
                    markers.length === 1
                        ? map.setView([markers[0].lat, markers[0].lng], 12)
                        : map.fitBounds(cluster.getBounds(), { padding: [28, 28], maxZoom: 13 });
                } catch (_) {}
            }, 120);
        };

        // Content tab switching for dashboard
        const contentTabs = root.querySelectorAll('[data-content-tab]');
        const filterBar   = root.querySelector('[data-slot="catalog-filter"]');
        if (filterBar) filterBar.style.display = 'none'; // default tab is "Tutto"
        if (contentTabs.length) {
            contentTabs.forEach((btn) => {
                btn.addEventListener('click', () => {
                    contentTabs.forEach((b) => b.classList.toggle('active', b === btn));
                    const tab = btn.dataset.contentTab;
                    if (filterBar) filterBar.style.display = tab === 'adoptions' ? '' : 'none';
                    if (tab === 'adoptions') {
                        adoptableLeafletMap = null;
                        renderAdoptableTrees(_filteredTrees());
                    } else {
                        renderExploreTab(tab);
                    }
                });
            });
        }

        // Initial view: everything together, no filters
        renderExploreTab('all');
    };

    const renderClientDashboard = (data) => {
        _statCardIdx = 0;
        const statsSlot = root.querySelector('[data-slot="stats"]');
        if (statsSlot) statsSlot.innerHTML = [
            statCard('Adottati', data.stats.adoptedTrees, 'Nel tuo portfolio'),
            statCard('Attivi', data.stats.activeAdoptions, 'Adozioni attive'),
        ].join('');
        renderLevelBadge(data.stats.activeAdoptions || 0);

        const treeRow = (tree) => {
            const planted = plantedDisplay(tree);
            const isCancelRequested = tree.adoption_status === 'cancel_requested';
            return `
            <a href="${appUrl(`trees/${tree.id}/`)}" class="tree-row tree-row--portfolio tree-row--link">
                <div class="tree-row-top">
                    <div>
                        <strong>${escapeHtml(tree.species)}</strong>${secolareBadge(tree)}<br>
                        <small>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.location)}</small>
                        ${planted ? `<br><small>🌱 ${escapeHtml(planted)}</small>` : ''}
                    </div>
                    <span class="badge">${escapeHtml(tree.code)}</span>
                </div>
                ${rewardChips(tree.rewards)}
                ${isCancelRequested ? `<span class="badge-warning">⏳ Cancellazione in attesa di conferma</span>` : ''}
            </a>`;
        };

        // Pending adoptions (shown first, section hidden if empty)
        const pendingTrees = data.trees.filter((t) => t.adoption_status === 'pending');
        const activeTrees  = data.trees.filter((t) => t.adoption_status !== 'pending');

        const pendingSection = root.querySelector('[data-profile-section="pending"]');
        if (pendingSection) {
            if (pendingTrees.length) {
                pendingSection.hidden = false;
                pendingSection.querySelector('[data-slot="trees-pending"]').innerHTML =
                    pendingTrees.map(treeRow).join('');
            } else {
                pendingSection.hidden = true;
            }
        }

        const treesSlot = root.querySelector('[data-slot="trees"]');
        if (treesSlot) treesSlot.innerHTML = activeTrees.length
            ? activeTrees.map(treeRow).join('')
            : '<div class="card empty-state">Non hai ancora adozioni attive. Adotta il tuo primo elemento dalla mappa qui sopra!</div>';

        // Il caricamento del catalogo avviene tramite la vista Esplora unificata (renderExploreTab).

        initExplore();
    };

    let _farmsData = [];

    const updateTreeRewardOptions = (farmId) => {
        const slot = document.querySelector('[data-slot="tree-reward-options"]');
        const box  = document.querySelector('[data-tree-reward-checkboxes]');
        if (!slot || !box) return;
        const farm = _farmsData.find((f) => String(f.id) === String(farmId));
        const rewards = farm?.rewards || [];
        slot.style.display = '';
        if (!rewards.length) {
            box.innerHTML = '<p style="color:var(--muted);font-size:.85rem;">⚠️ Nessun premio configurato per questo produttore. Chiedi all\'admin di aggiungerne prima di creare elementi.</p>';
            return;
        }
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
                : '<option value="">Crea prima un produttore</option>';
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
                        <small>${escapeHtml([req.adopter_email, req.adopter_phone ? `📞 ${req.adopter_phone}` : '', req.adopter_whatsapp ? `💬 ${req.adopter_whatsapp}` : ''].filter(Boolean).join(' · '))}</small>
                    </div>
                    <div class="row-actions">
                        ${req.adopter_whatsapp ? `<a class="button" href="https://wa.me/${String(req.adopter_whatsapp).replace(/\D/g,'')}?text=${encodeURIComponent(`Ciao ${req.adopter_name || ''}, ti scrivo riguardo all'adozione di ${req.species} (${req.code}).`)}" target="_blank" rel="noopener">💬 WhatsApp</a>` : ''}
                        ${req.adopter_phone ? `<a class="button ghost" href="tel:${escapeHtml(req.adopter_phone)}">📞 Chiama</a>` : ''}
                        <button class="button" type="button" data-adoption-decision="accept" data-request-id="${escapeHtml(req.id)}">Accetta</button>
                        <button class="button ghost" type="button" data-adoption-decision="reject" data-request-id="${escapeHtml(req.id)}">Rifiuta</button>
                    </div>
                </article>`).join('');
        }

        slot.innerHTML = html || '<div class="card empty-state">Nessuna richiesta in sospeso.</div>';
    };

    // DASHBOARD PRODUTTORE
    const renderFarmDashboard = (data) => {
        _statCardIdx = 0;
        const farm = (data.farms || [])[0] || null;

        const WIDO_PH2 = 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';
        const myProductsSlot = root.querySelector('[data-slot="my-products"]');
        if (myProductsSlot) {
            myProductsSlot.innerHTML = (data.products || []).length ? data.products.map((p) => `
                <div class="tree-row tree-row--manageable">
                    <img class="product-img product-img--small" src="${escapeHtml(p.media_url || WIDO_PH2)}" alt="${escapeHtml(p.name)}" loading="lazy" onerror="this.onerror=null;this.src='${WIDO_PH2}'">
                    <div class="tree-row-top">
                        <div><strong>${escapeHtml(p.name)}</strong><br><small>${p.description ? escapeHtml(p.description) : ''}</small></div>
                        <span class="badge">${p.price != null ? `€${Number(p.price).toFixed(2)} / ${escapeHtml(p.unit)}` : escapeHtml(p.unit || '')}</span>
                    </div>
                    <div class="tree-row-actions">
                        <button class="button ghost" style="padding:6px 12px;font-size:.8rem;" data-edit-product='${escapeHtml(JSON.stringify({id:p.id,name:p.name,description:p.description||"",price:p.price,unit:p.unit||"unità"}))}'>✏️ Modifica</button>
                        <button class="button ghost" style="padding:6px 12px;font-size:.8rem;color:#c62828;" data-delete-product="${p.id}">🗑 Elimina</button>
                    </div>
                </div>`).join('')
                : '<div class="card empty-state">Nessun prodotto pubblicato. Usa "+ Prodotto" per aggiungerne uno.</div>';
        }
        const myBarattiSlot = root.querySelector('[data-slot="my-baratti"]');
        if (myBarattiSlot) {
            myBarattiSlot.innerHTML = (data.baratti || []).length ? data.baratti.map((b) => `
                <div class="tree-row tree-row--manageable">
                    <img class="product-img product-img--small" src="${escapeHtml(b.media_url || WIDO_PH2)}" alt="${escapeHtml(b.offer_title)}" loading="lazy" onerror="this.onerror=null;this.src='${WIDO_PH2}'">
                    <div class="tree-row-top">
                        <div><strong>${escapeHtml(b.offer_title)}</strong><br><small>Cerco: ${escapeHtml(b.wants_title)}</small></div>
                        <span class="badge">🤝</span>
                    </div>
                    <div class="tree-row-actions">
                        <button class="button ghost" style="padding:6px 12px;font-size:.8rem;" data-edit-baratto='${escapeHtml(JSON.stringify({id:b.id,offer_title:b.offer_title,offer_description:b.offer_description||"",wants_title:b.wants_title,wants_description:b.wants_description||""}))}'>✏️ Modifica</button>
                        <button class="button ghost" style="padding:6px 12px;font-size:.8rem;color:#c62828;" data-delete-baratto="${b.id}">🗑 Elimina</button>
                    </div>
                </div>`).join('')
                : '<div class="card empty-state">Nessuna proposta di baratto. Usa "+ Baratto" per crearne una.</div>';
        }

        root.addEventListener('click', async (ev) => {
            const editP = ev.target.closest('[data-edit-product]');
            if (editP) {
                const d = JSON.parse(editP.dataset.editProduct);
                const form = document.querySelector('[data-agri-edit-product-form]');
                if (form) {
                    form.elements.product_id.value = d.id;
                    form.elements.name.value = d.name || '';
                    form.elements.description.value = d.description || '';
                    form.elements.price.value = d.price ?? '';
                    form.elements.unit.value = d.unit || 'unità';
                    openModal('[data-edit-product-form]');
                }
                return;
            }
            const editB = ev.target.closest('[data-edit-baratto]');
            if (editB) {
                const d = JSON.parse(editB.dataset.editBaratto);
                const form = document.querySelector('[data-agri-edit-baratto-form]');
                if (form) {
                    form.elements.baratto_id.value = d.id;
                    form.elements.offer_title.value = d.offer_title || '';
                    form.elements.offer_description.value = d.offer_description || '';
                    form.elements.wants_title.value = d.wants_title || '';
                    form.elements.wants_description.value = d.wants_description || '';
                    openModal('[data-edit-baratto-form]');
                }
                return;
            }
            const delP = ev.target.closest('[data-delete-product]');
            const delB = ev.target.closest('[data-delete-baratto]');
            if (!delP && !delB) return;
            const isProd = Boolean(delP);
            const id = isProd ? delP.dataset.deleteProduct : delB.dataset.deleteBaratto;
            if (!confirm(isProd ? 'Eliminare questo prodotto dal mercato?' : 'Eliminare questa proposta di baratto?')) return;
            try {
                await apiFetch(`/${isProd ? 'mercato' : 'baratto'}/${id}`, { method: 'DELETE' });
                window.location.reload();
            } catch (err) {
                alert(`Errore: ${err.message}`);
            }
        }, { once: false });

        const bindEditModal = (formSel, idField, endpointBase) => {
            const form = document.querySelector(formSel);
            if (!form || form.dataset.bound) return;
            form.dataset.bound = '1';
            form.addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                const statusEl = form.querySelector('[data-form-status]');
                btn.disabled = true;
                if (statusEl) statusEl.textContent = 'Salvataggio…';
                try {
                    const fd = new FormData(form);
                    const payload = {};
                    fd.forEach((v, k) => { if (k !== 'photo' && k !== idField) payload[k] = v; });
                    const fileInput = form.querySelector('input[type="file"][name="photo"]');
                    if (fileInput?.files?.length) {
                        const up = new FormData();
                        up.append('photo', fileInput.files[0]);
                        const res = await apiFetch('/media/photo', { method: 'POST', body: up });
                        payload.media_url = res.url;
                    }
                    await apiFetch(`${endpointBase}/${fd.get(idField)}`, { method: 'PUT', body: JSON.stringify(payload) });
                    closeAllModals();
                    window.location.reload();
                } catch (err) {
                    if (statusEl) statusEl.textContent = `❌ ${err.message}`;
                    btn.disabled = false;
                }
            });
        };
        bindEditModal('[data-agri-edit-product-form]', 'product_id', '/mercato');
        bindEditModal('[data-agri-edit-baratto-form]', 'baratto_id', '/baratto');
        root.querySelector('[data-slot="stats"]').innerHTML = [
            statCard('Elementi disponibili', data.stats.availableTrees, "Pronti per l'adozione"),
            statCard('Elementi adottati', data.stats.adoptedTrees, 'Sponsorizzati dai utenti'),
        ].join('');
        const nameEl = root.querySelector('[data-slot="farm-name"]');
        if (nameEl) nameEl.textContent = farm ? farm.name : '—';
        const infoEl = root.querySelector('[data-slot="farm-info"]');
        if (infoEl) {
            const brandPreview = (url, label) => url
                ? `<img src="${escapeHtml(url)}" alt="${label}" style="width:100%;height:90px;object-fit:cover;border-radius:10px;border:1px solid var(--border);">`
                : `<div class="map-placeholder" style="height:90px;border-radius:10px;"><small>Nessuna immagine</small></div>`;
            infoEl.innerHTML = farm ? `
                <div class="farm-row">
                    <div>
                        <small>${escapeHtml(farm.location)}${farm.crop_focus ? ` · ${escapeHtml(farm.crop_focus)}` : ''}${farm.latitude && farm.longitude ? ` · 📍 ${escapeHtml(farm.latitude)}, ${escapeHtml(farm.longitude)}` : ''}</small>
                    </div>
                    <div class="farm-row-end">
                        <a class="button ghost" href="${appUrl(`farms/${farm.id}/`)}" target="_blank" rel="noopener">👁 Vedi profilo pubblico</a>
                        <span class="badge">${escapeHtml(farm.health_score)} salute</span>
                        ${shareButtons(appUrl(`farms/${farm.id}/`), `🌾 ${farm.name} — Adotta un elemento!`)}
                    </div>
                </div>
                <div class="farm-branding" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px;">
                    <div>
                        <p class="eyebrow" style="margin-bottom:6px;">Logo</p>
                        <div data-brand-preview="logo">${brandPreview(farm.logo_url, 'Logo')}</div>
                        <label style="margin-top:6px;display:block;"><input type="file" accept="image/*" data-brand-input="logo"></label>
                    </div>
                    <div>
                        <p class="eyebrow" style="margin-bottom:6px;">Foto copertina</p>
                        <div data-brand-preview="cover">${brandPreview(farm.cover_url, 'Copertina')}</div>
                        <label style="margin-top:6px;display:block;"><input type="file" accept="image/*" data-brand-input="cover"></label>
                    </div>
                    <div style="grid-column:1/-1;">
                        <p class="eyebrow" style="margin:8px 0 6px;">Contatti pubblici (usati dal bottone Contatta)</p>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;">
                            <label style="font-size:.82rem;">WhatsApp<input type="tel" data-brand-contact="whatsapp" value="${escapeHtml(farm.contact_whatsapp || '')}" placeholder="+39…"></label>
                            <label style="font-size:.82rem;">Telefono<input type="tel" data-brand-contact="phone" value="${escapeHtml(farm.contact_phone || '')}" placeholder="+39…"></label>
                            <label style="font-size:.82rem;">Email<input type="email" data-brand-contact="email" value="${escapeHtml(farm.contact_email || '')}" placeholder="nome@esempio.it"></label>
                        </div>
                    </div>
                    <div style="grid-column:1/-1;display:flex;gap:10px;align-items:center;">
                        <button class="button" type="button" data-save-branding>Salva profilo pubblico</button>
                        <span class="map-note" data-branding-status></span>
                    </div>
                </div>` : '<div class="card empty-state">Profilo produttore non trovato.</div>';

            const saveBtn = infoEl.querySelector('[data-save-branding]');
            if (saveBtn) {
                saveBtn.addEventListener('click', async () => {
                    const statusEl = infoEl.querySelector('[data-branding-status]');
                    const logoInput  = infoEl.querySelector('[data-brand-input="logo"]');
                    const coverInput = infoEl.querySelector('[data-brand-input="cover"]');
                    saveBtn.disabled = true;
                    statusEl.textContent = 'Salvataggio…';
                    try {
                        const payload = {
                            contact_whatsapp: infoEl.querySelector('[data-brand-contact="whatsapp"]')?.value ?? '',
                            contact_phone:    infoEl.querySelector('[data-brand-contact="phone"]')?.value ?? '',
                            contact_email:    infoEl.querySelector('[data-brand-contact="email"]')?.value ?? '',
                        };
                        for (const [key, input] of [['logo_url', logoInput], ['cover_url', coverInput]]) {
                            if (input?.files?.length) {
                                const fd = new FormData();
                                fd.append('photo', input.files[0]);
                                const up = await apiFetch('/media/photo', { method: 'POST', body: fd });
                                payload[key] = up.url;
                            }
                        }
                        await apiFetch('/farms/branding', { method: 'POST', body: JSON.stringify(payload) });
                        statusEl.textContent = '✅ Profilo salvato!';
                        setTimeout(() => window.location.reload(), 700);
                    } catch (err) {
                        statusEl.textContent = `❌ ${err.message}`;
                        saveBtn.disabled = false;
                    }
                });
            }
        }
        root.querySelector('[data-slot="farm-trees"]').innerHTML = data.trees.length
            ? data.trees.map((tree) => `
                <div class="tree-row tree-row--manageable">
                    <a class="tree-row-link" href="${appUrl(`trees/${tree.id}/`)}">
                        <div><strong>${escapeHtml(tree.species)}</strong><br><small>${escapeHtml(tree.farm_name)} · ${escapeHtml(plantedDisplay(tree) || 'Data non disponibile')}</small></div>
                        <span class="badge">${escapeHtml(tree.code)} · ${escapeHtml(tree.status)}</span>
                    </a>
                    <div class="tree-row-actions">
                        <button class="button ghost" style="padding:6px 12px;font-size:.8rem;" data-edit-tree="${tree.id}">✏️ Modifica</button>
                        ${!tree.adopter_user_id ? `<button class="button ghost" style="padding:6px 12px;font-size:.8rem;color:var(--error);border-color:var(--error);" data-delete-tree="${tree.id}">🗑</button>` : ''}
                    </div>
                </div>`).join('')
            : '<div class="card empty-state">Nessun elemento pubblicato. Usa "+ Elemento" per renderlo disponibile.</div>';
        renderAdoptionRequests(data.requests || []);
        // Attach rewards to the single farm and pre-populate reward checkboxes
        const farmWithRewards = farm ? { ...farm, rewards: data.rewards || [] } : null;
        _farmsData = farmWithRewards ? [farmWithRewards] : [];
        if (farmWithRewards) updateTreeRewardOptions(farmWithRewards.id);

        // Edit tree button handler
        root.querySelector('[data-slot="farm-trees"]')?.addEventListener('click', async (ev) => {
            const editBtn   = ev.target.closest('[data-edit-tree]');
            const deleteBtn = ev.target.closest('[data-delete-tree]');

            if (editBtn) {
                const treeId = Number(editBtn.dataset.editTree);
                const tree   = data.trees.find((t) => t.id === treeId);
                if (!tree) return;
                const modal = document.querySelector('[data-edit-tree-form]');
                const form  = modal?.querySelector('[data-agri-edit-tree-form]');
                if (!form) return;
                form.querySelector('[name="tree_id"]').value    = tree.id;
                form.querySelector('[name="species"]').value    = tree.species || '';
                form.querySelector('[name="code"]').value       = tree.code || '';
                form.querySelector('[name="planted_at"]').value = tree.planted_at || '';
                form.querySelector('[name="latitude"]').value   = tree.map_latitude || '';
                form.querySelector('[name="longitude"]').value  = tree.map_longitude || '';
                form.querySelector('[data-edit-tree-media-url]').value = tree.media_url || '';
                const typeEl = form.querySelector('[name="type"]');
                if (typeEl) typeEl.value = tree.type || 'albero';
                const statusEl = form.querySelector('[name="status"]');
                if (statusEl) statusEl.value = tree.status || 'available';
                // Populate reward checkboxes
                const rewards = farmWithRewards?.rewards || [];
                const currentRewardIds = (tree.rewards || []).map((r) => String(r.id || r));
                const checkboxDiv = form.querySelector('[data-edit-tree-reward-checkboxes]');
                if (checkboxDiv) {
                    checkboxDiv.innerHTML = rewards.length
                        ? rewards.map((r) => `<label style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">
                            <input type="checkbox" name="reward_ids[]" value="${r.id}" ${currentRewardIds.includes(String(r.id)) ? 'checked' : ''}>
                            <span>${escapeHtml(r.name)}</span></label>`).join('')
                        : '<p style="color:var(--muted);font-size:.85rem;">Nessun premio configurato per questo produttore.</p>';
                }
                form.querySelector('[data-edit-tree-form-status]').textContent = '';
                modal.classList.add('active');
            }

            if (deleteBtn) {
                const treeId = Number(deleteBtn.dataset.deleteTree);
                const tree   = data.trees.find((t) => t.id === treeId);
                if (!confirm(`Eliminare "${tree?.species || 'questo elemento'}"? Questa azione non può essere annullata.`)) return;
                deleteBtn.disabled = true;
                try {
                    await apiFetch(`/trees/${treeId}`, { method: 'DELETE' });
                    window.location.reload();
                } catch (err) {
                    alert(err.message || 'Errore durante l\'eliminazione.');
                    deleteBtn.disabled = false;
                }
            }
        });

        // Edit tree form submit
        document.querySelector('[data-agri-edit-tree-form]')?.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const form       = ev.currentTarget;
            const statusEl   = form.querySelector('[data-edit-tree-form-status]');
            const photoInput = form.querySelector('[data-edit-tree-photo-input]');
            const mediaUrlEl = form.querySelector('[data-edit-tree-media-url]');
            const submitBtn  = form.querySelector('[type="submit"]');
            statusEl.textContent = '';
            submitBtn.disabled = true;
            try {
                if (photoInput?.files?.length) {
                    statusEl.textContent = 'Ottimizzazione foto…';
                    const fd = new FormData();
                    fd.append('photo', photoInput.files[0]);
                    const up = await apiFetch('/media/photo', { method: 'POST', body: fd });
                    mediaUrlEl.value = up.url;
                }
                const formData = new FormData(form);
                const treeId   = formData.get('tree_id');
                const payload  = Object.fromEntries(formData.entries());
                delete payload.tree_photo;
                delete payload.tree_id;
                const rewardIds = formData.getAll('reward_ids[]').map(Number).filter(Boolean);
                payload.reward_ids = rewardIds;
                await apiFetch(`/trees/${treeId}`, { method: 'PUT', body: JSON.stringify(payload) });
                window.location.reload();
            } catch (err) {
                statusEl.textContent = err.message || 'Errore durante il salvataggio.';
                submitBtn.disabled = false;
            }
        });
    };

    // DETTAGLIO ALBERO
    const treeAge = (tree) => {
        if (!tree.planted_at) return 'N/D';
        const year = parseInt(tree.planted_at.slice(0, 4), 10);
        if (!year || year < 100) return 'N/D';
        const years = new Date().getFullYear() - year;
        if (years <= 0) return 'N/D';
        if (years >= 200) return 'circa 200 anni';
        if (years >= 150) return 'circa 150 anni';
        if (years >= 120) return 'circa 120 anni';
        if (years >= 100) return 'circa 100 anni';
        return `${years} anni`;
    };

    let treeDetailLeafletMap = null;

    const renderTreeDetail = (data) => {
        _statCardIdx = 0;
        if (!data.tree) {
            console.error('[AgriSaas] renderTreeDetail: data.tree is missing. Full response:', data);
            throw new Error(`Dati elemento non trovati. Risposta API: ${JSON.stringify(Object.keys(data))}`);
        }
        const tree = data.tree;
        const treeUrl = appUrl(`trees/${tree.id}/`);
        const adoption = data.user_adoption;
        const isAvailable = tree.status === 'available';
        const isAdopter   = adoption && Number(tree.adopter_user_id) === Number(window.AgriSaas.userId);
        const isCancelReq = adoption?.status === 'cancel_requested';
        const isPending   = adoption?.status === 'pending';

        let adoptionHtml = '';
        if (isAvailable && !adoption) {
            adoptionHtml = `<div style="margin-top:16px;"><button class="button" type="button" data-request-adoption="${escapeHtml(tree.id)}">🌱 Adotta questo elemento</button></div>`;
        } else if (isPending) {
            adoptionHtml = `<div style="margin-top:16px;"><span class="badge-pending">⏳ Richiesta di adozione inviata — in attesa di conferma</span></div>`;
        } else if (isAdopter && isCancelReq) {
            adoptionHtml = `<div style="margin-top:16px;"><span class="badge-warning">⏳ Richiesta di cancellazione in attesa di conferma dal produttore</span></div>`;
        } else if (isAdopter && adoption?.status === 'active') {
            adoptionHtml = `<div style="margin-top:16px;"><button class="button ghost" type="button" style="font-size:.82rem;" data-request-cancel="${escapeHtml(adoption.id)}">Richiedi cancellazione adozione</button></div>`;
        }

        // Step 1 = available+no adoption; Step 2 = pending; Step 3 = active
        const _step = !adoption ? 1 : isPending ? 2 : isAdopter && adoption.status === 'active' ? 3 : 2;
        const adoptionStepsHtml = `
        <div class="adoption-steps">
            <div class="adoption-step ${_step > 1 ? 'done' : 'active'}">
                <div class="adoption-step-num">${_step > 1 ? '✓' : '1'}</div>
                <div class="adoption-step-body">
                    <div class="adoption-step-title">Richiedi l'adozione</div>
                    <div class="adoption-step-desc">Clicca su "Adotta" e invia la tua richiesta al farmer.</div>
                </div>
            </div>
            <div class="adoption-step ${_step > 2 ? 'done' : _step === 2 ? 'active' : ''}">
                <div class="adoption-step-num">${_step > 2 ? '✓' : '2'}</div>
                <div class="adoption-step-body">
                    <div class="adoption-step-title">Mettiti d'accordo con il farmer</div>
                    <div class="adoption-step-desc">Concordate modalità di pagamento e raccolta dei prodotti.</div>
                </div>
            </div>
            <div class="adoption-step ${_step >= 3 ? 'done' : ''}">
                <div class="adoption-step-num">${_step >= 3 ? '✓' : '3'}</div>
                <div class="adoption-step-body">
                    <div class="adoption-step-title">Segui gli aggiornamenti e goditi i premi!</div>
                    <div class="adoption-step-desc">Ricevi notizie dal campo su Wido e riscatta i premi inclusi nell'adozione.</div>
                </div>
            </div>
        </div>`;

        const WIDO_PH = 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';
        const treePhoto = tree.media_url || tree.farm_photo || WIDO_PH;
        const ageYrs = treeAgeYears(tree);

        const _rewardTypeIcon = (type) => ({
            product: '📦', experience: '🎟️', discount: '🏷️', surprise: '🎁', harvest: '🌾', oil: '🫒', wine: '🍷', honey: '🍯',
        }[type] || '🎁');

        const rewardsHeroHtml = data.rewards && data.rewards.length ? `
        <div class="tree-rewards-hero">
            <p class="rewards-hero-title">🎁 Cosa ricevi adottando</p>
            <div class="rewards-hero-grid">
                ${data.rewards.map((r) => {
                    const whenLabel = { immediate: 'Subito', '6m': '6 mesi', '1y': '1 anno', harvest: 'Al raccolto', annually: 'Ogni anno' }[r.when_received] || r.when_received;
                    return `<div class="reward-hero-card">
                        <div class="reward-hero-icon">${_rewardTypeIcon(r.reward_type)}</div>
                        <div class="reward-hero-body">
                            <strong class="reward-hero-name">${escapeHtml(r.name)}</strong>
                            <p class="reward-hero-desc">${escapeHtml(r.description)}</p>
                            <div class="reward-hero-meta">
                                <span class="reward-hero-when">⏱ ${whenLabel}</span>
                                ${r.estimated_value ? `<span class="reward-hero-value">💰 ${escapeHtml(r.estimated_value)}</span>` : ''}
                            </div>
                        </div>
                    </div>`;
                }).join('')}
            </div>
        </div>` : '';

        root.querySelector('[data-slot="tree"]').innerHTML = `
            <div class="tree-hero-photo"><img src="${escapeHtml(treePhoto)}" alt="${escapeHtml(tree.species)}" loading="lazy" onerror="this.onerror=null;this.src='${WIDO_PH}';this.style.objectFit='contain';this.style.padding='40px';this.style.background='var(--surface-soft)'"></div>
            <div class="tree-hero-info">
                <p class="eyebrow">${escapeHtml(tree.code)}${tree.type ? ` · ${escapeHtml(tree.type)}` : ''}</p>
                <h2 style="margin-top:4px;font-size:1.8rem;">${escapeHtml(tree.species)}</h2>
                <p style="margin-top:6px;"><a href="${appUrl(`farms/${tree.farm_id}/`)}" style="color:var(--brand);font-weight:600;">${escapeHtml(tree.farm_name)}</a> · ${escapeHtml(tree.location)}${tree.crop_focus ? ` · ${escapeHtml(tree.crop_focus)}` : ''}</p>
                <div class="tree-meta-badges">
                    <span class="tree-meta-badge">📋 ${escapeHtml(tree.status)}</span>
                    ${ageYrs !== null ? `<span class="tree-meta-badge">🌱 ${ageYrs} anni</span>` : ''}
                    ${secolareBadge(tree) ? `<span class="tree-meta-badge">🏛️ Secolare</span>` : ''}
                </div>
            </div>
            ${rewardsHeroHtml}
            <div class="tree-adoption-section">
                ${adoptionStepsHtml}
                ${adoptionHtml}
                <div class="share-section" style="margin-top:16px;">
                    <span class="share-label">Condividi:</span>
                    ${shareButtons(treeUrl, `🌱 ${tree.species} — Adotta su Wido!`)}
                </div>
            </div>`;

        // Map slot
        const mapSlot = root.querySelector('[data-slot="tree-map"]');
        if (mapSlot) {
            const lat = Number(tree.latitude || tree.farm_latitude);
            const lng = Number(tree.longitude || tree.farm_longitude);
            if (lat && lng) {
                mapSlot.innerHTML = '<p class="eyebrow">Posizione</p><div id="tree-detail-leaflet-map" class="leaflet-map"></div>';
                if (!treeDetailLeafletMap) {
                    treeDetailLeafletMap = L.map('tree-detail-leaflet-map').setView([lat, lng], 14);
                    makeTileLayer().addTo(treeDetailLeafletMap);
                } else {
                    treeDetailLeafletMap.setView([lat, lng], 14);
                }
                L.marker([lat, lng])
                    .bindPopup(`<strong>${escapeHtml(tree.species)}</strong><br>${escapeHtml(tree.code)}`)
                    .addTo(treeDetailLeafletMap);
                setTimeout(() => treeDetailLeafletMap.invalidateSize(), 80);
            } else {
                mapSlot.innerHTML = '<p class="eyebrow">Posizione</p><div class="map-placeholder">&#9678;<br><small>Coordinate non disponibili</small></div>';
            }
        }

        renderUpdates(data.updates || [], tree.farm_id);
        renderFarmTreesSuggestions(data.farm_trees || [], tree.farm_id, tree.farm_name);
    };

    const renderFarmTreesSuggestions = (trees, farmId, farmName) => {
        const slot = root?.querySelector('[data-slot="farm-trees"]');
        if (!slot) return;
        if (!trees.length) { slot.hidden = true; return; }
        const WIDO_PH = 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';
        slot.hidden = false;
        slot.innerHTML = `
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Stesso farmer</p>
                    <h2>Altre adozioni disponibili da <a href="${appUrl(`farms/${farmId}/`)}" style="color:var(--brand);">${escapeHtml(farmName)}</a></h2>
                </div>
            </div>
            <div class="farm-suggestions-grid">
                ${trees.map((t) => {
                    const photo = t.media_url || t.farm_photo || WIDO_PH;
                    return `<a class="farm-suggestion-card" href="${appUrl(`trees/${t.id}/`)}">
                        <img src="${escapeHtml(photo)}" alt="${escapeHtml(t.species)}" loading="lazy" onerror="this.onerror=null;this.src='${WIDO_PH}';this.style.objectFit='contain';this.style.padding='16px';this.style.background='var(--surface-soft)'">
                        <div class="farm-suggestion-info">
                            <span class="eyebrow" style="font-size:.7rem;">${escapeHtml(t.code)}</span>
                            <strong>${escapeHtml(t.species)}</strong>
                            <span class="dist-label">${_TYPE_ICONS[t.type] || '🌿'} ${escapeHtml(t.type || 'elemento')}</span>
                        </div>
                    </a>`;
                }).join('')}
            </div>`;
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
            const farmUrl   = farmId ? appUrl(`farms/${farmId}/`) : null;
            const treeUrl   = update.tree_id ? appUrl(`trees/${update.tree_id}/`) : null;
            const detailUrl = treeUrl || farmUrl;
            const updateUrl = farmUrl ? farmUrl + `#update-${update.id}` : appUrl('updates/') + `#update-${update.id}`;
            return `
            <article class="ig-card" id="update-${escapeHtml(update.id)}">
                <div class="ig-card-header">
                    ${farmUrl
                        ? `<a class="ig-avatar" href="${escapeHtml(farmUrl)}">🌿</a>`
                        : `<div class="ig-avatar">🌿</div>`}
                    <div class="ig-card-meta">
                        ${farmUrl
                            ? `<a class="ig-farm-name" href="${escapeHtml(farmUrl)}">${escapeHtml(update.farm_name || 'Produttore')}</a>`
                            : `<strong class="ig-farm-name">${escapeHtml(update.farm_name || 'Produttore')}</strong>`}
                        <span class="ig-timestamp">${timeAgo(update.created_at)}</span>
                    </div>
                    <span class="ig-visibility">${visibilityLabel(update.visibility)}</span>
                </div>
                ${update.media_url
                    ? `<div class="ig-card-img-wrap">${detailUrl ? `<a href="${escapeHtml(detailUrl)}">` : ''}<img class="ig-card-img" src="${escapeHtml(update.media_url)}" alt="${escapeHtml(update.title)}" loading="lazy">${detailUrl ? '</a>' : ''}</div>`
                    : ''}
                <div class="ig-card-body">
                    <p class="ig-card-title">${escapeHtml(update.title)}</p>
                    <p class="ig-card-text">${escapeHtml(update.body)}</p>
                    ${update.tree_code ? `<a class="ig-tag" href="${escapeHtml(treeUrl || '#')}">🌱 ${escapeHtml(update.tree_code)}</a>` : ''}
                    ${detailUrl ? `<div style="margin-top:8px;"><a class="ig-scopri" href="${escapeHtml(detailUrl)}">Scopri di più →</a></div>` : ''}
                </div>
                <div class="ig-card-footer">
                    ${shareButtons(updateUrl, `📰 ${update.title} — Adotta un elemento su Adotta!`)}
                </div>
            </article>`;
        }).join('');
    };

    // RECENSIONI PRODUTTORE
    const renderFarmReviews = async (farmId, container) => {
        const slot = container?.querySelector('[data-slot="farm-reviews"]');
        if (!slot) return;
        const clovers = (n, total = 5) => Array.from({ length: total }, (_, i) =>
            i < n ? '🍀' : '<span style="opacity:.3">🍀</span>'
        ).join('');
        try {
            const data = await apiFetch(`/farms/${farmId}/reviews`);
            const { reviews, avg, count } = data;
            const avgHtml = count
                ? `<div class="reviews-avg">
                    <span class="reviews-avg-score">${avg}</span>
                    <span class="review-clovers">${clovers(Math.round(avg))}</span>
                    <span style="color:var(--muted);font-size:.85rem;">${count} recension${count === 1 ? 'e' : 'i'}</span>
                  </div>`
                : '<p style="color:var(--muted);font-size:.9rem;">Nessuna recensione ancora. Sii il primo!</p>';
            const listHtml = reviews.length
                ? `<div class="reviews-list">${reviews.map((r) => `
                    <div class="review-item">
                        <div class="review-meta">
                            <span class="review-author">${escapeHtml(r.display_name)}</span>
                            <span class="review-clovers">${clovers(Number(r.rating))}</span>
                            <span class="review-date">${timeAgo(r.created_at)}</span>
                        </div>
                        ${r.comment ? `<p class="review-comment">${escapeHtml(r.comment)}</p>` : ''}
                    </div>`).join('')}</div>`
                : '';
            const formHtml = window.AgriSaas?.userId
                ? `<form class="review-form" data-review-form="${farmId}">
                    <strong style="font-size:.95rem;">Lascia una recensione</strong>
                    <div class="clover-picker" data-clover-picker>
                        ${Array.from({ length: 5 }, (_, i) => `<span data-val="${i + 1}">🍀</span>`).join('')}
                    </div>
                    <input type="hidden" name="rating" value="0">
                    <textarea name="comment" rows="3" placeholder="Scrivi un commento (opzionale)…" style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:inherit;font-size:.9rem;resize:vertical;"></textarea>
                    <button class="button" type="submit">Invia recensione</button>
                    <p class="review-form-status" style="font-size:.85rem;"></p>
                   </form>`
                : '';
            slot.innerHTML = `
                <div class="section-heading"><div>
                    <p class="eyebrow">Opinioni dei utenti</p>
                    <h2>Recensioni</h2>
                </div></div>
                ${avgHtml}
                ${listHtml}
                ${formHtml}`;
            const form = slot.querySelector('[data-review-form]');
            if (form) {
                const picker = form.querySelector('[data-clover-picker]');
                const ratingInput = form.querySelector('input[name="rating"]');
                picker?.querySelectorAll('span').forEach((span) => {
                    span.addEventListener('click', () => {
                        const val = Number(span.dataset.val);
                        ratingInput.value = val;
                        picker.querySelectorAll('span').forEach((s, i) => {
                            s.classList.toggle('active', i < val);
                        });
                    });
                });
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const rating = Number(ratingInput.value);
                    if (!rating) { form.querySelector('.review-form-status').textContent = 'Seleziona un punteggio.'; return; }
                    const comment = form.querySelector('textarea[name="comment"]').value;
                    const btn = form.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    try {
                        await apiFetch(`/farms/${farmId}/reviews`, { method: 'POST', body: JSON.stringify({ rating, comment }) });
                        renderFarmReviews(farmId, container);
                    } catch (_) {
                        form.querySelector('.review-form-status').textContent = 'Errore durante l\'invio. Riprova.';
                        btn.disabled = false;
                    }
                });
            }
        } catch (_) {
            if (slot) slot.innerHTML = '<p style="color:var(--muted);">Impossibile caricare le recensioni.</p>';
        }
    };

    // MAPPA PROFILO PRODUTTORE (cluster)
    const renderFarmProfileMap = (trees) => {
        const slot = root?.querySelector('[data-slot="farm-profile-map"]');
        if (!slot) return;
        const mapped = trees.filter((t) => Number.isFinite(Number(t.map_latitude)) && Number.isFinite(Number(t.map_longitude)));
        if (!mapped.length) {
            slot.innerHTML = '<div class="map-placeholder">&#9678;<small>Nessuna coordinata per gli elementi</small></div>';
            farmProfileLeafletMap = null;
            return;
        }
        if (!farmProfileLeafletMap) {
            slot.innerHTML = '<div id="farm-profile-leaflet-map" class="leaflet-map"></div><p class="map-note">I numeri nei cerchi indicano cluster — clicca per espandere e vedere i singoli elementi.</p>';
            farmProfileLeafletMap = makeClusterMap('farm-profile-leaflet-map');
        } else {
            clearMapLayers(farmProfileLeafletMap);
        }
        const cluster = makeClusterGroup();
        mapped.forEach((tree) => {
            L.marker([Number(tree.map_latitude), Number(tree.map_longitude)], { icon: _makeTypeIcon(tree.type) })
                .bindPopup(`<strong>${escapeHtml(tree.species)}</strong><br>${escapeHtml(tree.code)} · ${escapeHtml(tree.status)}<br><a href="${appUrl(`trees/${tree.id}/`)}">Vedi elemento →</a>`)
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

    // PROFILO PRODUTTORE
    const renderFarmProfile = (data) => {
        _statCardIdx = 0;
        const farm = data.farm;
        const farmUrl = appUrl(`farms/${farm.id}/`);
        const hero = root.querySelector('.farm-hero');
        if (hero && farm.cover_url && !hero.querySelector('.farm-cover')) {
            hero.insertAdjacentHTML('afterbegin', `<img class="farm-cover" src="${escapeHtml(farm.cover_url)}" alt="" style="grid-column:1/-1;width:100%;height:220px;object-fit:cover;border-radius:14px;margin-bottom:14px;">`);
        }
        const titleEl = root.querySelector('[data-slot="farm-title"]');
        titleEl.textContent = farm.name;
        if (farm.logo_url && !titleEl.parentElement.querySelector('.farm-logo')) {
            titleEl.insertAdjacentHTML('beforebegin', `<img class="farm-logo" src="${escapeHtml(farm.logo_url)}" alt="Logo ${escapeHtml(farm.name)}" style="width:64px;height:64px;object-fit:cover;border-radius:50%;border:2px solid var(--border);margin-bottom:8px;">`);
        }
        // Also update the shell topbar h1 if present
        const shellH1 = document.querySelector('.app-topbar h1');
        if (shellH1) shellH1.textContent = farm.name;
        root.querySelector('[data-slot="farm-summary"]').innerHTML =
            `${escapeHtml(farm.location)} · ${escapeHtml(farm.crop_focus || 'Produzione mista')}<br>${escapeHtml(farm.description || "Questo produttore usa il suo profilo come vetrina pubblica per elementi, foto e aggiornamenti dal campo.")}`;
        if (!data.logged_in) {
            root.querySelector('[data-slot="farm-contacts"]').innerHTML =
                `<button class="button ghost" type="button" data-auth-contact>🔒 Accedi per vedere i contatti</button>`;
        } else {
            root.querySelector('[data-slot="farm-contacts"]').innerHTML = [
                contactButton(farm.contact_email  ? `mailto:${farm.contact_email}` : '', '📧 Email'),
                contactButton(farm.contact_whatsapp ? `https://wa.me/${String(farm.contact_whatsapp).replace(/\D/g, '')}` : '', '💬 WhatsApp'),
                contactButton(farm.contact_phone  ? `tel:${farm.contact_phone}` : '', '📞 Telefono'),
            ].join('') || '<span class="badge">Contatti in arrivo</span>';
        }

        const followButton = root.querySelector('[data-follow-farm]');
        if (followButton) {
            followButton.hidden = false;
            followButton.dataset.farmId = farm.id;
            followButton.dataset.following = data.isFollowing ? '1' : '0';
            followButton.textContent = data.canFollow
                ? (data.isFollowing ? 'Stai seguendo ✓' : 'Segui produttore')
                : 'Accedi per seguire';
            followButton.classList.toggle('ghost', Boolean(data.isFollowing));
            followButton.dataset.loginUrl = data.loginUrl || '';
        }

        const heroActions = root.querySelector('.farm-hero-actions');
        if (heroActions && !heroActions.querySelector('.share-bar')) {
            heroActions.insertAdjacentHTML('beforeend', shareButtons(farmUrl, `🌾 ${farm.name} — Adotta un elemento su Adotta!`));
        }

        root.querySelector('[data-slot="farm-profile-stats"]').innerHTML = [
            statCard('Elementi', data.stats.trees, 'Tutti visibili nella vetrina'),
            statCard('Adottati', data.stats.adoptedTrees, 'Già sponsorizzati'),
            statCard('Follower', data.stats.followers, 'Utenti che seguono gli aggiornamenti'),
        ].join('');

        root.querySelector('[data-slot="farm-profile-trees"]').innerHTML = data.trees.length
            ? data.trees.map((tree) => `
                <a class="tree-row" href="${appUrl(`trees/${tree.id}/`)}">
                    <div>
                        <strong>${escapeHtml(tree.species)}</strong><br>
                        <small>${escapeHtml(tree.code)} · ${escapeHtml(tree.planted_at || 'Data N/D')} · coord. ${escapeHtml(tree.coordinate_source || 'produttore')}</small>
                    </div>
                    <span class="badge">${escapeHtml(tree.status)}${tree.adopter_name ? ` · ${escapeHtml(tree.adopter_name)}` : ''}</span>
                </a>`).join('')
            : '<div class="card empty-state">Nessun elemento pubblicato da questo produttore.</div>';

        root.querySelector('[data-slot="farm-photos"]').innerHTML = data.photos.length
            ? data.photos.slice(0, 6).map((url) => `<a href="${escapeHtml(url)}"><img src="${escapeHtml(url)}" alt="Foto del produttore" loading="lazy"></a>`).join('')
            : '<div class="card empty-state">Nessuna foto ancora.</div>';

        renderFarmProfileMap(data.trees || []);
        renderUpdates(data.updates || [], farm.id);
        renderFarmReviews(data.farm.id, root);

        // Prodotti e baratti del produttore, con contatti diretti
        const _fallbackImg = 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';
        const _imgAttrs = `loading="lazy" onerror="this.onerror=null;this.src='${_fallbackImg}';this.style.objectFit='contain';this.style.padding='20px';this.style.background='var(--surface-soft)'"`;
        const _offerContacts = (subject) => {
            if (!data.logged_in) return `<button class="button ghost" type="button" data-auth-contact>🔒 Accedi per contattare</button>`;
            const out = [];
            if (farm.contact_whatsapp) {
                const text = encodeURIComponent(subject);
                out.push(`<a class="button" href="https://wa.me/${String(farm.contact_whatsapp).replace(/\D/g, '')}?text=${text}" target="_blank" rel="noopener">💬 Contatta</a>`);
            }
            if (farm.contact_phone) out.push(`<a class="button ghost" href="tel:${escapeHtml(farm.contact_phone)}">📞</a>`);
            return out.join('');
        };

        const productsSlot = root.querySelector('[data-slot="farm-products"]');
        if (productsSlot) {
            productsSlot.innerHTML = (data.products || []).length ? data.products.map((p) => `
                <article class="product-card">
                    <img class="product-img" src="${escapeHtml(p.media_url || _fallbackImg)}" alt="${escapeHtml(p.name)}" ${_imgAttrs}>
                    <div class="product-body">
                        <h3 class="product-name">${escapeHtml(p.name)}</h3>
                        ${p.description ? `<p class="product-desc">${escapeHtml(p.description)}</p>` : ''}
                        <p class="product-price">${p.price != null ? `€${Number(p.price).toFixed(2)} / ${escapeHtml(p.unit)}` : escapeHtml(p.unit)}</p>
                        <div class="product-actions">${_offerContacts(`Ciao, sono interessato al prodotto "${p.name}" di ${farm.name}`)}</div>
                    </div>
                </article>`).join('')
                : '<div class="card empty-state">Nessun prodotto pubblicato da questo produttore.</div>';
        }

        const barattiSlot = root.querySelector('[data-slot="farm-baratti"]');
        if (barattiSlot) {
            barattiSlot.innerHTML = (data.baratti || []).length ? data.baratti.map((b) => `
                <article class="product-card barter-card">
                    <img class="product-img" src="${escapeHtml(b.media_url || _fallbackImg)}" alt="${escapeHtml(b.offer_title)}" ${_imgAttrs}>
                    <div class="product-body">
                        <div class="barter-row">
                            <div class="barter-side">
                                <span class="eyebrow">Offro</span>
                                <h3 class="product-name">${escapeHtml(b.offer_title)}</h3>
                            </div>
                            <div class="barter-arrow">⇄</div>
                            <div class="barter-side">
                                <span class="eyebrow">Cerco</span>
                                <h3 class="product-name">${escapeHtml(b.wants_title)}</h3>
                            </div>
                        </div>
                        <div class="product-actions">${_offerContacts(`Ciao! Ho visto la tua offerta di baratto su wido: offri "${b.offer_title}" in cambio di "${b.wants_title}", e sono interessato.`)}</div>
                    </div>
                </article>`).join('')
                : '<div class="card empty-state">Nessun baratto attivo di questo produttore.</div>';
        }
    };

    // ── Lightbox per immagini prodotto/baratto ──────────────────────
    const _injectLightbox = () => {
        if (document.getElementById('img-lightbox')) return;
        document.body.insertAdjacentHTML('beforeend', `
        <dialog class="img-lightbox" id="img-lightbox">
            <button class="img-lightbox-close" data-close-modal aria-label="Chiudi">✕</button>
            <img class="img-lightbox-img" id="img-lightbox-img" src="" alt="">
        </dialog>`);
    };
    document.addEventListener('click', (e) => {
        const img = e.target.closest('.product-img');
        if (!img) return;
        _injectLightbox();
        const lb = document.getElementById('img-lightbox');
        const lbImg = document.getElementById('img-lightbox-img');
        lbImg.src = img.src;
        lbImg.alt = img.alt;
        lb.showModal();
        lbImg.onclick = () => lb.close();
    });

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

    // MERCATO
    const renderMercato = (data) => {
        const slot = root.querySelector('[data-slot="products"]');
        const mapSlot = root.querySelector('[data-slot="mercato-map"]');
        if (!slot) return;

        if (data.is_farmer) {
            const btn = root.querySelector('[data-open-product-form]');
            if (btn) btn.style.display = '';
        }

        // View toggle (mobile): switches data-view on the layout; desktop shows both
        const layout = root.querySelector('[data-market-layout]');
        root.querySelectorAll('[data-view-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.viewToggle;
                root.querySelectorAll('[data-view-toggle]').forEach((b) => b.classList.toggle('active', b === btn));
                if (layout) layout.dataset.view = view;
                if (view === 'map') renderMercatoMap(data.products || []);
            });
        });
        renderMercatoMap(data.products || []);

        const waLink = (p) => {
            if (!data.logged_in) return `<button class="button ghost" type="button" data-auth-contact>🔒 Accedi per contattare</button>`;
            const text = encodeURIComponent(`Ciao, sono interessato al prodotto "${p.name}" di ${p.farm_name}`);
            if (p.contact_whatsapp) return `<a class="button" href="https://wa.me/${p.contact_whatsapp.replace(/\D/g,'')}?text=${text}" target="_blank" rel="noopener">💬 WhatsApp</a>`;
            if (p.contact_phone) return `<a class="button ghost" href="tel:${escapeHtml(p.contact_phone)}">📞 Chiama</a>`;
            if (p.contact_email) return `<a class="button ghost" href="mailto:${escapeHtml(p.contact_email)}">📧 Email</a>`;
            return `<a class="button ghost" href="${appUrl(`farms/${p.farm_id}/`)}">🏡 Vedi produttore</a>`;
        };

        slot.innerHTML = data.products.length ? `<div class="product-grid">${data.products.map((p) => `
            <article class="product-card">
                <img class="product-img" src="${escapeHtml(p.media_url || 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png')}" alt="${escapeHtml(p.name)}" loading="lazy" onerror="this.onerror=null;this.src='https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';this.style.objectFit='contain';this.style.padding='20px';this.style.background='var(--surface-soft)'">
                <div class="product-body">
                    <a class="product-farm-link" href="${appUrl(`farms/${p.farm_id}/`)}">
                        <span class="product-farm">${escapeHtml(p.farm_name)}</span>
                        <span class="product-location">📍 ${escapeHtml(p.location)}${_distLabel(p.map_latitude, p.map_longitude) ? ` · ${_distLabel(p.map_latitude, p.map_longitude).replace(/<[^>]+>/g, '')}` : ''}</span>
                    </a>
                    <h3 class="product-name">${escapeHtml(p.name)}</h3>
                    ${p.description ? `<p class="product-desc">${escapeHtml(p.description)}</p>` : ''}
                    <p class="product-price">${p.price != null ? `€${Number(p.price).toFixed(2)} / ${escapeHtml(p.unit)}` : escapeHtml(p.unit)}</p>
                    <div class="product-actions">${waLink(p)}</div>
                </div>
            </article>`).join('')}</div>`
            : '<div class="card empty-state">Nessun prodotto disponibile al momento.</div>';
    };

    // BARATTO
    const renderBaratto = (data) => {
        const slot = root.querySelector('[data-slot="baratti"]');
        const mapSlot = root.querySelector('[data-slot="baratto-map"]');
        if (!slot) return;

        if (data.is_farmer) {
            const btn = root.querySelector('[data-open-baratto-form]');
            if (btn) btn.style.display = '';
        }

        // View toggle (mobile): switches data-view on the layout; desktop shows both
        const layout = root.querySelector('[data-market-layout]');
        root.querySelectorAll('[data-view-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.viewToggle;
                root.querySelectorAll('[data-view-toggle]').forEach((b) => b.classList.toggle('active', b === btn));
                if (layout) layout.dataset.view = view;
                if (view === 'map') renderBarattoMap(data.baratti || []);
            });
        });
        renderBarattoMap(data.baratti || []);

        const waLink = (b) => {
            if (!data.logged_in) return `<button class="button ghost" type="button" data-auth-contact>🔒 Accedi per contattare</button>`;
            const text = encodeURIComponent(`Ciao! Ho visto la tua offerta di baratto su wido: offri "${b.offer_title}" in cambio di "${b.wants_title}", e sono interessato.`);
            if (b.contact_whatsapp) return `<a class="button" href="https://wa.me/${b.contact_whatsapp.replace(/\D/g,'')}?text=${text}" target="_blank" rel="noopener">💬 Contatta</a>`;
            if (b.contact_phone) return `<a class="button ghost" href="tel:${escapeHtml(b.contact_phone)}">📞 Chiama</a>`;
            if (b.contact_email) return `<a class="button ghost" href="mailto:${escapeHtml(b.contact_email)}">📧 Email</a>`;
            return `<a class="button ghost" href="${appUrl(`farms/${b.farm_id}/`)}">🏡 Vedi produttore</a>`;
        };

        slot.innerHTML = data.baratti.length ? `<div class="product-grid">${data.baratti.map((b) => `
            <article class="product-card barter-card">
                <img class="product-img" src="${escapeHtml(b.media_url || 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png')}" alt="${escapeHtml(b.offer_title)}" loading="lazy" onerror="this.onerror=null;this.src='https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';this.style.objectFit='contain';this.style.padding='20px';this.style.background='var(--surface-soft)'">
                <div class="product-body">
                    <a class="product-farm-link" href="${appUrl(`farms/${b.farm_id}/`)}">
                        <span class="product-farm">${escapeHtml(b.farm_name)}</span>
                        <span class="product-location">📍 ${escapeHtml(b.location)}</span>
                    </a>
                    <div class="barter-row">
                        <div class="barter-side">
                            <span class="eyebrow">Offro</span>
                            <h3 class="product-name">${escapeHtml(b.offer_title)}</h3>
                            ${b.offer_description ? `<p class="product-desc">${escapeHtml(b.offer_description)}</p>` : ''}
                        </div>
                        <div class="barter-arrow">⇄</div>
                        <div class="barter-side">
                            <span class="eyebrow">Cerco</span>
                            <h3 class="product-name">${escapeHtml(b.wants_title)}</h3>
                            ${b.wants_description ? `<p class="product-desc">${escapeHtml(b.wants_description)}</p>` : ''}
                        </div>
                    </div>
                    <div class="product-actions">${waLink(b)}</div>
                </div>
            </article>`).join('')}</div>`
            : '<div class="card empty-state">Nessun baratto disponibile al momento.</div>';
    };

    let mercatoLeafletMap = null;
    let barattoLeafletMap = null;

    const renderMercatoMap = (items) => {
        const slot = root?.querySelector('[data-slot="mercato-map"]');
        if (!slot) return;
        const mapped = items.filter((i) => Number.isFinite(Number(i.map_latitude)) && Number.isFinite(Number(i.map_longitude)));
        if (!mapped.length) {
            slot.innerHTML = '<div class="map-placeholder" style="min-height:260px;"><small>Nessuna coordinata disponibile</small></div>';
            return;
        }
        if (!mercatoLeafletMap) {
            slot.innerHTML = '<div id="mercato-leaflet-map" class="leaflet-map" style="height:340px;border-radius:16px;"></div>';
            mercatoLeafletMap = makeClusterMap('mercato-leaflet-map');
        } else {
            clearMapLayers(mercatoLeafletMap);
        }
        const cluster = makeClusterGroup();
        mapped.forEach((item) => {
            const label = item.name || item.offer_title;
            L.marker([Number(item.map_latitude), Number(item.map_longitude)], { icon: _makeTypeIcon('_shop') })
                .bindPopup(`<strong>${escapeHtml(label)}</strong><br><a href="${appUrl(`farms/${item.farm_id}/`)}">→ ${escapeHtml(item.farm_name)}</a>`)
                .addTo(cluster);
        });
        mercatoLeafletMap.addLayer(cluster);
        try {
            mapped.length === 1
                ? mercatoLeafletMap.setView([Number(mapped[0].map_latitude), Number(mapped[0].map_longitude)], 12)
                : mercatoLeafletMap.fitBounds(cluster.getBounds ? cluster.getBounds() : mapped.map((i) => [Number(i.map_latitude), Number(i.map_longitude)]), { padding: [28, 28], maxZoom: 13 });
        } catch (_) {}
        setTimeout(() => mercatoLeafletMap.invalidateSize(), 80);
    };

    const renderBarattoMap = (items) => {
        const slot = root?.querySelector('[data-slot="baratto-map"]');
        if (!slot) return;
        const mapped = items.filter((i) => Number.isFinite(Number(i.map_latitude)) && Number.isFinite(Number(i.map_longitude)));
        if (!mapped.length) {
            slot.innerHTML = '<div class="map-placeholder" style="min-height:260px;"><small>Nessuna coordinata disponibile</small></div>';
            return;
        }
        if (!barattoLeafletMap) {
            slot.innerHTML = '<div id="baratto-leaflet-map" class="leaflet-map" style="height:340px;border-radius:16px;"></div>';
            barattoLeafletMap = makeClusterMap('baratto-leaflet-map');
        } else {
            clearMapLayers(barattoLeafletMap);
        }
        const cluster = makeClusterGroup();
        mapped.forEach((item) => {
            L.marker([Number(item.map_latitude), Number(item.map_longitude)], { icon: _makeTypeIcon('_barter') })
                .bindPopup(`<strong>${escapeHtml(item.offer_title)}</strong><br>Cerca: ${escapeHtml(item.wants_title)}<br><a href="${appUrl(`farms/${item.farm_id}/`)}">→ ${escapeHtml(item.farm_name)}</a>`)
                .addTo(cluster);
        });
        barattoLeafletMap.addLayer(cluster);
        try {
            mapped.length === 1
                ? barattoLeafletMap.setView([Number(mapped[0].map_latitude), Number(mapped[0].map_longitude)], 12)
                : barattoLeafletMap.fitBounds(cluster.getBounds ? cluster.getBounds() : mapped.map((i) => [Number(i.map_latitude), Number(i.map_longitude)]), { padding: [28, 28], maxZoom: 13 });
        } catch (_) {}
        setTimeout(() => barattoLeafletMap.invalidateSize(), 80);
    };

    const renderPublicCatalog = (data) => {
        _lastAdoptableTrees = data.trees || [];
        renderCatalogFilter(_lastAdoptableTrees);
        renderAdoptableTrees(_filteredTrees());
        _onGeo(() => { if (_lastAdoptableTrees) renderAdoptableTrees(_filteredTrees()); });
    };

    // ── ADMIN DASHBOARD ───────────────────────────────────────────────
    const _adminPost = async (url, body = {}) => {
        const res = await fetch(`${window.AgriSaas.apiBase}${url}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.AgriSaas.nonce },
            body: JSON.stringify(body),
        });
        if (!res.ok) throw new Error(await res.text());
        return res.json();
    };
    const _adminDelete = async (url) => {
        const res = await fetch(`${window.AgriSaas.apiBase}${url}`, {
            method: 'DELETE',
            headers: { 'X-WP-Nonce': window.AgriSaas.nonce },
        });
        if (!res.ok) throw new Error(await res.text());
        return res.json();
    };

    const renderAdminDashboard = (data) => {
        const e = escapeHtml;
        const cell = (v) => `<td>${e(String(v ?? '—'))}</td>`;
        const dateCell = (v) => `<td>${v ? e(v.substring(0, 10)) : '—'}</td>`;
        const btnDel = (type, id) => `<button class="admin-action-btn danger" data-admin-delete="${type}" data-id="${e(String(id))}" title="Elimina">🗑</button>`;

        const _fillTables = (d) => {
            const errs = d.errors && typeof d.errors === 'object' ? Object.entries(d.errors) : [];
            let errBox = root.querySelector('[data-admin-errors]');
            if (!errBox) {
                errBox = document.createElement('div');
                errBox.setAttribute('data-admin-errors', '');
                errBox.style.cssText = 'margin:12px 0;';
                root.querySelector('.section-heading')?.insertAdjacentElement('afterend', errBox);
            }
            const dbg = d.debug || {};
            const dbgLine = `<p style="color:var(--muted);font-size:.75rem;margin:4px 0;">🔎 diag — tema attivo: ${e(String(window.AgriSaas?.version || '?'))} | endpoint: ${d.debug ? 'nuovo' : 'VECCHIO (niente debug — cache PHP?)'} | user_id: ${e(String(dbg.user_id ?? '?'))} | prefix: ${e(String(dbg.db_prefix ?? '?'))} | farms in DB: ${e(String(dbg.farms_rows ?? '?'))} | baratti in DB: ${e(String(dbg.baratti_rows ?? '?'))} | utenti in DB: ${e(String(dbg.users_rows ?? '?'))} | tema: ${e(String(dbg.theme_ver ?? '?'))}${d.__salvaged ? ` | ⚠️ output spurio prima del JSON: <code>${e(String(d.__salvaged))}</code>` : ''}</p>`;
            errBox.innerHTML = dbgLine + (errs.length
                ? `<div class="card" style="border:1px solid #c62828;color:#c62828;padding:12px;font-size:.85rem;"><strong>⚠️ Errori SQL nell'endpoint admin:</strong><br>${errs.map(([k, v]) => `<code>${e(k)}</code>: ${e(v)}`).join('<br>')}</div>`
                : '');
            const farms = d.farms || [];
            root.querySelector('[data-slot="admin-farms"]').innerHTML = farms.map((r) => {
                const verified = Number(r.is_verified) === 1;
                return `<tr data-farm-row="${e(String(r.id))}">
                    ${cell(r.id)}${cell(r.name)}${cell(r.location)}${cell(r.crop_focus)}${cell(r.owner_name)}${cell(r.owner_email)}${cell(r.tree_count)}${cell(r.adoption_count)}
                    <td><button class="admin-verify-btn ${verified ? 'verified' : ''}" data-farm-id="${e(String(r.id))}" data-verified="${verified ? '1' : '0'}">${verified ? '✅ Verificata' : '⬜ Verifica'}</button></td>
                </tr>`;
            }).join('') || '<tr><td colspan="9">Nessun produttore</td></tr>';

            const adoptionStatuses = ['pending', 'active', 'cancelled', 'cancel_requested'];
            root.querySelector('[data-slot="admin-adoptions"]').innerHTML = (d.adoptions || []).map((r) =>
                `<tr data-adoption-row="${e(String(r.id))}">
                 ${cell(r.id)}${cell(r.species)}${cell(r.type)}${cell(r.code)}${cell(r.farm_name)}${cell(r.adopter_name)}${cell(r.adopter_email)}
                 <td>${r.adopter_whatsapp ? `<a href="https://wa.me/${String(r.adopter_whatsapp).replace(/\D/g,'')}" target="_blank">💬 ${e(r.adopter_whatsapp)}</a>` : '—'}</td>
                 <td>${r.adopter_phone ? `<a href="tel:${e(r.adopter_phone)}">📞 ${e(r.adopter_phone)}</a>` : '—'}</td>
                 <td><select class="admin-status-select" data-adoption-id="${e(String(r.id))}">
                     ${adoptionStatuses.map((s) => `<option value="${s}" ${r.status === s ? 'selected' : ''}>${s}</option>`).join('')}
                 </select></td>
                 ${dateCell(r.requested_at)}</tr>`
            ).join('') || '<tr><td colspan="11">Nessuna adozione</td></tr>';

            root.querySelector('[data-slot="admin-users"]').innerHTML = (d.users || []).map((r) =>
                `<tr>
                 ${cell(r.id)}${cell(r.display_name)}${cell(r.user_email)}
                 <td>${r.whatsapp ? `<a href="https://wa.me/${String(r.whatsapp).replace(/\D/g,'')}" target="_blank">💬 ${e(r.whatsapp)}</a>` : '—'}</td>
                 <td>${r.phone ? `<a href="tel:${e(r.phone)}">📞 ${e(r.phone)}</a>` : '—'}</td>
                 ${cell(r.active_adoptions)}${cell(r.farms_count)}${dateCell(r.user_registered)}
                 <td><button class="admin-action-btn" data-impersonate="${e(String(r.id))}" title="Impersona">👤 Accedi come</button></td>
                </tr>`
            ).join('') || '<tr><td colspan="9">Nessun utente</td></tr>';

            root.querySelector('[data-slot="admin-products"]').innerHTML = (d.products || []).map((r) =>
                `<tr>${cell(r.id)}${cell(r.name)}<td>${r.price != null ? `€${Number(r.price).toFixed(2)}` : '—'}</td>${cell(r.unit)}${cell(r.price_note)}${cell(r.farm_name)}${cell(r.location)}${cell(r.owner_name)}${dateCell(r.created_at)}<td>${btnDel('product', r.id)}</td></tr>`
            ).join('') || '<tr><td colspan="10">Nessun prodotto</td></tr>';

            root.querySelector('[data-slot="admin-baratti"]').innerHTML = (d.baratti || []).map((r) =>
                `<tr>${cell(r.id)}${cell(r.offer_title)}${cell(r.wants_title)}${cell(r.farm_name)}${cell(r.location)}${cell(r.owner_name)}${dateCell(r.created_at)}<td>${btnDel('baratto', r.id)}</td></tr>`
            ).join('') || '<tr><td colspan="8">Nessun baratto</td></tr>';
        };

        _fillTables(data);

        const _refreshTables = async () => {
            try {
                const res  = await fetch(`${window.AgriSaas.apiBase}${bust('/admin/overview')}`, { headers: { 'X-WP-Nonce': window.AgriSaas.nonce } });
                const fresh = await res.json();
                _fillTables(fresh);
            } catch (_) {}
        };

        // ── Danger zone: reset all content ───────────────────────────
        const resetBtn = root.querySelector('[data-admin-reset]');
        if (resetBtn) {
            resetBtn.addEventListener('click', async () => {
                const first = confirm('⚠️ ATTENZIONE: questa operazione elimina TUTTI i produttori, elementi, adozioni, prodotti, baratti e aggiornamenti. Gli account utente restano intatti.\n\nSei sicuro di voler continuare?');
                if (!first) return;
                const code = prompt('Digita ELIMINA_TUTTO per confermare:');
                if (code !== 'ELIMINA_TUTTO') { alert('Operazione annullata.'); return; }
                resetBtn.disabled = true;
                resetBtn.textContent = 'Eliminazione…';
                try {
                    await _adminPost('/admin/reset-all-content', { confirm: 'ELIMINA_TUTTO' });
                    alert('✅ Tutti i contenuti eliminati. La pagina verrà ricaricata.');
                    window.location.reload();
                } catch (err) {
                    alert(`❌ Errore: ${err.message}`);
                    resetBtn.disabled = false;
                    resetBtn.textContent = '🗑 Svuota tutto';
                }
            });
        }

        // Tab switching
        root.querySelectorAll('[data-admin-tab]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.adminTab;
                root.querySelectorAll('[data-admin-tab]').forEach((b) => b.classList.toggle('active', b === btn));
                root.querySelectorAll('[data-admin-panel]').forEach((p) => { p.hidden = p.dataset.adminPanel !== tab; });
                _adminFilterSearch(document.getElementById('admin-search')?.value || '');
            });
        });

        // Live search/filter
        const _adminFilterSearch = (q) => {
            const lq = q.toLowerCase();
            const activePanel = root.querySelector('[data-admin-panel]:not([hidden])');
            if (!activePanel) return;
            activePanel.querySelectorAll('tbody tr').forEach((row) => {
                row.style.display = !lq || row.textContent.toLowerCase().includes(lq) ? '' : 'none';
            });
        };
        document.getElementById('admin-search')?.addEventListener('input', (ev) => _adminFilterSearch(ev.target.value));

        // ── Action: verify/de-verify farm ────────────────────────────
        root.addEventListener('click', async (ev) => {
            const btn = ev.target.closest('[data-farm-id]');
            if (!btn) return;
            const farmId  = btn.dataset.farmId;
            const wasVeri = btn.dataset.verified === '1';
            btn.disabled  = true;
            try {
                const res = await _adminPost(`/admin/farms/${farmId}/verify`);
                const isVeri = Number(res.is_verified) === 1;
                btn.dataset.verified = isVeri ? '1' : '0';
                btn.textContent = isVeri ? '✅ Verificata' : '⬜ Verifica';
                btn.classList.toggle('verified', isVeri);
            } catch (_) { alert('Errore durante la verifica.'); }
            btn.disabled = false;
        }, { capture: false });

        // ── Action: change adoption status ───────────────────────────
        root.addEventListener('change', async (ev) => {
            const sel = ev.target.closest('[data-adoption-id]');
            if (!sel) return;
            const adoptionId = sel.dataset.adoptionId;
            const newStatus  = sel.value;
            if (!confirm(`Imposta stato adozione #${adoptionId} → "${newStatus}"?`)) {
                // revert
                sel.value = sel.dataset.prev || sel.value;
                return;
            }
            sel.dataset.prev = newStatus;
            try {
                await _adminPost(`/admin/adoptions/${adoptionId}/status`, { status: newStatus });
                const row = root.querySelector(`[data-adoption-row="${adoptionId}"]`);
                if (row) row.style.opacity = newStatus === 'cancelled' ? '.45' : '1';
            } catch (_) { alert('Errore nel cambio stato.'); }
        });

        // ── Action: delete tree / product / baratto ──────────────────
        root.addEventListener('click', async (ev) => {
            const btn = ev.target.closest('[data-admin-delete]');
            if (!btn) return;
            const type = btn.dataset.adminDelete;
            const id   = btn.dataset.id;
            if (!confirm(`Eliminare ${type} #${id}? Operazione irreversibile.`)) return;
            btn.disabled = true;
            try {
                await _adminDelete(`/admin/${type === 'baratto' ? 'baratti' : type + 's'}/${id}`);
                btn.closest('tr')?.remove();
            } catch (_) { alert('Errore durante l\'eliminazione.'); btn.disabled = false; }
        });

        // ── Action: impersonate user ─────────────────────────────────
        root.addEventListener('click', async (ev) => {
            const btn = ev.target.closest('[data-impersonate]');
            if (!btn) return;
            const userId = btn.dataset.impersonate;
            if (!confirm(`Accedere come utente #${userId}? Verrai disconnesso dal tuo account admin.`)) return;
            btn.disabled = true;
            try {
                const res = await _adminPost(`/admin/impersonate/${userId}`);
                window.location.href = res.redirect || '/dashboard/';
            } catch (_) { alert('Errore durante l\'impersonazione.'); btn.disabled = false; }
        });

        // ── Creation panel ───────────────────────────────────────────
        const _renderCreatePanel = async () => {
            const slot = root.querySelector('[data-slot="admin-create-panel"]');
            if (!slot) return;
            slot.innerHTML = '<p style="color:var(--muted);">Caricamento utenti…</p>';

            // Fetch WP users and farms live — independent of overview cache
            let wpUsers = [], liveFarms = [];
            try {
                const [uRes, oRes] = await Promise.all([
                    fetch(`${window.AgriSaas.apiBase}${bust('/admin/wp-users')}`, { headers: { 'X-WP-Nonce': window.AgriSaas.nonce } }),
                    fetch(`${window.AgriSaas.apiBase}${bust('/admin/overview')}`, { headers: { 'X-WP-Nonce': window.AgriSaas.nonce } }),
                ]);
                wpUsers   = await uRes.json();
                const ov  = await oRes.json();
                liveFarms = ov.farms || [];
            } catch (_) {}

            const farmOptions = liveFarms.map((f) =>
                `<option value="${escapeHtml(String(f.id))}">${escapeHtml(f.name)} (${escapeHtml(f.location)})</option>`
            ).join('');
            const userOptions = wpUsers.map((u) =>
                `<option value="${escapeHtml(String(u.id))}">${escapeHtml(u.display_name)} — ${escapeHtml(u.user_email)}</option>`
            ).join('');

            slot.innerHTML = `
            <div class="admin-create-tabs">
                <button class="admin-create-tab active" data-create-tab="farm">🏡 Produttore</button>
                <button class="admin-create-tab" data-create-tab="tree">🌱 Elemento</button>
                <button class="admin-create-tab" data-create-tab="product">🛒 Prodotto</button>
                <button class="admin-create-tab" data-create-tab="baratto">🤝 Baratto</button>
                <button class="admin-create-tab" data-create-tab="update">📣 Aggiornamento</button>
            </div>

            <!-- FORM PRODUTTORE -->
            <div class="admin-create-form card" data-create-panel="farm">
                <p class="eyebrow">Nuova produttore</p>
                <h2 style="margin-bottom:20px;">Crea produttore</h2>
                <form data-admin-form="farm" class="admin-form-grid">
                    <label>Proprietario (utente) <select name="owner_user_id" required><option value="">Seleziona…</option>${userOptions}</select></label>
                    <div class="form-grid-2">
                        <label>Nome produttore <input name="name" required></label>
                        <label>Località <input name="location" required></label>
                    </div>
                    <div class="form-grid-2">
                        <label>Coltura principale <input name="crop_focus"></label>
                        <label>Ettari <input name="acreage" type="number" step="0.01" min="0"></label>
                    </div>
                    <div class="form-grid-2">
                        <label>Latitudine <input name="latitude" type="number" step="0.0000001"></label>
                        <label>Longitudine <input name="longitude" type="number" step="0.0000001"></label>
                    </div>
                    <label>Descrizione <textarea name="description" rows="3"></textarea></label>
                    <div class="form-grid-2">
                        <label>Email contatto <input name="contact_email" type="email"></label>
                        <label>WhatsApp <input name="contact_whatsapp" type="tel"></label>
                    </div>
                    <label>Foto produttore <input name="farm_photo" type="file" accept="image/*" data-admin-photo-input><input type="hidden" name="media_url" data-admin-media-url><span class="map-note" data-admin-upload-status></span></label>
                    <label class="checkbox-label"><input type="checkbox" name="is_verified" value="1"> Segna come verificata subito</label>
                    <button class="button" type="submit">Crea produttore</button>
                    <p class="form-status" data-form-status></p>
                </form>
            </div>

            <!-- FORM ELEMENTO -->
            <div class="admin-create-form card" data-create-panel="tree" hidden>
                <p class="eyebrow">Nuovo elemento</p>
                <h2 style="margin-bottom:20px;">Crea elemento adottabile</h2>
                <form data-admin-form="tree" class="admin-form-grid">
                    <label>Produttore <select name="farm_id" required><option value="">Seleziona…</option>${farmOptions}</select></label>
                    <div class="form-grid-2">
                        <label>Specie / nome <input name="species" required placeholder="es. Oliva Taggiasca"></label>
                        <label>Codice univoco <input name="code" required placeholder="es. OLV-001"></label>
                    </div>
                    <div class="form-grid-2">
                        <label>Tipo <select name="type">
                            <option value="albero">🌳 Elemento</option>
                            <option value="olivo">🫒 Olivo</option>
                            <option value="vite">🍇 Vite</option>
                            <option value="alveare">🐝 Alveare</option>
                            <option value="animale">🐄 Animale</option>
                            <option value="bosco">🌲 Bosco</option>
                            <option value="terreno">🌾 Terreno</option>
                            <option value="orto">🥦 Orto</option>
                            <option value="altro">🌿 Altro</option>
                        </select></label>
                        <label>Stato <select name="status">
                            <option value="available">Disponibile</option>
                            <option value="adopted">Adottato</option>
                            <option value="maintenance">Manutenzione</option>
                        </select></label>
                    </div>
                    <div class="form-grid-2">
                        <label>Piantato/creato il <input name="planted_at" placeholder="es. 2010 o 2010-03"></label>
                        <label>URL foto <input name="media_url" type="url"></label>
                    </div>
                    <div class="form-grid-2">
                        <label>Latitudine <input name="latitude" type="number" step="0.0000001"></label>
                        <label>Longitudine <input name="longitude" type="number" step="0.0000001"></label>
                    </div>
                    <label>Descrizione <textarea name="description" rows="3"></textarea></label>
                    <button class="button" type="submit">Crea elemento</button>
                    <p class="form-status" data-form-status></p>
                </form>
            </div>

            <!-- FORM PRODOTTO -->
            <div class="admin-create-form card" data-create-panel="product" hidden>
                <p class="eyebrow">Nuovo prodotto</p>
                <h2 style="margin-bottom:20px;">Crea prodotto mercato</h2>
                <form data-admin-form="product" class="admin-form-grid">
                    <label>Produttore <select name="farm_id" required><option value="">Seleziona…</option>${farmOptions}</select></label>
                    <div class="form-grid-2">
                        <label>Nome prodotto <input name="name" required></label>
                        <label>Unità <input name="unit" placeholder="es. kg, litro, confezione"></label>
                    </div>
                    <div class="form-grid-2">
                        <label>Prezzo (€) <input name="price" type="number" step="0.01" min="0"></label>
                        <label>Note prezzo <input name="price_note" placeholder="es. Su richiesta"></label>
                    </div>
                    <label>Descrizione <textarea name="description" rows="3"></textarea></label>
                    <label>Foto prodotto <input name="farm_photo" type="file" accept="image/*" data-admin-photo-input><input type="hidden" name="media_url" data-admin-media-url><span class="map-note" data-admin-upload-status></span></label>
                    <button class="button" type="submit">Crea prodotto</button>
                    <p class="form-status" data-form-status></p>
                </form>
            </div>

            <!-- FORM BARATTO -->
            <div class="admin-create-form card" data-create-panel="baratto" hidden>
                <p class="eyebrow">Nuovo baratto</p>
                <h2 style="margin-bottom:20px;">Crea offerta baratto</h2>
                <form data-admin-form="baratto" class="admin-form-grid">
                    <label>Produttore <select name="farm_id" required><option value="">Seleziona…</option>${farmOptions}</select></label>
                    <div class="form-grid-2">
                        <label>Offro (titolo) <input name="offer_title" required placeholder="es. Olio EVO 5L"></label>
                        <label>Cerco (titolo) <input name="wants_title" required placeholder="es. Miele artigianale"></label>
                    </div>
                    <div class="form-grid-2">
                        <label>Descrizione offerta <textarea name="offer_description" rows="2"></textarea></label>
                        <label>Descrizione richiesta <textarea name="wants_description" rows="2"></textarea></label>
                    </div>
                    <label>Foto baratto <input name="farm_photo" type="file" accept="image/*" data-admin-photo-input><input type="hidden" name="media_url" data-admin-media-url><span class="map-note" data-admin-upload-status></span></label>
                    <button class="button" type="submit">Crea baratto</button>
                    <p class="form-status" data-form-status></p>
                </form>
            </div>

            <!-- FORM AGGIORNAMENTO -->
            <div class="admin-create-form card" data-create-panel="update" hidden>
                <p class="eyebrow">Nuovo aggiornamento</p>
                <h2 style="margin-bottom:20px;">Pubblica aggiornamento</h2>
                <form data-admin-form="update" class="admin-form-grid">
                    <label>Produttore <select name="farm_id" required><option value="">Seleziona…</option>${farmOptions}</select></label>
                    <label>Titolo <input name="title" required></label>
                    <label>Testo <textarea name="body" rows="4"></textarea></label>
                    <div class="form-grid-2">
                        <label>Visibilità <select name="visibility">
                            <option value="public">Pubblico</option>
                            <option value="adopters">Solo adottanti</option>
                            <option value="private">Privato</option>
                        </select></label>
                        <label>URL foto <input name="media_url" type="url"></label>
                    </div>
                    <button class="button" type="submit">Pubblica aggiornamento</button>
                    <p class="form-status" data-form-status></p>
                </form>
            </div>`;

            // Sub-tab switching
            slot.querySelectorAll('[data-create-tab]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    slot.querySelectorAll('[data-create-tab]').forEach((b) => b.classList.toggle('active', b === btn));
                    slot.querySelectorAll('[data-create-panel]').forEach((p) => { p.hidden = p.dataset.createPanel !== btn.dataset.createTab; });
                });
            });

            // Form submissions
            slot.querySelectorAll('[data-admin-form]').forEach((form) => {
                form.addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    const type   = form.dataset.adminForm;
                    const status = form.querySelector('[data-form-status]');
                    const btn    = form.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    // Upload photo if file input present
                    const photoInput  = form.querySelector('[data-admin-photo-input]');
                    const mediaUrlEl  = form.querySelector('[data-admin-media-url]');
                    const uploadStatus = form.querySelector('[data-admin-upload-status]');
                    if (photoInput?.files?.length) {
                        if (uploadStatus) uploadStatus.textContent = 'Caricamento foto…';
                        const fd = new FormData();
                        fd.append('photo', photoInput.files[0]);
                        try {
                            const up = await apiFetch('/media/photo', { method: 'POST', body: fd });
                            if (mediaUrlEl) mediaUrlEl.value = up.url;
                            if (uploadStatus) uploadStatus.textContent = `Foto caricata (${Math.round((up.size || 0) / 1024)} KB)`;
                        } catch (_) { status.textContent = '❌ Errore durante il caricamento della foto.'; btn.disabled = false; return; }
                    }
                    // Require photo for farm, product, baratto
                    if (['farm','product','baratto'].includes(type) && !form.querySelector('[data-admin-media-url]')?.value) {
                        status.style.color = 'var(--error)';
                        status.textContent = '❌ La foto è obbligatoria.';
                        btn.disabled = false; return;
                    }
                    const data = Object.fromEntries(new FormData(form));
                    delete data.farm_photo;
                    // checkbox: if unchecked it won't appear in FormData
                    if (type === 'farm') data.is_verified = form.querySelector('[name="is_verified"]')?.checked ? 1 : 0;
                    status.textContent = 'Salvataggio…';
                    try {
                        const res = await _adminPost(`/admin/create/${type}`, data);
                        status.style.color = 'var(--brand)';
                        status.textContent = `✅ Creato con successo (ID: ${res.id}${res.name ? ' — ' + res.name : ''})`;
                        form.reset();
                        // Refresh all admin tables and, if a farm was created, also update farm selects
                        await _refreshTables();
                        if (type === 'farm') {
                            try {
                                const oRes  = await fetch(`${window.AgriSaas.apiBase}${bust('/admin/overview')}`, { headers: { 'X-WP-Nonce': window.AgriSaas.nonce } });
                                const fresh = await oRes.json();
                                const newOpts = '<option value="">Seleziona…</option>' + (fresh.farms || []).map((f) =>
                                    `<option value="${escapeHtml(String(f.id))}">${escapeHtml(f.name)} (${escapeHtml(f.location)})</option>`
                                ).join('');
                                slot.querySelectorAll('select[name="farm_id"]').forEach((sel) => { sel.innerHTML = newOpts; });
                            } catch (_) {}
                        }
                    } catch (err) {
                        status.style.color = 'var(--error)';
                        status.textContent = `❌ Errore: ${err.message}`;
                    }
                    btn.disabled = false;
                });
            });
        };

        // Render create panel when tab is clicked
        root.querySelector('[data-admin-tab][data-admin-tab="create"]')
            ?.addEventListener('click', () => {
                // only render once
                if (!root.querySelector('[data-create-tab]')) _renderCreatePanel();
            });
    };

    const renderProfile = (data) => {
        const user      = data.user || {};
        const stats     = data.stats || {};
        const adoptions = data.adoptions || [];
        const WIDO_PH   = 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png';

        renderLevelBadge(stats.activeAdoptions || 0);

        root.querySelector('[data-slot="profile-info"]').innerHTML = `
            <p class="eyebrow">Account</p>
            <h2 style="margin-bottom:20px;">${escapeHtml(user.display_name || '')}</h2>
            <form data-profile-form class="profile-form">
                <label>Nome visualizzato<input name="display_name" value="${escapeHtml(user.display_name || '')}" required></label>
                <label>Email<input name="email" value="${escapeHtml(user.user_email || '')}" type="email" disabled style="opacity:.5;cursor:not-allowed;"></label>
                <div class="form-grid-2">
                    <label>WhatsApp<input name="whatsapp" value="${escapeHtml(user.whatsapp || '')}" type="tel"></label>
                    <label>Telefono<input name="phone" value="${escapeHtml(user.phone || '')}" type="tel"></label>
                </div>
                <button class="button" type="submit">Salva modifiche</button>
                <p class="form-status" data-form-status></p>
            </form>`;

        root.querySelector('[data-slot="profile-stats"]').innerHTML = `
            <p class="eyebrow">Le tue statistiche</p>
            <div style="display:flex;flex-direction:column;gap:14px;margin-top:12px;">
                <div class="profile-stat"><span class="profile-stat-num">${stats.activeAdoptions || 0}</span><span>Adozioni attive</span></div>
                <div class="profile-stat"><span class="profile-stat-num">${stats.adoptedTrees || 0}</span><span>Elementi adottati</span></div>
                ${stats.farms ? `<div class="profile-stat"><span class="profile-stat-num">${stats.farms}</span><span>Attività gestite</span></div>` : ''}
            </div>`;

        const statusLabel = { active: '✅ Attiva', pending: '⏳ In attesa', cancelled: '❌ Cancellata', cancel_requested: '⚠️ Cancellazione richiesta' };
        root.querySelector('[data-slot="profile-adoptions"]').innerHTML = `
            <div class="section-heading"><div><p class="eyebrow">Storico</p><h2>Le mie adozioni</h2></div></div>
            ${adoptions.length ? `<div class="profile-adoptions-grid">
                ${adoptions.map((a) => {
                    const photo = a.media_url || a.farm_photo || WIDO_PH;
                    return `<a class="profile-adoption-card" href="${appUrl(`trees/${a.tree_id}/`)}">
                        <img src="${escapeHtml(photo)}" alt="${escapeHtml(a.species || '')}" loading="lazy" onerror="this.onerror=null;this.src='${WIDO_PH}';this.style.objectFit='contain';this.style.padding='12px'">
                        <div class="profile-adoption-info">
                            <strong>${escapeHtml(a.species || '—')}</strong>
                            <small>${escapeHtml(a.farm_name || '')} · ${escapeHtml(a.location || '')}</small>
                            <span class="profile-adoption-status">${statusLabel[a.status] || a.status}</span>
                        </div>
                    </a>`;
                }).join('')}
            </div>` : '<div class="card empty-state">Nessuna adozione ancora.</div>'}`;

        // Form submit
        root.querySelector('[data-profile-form]')?.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const btn    = ev.target.querySelector('button[type="submit"]');
            const status = ev.target.querySelector('[data-form-status]');
            const fd     = new FormData(ev.target);
            btn.disabled = true; status.textContent = 'Salvataggio…';
            try {
                const res = await fetch(`${window.AgriSaas.apiBase}/profile`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.AgriSaas.nonce },
                    body: JSON.stringify(Object.fromEntries(fd)),
                });
                if (!res.ok) throw new Error('Errore server');
                const r = await res.json();
                status.style.color = 'var(--brand)';
                status.textContent = '✅ Salvato!';
                // Update name in topbar
                document.querySelectorAll('.user-pill, .mobile-user').forEach((el) => { el.textContent = r.display_name; });
            } catch (_) { status.style.color = 'var(--error)'; status.textContent = '❌ Errore durante il salvataggio.'; }
            btn.disabled = false;
        });
    };

    const renderers = {
        'client-dashboard': renderClientDashboard,
        'farm-dashboard':   renderFarmDashboard,
        'tree-detail':      renderTreeDetail,
        'updates-feed':     (data) => {
            renderUpdates(data.updates || []);
            if (window.AgriSaas && window.AgriSaas.userId) {
                const updatesCard = root?.querySelector('[data-slot="updates"]')?.closest('.card');
                if (updatesCard && !updatesCard.querySelector('.updates-toggle')) {
                    const toggle = document.createElement('div');
                    toggle.className = 'updates-toggle dashboard-content-tabs';
                    toggle.innerHTML = `
                        <button class="dash-content-tab active" data-updates-filter="all">Tutti gli aggiornamenti</button>
                        <button class="dash-content-tab" data-updates-filter="mine">I miei aggiornamenti</button>`;
                    updatesCard.querySelector('.section-heading')?.insertAdjacentElement('afterend', toggle);
                    toggle.addEventListener('click', async (e) => {
                        const btn = e.target.closest('[data-updates-filter]');
                        if (!btn) return;
                        toggle.querySelectorAll('.dash-content-tab').forEach((b) => b.classList.remove('active'));
                        btn.classList.add('active');
                        const mine = btn.dataset.updatesFilter === 'mine';
                        const slot = root.querySelector('[data-slot="updates"]');
                        if (slot) slot.innerHTML = '<div class="card empty-state">Caricamento…</div>';
                        try {
                            const result = await apiFetch('/updates' + (mine ? '?mine=1' : ''));
                            renderUpdates(result.updates || []);
                        } catch (_) {
                            if (slot) slot.innerHTML = '<div class="card empty-state" style="color:#c62828;">Errore nel caricamento aggiornamenti.</div>';
                        }
                    });
                }
            }
        },
        'farm-profile':     renderFarmProfile,
        'mercato':          renderMercato,
        'baratto':          renderBaratto,
        'public-catalog':   renderPublicCatalog,
        'public-explore':   (data) => { _lastAdoptableTrees = data.trees || []; renderCatalogFilter(_lastAdoptableTrees); initExplore(); },
        'admin-dashboard':  renderAdminDashboard,
        'profile':          renderProfile,
    };

    const loadRoot = () => {
        if (!root) return Promise.resolve();
        return apiFetch(root.dataset.agriEndpoint)
            .then((data) => renderers[root.dataset.render]?.(data))
            .catch((err) => {
                console.error('[AgriSaas] loadRoot error:', err);
                const slot = root.querySelector('[data-slot="tree"], [data-slot="stats"], [data-slot="trees"]') || root;
                slot.innerHTML = `<div class="card empty-state" style="color:#c62828;">⚠️ Impossibile caricare i dati: ${escapeHtml(err?.message || 'errore sconosciuto')}. Ricarica la pagina.</div>`;
            });
    };

    const openModal = (selector) => {
        const modal = document.querySelector(selector);
        if (!modal) return;
        if (typeof modal.showModal === 'function') {
            modal.showModal();
        } else {
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
        setTimeout(() => { try { initCoordinateMaps(); refreshCoordinateMaps(); } catch(e) {} }, 50);
    };

    const closeAllModals = () => {
        document.querySelectorAll('dialog[open]').forEach((d) => d.close());
        document.querySelectorAll('.modal-backdrop.is-open').forEach((m) => m.classList.remove('is-open'));
        document.body.style.overflow = '';
    };

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-close-modal]')) { closeAllModals(); return; }
        if (e.target.tagName === 'DIALOG') { e.target.close(); document.body.style.overflow = ''; }
        if (e.target.classList.contains('modal-backdrop')) { closeAllModals(); }
        if (e.target.closest('[data-auth-contact]')) { showAuthModal(); return; }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { document.body.style.overflow = ''; } });
    document.querySelectorAll('dialog').forEach((d) => d.addEventListener('close', () => { document.body.style.overflow = ''; }));

    const bindDashboardActions = () => {
        if (!root) return;
        document.querySelector('[data-open-tree-form]')?.addEventListener('click',     () => openModal('[data-tree-form]'));
        document.querySelector('[data-open-update-form]')?.addEventListener('click',   () => openModal('[data-update-form]'));
        document.querySelector('[data-open-product-form]')?.addEventListener('click',  () => openModal('[data-product-form]'));
        document.querySelector('[data-open-become-producer]')?.addEventListener('click', () => openModal('[data-become-producer-form]'));
        document.querySelector('[data-open-baratto-form]')?.addEventListener('click',  () => openModal('[data-baratto-form]'));

        // Adoptable trees: view toggle (map ↔ list)
        root.querySelectorAll('[data-view-toggle]').forEach((btn) => {
            if (btn.closest('[data-slot="mercato-map"]') || btn.closest('[data-slot="baratto-map"]')) return;
            btn.addEventListener('click', () => {
                const view = btn.dataset.viewToggle;
                btn.closest('.section-heading, article')?.querySelectorAll('[data-view-toggle]').forEach((b) => {
                    b.classList.toggle('active', b === btn);
                    b.classList.toggle('ghost', b !== btn);
                });
                const mapSlot  = root.querySelector('[data-slot="adoptable-map"]');
                const listSlot = root.querySelector('[data-slot="adoptable-trees"]');
                if (!mapSlot || !listSlot) return;
                if (view === 'map') {
                    mapSlot.style.display  = '';
                    listSlot.style.display = 'none';
                    if (adoptableLeafletMap) setTimeout(() => adoptableLeafletMap.invalidateSize(), 80);
                } else {
                    mapSlot.style.display  = 'none';
                    listSlot.style.display = '';
                }
            });
        });

        document.addEventListener('click', async (event) => {
            const requestButton = event.target.closest('[data-request-adoption]');
            if (requestButton) {
                if (!window.AgriSaas.userId) { showAuthModal(); return; }
                requestButton.disabled = true;
                await apiFetch('/adoption-requests', { method: 'POST', body: JSON.stringify({ tree_id: requestButton.dataset.requestAdoption }) });
                requestButton.textContent = 'In attesa';
                loadAdoptableTrees();
                return;
            }
            const followButton = event.target.closest('[data-follow-farm]');
            if (followButton) {
                if (!window.AgriSaas.userId) { showAuthModal(); return; }
                followButton.disabled = true;
                const method = followButton.dataset.following === '1' ? 'DELETE' : 'POST';
                await apiFetch(`/farms/${followButton.dataset.farmId}/follow`, { method, body: JSON.stringify({}) });
                await loadRoot();
                followButton.disabled = false;
                return;
            }
            const cancelButton = event.target.closest('[data-request-cancel]');
            if (cancelButton) {
                if (!confirm('Sei sicuro di voler richiedere la cancellazione di questa adozione? Il produttore dovrà confermare.')) return;
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

                const mediaUrl = form.querySelector('[data-tree-media-url]')?.value?.trim();
                if (!mediaUrl) { if (statusEl) statusEl.textContent = '❌ Carica una foto prima di pubblicare.'; submitBtn.disabled = false; return; }

                const formData = new FormData(form);
                const payload = Object.fromEntries(formData.entries());
                delete payload.tree_photo;
                const rewardIds = formData.getAll('reward_ids[]').map(Number).filter(Boolean);
                const hasRewards = form.querySelectorAll('[data-tree-reward-checkboxes] input[type="checkbox"]').length > 0;
                if (hasRewards && !rewardIds.length) { if (statusEl) statusEl.textContent = '❌ Seleziona almeno un premio da associare all\'elemento.'; submitBtn.disabled = false; return; }
                payload.reward_ids = rewardIds;

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

        const bindSimpleModal = (formSelector, endpoint) => {
            document.querySelector(formSelector)?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const form = event.currentTarget;
                const btn = form.querySelector('[type="submit"]');
                const statusEl = form.querySelector('[data-form-status]');
                btn.disabled = true;
                if (statusEl) statusEl.textContent = '';
                try {
                    const fd = new FormData(form);
                    const payload = {};
                    fd.forEach((v, k) => {
                        if (k !== 'photo' && k !== 'price_option') payload[k] = v;
                    });
                    // price_option: "su_richiesta" or "prezzo_variabile" — overrides price
                    const priceOpt = fd.get('price_option');
                    if (priceOpt) { payload.price = null; payload.unit = priceOpt === 'su_richiesta' ? 'su richiesta' : 'prezzo variabile'; }
                    // Handle photo upload
                    const fileInput = form.querySelector('input[type="file"][name="photo"]');
                    if (fileInput?.files?.length) {
                        const uploadData = new FormData();
                        uploadData.append('photo', fileInput.files[0]);
                        const upload = await apiFetch('/media/photo', { method: 'POST', body: uploadData });
                        payload.media_url = upload.url;
                    }
                    // publish_update checkbox: FormData gives 'on' when checked
                    if (payload.publish_update) payload.publish_update = true;
                    await apiFetch(endpoint, { method: 'POST', body: JSON.stringify(payload) });
                    closeAllModals();
                    window.location.reload();
                } catch (err) {
                    if (statusEl) statusEl.textContent = err.message || 'Errore. Riprova.';
                    btn.disabled = false;
                }
            });
        };
        bindSimpleModal('[data-agri-product-form]', '/mercato');
        bindSimpleModal('[data-agri-baratto-form]', '/baratto');

        // Diventa produttore
        const bpForm = document.querySelector('[data-agri-become-producer-form]');
        if (bpForm) {
            bpForm.addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const btn = bpForm.querySelector('button[type="submit"]');
                const statusEl = bpForm.querySelector('[data-form-status]');
                btn.disabled = true;
                if (statusEl) statusEl.textContent = '';
                try {
                    const fd = new FormData(bpForm);
                    const payload = {};
                    fd.forEach((v, k) => { payload[k] = v; });
                    const res = await apiFetch('/farms/become', { method: 'POST', body: JSON.stringify(payload) });
                    if (statusEl) statusEl.textContent = '✅ Profilo produttore creato! Reindirizzamento…';
                    window.location.href = res.redirect || appUrl('farm-dashboard/');
                } catch (err) {
                    if (statusEl) statusEl.textContent = err.message || 'Errore. Riprova.';
                    btn.disabled = false;
                }
            });
        }
    };

    const bindRegistration = () => {
        const panels = document.querySelectorAll('[data-registration-panel]');
        if (!panels.length) return;
        const openRegTab = (type) => {
            panels.forEach((panel) => { panel.hidden = panel.dataset.registrationPanel !== type; });
            document.querySelectorAll('[data-registration-tab]').forEach((btn) => btn.classList.toggle('ghost', btn !== btn.closest('[data-registration-tab]') || btn.dataset.registrationTab !== type));
            initCoordinateMaps();
            refreshCoordinateMaps();
        };
        document.querySelectorAll('[data-registration-tab]').forEach((tab) => {
            tab.addEventListener('click', () => openRegTab(tab.dataset.registrationTab));
        });
        // Auto-open from ?type= URL param
        const urlType = new URLSearchParams(window.location.search).get('type');
        if (urlType === 'client' || urlType === 'farm') {
            openRegTab(urlType);
        }
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

    // ── Mobile hamburger drawer ────────────────────────────────────
    const hamburger = document.getElementById('nav-hamburger');
    const navDrawer  = document.getElementById('nav-drawer');
    const navOverlay = document.getElementById('nav-overlay');
    const navClose   = document.getElementById('nav-close');

    const openDrawer = () => {
        navDrawer?.classList.add('is-open');
        navOverlay?.classList.add('is-visible');
        navDrawer?.setAttribute('aria-hidden', 'false');
        hamburger?.setAttribute('aria-expanded', 'true');
        hamburger?.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    };
    const closeDrawer = () => {
        navDrawer?.classList.remove('is-open');
        navOverlay?.classList.remove('is-visible');
        navDrawer?.setAttribute('aria-hidden', 'true');
        hamburger?.setAttribute('aria-expanded', 'false');
        hamburger?.classList.remove('is-open');
        document.body.style.overflow = '';
    };

    hamburger?.addEventListener('click', openDrawer);
    navClose?.addEventListener('click', closeDrawer);
    navOverlay?.addEventListener('click', closeDrawer);
    navDrawer?.querySelectorAll('a').forEach((a) => a.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });

    // Catalog list/map toggle
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.catalog-toggle-btn[data-catalog-view]');
        if (!btn) return;
        const view = btn.dataset.catalogView;
        // Buttons may be inside .section-heading which is a sibling of .pub-catalog-view —
        // walk up to the nearest card/article ancestor that contains both
        const container = btn.closest('article, .card, [data-agri-endpoint]');
        if (!container) return;
        container.querySelectorAll('.catalog-toggle-btn').forEach((b) => b.classList.toggle('active', b === btn));
        container.querySelectorAll('[data-catalog-panel]').forEach((p) => { p.hidden = p.dataset.catalogPanel !== view; });
        if (view === 'map') {
            setTimeout(() => {
                if (adoptableLeafletMap) {
                    adoptableLeafletMap.invalidateSize();
                } else {
                    _initAdoptableLeafletMap();
                }
            }, 150);
        }
    });

    window._agriShowAuthModal = showAuthModal;
    bindCoordinateButtons();
    bindRegistration();
    bindDashboardActions();
    initCoordinateMaps();
    loadRoot();
}());
