var map;
var alarmMarker;
var oms;
var infowindow;
var ownLocation = null;
var geocoder;

function initialize() {
	
	var mapOptions = {
		zoom: 7,
		center: new google.maps.LatLng(52.132,5.291),
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};

	map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
	
	// Request own location (now we have a map)
	getOwnLocation();
	
	// Spider options (for multiple markers nearby eachother)
	var spiderOptions = {
		markersWontMove: true,
		markersWontHide: true,
		keepSpiderfied: true,
		circleSpiralSwitchover: 'Infinity',
		nearbyDistance: 10
	};
	
	oms = new OverlappingMarkerSpiderfier(map, spiderOptions);
	
	// Default info window
	infowindow = new google.maps.InfoWindow();
	
	// Geocoder
	geocoder = new google.maps.Geocoder();
	
}

// Call initialize after loaded
google.maps.event.addDomListener(window, 'load', initialize);

// Get our own location
function getOwnLocation(){
	if (navigator.geolocation){
		navigator.geolocation.getCurrentPosition(showOwnLocation);
	}else{
		console.log('Geolocation API not supported in this browser.');
	}
}

function showOwnLocation(position){
	ownLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
	map.setZoom(12);
	map.panTo(ownLocation);
}