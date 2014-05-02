var layer;
var map;
var options = {
	center: new L.LatLng(51.4267, -0.3312),
	zoom: 15,
	dragging: false,
	touchZoom: false,
	scrollWheelZoom: false,
	doubleClickZoom: false,
	boxZoom: false,
	keyboard: false,
	zoomControl: false,
	attributionControl: false
};

$.getJSON('/scripts/location.php')
	.done(function(data) {
		var coords = data.Placemark.Point.coordinates.split(',');
		options.center = new L.LatLng(coords[1], coords[0]);
	})
	.always(function(data) {
		layer = new L.TileLayer('http://{s}.tile.stamen.com/toner/{z}/{x}/{y}.png');
		map = new L.Map('map', options);
		map.addLayer(layer);
		var bounds = map.getBounds();
		map.setMaxBounds(bounds);
	});
