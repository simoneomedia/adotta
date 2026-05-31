(function () {
    if (!window.AgriSaas) return;

    const root = document.querySelector('[data-agri-endpoint]');
    const coordinateMaps = new Map();
    let adoptableMap = null;
    let farmProfileMap = null;
    let feedOffset = 0;
    const FEED_LIMIT = 20;

    // ── SimpleOsmMap — coordinate pickers only ───────────────────────────────
    const tileSize = 256;
    const tileUrl = (x, y, z) => `https://tile.openstreetmap.org/${z}/${x}/${y}.png`;
    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

    const project = (lat, lng, zoom) => {
        const sin = Math.sin((clamp(lat, -85.05112878, 85.05112878) * Math.PI) / 180);
        const scale = tileSize * (2 ** zoom);
        return {
            x: ((lng + 180) / 360) * scale,
            y: (0.5 - Math.log((1 + sin) / (1 - sin)) / (4 * Math.PI)) * scale,
        };
    };

    const unproject = (x, y, zoom) => {
        const scale = tileSize * (2 ** zoom);
        const lng = (x / scale) * 360 - 180;
        const n = Math.PI - (2 * Math.PI * y) / scale;
        const lat = (180 / Math.PI) * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n)));
        return { lat, lng };
    };

    class SimpleOsmMap {
        constructor(container, options = {}) {
            this.container = container;
            this.center = { lat: options.center?.[0] ?? 41.9028, lng: options.center?.[1] ?? 12.4964 };
            this.zoom = options.zoom ?? 6;
            this.markers = [];
            this.clickHandlers = [];
            this.drag = null;
            this.container.classList.add('osm-map');
            this.container.innerHTML = '<div class="osm-tile-layer"></div><div class="osm-marker-layer"></div><div class="osm-zoom"><button type="button" data-osm-zoom="in">+</button><button type="button" data-osm-zoom="out">−</button></div><a class="osm-attribution" href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">© OpenStreetMap contributors</a>';
            this.tileLayer = this.container.querySelector('.osm-tile-layer');
            this.markerLayer = this.container.querySelector('.osm-marker-layer');
            this.bindEvents();
            this.render();
        }

        bindEvents() {
            this.container.querySelector('[data-osm-zoom="in"]').addEventListener('click', () => this.setZoom(this.zoom + 1));
            this.container.querySelector('[data-osm-zoom="out"]').addEventListener('click', () => this.setZoom(this.zoom - 1));

            this.container.addEventListener('wheel', (event) => {
                event.preventDefault();
                this.setZoom(this.zoom + (event.deltaY < 0 ? 1 : -1));
            }, { passive: false });

            this.container.addEventListener('pointerdown', (event) => {
                if (event.target.closest('.osm-marker, .osm-zoom, .osm-attribution')) return;
                this.drag = {
                    id: event.pointerId, x: event.clientX, y: event.clientY, moved: false,
                    centerPoint: project(this.center.lat, this.center.lng, this.zoom),
                };
                this.container.setPointerCapture(event.pointerId);
                this.container.classList.add('is-dragging');
            });

            this.container.addEventListener('pointermove', (event) => {
                if (!this.drag || this.drag.id !== event.pointerId) return;
                const dx = event.clientX - this.drag.x;
                const dy = event.clientY - this.drag.y;
                if (Math.abs(dx) + Math.abs(dy) > 4) this.drag.moved = true;
                const next = unproject(this.drag.centerPoint.x - dx, this.drag.centerPoint.y - dy, this.zoom);
                this.center = { lat: clamp(next.lat, -85, 85), lng: next.lng };
                this.render();
            });

            this.container.addEventListener('pointerup', (event) => {
                if (!this.drag || this.drag.id !== event.pointerId) return;
                const wasClick = !this.drag.moved;
                this.container.releasePointerCapture(event.pointerId);
                this.container.classList.remove('is-dragging');
                this.drag = null;
                if (wasClick) {
                    const latlng = this.eventToLatLng(event);
                    this.clickHandlers.forEach((handler) => handler({ latlng }));
                }
            });
        }

        eventToLatLng(event) {
            const rect = this.container.getBoundingClientRect();
            const centerPoint = project(this.center.lat, this.center.lng, this.zoom);
            const x = centerPoint.x + event.clientX - rect.left - rect.width / 2;
            const y = centerPoint.y + event.clientY - rect.top - rect.height / 2;
            return unproject(x, y, this.zoom);
        }

        setZoom(zoom) { this.zoom = clamp(zoom, 2, 19); this.render(); }
        setView(latlng, zoom = this.zoom) { this.center = { lat: Number(latlng[0]), lng: Number(latlng[1]) }; this.zoom = clamp(zoom, 2, 19); this.render(); }

        fitBounds(bounds, options = {}) {
            if (!bounds.length) return;
            const lats = bounds.map((item) => item[0]);
            const lngs = bounds.map((item) => item[1]);
            const center = [(Math.min(...lats) + Math.max(...lats)) / 2, (Math.min(...lngs) + Math.max(...lngs)) / 2];
            let zoom = options.maxZoom ?? 14;
            const rect = this.container.getBoundingClientRect();
            for (let candidate = zoom; candidate >= 2; candidate--) {
                const points = bounds.map((item) => project(item[0], item[1], candidate));
                const width = Math.max(...points.map((point) => point.x)) - Math.min(...points.map((point) => point.x));
                const height = Math.max(...points.map((point) => point.y)) - Math.min(...points.map((point) => point.y));
                if (width <= rect.width - 56 && height <= rect.height - 56) { zoom = candidate; break; }
            }
            this.setView(center, zoom);
        }

        on(eventName, handler) { if (eventName === 'click') this.clickHandlers.push(handler); }
        clearMarkers() { this.markers = []; this.markerLayer.innerHTML = ''; }

        addMarker(latlng, options = {}) {
            const marker = document.createElement(options.href ? 'a' : 'button');
            marker.className = 'osm-marker';
            marker.type = options.href ? undefined : 'button';
            if (options.href) marker.href = options.href;
            marker.textContent = options.icon || '🌳';
            marker.title = options.title || '';
            if (options.popup) marker.dataset.popup = options.popup;
            this.markerLayer.append(marker);
            const entry = { marker, lat: Number(latlng[0]), lng: Number(latlng[1]), draggable: Boolean(options.draggable), onDragEnd: options.onDragEnd };
            this.markers.push(entry);

            if (options.popup) {
                marker.addEventListener('click', (event) => {
                    event.preventDefault();
                    this.container.querySelectorAll('.osm-popup').forEach((popup) => popup.remove());
                    const popup = document.createElement('div');
                    popup.className = 'osm-popup';
                    popup.innerHTML = options.popup;
                    marker.append(popup);
                });
            }

            if (entry.draggable) this.bindMarkerDrag(entry);
            this.positionMarker(entry);
            return entry;
        }

        bindMarkerDrag(entry) {
            entry.marker.addEventListener('pointerdown', (event) => { event.preventDefault(); event.stopPropagation(); entry.drag = true; entry.marker.setPointerCapture(event.pointerId); entry.marker.classList.add('is-dragging'); });
            entry.marker.addEventListener('pointermove', (event) => { if (!entry.drag) return; const latlng = this.eventToLatLng(event); entry.lat = latlng.lat; entry.lng = latlng.lng; this.positionMarker(entry); });
            entry.marker.addEventListener('pointerup', (event) => { if (!entry.drag) return; entry.drag = false; entry.marker.releasePointerCapture(event.pointerId); entry.marker.classList.remove('is-dragging'); entry.onDragEnd?.({ lat: entry.lat, lng: entry.lng }); });
        }

        positionMarker(entry) {
            const rect = this.container.getBoundingClientRect();
            const centerPoint = project(this.center.lat, this.center.lng, this.zoom);
            const point = project(entry.lat, entry.lng, this.zoom);
            entry.marker.style.left = `${rect.width / 2 + point.x - centerPoint.x}px`;
            entry.marker.style.top = `${rect.height / 2 + point.y - centerPoint.y}px`;
        }

        renderTiles() {
            const rect = this.container.getBoundingClientRect();
            const centerPoint = project(this.center.lat, this.center.lng, this.zoom);
            const startX = Math.floor((centerPoint.x - rect.width / 2) / tileSize);
            const endX = Math.floor((centerPoint.x + rect.width / 2) / tileSize);
            const startY = Math.floor((centerPoint.y - rect.height / 2) / tileSize);
            const endY = Math.floor((centerPoint.y + rect.height / 2) / tileSize);
            const maxTile = 2 ** this.zoom;
            const tiles = [];
            for (let x = startX; x <= endX; x++) {
                for (let y = startY; y <= endY; y++) {
                    if (y < 0 || y >= maxTile) continue;
                    const wrappedX = ((x % maxTile) + maxTile) % maxTile;
                    const left = x * tileSize - centerPoint.x + rect.width / 2;
                    const top = y * tileSize - centerPoint.y + rect.height / 2;
                    tiles.push(`<img src="${tileUrl(wrappedX, y, this.zoom)}" alt="" draggable="false" style="left:${left}px;top:${top}px;">`);
                }
            }
            this.tileLayer.innerHTML = tiles.join('');
        }

        render() { this.renderTiles(); this.markers.forEach((marker) => this.positionMarker(marker)); }
    }

    // ── Utility ──────────────────────────────────────────────────────────────
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

    const relativeTime = (dateStr) => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return String(dateStr);
        const diff = Math.floor((Date.now() - date) / 1000);
        if (diff < 60) return 'Adesso';
        if (diff < 3600) return `${Math.floor(diff / 60)} min fa`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h fa`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} giorni fa`;
        return date.toLocaleDateString('it-IT');
    };

    const visibilityLabel = (v) => ({ public: 'Pubblico', followers: 'Follower', adopters: 'Adottanti', tree_adopter: 'Adottante' }[v] || 'Pubblico');
    const statusLabel = (s) => ({ available: 'Disponibile', adopted: 'Adottato', maintenance: 'In manutenzione' }[s] || s || '');
    const rewardTypeLabel = (t) => ({ physical: 'Prodotto fisico', digital: 'Digitale', experience: 'Esperienza', surprise: 'A sorpresa' }[t] || t || '');
    const whenReceivedLabel = (w) => ({ immediate: 'All\'adozione', '6m': 'Dopo 6 mesi', '1y': 'Dopo 1 anno', harvest: 'Al raccolto', annually: 'Ogni anno' }[w] || 'All\'adozione');

    const verifiedBadge = (isVerified) => isVerified
        ? '<span class="verified-badge" title="Agricoltore verificato">✓ Verificato</span>'
        : '';

    // ── Share bar ─────────────────────────────────────────────────────────────
    const shareBar = (url, title) => {
        const encoded = encodeURIComponent(url);
        const waText = encodeURIComponent(`${title}\n${url}`);
        const xText = encodeURIComponent(title);
        return `<div class="share-bar">
            <a class="share-btn share-wa" href="https://api.whatsapp.com/send?text=${waText}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                WhatsApp
            </a>
            <a class="share-btn share-fb" href="https://www.facebook.com/sharer/sharer.php?u=${encoded}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                Facebook
            </a>
            <a class="share-btn share-x" href="https://twitter.com/intent/tweet?text=${xText}&url=${encoded}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.742l7.742-8.872L2.25 2.25h6.845l4.264 5.64 5.885-5.64Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                X
            </a>
            <button class="share-btn share-copy" type="button" data-copy-link="${escapeHtml(url)}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copia link
            </button>
        </div>`;
    };

    // ── Reaction bar ──────────────────────────────────────────────────────────
    const reactionBar = (update) => {
        const reactions = update.reactions || { heart: 0, leaf: 0, clap: 0 };
        const my = update.my_reaction || null;
        const btn = (emoji, key) => {
            const count = reactions[key] || 0;
            const active = my === key ? ' is-active' : '';
            return `<button class="reaction-btn${active}" type="button" data-react="${escapeHtml(String(update.id))}" data-reaction="${key}" aria-pressed="${my === key}">${emoji} <span class="reaction-count">${count > 0 ? count : ''}</span></button>`;
        };
        return `<div class="reaction-bar">${btn('❤️', 'heart')}${btn('🌿', 'leaf')}${btn('👏', 'clap')}</div>`;
    };

    // ── Coordinate pickers ────────────────────────────────────────────────────
    const getCoordinateInputs = (container) => {
        const scope = container.closest('form') || container.parentElement || document;
        return { lat: scope.querySelector('[data-marker-lat]'), lng: scope.querySelector('[data-marker-lng]') };
    };

    const defaultLatLng = () => [41.9028, 12.4964];

    const readLatLng = (container) => {
        const inputs = getCoordinateInputs(container);
        const lat = Number(inputs.lat?.value);
        const lng = Number(inputs.lng?.value);
        if (Number.isFinite(lat) && Number.isFinite(lng)) return [lat, lng];
        return defaultLatLng();
    };

    const setCoordinateMarker = (container, lat, lng, zoom = 13) => {
        const state = coordinateMaps.get(container);
        if (!state) return;
        const inputs = getCoordinateInputs(container);
        if (inputs.lat) inputs.lat.value = Number(lat).toFixed(7);
        if (inputs.lng) inputs.lng.value = Number(lng).toFixed(7);

        if (!state.marker) {
            state.marker = state.map.addMarker([lat, lng], {
                icon: '📍', draggable: true, title: 'Posizione azienda',
                onDragEnd: (position) => setCoordinateMarker(container, position.lat, position.lng, state.map.zoom),
            });
        } else {
            state.marker.lat = Number(lat);
            state.marker.lng = Number(lng);
            state.map.positionMarker(state.marker);
        }
        state.map.setView([lat, lng], zoom);
    };

    const initCoordinateMaps = () => {
        document.querySelectorAll('[data-coordinate-map]').forEach((container) => {
            if (coordinateMaps.has(container)) return;
            const map = new SimpleOsmMap(container, { center: readLatLng(container), zoom: 6 });
            coordinateMaps.set(container, { map, marker: null });
            map.on('click', (event) => setCoordinateMarker(container, event.latlng.lat, event.latlng.lng, map.zoom));
            const [lat, lng] = readLatLng(container);
            const inputs = getCoordinateInputs(container);
            if (inputs.lat?.value && inputs.lng?.value) setCoordinateMarker(container, lat, lng);
        });
    };

    const refreshCoordinateMaps = () => { coordinateMaps.forEach(({ map }) => setTimeout(() => map.render(), 80)); };

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

    // ── Stat card ─────────────────────────────────────────────────────────────
    const statCard = (label, value, meta) => `
        <article class="card stat-card">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
            <small>${escapeHtml(meta)}</small>
        </article>`;

    const treeMeta = (tree) => [tree.farm_name, tree.location, tree.crop_focus].filter(Boolean).join(' · ');

    // ── QR Code ───────────────────────────────────────────────────────────────
    const generateQR = (container, url) => {
        container.innerHTML = '';
        if (window.QRCode) {
            new window.QRCode(container, { text: url, width: 200, height: 200, correctLevel: window.QRCode.CorrectLevel?.M });
        }
    };

    const qrSection = (url, treeCode) => `
        <div class="qr-section">
            <p class="eyebrow">QR Code albero</p>
            <button class="button ghost qr-toggle-btn" type="button" data-qr-url="${escapeHtml(url)}" data-qr-label="${escapeHtml(treeCode)}">
                Mostra QR
            </button>
            <div class="qr-canvas-wrap" hidden>
                <div class="qr-canvas"></div>
                <a class="button ghost qr-download-btn" download="albero-${escapeHtml(treeCode)}.png">Scarica QR</a>
            </div>
        </div>`;

    // ── Leaflet maps ──────────────────────────────────────────────────────────
    const makeLeafletMap = (el) => {
        if (!window.L) return null;
        const map = L.map(el);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);
        return map;
    };

    const makeCluster = () => (window.L?.markerClusterGroup ? L.markerClusterGroup() : L.featureGroup());

    const speciesEmoji = (species) => {
        const s = (species || '').toLowerCase();
        if (s.includes('olivo') || s.includes('olive')) return '🫒';
        if (s.includes('arancio') || s.includes('orange')) return '🍊';
        if (s.includes('limone') || s.includes('lemon')) return '🍋';
        if (s.includes('fico') || s.includes('fig')) return '🍈';
        if (s.includes('melo') || s.includes('apple') || s.includes('mela')) return '🍎';
        if (s.includes('pero') || s.includes('pear') || s.includes('pera')) return '🍐';
        if (s.includes('ciliegio') || s.includes('cherry')) return '🍒';
        if (s.includes('pesco') || s.includes('peach')) return '🍑';
        if (s.includes('noce') || s.includes('walnut')) return '🥜';
        if (s.includes('mandorlo') || s.includes('almond')) return '🌰';
        if (s.includes('vite') || s.includes('uva') || s.includes('grape')) return '🍇';
        if (s.includes('ulivo')) return '🫒';
        return '🌳';
    };

    const treeCardHtml = (tree) => {
        const emoji = speciesEmoji(tree.species);
        const isPending = tree.request_status === 'pending';
        const imgStyle = tree.farm_photo
            ? `background-image:url('${escapeHtml(tree.farm_photo)}')`
            : `background:linear-gradient(135deg,#c8e6c9 0%,#a5d6a7 100%)`;
        return `<article class="tree-card" data-tree-id="${escapeHtml(String(tree.id))}" data-lat="${escapeHtml(String(tree.map_latitude || ''))}" data-lng="${escapeHtml(String(tree.map_longitude || ''))}">
            <a class="tree-card-link" href="${appUrl(`trees/${tree.id}/`)}">
                <div class="tree-card-img" style="${imgStyle}">
                    <div class="tree-card-img-overlay">
                        <span class="tree-species-emoji-large" aria-hidden="true">${emoji}</span>
                    </div>
                </div>
                <div class="tree-card-body">
                    <div class="tree-card-header">
                        <span class="tree-species-name">${escapeHtml(tree.species)}</span>
                        <span class="tree-card-code">${escapeHtml(tree.code)}</span>
                    </div>
                    <div class="tree-card-farm">🌿 ${escapeHtml(tree.farm_name)}${tree.location ? ` · ${escapeHtml(tree.location)}` : ''}</div>
                    <div class="tree-card-badges">
                        ${Number(tree.adoption_count) > 0 ? `<span class="adoption-count-badge">${escapeHtml(String(tree.adoption_count))} adozioni</span>` : ''}
                        ${Number(tree.has_rewards) ? '<span class="reward-available-badge">🎁 Premio</span>' : ''}
                        ${Number(tree.is_verified) ? '<span class="verified-badge">✓ Verificato</span>' : ''}
                    </div>
                </div>
            </a>
            <div class="tree-card-actions">
                <button class="button${isPending ? ' ghost' : ''}" type="button" data-request-adoption="${escapeHtml(String(tree.id))}"${isPending ? ' disabled' : ''}>${isPending ? 'In attesa…' : 'Richiedi adozione'}</button>
                <button class="button ghost tree-card-gift-btn" type="button" data-gift-tree="${escapeHtml(String(tree.id))}" data-gift-species="${escapeHtml(tree.species)}" title="Regala questo albero" aria-label="Regala ${escapeHtml(tree.species)}">🎁</button>
            </div>
        </article>`;
    };

    let catalogMarkerMap = new Map();

    const renderAdoptableMap = (trees) => {
        const slot = root?.querySelector('[data-slot="adoptable-map"]');
        if (!slot) return;
        if (adoptableMap) { adoptableMap.remove(); adoptableMap = null; }
        catalogMarkerMap = new Map();

        const mapped = trees.filter((t) => Number.isFinite(Number(t.map_latitude)) && Number.isFinite(Number(t.map_longitude)));
        if (!mapped.length) {
            slot.innerHTML = '<div class="map-placeholder">◎<small>Nessuna coordinata disponibile</small></div>';
            return;
        }

        adoptableMap = makeLeafletMap(slot);
        if (!adoptableMap) return;

        const cluster = makeCluster();
        const bounds = [];

        mapped.forEach((tree) => {
            const lat = Number(tree.map_latitude);
            const lng = Number(tree.map_longitude);
            bounds.push([lat, lng]);
            const marker = L.marker([lat, lng])
                .bindPopup(`<strong>${escapeHtml(tree.species)}</strong><br>${escapeHtml(tree.farm_name)}<br><a href="${appUrl(`trees/${tree.id}/`)}">Vedi albero →</a>`);

            marker.on('click', () => {
                const listPanel = root?.querySelector('[data-slot="adoptable-trees"]');
                listPanel?.querySelectorAll('.tree-card').forEach((c) => c.classList.remove('is-highlighted'));
                const card = listPanel?.querySelector(`[data-tree-id="${tree.id}"]`);
                if (card) {
                    card.classList.add('is-highlighted');
                    const body = root?.querySelector('.catalog-body');
                    if (body && window.innerWidth <= 768) {
                        body.dataset.catalogView = 'lista';
                        root?.querySelectorAll('[data-view-toggle]').forEach((b) => b.classList.toggle('is-active', b.dataset.viewToggle === 'lista'));
                        setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'center' }), 80);
                    } else {
                        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }
            });

            marker.addTo(cluster);
            catalogMarkerMap.set(String(tree.id), marker);
        });

        cluster.addTo(adoptableMap);
        if (bounds.length === 1) adoptableMap.setView(bounds[0], 13);
        else adoptableMap.fitBounds(bounds, { maxZoom: 14, padding: [28, 28] });

        // Card hover → pan map
        const listPanel = root?.querySelector('[data-slot="adoptable-trees"]');
        if (listPanel) {
            listPanel.addEventListener('mouseover', (e) => {
                const card = e.target.closest('[data-tree-id]');
                if (!card || !adoptableMap) return;
                const marker = catalogMarkerMap.get(card.dataset.treeId);
                if (marker) adoptableMap.panTo(marker.getLatLng(), { animate: true, duration: 0.4 });
            }, { passive: true });
        }
    };

    const initViewToggle = () => {
        const body = root?.querySelector('.catalog-body');
        if (!body) return;
        root?.querySelectorAll('[data-view-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.viewToggle;
                body.dataset.catalogView = view;
                root?.querySelectorAll('[data-view-toggle]').forEach((b) => b.classList.toggle('is-active', b.dataset.viewToggle === view));
                if (view === 'mappa' && adoptableMap) {
                    setTimeout(() => adoptableMap.invalidateSize(), 60);
                }
            });
        });
    };

    const renderAdoptableTrees = (trees) => {
        const slot = root?.querySelector('[data-slot="adoptable-trees"]');
        if (!slot) return;

        if (trees.length) {
            slot.innerHTML = trees.map(treeCardHtml).join('');
        } else {
            slot.innerHTML = '<div class="card empty-state">Nessun albero disponibile per l\'adozione al momento.</div>';
        }

        // Search filter (topbar input wired here since it lives outside slot)
        const searchInput = root?.querySelector('[data-tree-search]');
        if (searchInput) {
            const doFilter = () => {
                const q = searchInput.value.toLowerCase().trim();
                slot.querySelectorAll('.tree-card').forEach((card) => {
                    card.hidden = Boolean(q && !card.textContent.toLowerCase().includes(q));
                });
            };
            // Avoid double-binding if called again
            searchInput.removeEventListener('input', searchInput._filterFn);
            searchInput._filterFn = doFilter;
            searchInput.addEventListener('input', doFilter);
        }

        initViewToggle();
        renderAdoptableMap(trees);
    };

    const loadAdoptableTrees = async () => {
        if (!root?.querySelector('[data-slot="adoptable-trees"]')) return;
        const data = await apiFetch('/catalog/trees');
        renderAdoptableTrees(data.trees || []);
    };

    const setSlot = (selector, html) => {
        const el = root?.querySelector(selector);
        if (el) el.innerHTML = html;
    };

    const renderClientDashboard = (data) => {
        setSlot('[data-slot="stats"]', [
            statCard('Alberi adottati', String(data.stats.adoptedTrees), 'Nel tuo portafoglio'),
            statCard('Adozioni attive', String(data.stats.activeAdoptions), 'Attualmente attive'),
            statCard('Stima CO₂', `${data.stats.estimatedCarbonKg} kg`, 'Sequestro stimato'),
        ].join(''));

        // Milestone banner
        const milestoneSlot = root.querySelector('[data-slot="milestones"]');
        if (milestoneSlot && data.milestones?.length) {
            milestoneSlot.innerHTML = `<div class="milestone-banner">
                <p class="eyebrow">Traguardi in arrivo 🏆</p>
                ${data.milestones.map((m) => `<div class="milestone-item">
                    <span class="milestone-icon">🌳</span>
                    <div>
                        <strong>${escapeHtml(m.species)} (${escapeHtml(m.code)})</strong><br>
                        <small>${m.days_away === 0 ? 'Oggi!' : `Tra ${escapeHtml(String(m.days_away))} giorno/i`} compie ${escapeHtml(m.period)} di adozione
                            — <a href="${appUrl(`trees/${m.tree_id}/`)}">Vedi albero →</a>
                        </small>
                    </div>
                </div>`).join('')}
            </div>`;
            milestoneSlot.hidden = false;
        }

        setSlot('[data-slot="trees"]', data.trees.length ? data.trees.map((tree) => `
            <a class="tree-row" href="${appUrl(`trees/${tree.id}/`)}">
                <div><strong>${escapeHtml(tree.species)}</strong><br><small>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.location)}</small></div>
                <span class="badge">${escapeHtml(tree.code)}</span>
            </a>`).join('') : '<div class="card empty-state">Nessun albero adottato ancora.</div>');

        loadAdoptableTrees().catch(() => root.querySelector('[data-slot="adoptable-trees"]')?.insertAdjacentHTML('beforeend', '<div class="card empty-state">Impossibile caricare gli alberi disponibili.</div>'));
    };

    const updateFarmOptions = (farms) => {
        document.querySelectorAll('[data-farm-options]').forEach((select) => {
            select.innerHTML = farms.length ? farms.map((farm) => `
                <option value="${escapeHtml(String(farm.id))}">${escapeHtml(farm.name)} · ${escapeHtml(farm.location)}</option>
            `).join('') : '<option value="">Crea prima un\'azienda</option>';
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
                    <small>${escapeHtml(request.farm_name)} · da ${escapeHtml(request.adopter_name || request.adopter_email || `Utente #${request.adopter_user_id}`)} · ${escapeHtml(relativeTime(request.requested_at))}</small><br>
                    <small>${escapeHtml([request.adopter_email, request.adopter_whatsapp ? `WhatsApp ${request.adopter_whatsapp}` : '', request.adopter_phone ? `Tel ${request.adopter_phone}` : ''].filter(Boolean).join(' · '))}</small>
                </div>
                <div class="row-actions">
                    <button class="button" type="button" data-adoption-decision="accept" data-request-id="${escapeHtml(String(request.id))}">Accetta</button>
                    <button class="button ghost" type="button" data-adoption-decision="reject" data-request-id="${escapeHtml(String(request.id))}">Rifiuta</button>
                </div>
            </article>`).join('') : '<div class="card empty-state">Nessuna richiesta di adozione in sospeso.</div>';
    };

    const renderRewards = (rewards) => {
        if (!rewards?.length) return '<div class="card empty-state">Nessun premio incluso in questa adozione.</div>';
        return `<p style="color:var(--muted);font-size:.9rem;margin:0 0 14px;">Adottando un albero di questa azienda riceverai:</p>
        <div class="rewards-list">${rewards.map((r) => `
            <div class="reward-card">
                <div class="reward-icon">🎁</div>
                <div class="reward-info">
                    <strong>${escapeHtml(r.name)}</strong>
                    <span class="reward-type-badge">${escapeHtml(rewardTypeLabel(r.reward_type))}</span>
                    <span class="reward-when-badge">⏰ ${escapeHtml(whenReceivedLabel(r.when_received))}</span>
                    ${r.estimated_value ? `<small class="reward-value">Valore stimato: ${escapeHtml(r.estimated_value)}</small>` : ''}
                    <p class="reward-desc">${escapeHtml(r.description)}</p>
                </div>
            </div>`).join('')}</div>`;
    };

    const renderRewardsManage = (rewards, farms) => {
        const farmId = farms?.[0]?.id || '';
        return `<div>
            ${rewards?.length ? `<div class="rewards-list" style="margin-bottom:18px;">${rewards.map((r) => `
                <div class="reward-card">
                    <div class="reward-icon">🎁</div>
                    <div class="reward-info" style="flex:1">
                        <strong>${escapeHtml(r.name)}</strong>
                        <span class="reward-type-badge">${escapeHtml(rewardTypeLabel(r.reward_type))}</span>
                        ${r.estimated_value ? `<small class="reward-value">${escapeHtml(r.estimated_value)}</small>` : ''}
                        <p class="reward-desc">${escapeHtml(r.description)}</p>
                    </div>
                    <button class="button ghost" type="button" data-delete-reward="${escapeHtml(String(r.id))}" style="flex-shrink:0;align-self:flex-start;">Rimuovi</button>
                </div>`).join('')}</div>` : ''}
            <form class="reward-form" data-add-reward-form>
                <p class="eyebrow" style="margin:0 0 10px;">Aggiungi nuovo premio</p>
                <label>Nome premio<input name="reward_name" required placeholder="es. 1 kg di olio extravergine"></label>
                <label>Descrizione<textarea name="reward_description" required placeholder="Descrivi in dettaglio cosa riceverà l'adottante"></textarea></label>
                <div class="form-grid-2">
                    <label>Tipo
                        <select name="reward_type">
                            <option value="surprise">A sorpresa</option>
                            <option value="physical">Prodotto fisico</option>
                            <option value="digital">Digitale</option>
                            <option value="experience">Esperienza</option>
                        </select>
                    </label>
                    <label>Quando lo riceve
                        <select name="when_received">
                            <option value="immediate">All'adozione</option>
                            <option value="harvest">Al raccolto</option>
                            <option value="6m">Dopo 6 mesi</option>
                            <option value="1y">Dopo 1 anno</option>
                            <option value="annually">Ogni anno</option>
                        </select>
                    </label>
                </div>
                <label>Valore stimato<input name="estimated_value" placeholder="es. €25 oppure 'variabile'"></label>
                <label>Linee guida (facoltativo)<textarea name="guidelines" placeholder="Suggerimento: descrivi cosa includerà il premio (es. 1 kg di olio extravergine, una visita in azienda, una videochiamata con l'agricoltore)"></textarea></label>
                <input type="hidden" name="farm_id" value="${escapeHtml(String(farmId))}" data-reward-farm-id>
                <button class="button" type="submit">Aggiungi premio</button>
            </form>
        </div>`;
    };

    const renderFarmDashboard = (data) => {
        setSlot('[data-slot="stats"]', [
            statCard('Aziende gestite', String(data.stats.farms), 'Aziende registrate'),
            statCard('Alberi disponibili', String(data.stats.availableTrees), 'Pronti per adozione'),
            statCard('Alberi adottati', String(data.stats.adoptedTrees), 'Sponsorizzati da clienti'),
        ].join(''));

        setSlot('[data-slot="farms"]', data.farms.length ? data.farms.map((farm) => `
            <div class="farm-row">
                <div><strong><a href="${appUrl(`farms/${farm.id}/`)}">${escapeHtml(farm.name)}</a></strong> ${verifiedBadge(farm.is_verified)}<br>
                <small>${escapeHtml(farm.location)} · ${escapeHtml(farm.crop_focus)}${farm.latitude && farm.longitude ? ` · ${escapeHtml(farm.latitude)}, ${escapeHtml(farm.longitude)}` : ''}</small></div>
                <span class="badge">${escapeHtml(String(farm.tree_count))} alberi · salute ${escapeHtml(String(farm.health_score))}</span>
            </div>`).join('') : '<div class="card empty-state">Nessuna azienda. Aggiungine una prima di pubblicare alberi.</div>');

        setSlot('[data-slot="farm-trees"]', data.trees.length ? data.trees.map((tree) => `
            <div class="tree-row" style="justify-content:space-between;">
                <a href="${appUrl(`trees/${tree.id}/`)}">
                    <div><strong>${escapeHtml(tree.species)}</strong><br><small>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.planted_at || 'Data non disponibile')}</small></div>
                </a>
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
                    <span class="badge">${escapeHtml(tree.code)} · ${escapeHtml(statusLabel(tree.status))}</span>
                    ${tree.adoption_count > 0 ? `<span class="adoption-count-badge">${escapeHtml(String(tree.adoption_count))} adoz.</span>` : ''}
                    <button class="button ghost" type="button" data-show-qr="${escapeHtml(String(tree.id))}" data-qr-url="${escapeHtml(appUrl(`trees/${tree.id}/`))}" data-qr-label="${escapeHtml(tree.code)}" style="padding:6px 12px;font-size:.78rem;">QR</button>
                </div>
            </div>`).join('') : '<div class="card empty-state">Nessun albero pubblicato.</div>');

        renderAdoptionRequests(data.requests || []);
        updateFarmOptions(data.farms || []);

        const rewardsSlot = root.querySelector('[data-slot="farm-rewards-manage"]');
        if (rewardsSlot) {
            rewardsSlot.innerHTML = renderRewardsManage(data.rewards || [], data.farms || []);
        }

        // Sync quick-update FAB farm selector
        const fabFarmId = document.querySelector('[data-fab-farm-id]');
        if (fabFarmId && data.farms?.length) {
            fabFarmId.value = data.farms[0].id;
        }
    };

    const renderTreeDetail = (data) => {
        const tree = data.tree;
        const qrUrl = window.location.href;
        root.querySelector('[data-slot="tree"]').innerHTML = `
            <p class="eyebrow">${escapeHtml(tree.code)}</p>
            <h2>${escapeHtml(tree.species)}</h2>
            <p>${escapeHtml(tree.farm_name)} · ${escapeHtml(tree.location)} · ${escapeHtml(tree.crop_focus)}</p>
            <div class="stats-grid">
                ${statCard('Stato', statusLabel(tree.status), 'Ciclo di vita attuale')}
                ${statCard('CO₂', `${tree.carbon_estimate} kg`, 'Sequestro stimato')}
                ${statCard('Messa a dimora', tree.planted_at || 'In attesa', 'Data di piantagione')}
            </div>
            ${qrSection(qrUrl, tree.code)}
            <div class="share-section">
                <p class="eyebrow">Condividi questo albero</p>
                ${shareBar(qrUrl, `${tree.species} · ${tree.code}`)}
            </div>`;
        renderUpdates(data.updates || []);
    };

    // ── Feed aggiornamenti stile Instagram ────────────────────────────────────
    const renderUpdates = (updates, append = false) => {
        const slot = root?.querySelector('[data-slot="updates"]');
        if (!slot) return;

        if (!updates.length && !append) {
            slot.innerHTML = '<div class="card empty-state">Nessun aggiornamento pubblicato ancora.</div>';
            return;
        }

        const html = updates.map((update) => {
            const shareUrl = update.tree_id
                ? appUrl(`trees/${update.tree_id}/`)
                : (update.farm_id ? appUrl(`farms/${update.farm_id}/`) : window.location.href);
            const author = update.farm_name || update.tree_code || 'Aggiornamento';
            const avatar = update.tree_id ? '🌳' : '🌿';
            const vis    = update.visibility || 'public';

            return `
                <article class="insta-post" data-update-id="${escapeHtml(String(update.id))}">
                    <header class="insta-post-header">
                        <div class="insta-avatar">${avatar}</div>
                        <div class="insta-post-meta">
                            <strong class="insta-author">${escapeHtml(author)}</strong>
                            <time class="insta-timestamp">${escapeHtml(relativeTime(update.created_at))}</time>
                        </div>
                        <span class="insta-visibility insta-vis-${escapeHtml(vis)}">${escapeHtml(visibilityLabel(vis))}</span>
                    </header>
                    ${update.media_url ? `<div class="insta-media"><img src="${escapeHtml(update.media_url)}" alt="${escapeHtml(update.title)}" loading="lazy"></div>` : ''}
                    <div class="insta-body">
                        <h3 class="insta-title">${escapeHtml(update.title)}</h3>
                        <p class="insta-caption">${escapeHtml(update.body)}</p>
                    </div>
                    <footer class="insta-footer">
                        ${reactionBar(update)}
                        ${shareBar(shareUrl, update.title)}
                    </footer>
                </article>`;
        }).join('');

        if (append) slot.insertAdjacentHTML('beforeend', html);
        else slot.innerHTML = html || '<div class="card empty-state">Nessun aggiornamento pubblicato ancora.</div>';
    };

    // ── Lightbox ──────────────────────────────────────────────────────────────
    const initLightbox = () => {
        const overlay = document.createElement('div');
        overlay.className = 'lightbox-overlay';
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('role', 'dialog');
        overlay.innerHTML = '<button class="lightbox-close" type="button" aria-label="Chiudi">✕</button><img class="lightbox-img" alt="">';
        document.body.appendChild(overlay);

        const closeLightbox = () => overlay.classList.remove('is-open');
        overlay.addEventListener('click', (e) => { if (e.target === overlay || e.target.classList.contains('lightbox-close')) closeLightbox(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLightbox(); });
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('.insta-media img, .photo-grid a');
            if (!trigger) return;
            e.preventDefault();
            const img = trigger.tagName === 'IMG' ? trigger : trigger.querySelector('img');
            if (!img) return;
            overlay.querySelector('.lightbox-img').src = img.src;
            overlay.querySelector('.lightbox-img').alt = img.alt;
            overlay.classList.add('is-open');
        });
    };

    // ── Adoption modal ────────────────────────────────────────────────────────
    let adoptionModal = null;
    const createAdoptionModal = () => {
        const modal = document.createElement('div');
        modal.className = 'adoption-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.innerHTML = `<div class="adoption-modal-card">
            <div class="adoption-modal-icon">🌱</div>
            <h2>Richiesta inviata!</h2>
            <p class="adoption-modal-body"></p>
            <button class="button" type="button" data-close-adoption-modal>Ottimo!</button>
        </div>`;
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => { if (e.target === modal || e.target.closest('[data-close-adoption-modal]')) modal.classList.remove('is-open'); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.classList.remove('is-open'); });
        return modal;
    };

    const showAdoptionModal = (species, farmInfo, code) => {
        if (!adoptionModal) adoptionModal = createAdoptionModal();
        adoptionModal.querySelector('.adoption-modal-body').innerHTML =
            `Hai richiesto di adottare <strong>${escapeHtml(species || 'albero')}</strong>${code ? ` (${escapeHtml(code)})` : ''}${farmInfo ? `<br>presso <strong>${escapeHtml(farmInfo)}</strong>` : ''}.<br><br>La tua richiesta è in attesa di approvazione.`;
        adoptionModal.classList.add('is-open');
    };

    // ── Gift modal ────────────────────────────────────────────────────────────
    let giftModal = null;
    const renderGiftModal = () => {
        const modal = document.createElement('div');
        modal.className = 'gift-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.innerHTML = `<div class="gift-modal-card">
            <div class="adoption-modal-icon">🎁</div>
            <h2>Regala un albero</h2>
            <p class="adoption-modal-body" data-gift-species></p>
            <form class="gift-form" data-gift-form>
                <input type="hidden" data-gift-tree-id>
                <label>Email destinatario *<input type="email" name="recipient_email" required placeholder="nome@esempio.it"></label>
                <label>Messaggio (facoltativo)<textarea name="gift_message" rows="3" placeholder="Auguri! Ho adottato questo albero per te…"></textarea></label>
                <p class="form-status" data-gift-status></p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="button" type="submit">Invia regalo</button>
                    <button class="button ghost" type="button" data-close-gift-modal>Annulla</button>
                </div>
            </form>
        </div>`;
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('is-open'); });
        modal.querySelector('[data-close-gift-modal]').addEventListener('click', () => modal.classList.remove('is-open'));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.classList.remove('is-open'); });
        return modal;
    };

    const showGiftModal = (treeId, species) => {
        if (!giftModal) giftModal = renderGiftModal();
        giftModal.querySelector('[data-gift-species]').textContent = `Albero: ${species}`;
        giftModal.querySelector('[data-gift-status]').textContent = '';
        giftModal.querySelector('[data-gift-form]').reset();
        giftModal.querySelector('[data-gift-tree-id]').value = treeId;
        giftModal.classList.add('is-open');
    };

    // ── QR popover ────────────────────────────────────────────────────────────
    let qrPopover = null;
    const showQrPopover = (url, label) => {
        if (!qrPopover) {
            qrPopover = document.createElement('div');
            qrPopover.className = 'qr-popover';
            qrPopover.innerHTML = '<button class="lightbox-close" type="button" style="position:static;margin:0 0 10px auto;display:block;" aria-label="Chiudi">✕</button><div class="qr-canvas"></div><a class="button ghost qr-download-btn" download style="margin-top:10px;display:inline-block;">Scarica QR</a>';
            qrPopover.addEventListener('click', (e) => { if (e.target === qrPopover || e.target.classList.contains('lightbox-close')) qrPopover.hidden = true; });
            document.body.appendChild(qrPopover);
        }
        const canvas = qrPopover.querySelector('.qr-canvas');
        canvas.innerHTML = '';
        generateQR(canvas, url);
        qrPopover.querySelector('.qr-download-btn').setAttribute('download', `albero-${label}.png`);
        // QRCode renders synchronously; read the canvas immediately after generateQR.
        const qrCanvas = canvas.querySelector('canvas');
        if (qrCanvas) qrPopover.querySelector('.qr-download-btn').href = qrCanvas.toDataURL('image/png');
        qrPopover.hidden = false;
    };

    // ── Form validation ────────────────────────────────────────────────────────
    const validateForm = (form) => {
        let valid = true;
        form.querySelectorAll('[required]').forEach((field) => {
            let errorEl = field.parentElement?.querySelector('.field-error');
            if (!errorEl) { errorEl = document.createElement('span'); errorEl.className = 'field-error'; field.after(errorEl); }
            let msg = '';
            if (!field.value.trim()) msg = 'Campo obbligatorio';
            else if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) msg = 'Inserisci un\'email valida';
            else if (field.type === 'password' && field.value.length < 8) msg = 'Password di almeno 8 caratteri';
            errorEl.textContent = msg;
            field.classList.toggle('is-invalid', Boolean(msg));
            if (msg) valid = false;
        });
        return valid;
    };

    // ── Farm profile map ──────────────────────────────────────────────────────
    const renderFarmProfileMap = (trees) => {
        const slot = root?.querySelector('[data-slot="farm-profile-map"]');
        if (!slot) return;
        if (farmProfileMap) { farmProfileMap.remove(); farmProfileMap = null; }

        const mapped = trees.filter((t) => Number.isFinite(Number(t.map_latitude)) && Number.isFinite(Number(t.map_longitude)));
        if (!mapped.length) { slot.innerHTML = '<div class="map-placeholder">◎<small>Nessuna coordinata albero disponibile</small></div>'; return; }

        slot.innerHTML = '<div class="leaflet-map"></div><p class="map-note">Tutti gli alberi pubblicati sono visibili sulla mappa.</p>';
        farmProfileMap = makeLeafletMap(slot.querySelector('.leaflet-map'));
        if (!farmProfileMap) return;

        const cluster = makeCluster();
        const bounds = [];
        mapped.forEach((tree) => {
            const lat = Number(tree.map_latitude);
            const lng = Number(tree.map_longitude);
            bounds.push([lat, lng]);
            const icon = tree.status === 'adopted' ? '🌳' : '🌱';
            L.marker([lat, lng], {
                icon: L.divIcon({ className: 'leaflet-tree-icon', html: `<span style="font-size:1.3rem;line-height:1;display:block;text-align:center;">${icon}</span>`, iconSize: [28, 28], iconAnchor: [14, 28] }),
            }).bindPopup(`<strong>${escapeHtml(tree.species)}</strong><br>${escapeHtml(tree.code)} · ${escapeHtml(statusLabel(tree.status))}<br><a href="${appUrl(`trees/${tree.id}/`)}">Vedi albero →</a>`).addTo(cluster);
        });

        cluster.addTo(farmProfileMap);
        if (bounds.length === 1) farmProfileMap.setView(bounds[0], 14);
        else farmProfileMap.fitBounds(bounds, { maxZoom: 15, padding: [28, 28] });
    };

    const contactButton = (href, label) => href ? `<a class="button ghost" href="${escapeHtml(href)}">${escapeHtml(label)}</a>` : '';

    const renderFarmProfile = (data) => {
        const farm = data.farm;
        root.querySelector('[data-slot="farm-title"]').innerHTML = `${escapeHtml(farm.name)} ${verifiedBadge(farm.is_verified)}`;
        root.querySelector('[data-slot="farm-summary"]').innerHTML = `${escapeHtml(farm.location)} · ${escapeHtml(farm.crop_focus || 'Colture miste')}<br>${escapeHtml(farm.description || 'Questa azienda usa il suo profilo come vetrina pubblica per alberi, foto e aggiornamenti dal campo.')}`;
        root.querySelector('[data-slot="farm-contacts"]').innerHTML = [
            contactButton(farm.contact_email ? `mailto:${farm.contact_email}` : '', 'Email'),
            contactButton(farm.contact_whatsapp ? `https://wa.me/${String(farm.contact_whatsapp).replace(/\D/g, '')}` : '', 'WhatsApp'),
            contactButton(farm.contact_phone ? `tel:${farm.contact_phone}` : '', 'Telefono'),
        ].join('') || '<span class="badge">Contatti in arrivo</span>';

        const followButton = root.querySelector('[data-follow-farm]');
        if (followButton) {
            followButton.hidden = false;
            followButton.dataset.farmId = farm.id;
            followButton.dataset.following = data.isFollowing ? '1' : '0';
            followButton.textContent = data.canFollow ? (data.isFollowing ? 'Seguendo' : 'Segui azienda') : 'Accedi per seguire';
            followButton.classList.toggle('ghost', Boolean(data.isFollowing));
            followButton.dataset.loginUrl = data.loginUrl || '';
        }

        const shareSlot = root.querySelector('[data-slot="farm-share"]');
        if (shareSlot) shareSlot.innerHTML = shareBar(window.location.href, farm.name);

        root.querySelector('[data-slot="farm-profile-stats"]').innerHTML = [
            statCard('Alberi', String(data.stats.trees), 'Tutti visibili in vetrina'),
            statCard('Adottati', String(data.stats.adoptedTrees), 'Già sponsorizzati'),
            statCard('Follower', String(data.stats.followers), 'Clienti che seguono gli aggiornamenti'),
        ].join('');

        root.querySelector('[data-slot="farm-profile-trees"]').innerHTML = data.trees.length ? data.trees.map((tree) => `
            <a class="tree-row" href="${appUrl(`trees/${tree.id}/`)}">
                <div><strong>${escapeHtml(tree.species)}</strong><br><small>${escapeHtml(tree.code)} · ${escapeHtml(tree.planted_at || 'Data non disponibile')} · coord. ${escapeHtml(tree.coordinate_source || 'azienda')}</small></div>
                <div style="display:flex;gap:6px;flex-direction:column;align-items:flex-end;">
                    <span class="badge">${escapeHtml(statusLabel(tree.status))}${tree.adopter_name ? ` · ${escapeHtml(tree.adopter_name)}` : ''}</span>
                    ${tree.adoption_count > 0 ? `<span class="adoption-count-badge">${escapeHtml(String(tree.adoption_count))} adozione/i</span>` : ''}
                </div>
            </a>`).join('') : '<div class="card empty-state">Nessun albero pubblicato da questa azienda.</div>';

        root.querySelector('[data-slot="farm-photos"]').innerHTML = data.photos.length ? data.photos.slice(0, 6).map((url) => `
            <a href="${escapeHtml(url)}"><img src="${escapeHtml(url)}" alt="Foto azienda" loading="lazy"></a>`).join('') : '<div class="card empty-state">Nessuna foto ancora.</div>';

        const rewardsSlot = root.querySelector('[data-slot="farm-rewards"]');
        if (rewardsSlot) rewardsSlot.innerHTML = renderRewards(data.rewards || []);

        renderFarmProfileMap(data.trees || []);
        renderUpdates(data.updates || []);
    };

    const renderers = {
        'client-dashboard': renderClientDashboard,
        'farm-dashboard':   renderFarmDashboard,
        'tree-detail':      renderTreeDetail,
        'updates-feed': (data) => {
            feedOffset = data.next_offset ?? FEED_LIMIT;
            renderUpdates(data.updates || []);
            const slot = root?.querySelector('[data-slot="updates"]');
            if (!slot) return;
            let loadMoreBtn = document.querySelector('[data-load-more-updates]');
            if (loadMoreBtn) loadMoreBtn.remove();
            if (data.has_more) {
                loadMoreBtn = document.createElement('button');
                loadMoreBtn.className = 'button ghost load-more-btn';
                loadMoreBtn.type = 'button';
                loadMoreBtn.dataset.loadMoreUpdates = '1';
                loadMoreBtn.textContent = 'Carica altri aggiornamenti';
                slot.after(loadMoreBtn);
            }
        },
        'farm-profile': renderFarmProfile,
    };

    const loadRoot = () => {
        if (!root) return Promise.resolve();
        return apiFetch(root.dataset.agriEndpoint)
            .then((data) => renderers[root.dataset.render]?.(data))
            .catch(() => root.insertAdjacentHTML('beforeend', '<div class="card empty-state">Impossibile caricare i dati. Riprova tra poco.</div>'));
    };

    const showPanel = (selector) => {
        document.querySelector(selector)?.removeAttribute('hidden');
        initCoordinateMaps();
        refreshCoordinateMaps();
    };

    // ── Shared update submit logic ─────────────────────────────────────────────
    const doSubmitUpdate = async (form, statusEl) => {
        if (!validateForm(form)) return false;
        const fileInput = form.querySelector('[data-photo-input]');

        if (fileInput?.files?.length) {
            if (statusEl) statusEl.textContent = 'Ottimizzazione foto e salvataggio…';
            const uploadData = new FormData();
            uploadData.append('photo', fileInput.files[0]);
            const upload = await apiFetch('/media/photo', { method: 'POST', body: uploadData });
            const mediaUrlInput = form.querySelector('[data-media-url]');
            if (mediaUrlInput) mediaUrlInput.value = upload.url;
            if (statusEl) statusEl.textContent = `Foto caricata (${Math.round(upload.size / 1024)} KB).`;
        }

        const payload = Object.fromEntries(new FormData(form).entries());
        delete payload.photo;
        await apiFetch('/updates', { method: 'POST', body: JSON.stringify(payload) });
        return true;
    };

    const bindDashboardActions = () => {
        if (!root) return;

        document.querySelector('[data-open-farm-form]')?.addEventListener('click', () => showPanel('[data-farm-form]'));
        document.querySelector('[data-open-tree-form]')?.addEventListener('click', () => showPanel('[data-tree-form]'));
        document.querySelector('[data-open-update-form]')?.addEventListener('click', () => showPanel('[data-update-form]'));

        // Quick-update FAB
        document.querySelector('[data-open-quick-update]')?.addEventListener('click', () => {
            document.querySelector('.quick-update-drawer')?.classList.add('is-open');
        });
        document.querySelector('[data-close-quick-update]')?.addEventListener('click', () => {
            document.querySelector('.quick-update-drawer')?.classList.remove('is-open');
        });

        document.addEventListener('click', async (event) => {
            const copyBtn = event.target.closest('[data-copy-link]');
            if (copyBtn) {
                const url = copyBtn.dataset.copyLink;
                try {
                    await navigator.clipboard.writeText(url);
                    const orig = copyBtn.innerHTML;
                    copyBtn.textContent = '✓ Copiato!';
                    setTimeout(() => { copyBtn.innerHTML = orig; }, 2200);
                } catch { prompt('Copia questo link:', url); }
                return;
            }

            // QR toggle on tree detail
            const qrToggle = event.target.closest('[data-qr-url]');
            if (qrToggle && !qrToggle.dataset.showQr) {
                const wrap = qrToggle.closest('.qr-section')?.querySelector('.qr-canvas-wrap');
                if (!wrap) return;
                const isHidden = wrap.hidden;
                wrap.hidden = false;
                qrToggle.textContent = isHidden ? 'Nascondi QR' : 'Mostra QR';
                if (isHidden) {
                    const canvas = wrap.querySelector('.qr-canvas');
                    generateQR(canvas, qrToggle.dataset.qrUrl);
                    const qrCanvas = canvas.querySelector('canvas');
                    if (qrCanvas) wrap.querySelector('.qr-download-btn').href = qrCanvas.toDataURL('image/png');
                }
                return;
            }

            // QR popover on farm dashboard tree list
            const qrBtn = event.target.closest('[data-show-qr]');
            if (qrBtn) {
                showQrPopover(qrBtn.dataset.qrUrl, qrBtn.dataset.qrLabel);
                return;
            }

            const loadMoreBtn = event.target.closest('[data-load-more-updates]');
            if (loadMoreBtn) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'Caricamento…';
                try {
                    const data = await apiFetch(`/updates?limit=${FEED_LIMIT}&offset=${feedOffset}`);
                    feedOffset = data.next_offset ?? (feedOffset + FEED_LIMIT);
                    renderUpdates(data.updates || [], true);
                    loadMoreBtn.hidden = !data.has_more;
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Carica altri aggiornamenti';
                } catch {
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Errore — Riprova';
                }
                return;
            }

            // Reactions
            const reactBtn = event.target.closest('[data-react]');
            if (reactBtn) {
                const updateId = reactBtn.dataset.react;
                const reaction = reactBtn.dataset.reaction;
                const article = reactBtn.closest('[data-update-id]');

                // Optimistic update
                const bar = reactBtn.closest('.reaction-bar');
                const wasActive = reactBtn.classList.contains('is-active');
                bar.querySelectorAll('.reaction-btn').forEach((b) => b.classList.remove('is-active'));
                if (!wasActive) reactBtn.classList.add('is-active');

                try {
                    const res = await apiFetch(`/updates/${updateId}/react`, { method: 'POST', body: JSON.stringify({ reaction }) });
                    // Update counts
                    bar.querySelectorAll('.reaction-btn').forEach((b) => {
                        const key = b.dataset.reaction;
                        const countEl = b.querySelector('.reaction-count');
                        if (countEl) countEl.textContent = res.counts[key] > 0 ? res.counts[key] : '';
                        b.classList.toggle('is-active', res.my_reaction === key);
                    });
                } catch { /* revert */ if (wasActive) reactBtn.classList.add('is-active'); }
                return;
            }

            const requestButton = event.target.closest('[data-request-adoption]');
            if (requestButton) {
                requestButton.disabled = true;
                try {
                    await apiFetch('/adoption-requests', { method: 'POST', body: JSON.stringify({ tree_id: requestButton.dataset.requestAdoption }) });
                } catch { requestButton.disabled = false; return; }
                const row = requestButton.closest('.catalog-row');
                const species = row?.querySelector('strong')?.textContent?.trim() || '';
                const farmInfo = row?.querySelector('small')?.textContent?.split('·')[0]?.trim() || '';
                const code = row?.querySelector('.badge')?.textContent?.trim() || '';
                requestButton.textContent = 'In attesa';
                showAdoptionModal(species, farmInfo, code);
                loadAdoptableTrees();
                return;
            }

            // Gift button
            const giftBtn = event.target.closest('[data-gift-tree]');
            if (giftBtn) {
                showGiftModal(giftBtn.dataset.giftTree, giftBtn.dataset.giftSpecies);
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

            const decisionButton = event.target.closest('[data-adoption-decision]');
            if (decisionButton) {
                decisionButton.disabled = true;
                try {
                    await apiFetch(`/adoption-requests/${decisionButton.dataset.requestId}/${decisionButton.dataset.adoptionDecision}`, { method: 'POST', body: JSON.stringify({}) });
                    loadRoot();
                } catch (err) {
                    decisionButton.disabled = false;
                    alert(err.message || 'Errore durante l\'operazione. Riprova.');
                }
                return;
            }

            // Delete reward
            const deleteRewardBtn = event.target.closest('[data-delete-reward]');
            if (deleteRewardBtn) {
                if (!confirm('Rimuovere questo premio?')) return;
                deleteRewardBtn.disabled = true;
                await apiFetch(`/rewards/${deleteRewardBtn.dataset.deleteReward}`, { method: 'DELETE' });
                loadRoot();
                return;
            }
        });

        // Gift form submit
        document.addEventListener('submit', async (event) => {
            const giftForm = event.target.closest('[data-gift-form]');
            if (giftForm) {
                event.preventDefault();
                if (!validateForm(giftForm)) return;
                const status = giftForm.querySelector('[data-gift-status]');
                if (status) status.textContent = 'Invio in corso…';
                const treeId = giftForm.querySelector('[data-gift-tree-id]').value;
                const payload = Object.fromEntries(new FormData(giftForm).entries());
                try {
                    await apiFetch('/gift-adoption', { method: 'POST', body: JSON.stringify({ tree_id: treeId, recipient_email: payload.recipient_email, gift_message: payload.gift_message }) });
                    if (status) status.textContent = 'Regalo inviato! Il destinatario riceverà un\'email.';
                    giftForm.reset();
                    setTimeout(() => giftModal?.classList.remove('is-open'), 2000);
                } catch (err) {
                    if (status) status.textContent = err.message;
                }
                return;
            }

            // Add reward form
            const rewardForm = event.target.closest('[data-add-reward-form]');
            if (rewardForm) {
                event.preventDefault();
                if (!validateForm(rewardForm)) return;
                const payload = Object.fromEntries(new FormData(rewardForm).entries());
                await apiFetch('/rewards', { method: 'POST', body: JSON.stringify({
                    farm_id:         payload.farm_id,
                    name:            payload.reward_name,
                    description:     payload.reward_description,
                    reward_type:     payload.reward_type,
                    when_received:   payload.when_received,
                    estimated_value: payload.estimated_value,
                    guidelines:      payload.guidelines,
                }) });
                loadRoot();
                return;
            }
        });

        document.querySelector('[data-agri-farm-form]')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            if (!validateForm(form)) return;
            const payload = Object.fromEntries(new FormData(form).entries());
            await apiFetch('/farms', { method: 'POST', body: JSON.stringify(payload) });
            window.location.reload();
        });

        document.querySelector('[data-agri-tree-form]')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            if (!validateForm(form)) return;
            const payload = Object.fromEntries(new FormData(form).entries());
            await apiFetch('/trees', { method: 'POST', body: JSON.stringify(payload) });
            window.location.reload();
        });

        document.querySelector('[data-agri-update-form]')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const status = form.querySelector('[data-upload-status]');
            try {
                if (await doSubmitUpdate(form, status)) {
                    form.reset();
                    window.location.href = appUrl('updates/');
                }
            } catch (err) { if (status) status.textContent = err.message; }
        });

        document.querySelector('[data-quick-update-form]')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const status = form.querySelector('[data-upload-status]');
            try {
                if (await doSubmitUpdate(form, status)) {
                    form.reset();
                    document.querySelector('.quick-update-drawer')?.classList.remove('is-open');
                    window.location.href = appUrl('updates/');
                }
            } catch (err) { if (status) status.textContent = err.message; }
        });
    };

    const bindRegistration = () => {
        const panels = document.querySelectorAll('[data-registration-panel]');
        if (!panels.length) return;

        document.querySelectorAll('[data-registration-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                const type = tab.dataset.registrationTab;
                panels.forEach((panel) => { panel.hidden = panel.dataset.registrationPanel !== type; });
                document.querySelectorAll('[data-registration-tab]').forEach((button) => button.classList.toggle('ghost', button !== tab));
                initCoordinateMaps();
                refreshCoordinateMaps();
            });
        });

        document.querySelectorAll('[data-registration-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const status = form.querySelector('[data-form-status]');
                if (!validateForm(form)) { if (status) status.textContent = ''; return; }
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

    // ── Web Push ──────────────────────────────────────────────────────────────
    const urlBase64ToUint8Array = (base64String) => {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const output = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) output[i] = raw.charCodeAt(i);
        return output;
    };

    const subscribePush = async (registration) => {
        try {
            const existing = await registration.pushManager.getSubscription();
            const sub = existing || await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(window.AgriSaas.vapidPublicKey),
            });
            const key    = sub.getKey ? sub.getKey('p256dh') : null;
            const authKey = sub.getKey ? sub.getKey('auth') : null;
            if (!key || !authKey) return;
            await apiFetch('/push/subscribe', {
                method: 'POST',
                body: JSON.stringify({
                    endpoint: sub.endpoint,
                    p256dh:   btoa(String.fromCharCode(...new Uint8Array(key))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, ''),
                    auth:     btoa(String.fromCharCode(...new Uint8Array(authKey))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, ''),
                }),
            });
        } catch { /* Push permission denied or not supported — silent */ }
    };

    const initPushNotifications = () => {
        if (!window.AgriSaas.vapidPublicKey || !window.AgriSaas.userId || !('serviceWorker' in navigator) || !('PushManager' in window)) return;

        const showBanner = () => {
            if (Notification.permission !== 'default') return;
            const banner = document.createElement('div');
            banner.className = 'push-banner';
            banner.innerHTML = `<span>🔔 Attiva le notifiche per aggiornamenti dal tuo albero</span>
                <button class="button" type="button" data-enable-push>Attiva</button>
                <button class="button ghost" type="button" data-dismiss-push>✕</button>`;
            document.body.prepend(banner);

            banner.querySelector('[data-dismiss-push]').addEventListener('click', () => banner.remove());
            banner.querySelector('[data-enable-push]').addEventListener('click', async () => {
                const permission = await Notification.requestPermission();
                banner.remove();
                if (permission === 'granted') {
                    const reg = await navigator.serviceWorker.ready;
                    subscribePush(reg);
                }
            });
        };

        navigator.serviceWorker.register(window.AgriSaas.swUrl)
            .then((registration) => {
                if (Notification.permission === 'granted') {
                    subscribePush(registration);
                } else {
                    showBanner();
                }
            })
            .catch(() => { /* SW registration failed — silent */ });
    };

    // ── Claim gift ────────────────────────────────────────────────────────────
    const initClaimGift = () => {
        const claimBtn = document.querySelector('[data-claim-gift]');
        if (!claimBtn) return;
        const section = claimBtn.closest('[data-gift-claim-section]');
        const token = section?.dataset.giftToken;
        if (!token) return;

        claimBtn.addEventListener('click', async () => {
            claimBtn.disabled = true;
            const statusEl = section.querySelector('[data-claim-status]');
            if (statusEl) statusEl.textContent = 'Riscatto in corso…';
            try {
                const res = await apiFetch('/claim-gift', { method: 'POST', body: JSON.stringify({ token }) });
                if (statusEl) statusEl.textContent = `🌳 Adozione riscattata! ${escapeHtml(res.species)} (${escapeHtml(res.code)}) presso ${escapeHtml(res.farm_name)}.`;
                claimBtn.textContent = '✓ Riscattato!';
                setTimeout(() => { window.location.href = appUrl('dashboard/'); }, 2200);
            } catch (err) {
                claimBtn.disabled = false;
                if (statusEl) statusEl.textContent = err.message || 'Errore. Riprova.';
            }
        });
    };

    // ── Init ──────────────────────────────────────────────────────────────────
    bindCoordinateButtons();
    bindRegistration();
    bindDashboardActions();
    initCoordinateMaps();
    initLightbox();
    initPushNotifications();
    initClaimGift();
    loadRoot();
}());
