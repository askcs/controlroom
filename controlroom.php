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
	var startInterval = <?=$startInterval?>;
	
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
	var password = "<?=base64_encode($_POST['pw'])?>";
	var userPersonalAgentXMPPAddress = "<?=$_POST['un']?>" + PA_CLOUD_HOST;
	
	var currentRealtimeUserPagerId = null;
	</script>
	
	<script src="js/base64.js"></script>
	<script src="js/app.js"></script>
	<script src="js/map.js"></script>
	
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
