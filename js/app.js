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
			
			// Set this user as 'last alarming user' for now
			lastAlarmingUser = params.sender;
			
			// Re-generate the groups table with the latest known user-data, but with the alarm user in red
			createGroupsTable(savedGroupsJSON);
			
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
					
				}, startInterval);
			
			}, startInterval);
		
		
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
	cc.login(username, base64_decode(password), uiLoginCallback);
	
});

// Keep track of the users current status
var firstRun = true;
var currentUsersStatus = {};
function createGroupsTable(groupsJSON){

	savedGroupsJSON = groupsJSON;
	
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
			
			// Add a round border for the last alarming user
			if(k1 == lastAlarmingUser){
				row.addClass('groupmember-statusses-alarming-user');
			}
			
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
			
			// If this is the user who send an alarm as last one; provide a 'resolved' (Probleem opgelost) button
			if(k1 == lastAlarmingUser){
				$(column1).append("<span class='badge badge-important' id='resolve-alarm'>Opgelost?</span></a>");
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
	
	// Are we still in an alarm situation?
	if(lastAlarmingUser != null){
	
		// Create an event handler for the label/button that resolves the alarm
		$('#resolve-alarm').click(function(){
			console.log('Resolving alarm of the last user [' + lastAlarmingUser + ']');
			
			
			addAlarmListUpdate('De alarmsituatie van ' + lastAlarmingUser + ' is opgelost.', 'alarm-resolved');
			
			// No active alarm (user)
			lastAlarmingUser = null;
			
			// Re-generate the groups table so that the alarm is resolved
			createGroupsTable(savedGroupsJSON);

		});
		
	}
	
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
			
			// Close the modal
			modalWindow.modal('hide');
				
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
		
		//cc.call(username + "" + CLIENT_HOST , "requestData", {'type': 'audio', 'transferToUser': self.username + CLIENT_HOST }, function(result){});
		
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
		}else if(type == "alarm-resolved"){
			extraHtml = "<div class='list-icon list-alarm-icon'><img src='img/icon_check_small.png' /></div>";
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

/* Logout */
$('#logout').click(function(e){
	cc.disconnect();
});

// ucfirst
String.prototype.ucfirst = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
}