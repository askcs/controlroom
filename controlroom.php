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

	<!-- Custom CSS -->
	<link href="styles/style.css" rel="stylesheet">
	
    <script type="text/javascript">
	
	// Stophe documentation: http://strophe.im/strophejs/doc/1.1.3/files/strophe-js.html
	
	
	// ucfirst
	String.prototype.ucfirst = function() {
		return this.charAt(0).toUpperCase() + this.slice(1);
	}

	// Currently empty, but after we got a XMPP login, we can find out these URL's by asking the personal agent
	var ALARM_AGENT_URL = "";
	var DOMAIN_AGENT_URL = "";
	
	const CLIENT_HOST = "@xmpp.ask-cs.com/web"; // There is another one hardcoded in cape.js
	const PA_CLOUD_HOST = "@xmpp.ask-cs.com/cloud";
	const MOBILE_HOST = "@xmpp.ask-cs.com/android";
	
	var cc;
	var username = "<?=$_POST['un']?>"; // Note: May conflict with the 'username' var used in the modal code
	var userPersonalAgentXMPPAddress = "<?=$_POST['un']?>" + PA_CLOUD_HOST;
	
	var currentRealtimeUserPagerId = null;
	
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
				
				//addAlarmListUpdate("Een groepslid heeft zijn status aangepast, de lijst in de statussen kolom is geupdatet.", 'group-change');
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
						addAlarmListUpdate(alarmString, 'alarm-no-location');
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
			
			// Message read confirmation
			if(method == "messageread"){
				// A user confirms that a message is read
				console.log("Incoming 'message read' confirmation");
				
				addAlarmListUpdate('Bericht gelezen op de <em>' + params.platform + '</em> door <em>' + params.username + '</em>', 'message-read');
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
			
			// Reply on the pagerId request
			if(lastCalledMethod == "getPagerId"){
				//console.log(json.result);
				var pagerId = json.result
				currentRealtimeUserPagerId = pagerId;
				
				// Disable realtime pager messaging if this user doesnt have a pager
				if(typeof pagerId == 'undefined' || pagerId == null || pagerId == ''){
					$('#message-to-pager .platform_pager').remove(); // .attr('disabled', 'disabled'); // Note: Doesnt really work, input keeps the disabled option as current
				} else {
					$('#message-to-pager .platform_pager:not(.platform_mobile)').append(' ['+pagerId+']');
				}
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
						
						// Initial agent data loading done
						$("#dashboard-loading").html('');
						
						// Show loading icon from here for the groupstatusses
						$("#groupstatusses-container").html('<p><img alt="Laden..." src="img/loader.gif" /></p>');
						
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
			
			// Also remove the loading icon if something went wrong
			if(status == Strophe.Status.CONNFAIL || status == Strophe.Status.AUTHFAIL || status == Strophe.Status.ERROR){
				$("#dashboard-loading").html('');
			}
			
		};
		
		// Starting the login process, show loader
		$("#dashboard-loading").html('<p><img alt="Laden..." src="img/loader.gif" /></p>');
		
		cc = new CapeClient(msgHandler, messageFromAgentHandler);
		cc.login(username, "<?=$_POST['pw']?>", uiLoginCallback);
		
	});
	
	// Keep track of the users current status
	var firstRun = true;
	var currentUsersStatus = {};
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
				
				// Check the current status against previous call to this function (check for changes)
				// Only check for changes after the first run (reset firstRun outside each loops)
				if(!firstRun){
					// Is this user already known or new?
					
					if(currentUsersStatus[k1] != null && typeof currentUsersStatus[k1] != 'undefined'){
						
						// Is the status changed?
						if(currentUsersStatus[k1] != v1){
						
							// Is this change to available or unavailable?
							if(v1){
								addAlarmListUpdate("Collega "+k1+" heeft zijn status aangepast naar beschikbaar", 'group-change-available');
							} else {
								addAlarmListUpdate("Collega "+k1+" heeft zijn status aangepast naar niet beschikbaar", 'group-change-unavailable');
							}
						
						} else {
							// Nothing, this users status is not changed
						}
						
					} else {
						addAlarmListUpdate("Een nieuw groepslid ("+k1+") heeft zijn status aangepast.", 'group-change');
					}
				}
				
				// Push user status
				currentUsersStatus[k1] = v1;
				
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
		
		// Reset firstrun var
		if(firstRun){
			// Now it's not longer the first run
			firstRun = false;
		}

		console.log('Current users status: ');
		console.log(currentUsersStatus);
		
		$("#groupstatusses-container").html(table);
		
		var self = this;
		
		// Link evenhandler to the usernames so it will open a dialog with the username in it
		var modalWindow = $('.modal');
		$(".realtime-link").click(function () {
			var username = $(this).data('un');
			console.log("Opening realtime dialog for user: " + username);
			$("#dialog-username").text( username );
			
			var status = $(this).data('status');
			console.log("This user has the status of: " + status);
			if(status){
				$("#realtime-content-container").html("<h1>Gebruiker: " + username + "</h1>");
			} else {
				$("#realtime-content-container").html("Deze gebruiker is momenteel niet aanwezig, hierdoor is het niet mogelijk om in realtime berichten te versturen."); // zijn status, bewegingen en context te volgen.");
				return;
			}
			
			// Do some more (call agent to start realtime stuff and update the dialog from there)
			//$("#realtime-content-container").append("<br />Bezig met aanvragen van realtime data van " + username + "...");
			
			// Send message to this user
			$("#realtime-content-container").append("<p>Verstuur een bericht naar " + username + ": <p>");
			$("#realtime-content-container").append("<div><textarea id='message-text'></textarea></div>");
			$("#realtime-content-container").append("<div>Platform: <select id='message-to-pager'><option value='mobile_pager' class='platform_mobile platform_pager'>Mobiel + Pager</option><option value='mobile' class='platform_mobile'>Mobiel</option><option value='pager' class='platform_pager'>Pager</option></select></div>");
			$("#realtime-content-container").append("<div><button id='send-message' class='btn btn-invert'>Versturen</button></div>");
			
			// Check if this user has a pager to send the message to (pagerId in return function saved in var: currentRealtimeUserPagerId)
			var receiver = $("#dialog-username").text() + PA_CLOUD_HOST;
			cc.call(receiver, "getPagerId", {}, function(result){});
			
			/* Send message to (mobile) user */
			$('#send-message').click(function(){
			
				// Close the modal
				modalWindow.modal('hide');
				
				// Where to send this message to?
				var sendOption = $('#message-to-pager').val();
				
				var usernameReceiver = $("#dialog-username").text();
				var mobileReceiver = usernameReceiver + MOBILE_HOST;
				var pagerIdReceiver = currentRealtimeUserPagerId;
				var msg = $('#message-text').val();
				
				if(msg == ''){
					alert('Vul een bericht in voordat u deze verzend.');
					return;
				}
					
				// Option: To mobile only and mobile+pager (mobile only part)
				if(sendOption == 'mobile' || sendOption == 'mobile_pager'){
					console.log('Sending message from ['+self.username+'] to ['+mobileReceiver+'] on the mobile app. [' + msg + ']');
					addAlarmListUpdate('Bericht verzonden namens <em>'+self.username+'</em> naar de <strong>mobiele app</strong> van <em>'+usernameReceiver+'</em>.', 'message-outgoing');
					cc.call( mobileReceiver, "onIncomingMessage", {'message': msg, 'fromUser': self.username}, function(result){ });
				}
				
				// Option: To pager only and mobile+pager (pager only part)
				if(sendOption == 'pager' || sendOption == 'mobile_pager'){
					console.log('Sending message from ['+self.username+'] to ['+pagerIdReceiver+'] on the pager. [' + msg + ']');
					// Get
					$.get('pagerproxy.php?pagerId='+pagerIdReceiver+'&message=' + encodeURIComponent(msg) + '', function(data){
						addAlarmListUpdate('Bericht verzonden namens <em>'+self.username+'</em> naar de <strong>pager ['+pagerIdReceiver+']</strong> van <em>'+usernameReceiver+'</em> [<code>'+data+'</code>].', 'message-outgoing');
					});
				}
				
			});
			
			
			//cc.call(username + "" + CLIENT_HOST , "requestData", {'type': 'audio', 'transferToUser': "<?=$_POST['un']?>" + CLIENT_HOST }, function(result){});
			
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
			}else if(type == "alarm-no-location"){
				extraHtml = "<div class='list-icon list-alarm-icon'><img src='img/icon_missing_location_small.png' /></div>";
			}else if(type == "group-change"){
				extraHtml = "<div class='list-icon list-change-icon'><img src='img/icon_group_change_small.png' /></div>";
			}else if(type == "group-change-available"){
				extraHtml = "<div class='list-icon list-change-icon'><img src='img/icon_group_member_available_small.png' /></div>";
			}else if(type == "group-change-unavailable"){
				extraHtml = "<div class='list-icon list-change-icon'><img src='img/icon_group_member_unavailable_small.png' /></div>";
			}else if(type == "status-go"){
				extraHtml = "<div class='list-icon list-status-icon'><img src='img/icon_status_go_small.png' /></div>";
			}else if(type == "status-no-go"){
				extraHtml = "<div class='list-icon list-status-icon'><img src='img/icon_status_no_go_small.png' /></div>";
			}else if(type == "connection-online"){
				extraHtml = "<div class='list-icon list-status-icon'><img src='img/icon_connection_small.png' /></div>";
			}else if(type == "message-read"){
				extraHtml = "<div class='list-icon list-status-icon'><img src='img/icon_message_read_small.png' /></div>";
			}else if(type == "message-outgoing"){
				extraHtml = "<div class='list-icon list-status-icon'><img src='img/icon_message_outgoing_small.png' /></div>";
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
	
	/* Logout */
	$('#logout').click(function(e){
		cc.disconnect();
	});
	
    </script>
	
</head>
<body id="dashboard">
	
	  <!-- Navbar -->
<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<span class="brand"><a href="" class="brand"><?=FULL_NAME?> &ndash; Alarmcontrolecentrum</a> <a id="logout" class="btn btn-invert" href="login.php"> Uitloggen</a></span>

			<div class="nav pull-right" id="dashboard-loading"></div>
			<div class="nav pull-right" id="dashboard-status">
				<h3>Status: <span class='label label-warning' style='font-size: 85%; line-height: 1.1em;'>Verbinden...</a></h3>
			</div>

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
					<div id="groupstatusses-container"></div>
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
