<?php 
// Redirect if no username is given
if(!isset($_POST['un'])){ header('Location: login.php?un=ecr'); }

// Determine the interval for getting initial results via XMPP
// TODO/NOTE: We need a proper callback system on the XMPP library (to 'stack' the calls instead of guessing the time intervals)
$startInterval = 850;
if(isset($_POST['safestart'])){ $startInterval = 1800; }

const SHORT_NAME = 'BHV';
const FULL_NAME = 'Bedrijfshulpverlening';
?>
<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <title><?=SHORT_NAME?> Alarmcontrolecentrum</title>
    
    <meta name="description" content="CapeClient v1.0.0">
    <meta name="viewport" content="width=device-width">
    
	<!--<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>-->
	<script src="external/jquery.min.js"></script>
    <script src="lib/strophe.js"></script>
    <script src="cape.js"></script>
	
	<!-- <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet"> -->
	<link href="external/bootstrap-combined.min.css" rel="stylesheet">
	<!-- <link href="//netdna.bootstrapcdn.com/bootswatch/2.3.2/cyborg/bootstrap.min.css" rel="stylesheet"> -->
	<link href="external/bootstrap.min.css" rel="stylesheet">
	<!-- <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script> -->
	<script src="external/bootstrap.min.js"></script>
	
	<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCJjmPn0FpxM7wZ8y-lW4aNsPsq79sKrd8&v=3.exp&sensor=false"></script>
	<!--<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCXRA0U_rMInxjO3lw2v4_oxAjBOVK4xes&v=3.exp&sensor=false"></script>-->
	<!-- <script src="http://jawj.github.com/OverlappingMarkerSpiderfier/bin/oms.min.js"></script> -->
	<script src="external/oms.min.js"></script>

    <script type="text/javascript">
	
	// ucfirst
	String.prototype.ucfirst = function() {
		return this.charAt(0).toUpperCase() + this.slice(1);
	}

	// Currently empty, but after we got a XMPP login, we can find out these URL's by asking the personal agent
	var ALARM_AGENT_URL = "";
	var DOMAIN_AGENT_URL = "";
	
	const CLIENT_HOST = "@xmpp.ask-cs.com/web"; // There is another one hardcoded in cape.js
	const PA_CLOUD_HOST = "@xmpp.ask-cs.com/cloud";
	
	var cc;
	var username = "<?=$_POST['un']?>"; // Note: May conflict with the 'username' var used in the modal code
	var userPersonalAgentXMPPAddress = "<?=$_POST['un']?>" + PA_CLOUD_HOST;
	
	$(document).ready(function(){
		
		console.log("Start!");
		
		var msgHandler = function(method, params){
			console.log("Method: " + method);
			console.log(params);
			
			// Data which comes 'on the fly' from the cloud agent
			if(method == "groupstatuschange"){
				// Re-request all current userstatusses of all groups
				console.log("Someone's status has changed, lets re-request all groupmembers statusses");
				cc.call(ALARM_AGENT_URL, "getAllGroupMembersStatus", {}, function(result){ });
				
				addAlarmListUpdate("Een groepslid heeft zijn status aangepast, de lijst in de statussen kolom is geupdatet.", 'group-change');
			}
			
			if(method == "alarm"){
				setBlinkBackgroundColor("#FF0000");
				triggerAlarmBackground();
				
				// Zoom and pan to alarm location if map is loaded
				if(map != null){
				
					alarmString = 'Alarm door <strong>' + params.sender + '</strong> van het type: ' + params.text + ' (' + params.datetime + ')';
					addAlarmListUpdate(alarmString, 'alarm');
					
					var alarmLocation = new google.maps.LatLng(params.lat, params.lng);
					if( (params.lat == 0 && params.lng == 0) || (params.lat == '0' && params.lng == '0') ){
						// If we don't really have a loaction fix, notify the alarm room and don't zoom to the (ocean) location.
						alarmString = '<strong>Geen alarmlocatie beschikbaar voor:</strong> Alarm van <strong>' + params.sender + '</strong>, type: ' + params.text + ' (' + params.datetime + ')';
						addAlarmListUpdate(alarmString, 'alarm');
					} else {
						map.setZoom(17);
						map.panTo(alarmLocation);
					}
					
					alarmMarker = new google.maps.Marker({
						position: alarmLocation,
						map: map,
						infowindowContent: alarmString
					});
					
					// Add marker to spider
					oms.addMarker(alarmMarker);
			  
					// Add infowindow to marker
					google.maps.event.addListener(alarmMarker, 'click', function() {
						infowindow.setContent(this.infowindowContent); 
						infowindow.open(map, this);
					});
					
					// Auto open this last alarm
					//infowindow.open(map,alarmMarker);
		
				}
				
			}
			
			if(method == "alarmresponse"){
			
				if(params.status){
					addAlarmListUpdate('Collega <strong>' + params.usernameSender + '</strong> heeft aangegeven om naar de alarmlocatie te gaan van <strong>' + params.usernameEmergency + '</strong>', 'status-go');
					
					// Green blink
					setBlinkBackgroundColor("#228B22");
					triggerAlarmBackground();
				
				} else {
					addAlarmListUpdate('Collega <strong>' + params.usernameSender + '</strong> heeft aangegeven om <em>niet</em> naar de alarmlocatie te gaan van <strong>' + params.usernameEmergency + '</strong>', 'status-no-go');
					
					// Orange blink
					setBlinkBackgroundColor("#FF8C00");
					triggerAlarmBackground();
				}
			}
			
		}
		
		var messageFromAgentHandler = function(json, lastCalledMethod){
			console.log("XMPP Data:");
			console.log(json);
			
			// Data which this webclient has requested
			if(lastCalledMethod == "getAllGroupMembersStatus"){
				//console.log(json.result);
				createGroupsTable(json.result);
			}
			
			if(lastCalledMethod == "getDomainAgentUrl"){
				// TODO: Loop over results
				//console.log(json.result);
				
				DOMAIN_AGENT_URL = json.result;
				DOMAIN_AGENT_URL = DOMAIN_AGENT_URL.replace('xmpp:', '');
				console.log('DomainAgent URL set to: ' + DOMAIN_AGENT_URL);
			}
			
			if(lastCalledMethod == "getAlarmManagementAgentUrl"){
				// TODO: Loop over results
				//console.log(json.result);
				
				ALARM_AGENT_URL = json.result;
				ALARM_AGENT_URL = ALARM_AGENT_URL.replace('xmpp:', '');
				console.log('AlarmAgent URL set to: ' + ALARM_AGENT_URL);
				
			}
			
		}
		
		var uiLoginCallback = function loginStatusUpdate(status){
			// Switch it
			console.log('Login status update: ' + status);
			
			// Succesfully connected; show it in the UI
			if(status == Strophe.Status.CONNECTED){
			
				// Set the connection state
				addAlarmListUpdate("Verbonden met het alarmeringen netwerk.", 'connection-online');
				
				// Set the status in the interface
				$("#dashboard-status").html("<h3>Status: <span class='label label-success' style='font-size: 85%; line-height: 1.1em;'>Verbonden</a></h3>");
				
				
				// Now load the initial data
				
				// Get the domain agent id
				console.log('Request domainAgentUrl from our own cloudagent: ' + userPersonalAgentXMPPAddress);
				cc.call(userPersonalAgentXMPPAddress, "getDomainAgentUrl", {}, function(result){});
				
				setTimeout(function(){
					
					// Get the AlarmAgent URL from the domainagent
					cc.call(DOMAIN_AGENT_URL, "getAlarmManagementAgentUrl", {}, function(result){});
					
					setTimeout(function(){
						// Get intial data to display
						cc.call(ALARM_AGENT_URL, "getAllGroupMembersStatus", {}, function(result){});
					}, <?=$startInterval?>);
				
				}, <?=$startInterval?>);
			
			
			} else if(status == Strophe.Status.CONNECTING){ // Currently connecting
				$("#dashboard-status").html("<h3>Status: <span class='label label-info' style='font-size: 85%; line-height: 1.1em;'>Verbinden...</a></h3>");
			} else if(status == Strophe.Status.CONNFAIL){ // Random error
				$("#dashboard-status").html("<h3>Status: <span class='label label-important' style='font-size: 85%; line-height: 1.1em;'>Verbinden mislukt</a></h3>");
			} else if(status == Strophe.Status.AUTHENTICATING){ // Currently authentication
				$("#dashboard-status").html("<h3>Status: <span class='label label-info' style='font-size: 85%; line-height: 1.1em;'>Aanmelden...</a></h3>");
			} else if(status == Strophe.Status.AUTHFAIL){ // Authentication failed
				$("#dashboard-status").html("<h3>Status: <span class='label label-important' style='font-size: 85%; line-height: 1.1em;'>Aanmelden mislukt</a></h3>");
			} else if(status == Strophe.Status.DISCONNECTED){ // Disconnected
				$("#dashboard-status").html("<h3>Status: <span class='label label-warning' style='font-size: 85%; line-height: 1.1em;'>Verbinding losgekoppeld</a></h3>");
			} else if(status == Strophe.Status.DISCONNECTING){ // Currently disconnected
				$("#dashboard-status").html("<h3>Status: <span class='label label-warning' style='font-size: 85%; line-height: 1.1em;'>Verbinding loskoppelen...</a></h3>");
			} else if(status == Strophe.Status.ATTACHED){ // Attatched (no clue what it is exactly)
				//$("#dashboard-status").html("<h3>Status: <span class='label label-warning' style='font-size: 85%; line-height: 1.1em;'>Disconnected</a></h3>");
			} else if(status == Strophe.Status.ERROR){ // Authentication failed (wrong username/password)
				$("#dashboard-status").html("<h3>Status: <span class='label label-important' style='font-size: 85%; line-height: 1.1em;'>Login mislukt</a></h3>");
			}
			
		};
		
		cc = new CapeClient(msgHandler, messageFromAgentHandler);
		cc.login(username, "<?=$_POST['pw']?>", uiLoginCallback);
		
	});
	
	function createGroupsTable(groupsJSON){
	
		// Create table
		var table = $('<table></table>').addClass('table table-striped table-hover table-bordered groupmember-statusses-table');
		
		// Add heading
		var head = $('<thead></thead>');
		
		var row = $('<tr></tr>');
		row.append( $('<td></td>').text("Gebruiker") );
		row.append( $('<td></td>').text("Status") );
		head.append(row);
		table.append(head);
		
		// Fill table		
		$.each(groupsJSON, function(k, v) {
		
			row = $('<tr></tr>');
			var column0 = $('<td class="groupmember-statusses-table-group-heading" colspan="2"></td>').text("Groep: " + k.ucfirst() );
			
			row.append(column0);
			
			table.append(row);
		
			$.each(v, function(k1, v1) {
				row = $('<tr></tr>');
				
				var column0 = $('<td></td>').html("<a data-toggle='modal' href='#realtime' data-un='" + k1 + "' data-status='" + v1 + "' class='btn btn-primary realtime-link' style='margin-right: 10px; padding: 2px;'><img src='img/icon_smartphone_small.png' /></a>" + k1);
				if(v1){
					var column1 = $('<td></td>').html("<span class='badge badge-success'>Aanwezig</span></a>");
				} else {
					var column1 = $('<td></td>').html("<span class='badge badge-warning'>Afwezig</span></a>");
				}
				
				row.append(column0);
				row.append(column1);
				
				table.append(row);
			
			});
		});

		$("#groupstatusses-container").html(table);
		
		// Link evenhandler to the usernames to it will open a dialog with the username in it
		$(".realtime-link").click(function () {
			var username = $(this).data('un');
			console.log("Opening realtime dialog for user: " + username);
			$("#dialog-username").text( username );
			
			var status = $(this).data('status');
			console.log("This user has the status of: " + status);
			if(status){
				$("#realtime-content-container").html("Laden...");
			} else {
				$("#realtime-content-container").html("Deze gebruiker is momenteel niet aanwezig, hierdoor is het niet mogelijk om in realtime zijn status, bewegingen en context te volgen.");
				return;
			}
			
			// Do some more (call agent to start realtime stuff and update the dialog from there)
			$("#realtime-content-container").append("<br />Bezig met aanvragen van realtime data van " + username + "...");
			
			cc.call(username + "" + CLIENT_HOST , "requestData", {'type': 'audio', 'transferToUser': "<?=$_POST['un']?>" + CLIENT_HOST }, function(result){});
			
		});
		
	}
	
	var blinker = 0;
	var maxblinks = 8;
	var colorToBlink = "#FF0000";
	function setBlinkBackgroundColor(color){
		colorToBlink = color;
	}
	
	function triggerAlarmBackground(){
		
		if(blinker > maxblinks){
			blinker = 0;
			return;
		}
		
		setTimeout(function(){
			$("body").css("background", "linear-gradient(to bottom, " + colorToBlink + ", #252A30)");
			
			setTimeout(function(){
				$("body").css("background", "linear-gradient(to bottom, #060606, #252A30)");
				blinker++;
				triggerAlarmBackground();
			}, 100);
		}, 100);
		
	}
	
	var curTime;
	var curTimeString;
	function addAlarmListUpdate(msg, type){
		
		var extraHtml = "";
		if(type != null){
			
			if(type == "alarm"){
				extraHtml = "<div class='list-icon list-alarm-icon'><img src='img/icon_alarm_small.png' /></div>";
			}else if(type == "group-change"){
				extraHtml = "<div class='list-icon list-change-icon'><img src='img/icon_group_change_small.png' /></div>";
			}else if(type == "status-go"){
				extraHtml = "<div class='list-icon list-status-icon'><img src='img/icon_status_go_small.png' /></div>";
			}else if(type == "status-no-go"){
				extraHtml = "<div class='list-icon list-status-icon'><img src='img/icon_status_no_go_small.png' /></div>";
			}else if(type == "connection-online"){
				extraHtml = "<div class='list-icon list-status-icon'><img src='img/icon_connection_small.png' /></div>";
			}
			
			
		}
	
		curTime = new Date();
		curTimeString = curTime.getHours() + ":" + curTime.getMinutes() + ":" + curTime.getSeconds();
		$("#alarm-updates-list").prepend( $("<li />").html(extraHtml + "<span class='alarm-updates-list-time badge badge-info'>" + curTimeString + "</span>" + msg) );
	}
	</script>
	
	<script>
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
    </script>
	
	<style>
	body{
		padding-top: 80px;
		overflow-y: scroll;
	}
	
	.groupmember-statusses-table-group-heading{
		font-weight: bold;
		background-color: #111 !important;
	}
	
	.groupmember-statusses-table td:hover{
		cursor: pointer;
	}
	
	.content-block{
		margin-top: 20px;
		padding: 10px;

		background-color: #333;
		-webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
		
		-webkit-border-radius: 6px;
		-moz-border-radius: 6px;
		border-radius: 6px;
	}
	
	#map-canvas{
		width: 100%;
		height: 400px;
	}
	
	#map-canvas img { 
		max-width: none;
	}

	#map-canvas label { 
		width: auto; display:inline; 
	} 
	
	hr{
		margin: 10px 0;
		border-color: #444;
	}
	
	#alarm-updates-list{
		list-style-type: none;
		margin: 0;
		margin-top: 20px;
		padding: 4px;
		overflow: auto;
	}
	
	#alarm-updates-list li{
		padding: 2px 0;
		border-bottom: 1px solid #CCC;
		float: left;
		overflow: auto;
		width: 100%;
	}
	
	.alarm-updates-list-time{
		margin-right: 5px;
	}
	
	.list-icon{
		float: left;
		margin: 0px 15px 5px 5px;
	}
	
	#groupstatusses-container p{
		margin-top: 20px;
		text-align: center;
	}
	
	</style>
	
  </head>
  <body>
	
	  <!-- Navbar
    ================================================== -->
 <div class="navbar navbar-fixed-top">
   <div class="navbar-inner">
     <div class="container">
       <a class="brand" href=""><?=FULL_NAME?> &ndash; Alarmcontrolecentrum</a>
       <div class="nav pull-right" id="dashboard-status"><h3>Status: <span class='label label-warning' style='font-size: 85%; line-height: 1.1em;'>Verbinden...</a></h3></div>
     </div>
   </div>
 </div>


	<div class="container">

		<!-- Main component for a primary marketing message or call to action -->
		<div class="row-fluid">
			<div class="span12">
				<div class="content-block">
					<h1><?=SHORT_NAME?> &ndash; Alarm App Dashboard</h1>
				</div>
			</div>
		</div>

		<div class="row-fluid">
			<div class="span8">
				<div class="content-block">
					<h3>Alarmeringen kaart</h2>
					<hr />
					<div id="map-canvas"></div>
					
					<ul id="alarm-updates-list"></ul>
				</div>
			</div>
			<div class="span4">
				<div class="content-block">
					<h3>Groepsleden statussen</h2>
					<hr />
					<div id="groupstatusses-container"><p><img alt="Laden..." src="img/loader.gif" /></p></div>
				</div>
			</div>
		</div>

	</div> <!-- /container -->

  <!-- Modal -->
  <div class="modal" id="realtime" style="display: none;">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">Alarm App &ndash; Realtime data van <strong><span id="dialog-username"></span></strong></h4>
        </div>
        <div class="modal-body" id="realtime-content-container">
          ...
        </div>
        <div class="modal-footer">
          <a href="#" class="btn" data-dismiss="modal">Close</a>
        </div>
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->
	
  </body>
</html>
