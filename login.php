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
    <style>
	body {
	  padding-top: 40px;
	  padding-bottom: 40px;
	  background-color: #eee;
	}

	.form-signin {
	  max-width: 330px;
	  padding: 15px;
	  margin: 0 auto;
	}
	.form-signin .form-signin-heading,
	.form-signin .checkbox {
	  margin-bottom: 10px;
	}
	.form-signin .checkbox {
	  font-weight: normal;
	}
	.form-signin input[type="text"],
	.form-signin input[type="password"],
	.form-signin input[type="checkbox"] {
	  position: relative;
	  font-size: 16px;
	  height: auto;
	  padding: 10px;
	  -webkit-box-sizing: border-box;
		 -moz-box-sizing: border-box;
			  box-sizing: border-box;
	}
	.form-signin input[type="text"]:focus,
	.form-signin input[type="password"]:focus,
	.form-signin input[type="checkbox"]:focus {
	  z-index: 2;
	}
	.form-signin input[type="text"],
	.form-signin input[type="checkbox"] {
	  margin-bottom: -1px;
	  border-bottom-left-radius: 0;
	  border-bottom-right-radius: 0;
	}
	.form-signin input[type="password"] {
	  margin-bottom: 10px;
	  border-top-left-radius: 0;
	  border-top-right-radius: 0;
	}
	
	label{
		display: inline-block;
		margin-right: 1px;
	}
	
	#safestart{
		display: inline-block;
	}
	</style>
  </head>

  <body>

    <div class="container">

      <form class="form-signin" method="post" action="controlroom.php">
        <h2 class="form-signin-heading">Inloggen &ndash; BHV</h2>
        <input type="text" name="un" class="input-block-level" placeholder="Username" value="<?=$_GET['un']?>" autofocus>
        <input type="password" name="pw" class="input-block-level" placeholder="Password">
        <label for="safestart" id="label-safestart">Safe start (for slower connections)? </label>
		<input type="checkbox" name="safestart" id="safestart" class="input-block-level" placeholder="Safestart">
        <button class="btn btn-large btn-primary btn-block" type="submit">Sign in</button>
      </form>

    </div> <!-- /container -->

  </body>
</html>