<?php

require_once 'includes/inc.global.php';

if (isset($_POST['id'])) {
	test_token( );

	// make sure this user can edit / delete this setup
	$Setup = new Setup((int) $_POST['id']);

	if ( ! $Setup->creator || (((string) $_SESSION['player_id'] !== (string) $Setup->creator) && ! $GLOBALS['Player']->is_admin)) {
		Flash::store('You are not allowed to perform this action', 'stats.php');
	}

	Setup::delete($Setup->id);
	Flash::store('Setup deleted successfully', 'stats.php');
}
elseif (isset($_GET['id'])) {
	// make sure this user can edit / delete this setup
	$Setup = new Setup((int) $_GET['id']);

	if ( ! $Setup->creator || (((string) $_SESSION['player_id'] !== (string) $Setup->creator) && ! $GLOBALS['Player']->is_admin)) {
		Flash::store('You are not allowed to perform this action', 'stats.php');
	}

	// we need to confirm the delete request via a safer method (no XSRF here)
	$meta['title'] = 'Delete Game Setup';
	$meta['head_data'] = '
		<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />

		<script type="text/javascript">
			var invert = false;
			var board = "'.expandFEN($Setup->board).'";
		</script>

		<script type="text/javascript" src="scripts/board.js"></script>
		<script type="text/javascript" src="scripts/setups.js"></script>
	';

	$hints = array(
		'Delete your game setup by clicking the button.' ,
		'If you do not wish to delete your setup, simply go to another section of the site.' ,
	);

	$contents = <<< EOF

	<form method="post" action="{$_SERVER['REQUEST_URI']}" id="setup_form"><div class="formdiv">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="id" value="{$Setup->id}" />

		<div>Are you sure you wish to delete your setup, {$Setup->name}?  This cannot be undone.</div>
		<div><input type="submit" value="Delete Setup" /></div>
	</div></form>

	<div id="setup_display">
		<!-- the board will go here -->

		<div class="buttons" style="text-align:center;">
			<a href="javascript:;" id="red_laser" style="float:left;">Test Fire Red Laser</a>
			<a href="javascript:;" id="invert">Invert Board</a> |
			<a href="javascript:;" id="clear_laser">Clear Laser</a>
			<a href="javascript:;" id="silver_laser" style="float:right;">Test Fire Silver Laser</a>
		</div> <!-- .buttons -->
	</div>

EOF;

}

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

