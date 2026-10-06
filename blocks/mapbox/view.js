class MapboxComponent extends HTMLElement {
	// Shared across every instance so a page with several maps only fetches the key once
	static apiKeyRequest = null;

	static getApiKey() {
		if (!MapboxComponent.apiKeyRequest) {
			MapboxComponent.apiKeyRequest = fetch('/ajax/mapbox')
				.then((res) => res.json())
				.then((response) => response.apiKey || null);
		}

		return MapboxComponent.apiKeyRequest;
	}

	constructor() {
		super();
		this.map = null;
		this.config = {};
	}

	connectedCallback() {
		if (this.map) return;

		this.config = this.parseConfig();

		MapboxComponent.getApiKey()
			.then((apiKey) => {
				// The element may have been removed while the key was loading
				if (!this.isConnected || this.map) return;

				if (!apiKey) {
					this.showError();
					return;
				}

				mapboxgl.accessToken = apiKey;
				this.initMap();
			})
			.catch((err) => console.error('Mapbox fetch error:', err));
	}

	disconnectedCallback() {
		if (this.map) {
			this.map.remove();
			this.map = null;
		}
	}

	parseConfig() {
		try {
			return this.dataset.config ? JSON.parse(this.dataset.config) : {};
		} catch (err) {
			console.error('Mapbox config is not valid JSON:', err);
			return {};
		}
	}

	get center() {
		return new mapboxgl.LngLat(this.config.centerLongitude, this.config.centerLatitude);
	}

	initMap() {
		this.map = new mapboxgl.Map({
			container: this,
			center: this.center,
			zoom: this.config.zoom,
			pitch: this.config.pitch,
			interactive: this.config.interactive,
			style: this.config.theme,
			antialias: true
		});

		this.addControls();

		if (this.config.showBuildings) {
			this.map.on('load', () => {
				this.addBuildings();
				this.flyToLocation();
			});
		} else {
			this.flyToLocation();
		}
	}

	addControls() {
		if (!this.config.show_controls) return;

		const navOptions = {
			showCompass: true,
			showZoom: true
		};

		if (this.config.pitch > 0) {
			navOptions.visualizePitch = true;
		}

		this.map.addControl(
			new mapboxgl.NavigationControl(navOptions),
			this.config.control_placement
		);
	}

	addBuildings() {
		// Insert the extrusion layer beneath the first label layer so labels stay readable
		const labelLayer = this.map
			.getStyle()
			.layers.find((layer) => layer.type === 'symbol' && layer.layout['text-field']);

		this.map.addLayer(
			{
				id: '3d-buildings',
				source: 'composite',
				'source-layer': 'building',
				filter: ['==', 'extrude', 'true'],
				type: 'fill-extrusion',
				minzoom: 15,
				paint: {
					'fill-extrusion-color': this.config.extrusionColor,
					'fill-extrusion-height': [
						'interpolate',
						['linear'],
						['zoom'],
						15,
						0,
						15.05,
						['get', 'height']
					],
					'fill-extrusion-base': [
						'interpolate',
						['linear'],
						['zoom'],
						15,
						0,
						15.05,
						['get', 'min_height']
					],
					'fill-extrusion-opacity': 0.9
				}
			},
			labelLayer ? labelLayer.id : undefined
		);
	}

	addMarkers() {
		if (!Array.isArray(this.config.markers)) return;

		this.config.markers.forEach((marker) => {
			const options = marker.markerColor ? { color: marker.markerColor } : {};

			new mapboxgl.Marker(options)
				.setLngLat(new mapboxgl.LngLat(marker.longitude, marker.latitude))
				.addTo(this.map);
		});
	}

	flyToLocation() {
		setTimeout(() => {
			if (!this.map) return;

			this.addMarkers();
			this.map.flyTo({
				center: this.center,
				zoom: this.config.zoom,
				pitch: this.config.pitch
			});
		}, 500);
	}

	showError() {
		console.error('MAPBOX_API_KEY is not set in the site .env file');
		this.insertAdjacentHTML(
			'beforeend',
			'<div class="alert alert-danger">Mapbox API key missing. Add MAPBOX_API_KEY to the site .env file.</div>'
		);
	}
}

if (!customElements.get('mapbox-component')) {
	customElements.define('mapbox-component', MapboxComponent);
}
