<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Alarm App - Control Room Login</title>

    <!-- Bootstrap core CSS -->
    <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">

	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>

    <!-- Custom styles for this template -->
    <link href="styles/style.css" rel="stylesheet">
  </head>

  <body id="login">

    <div class="container">

	  <!-- <div class="alert alert-danger">Onderhoud &ndash; Het is op dit moment niet mogelijk om in te loggen op het Alarmcontrolecentrum. (Verwachting: Voor 12:00 terug online)</div> -->
	  
      <form class="form-signin" method="post" action="controlroom.php">
        <h2 class="form-signin-heading">Inloggen &ndash; BHV</h2>
        <input type="text" name="un" class="input-block-level" placeholder="Username" value="<?=$_GET['un']?>" autofocus>
        <input type="password" name="pw" class="input-block-level" placeholder="Password">
        <label for="safestart" id="label-safestart">Safe start (for slower connections)? </label>
		<input type="checkbox" name="safestart" id="safestart" class="input-block-level" placeholder="Safestart">
        <button class="btn btn-large btn-primary btn-block" type="submit" >Sign in</button><!-- disabled="disabled" -->
      </form>

    </div> <!-- /container -->

  </body>
</html>