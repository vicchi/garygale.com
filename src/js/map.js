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
	zoomControl: false
};

//console.log('default lat/lng: ' + options.center.toString());
$.getJSON('/scripts/location.php')
	.done(function(data) {
		var coords = data.Placemark.Point.coordinates.split(',');
		options.center = new L.LatLng(coords[1], coords[0]);
		//console.log('current lat/lng: ' + options.center.toString());
	})
	.always(function(data) {
		//console.log('using lat/lng: ' + options.center.toString());
		layer = new L.StamenTileLayer('toner');
		map = new L.Map('map', options);
		map.addLayer(layer);
		var bounds = map.getBounds();
		map.setMaxBounds(bounds);
	});

/*var layer = new L.StamenTileLayer('toner');
var map = new L.Map('map', {
	center: new L.LatLng(51.4267, -0.3312),
	zoom: 15,
	dragging: false,
	touchZoom: false,
	scrollWheelZoom: false,
	doubleClickZoom: false,
	boxZoom: false,
	keyboard: false,
	zoomControl: false
});
map.addLayer(layer);
var bounds = map.getBounds();
map.setMaxBounds(bounds);*/
