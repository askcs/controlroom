var map;
var alarmMarker;
var oms;
var infowindow;

function initialize() {

	var mapOptions = {
		zoom: 12,
		center: new google.maps.LatLng(51.906,4.482),
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};

	map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
	
	var spiderOptions = {
		markersWontMove: true,
		markersWontHide: true,
		keepSpiderfied: true,
		circleSpiralSwitchover: 'Infinity',
		nearbyDistance: 10
	};
	
	oms = new OverlappingMarkerSpiderfier(map, spiderOptions);
	
	infowindow = new google.maps.InfoWindow();
	
}

google.maps.event.addDomListener(window, 'load', initialize);