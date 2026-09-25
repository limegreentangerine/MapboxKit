document.addEventListener('DOMContentLoaded', function () {
	const mapElements = document.querySelectorAll('.block__lgt-mapbox');

	if (mapElements.length) {
		fetch('/ajax/mapbox')
			.then((res) => res.json())
			.then((response) => {
				if (response.apiKey) {
					mapboxgl.accessToken = response.apiKey;

					mapElements.forEach((element) => {
						let map, mapCenter;
						let mapId = element.id;
						let config = element.dataset.config
							? JSON.parse(element.dataset.config)
							: {};

						// config map
						mapCenter = new mapboxgl.LngLat(
							config.centerLongitude,
							config.centerLatitude
						);
						map = new mapboxgl.Map({
							container: mapId,
							center: mapCenter,
							zoom: config.zoom,
							pitch: config.pitch,
							interactive: config.interactive,
							style: config.theme,
							antialias: true
						});

						// Controls
						if (config.show_controls) {
							let navOptions = {
								showCompass: true,
								showZoom: true
							};

							if (config.pitch > 0) {
								navOptions.visualizePitch = true;
							}

							const nav = new mapboxgl.NavigationControl(navOptions);
							map.addControl(nav, config.control_placement);
						}

						// Buildings
						if (config.showBuildings) {
							map.on('load', function () {
								let layers = map.getStyle().layers;
								let labelLayerId;

								for (let i = 0; i < layers.length; i++) {
									if (
										layers[i].type === 'symbol' &&
										layers[i].layout['text-field']
									) {
										labelLayerId = layers[i].id;
										break;
									}
								}

								map.addLayer(
									{
										id: '3d-buildings',
										source: 'composite',
										'source-layer': 'building',
										filter: ['==', 'extrude', 'true'],
										type: 'fill-extrusion',
										minzoom: 15,
										paint: {
											'fill-extrusion-color': config.extrusionColor,
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
									labelLayerId
								);

								setTimeout(() => {
									flyToLocation(
										map,
										mapCenter,
										config.zoom,
										config.pitch,
										config.markers
									);
								}, 500);
							});
						} else {
							setTimeout(() => {
								flyToLocation(
									map,
									mapCenter,
									config.zoom,
									config.pitch,
									config.markers
								);
							}, 500);
						}
					});
				} else {
					console.error('API Key required in LGT Toolkit Dashboard');
					mapElements.forEach((el) => {
						el.insertAdjacentHTML(
							'beforeend',
							'<div class="alert alert-danger">API key missing in dashboard</div>'
						);
					});
				}
			})
			.catch((err) => console.error('Mapbox fetch error:', err));
	}
});

function addMarkers(map, markers) {
	if (!Array.isArray(markers)) return;

	markers.forEach((marker) => {
		let coords = new mapboxgl.LngLat(marker.longitude, marker.latitude);
		let uiMarker;

		if (marker.markerColor !== '') {
			uiMarker = new mapboxgl.Marker({
				color: marker.markerColor
			});
		} else {
			uiMarker = new mapboxgl.Marker();
		}

		uiMarker.setLngLat(coords).addTo(map);
	});
}

function flyToLocation(map, mapCenter, zoom, pitch, markers) {
	if (markers !== false) {
		addMarkers(map, markers);
	}

	map.flyTo({
		center: mapCenter,
		zoom: zoom,
		pitch: pitch
	});
}
