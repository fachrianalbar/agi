import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const badgeClasses = [
    'badge',
    'badge-success',
    'badge-warning',
    'badge-danger',
    'badge-info',
    'badge-neutral',
];

const numberFormat = new Intl.NumberFormat('en-US', {
    maximumFractionDigits: 6,
});

function parsePayload() {
    const source = document.getElementById('fleet-history-playback-data');

    if (!source) {
        return null;
    }

    try {
        return JSON.parse(source.textContent || '{}');
    } catch {
        return null;
    }
}

function normalizePoints(points) {
    if (!Array.isArray(points)) {
        return [];
    }

    return points
        .map((point) => ({
            datetime: point.datetime || 'Unavailable',
            address: point.address || 'Address unavailable',
            latitude: Number(point.latitude),
            longitude: Number(point.longitude),
            speed: Number(point.speed || 0),
            direction: Number(point.direction || 0),
            engine: Boolean(point.engine),
            odometer: Number(point.odometer || 0),
        }))
        .filter((point) => (
            Number.isFinite(point.latitude)
            && Number.isFinite(point.longitude)
            && Math.abs(point.latitude) <= 90
            && Math.abs(point.longitude) <= 180
        ));
}

function setText(root, selector, value) {
    const element = root?.querySelector(selector);

    if (element) {
        element.textContent = value || 'Unavailable';
    }
}

function setBadge(root, selector, text, variant = 'neutral') {
    const element = root?.querySelector(selector);

    if (!element) {
        return;
    }

    element.textContent = text || 'Unavailable';
    element.classList.remove(...badgeClasses);
    element.classList.add('badge', `badge-${variant}`);
}

function latLng(point) {
    return [point.latitude, point.longitude];
}

function coordinateText(point) {
    return `${numberFormat.format(point.latitude)}, ${numberFormat.format(point.longitude)}`;
}

function speedText(point) {
    return `${numberFormat.format(point.speed)} km/h`;
}

function odometerText(point) {
    return `${numberFormat.format(point.odometer)} km`;
}

function vehicleIcon(point) {
    return L.divIcon({
        className: 'playback-vehicle-marker',
        html: `
            <span class="playback-vehicle-marker-shell">
                <span class="playback-vehicle-marker-arrow" style="transform: rotate(${point.direction}deg)">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2.75 20.25 21 12 17.25 3.75 21 12 2.75Z" />
                    </svg>
                </span>
            </span>
        `,
        iconSize: [42, 42],
        iconAnchor: [21, 21],
    });
}

function initializeFleetHistoryPlayback() {
    const openButton = document.querySelector('[data-playback-open]');
    const modal = document.getElementById('fleetPlaybackModal');
    const mapElement = modal?.querySelector('[data-playback-map]');

    if (!openButton || !modal || !mapElement) {
        return;
    }

    let map = null;
    let routeLayer = null;
    let traveledLine = null;
    let vehicleMarker = null;
    let timer = null;
    let currentIndex = 0;
    let points = [];

    const progress = modal.querySelector('[data-playback-progress]');
    const toggleButton = modal.querySelector('[data-playback-toggle]');
    const resetButton = modal.querySelector('[data-playback-reset]');
    const speedRate = modal.querySelector('[data-playback-speed-rate]');

    const fitRoute = () => {
        if (!map || points.length === 0) {
            return;
        }

        if (points.length === 1) {
            map.setView(latLng(points[0]), 16);

            return;
        }

        map.fitBounds(L.latLngBounds(points.map(latLng)).pad(0.18), {
            animate: true,
        });
    };

    const pause = () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }

        if (toggleButton) {
            toggleButton.textContent = 'Play';
        }
    };

    const updateDetails = (point) => {
        setText(modal, '[data-playback-address]', point.address);
        setText(modal, '[data-playback-datetime]', point.datetime);
        setText(modal, '[data-playback-speed]', speedText(point));
        setText(modal, '[data-playback-odometer]', odometerText(point));
        setText(modal, '[data-playback-coordinate]', coordinateText(point));
        setBadge(
            modal,
            '[data-playback-engine]',
            point.engine ? 'Engine On' : 'Engine Off',
            point.engine ? 'success' : 'neutral',
        );
        setBadge(
            modal,
            '[data-playback-counter]',
            `${currentIndex + 1} / ${points.length} Points`,
            'info',
        );
    };

    const setIndex = (index, shouldPan = false) => {
        if (!points.length || !vehicleMarker || !traveledLine) {
            return;
        }

        currentIndex = Math.max(0, Math.min(index, points.length - 1));
        const point = points[currentIndex];
        const position = latLng(point);

        vehicleMarker.setLatLng(position);
        vehicleMarker.setIcon(vehicleIcon(point));
        traveledLine.setLatLngs(points.slice(0, currentIndex + 1).map(latLng));
        updateDetails(point);

        if (progress) {
            progress.value = String(currentIndex);
        }

        if (shouldPan && map) {
            map.panTo(position, {
                animate: true,
                duration: 0.35,
            });
        }
    };

    const playbackDelay = () => {
        const rate = Number(speedRate?.value || 1);

        return Math.max(40, 850 / Math.max(rate, 1));
    };

    const play = () => {
        if (points.length < 2) {
            return;
        }

        if (currentIndex >= points.length - 1) {
            setIndex(0, true);
        }

        pause();

        if (toggleButton) {
            toggleButton.textContent = 'Pause';
        }

        timer = setInterval(() => {
            if (currentIndex >= points.length - 1) {
                pause();

                return;
            }

            setIndex(currentIndex + 1, true);
        }, playbackDelay());
    };

    const reset = () => {
        pause();
        setIndex(0);
        fitRoute();
    };

    const initializeMap = () => {
        if (map) {
            return;
        }

        map = L.map(mapElement, {
            zoomControl: true,
            scrollWheelZoom: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        routeLayer = L.layerGroup().addTo(map);
    };

    const drawRoute = () => {
        if (!routeLayer || points.length === 0) {
            return;
        }

        routeLayer.clearLayers();

        L.polyline(points.map(latLng), {
            color: '#A08980',
            dashArray: '8 10',
            opacity: 0.65,
            weight: 4,
        }).addTo(routeLayer);

        traveledLine = L.polyline([], {
            color: '#E2725B',
            opacity: 0.95,
            weight: 5,
        }).addTo(routeLayer);

        L.circleMarker(latLng(points[0]), {
            radius: 8,
            color: '#2E8B57',
            fillColor: '#2E8B57',
            fillOpacity: 0.9,
            weight: 2,
        }).bindTooltip('Start', { permanent: false }).addTo(routeLayer);

        L.circleMarker(latLng(points[points.length - 1]), {
            radius: 8,
            color: '#D14343',
            fillColor: '#D14343',
            fillOpacity: 0.9,
            weight: 2,
        }).bindTooltip('End', { permanent: false }).addTo(routeLayer);

        vehicleMarker = L.marker(latLng(points[0]), {
            icon: vehicleIcon(points[0]),
            zIndexOffset: 1000,
        }).addTo(routeLayer);

        if (progress) {
            progress.max = String(Math.max(points.length - 1, 0));
            progress.value = '0';
        }

        setIndex(0);
        fitRoute();
    };

    openButton.addEventListener('click', () => {
        const payload = parsePayload();
        points = normalizePoints(payload?.points || []);

        if (points.length === 0) {
            return;
        }

        setText(modal, '[data-playback-vehicle]', payload.vehicleName || payload.deviceName || 'Selected Fleet');
        setText(modal, '[data-playback-range]', payload.range || 'Selected range');
        pause();
        currentIndex = 0;

        window.requestAnimationFrame(() => {
            initializeMap();
            drawRoute();

            window.setTimeout(() => {
                map?.invalidateSize();
                fitRoute();
            }, 120);
        });
    });

    toggleButton?.addEventListener('click', () => {
        if (timer) {
            pause();

            return;
        }

        play();
    });

    resetButton?.addEventListener('click', reset);

    progress?.addEventListener('input', (event) => {
        pause();
        setIndex(Number(event.target.value), true);
    });

    speedRate?.addEventListener('change', () => {
        if (timer) {
            play();
        }
    });

    modal.querySelectorAll('[data-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', pause);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            pause();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            pause();
        }
    });
}

document.addEventListener('DOMContentLoaded', initializeFleetHistoryPlayback);
