<?php

require_once 'includes/inc.global.php';

if (isset($_POST['id'])) {
	test_token( );

	// make sure this user can edit / delete this setup
	$Setup = new Setup((int) $_POST['id']);

	if ( ! $Setup->creator || (((string) $_SESSION['player_id'] !== (string) $Setup->creator) && ! $GLOBALS['Player']->is_admin)) {
		Flash::store('You are not allowed to perform this action', 'stats.php');
	}

	try {
		$Setup->save( );
		Flash::store('Setup edited successfully', 'stats.php');
	}
	catch (MyException $e) {
		Flash::store('Setup Edit FAILED !\n'.$e->outputMessage( ), false);
		// it will just continue on here...
	}
}

if (isset($_GET['id']) || isset($_POST['id'])) {
	$id = (int) (isset($_GET['id']) ? $_GET['id'] : $_POST['id']);

	// make sure this user can edit / delete this setup
	$Setup = new Setup($id);

	if ( ! $Setup->creator || (((string) $_SESSION['player_id'] !== (string) $Setup->creator) && ! $GLOBALS['Player']->is_admin)) {
		Flash::store('You are not allowed to perform this action', 'stats.php');
	}

	$meta['title'] = 'Edit Game Setup';
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
		'Click on a piece to select it, and then click anywhere on the board to place that piece.' ,
		'Click on the Cancel button to cancel piece selection.' ,
		'Click on the Delete button, and then click on the piece to delete to delete an existing piece.' ,
		'The reflection setting will also place the reflected pieces along with the pieces you place, making board creation faster and less prone to errors.' ,
		'The reflections setting will also validate, so if you have a few non-reflected pieces in your layout, set reflection to "None" before submitting.' ,
		'Setups will be immediately available for use after successful edits.' ,
		'Only unique names and layouts will be accepted.' ,
		'<span class="highlight">NOTE</span>: The name is read-only, and cannot be edited. If you wish to edit the name, create a new setup based on this setup, and then delete this setup.' ,
	);

	$reflection = $Setup->get_reflection( );
	foreach (array('Origin', 'Long', 'Short', 'None') as $type) {
		${$type} = '';
		if ($reflection == $type) {
			${$type} = ' selected="selected"';
		}
	}

	$contents = <<< EOF

	<form method="post" action="{$_SERVER['REQUEST_URI']}" id="setup_form"><div class="formdiv">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="setup" id="setup" value="" />
		<input type="hidden" name="id" value="{$Setup->id}" />

		<div><label for="name">Setup Name</label><input id="name" name="name" value="{$Setup->name}" readonly="readonly" /></div>
		<div><label for="reflection">Piece Reflection</label><select id="reflection" name="reflection">
			<option{$None}>None</option>
			<option{$Origin}>Origin</option>
			<option{$Long}>Long</option>
			<option{$Short}>Short</option>
		</select></div>

		<div id="pieces_display"><img src="images/cancel.png" alt="Cancel" title="Cancel Selection" class="cancel" /><img src="images/delete.png" alt="Delete" title="Delete Piece" class="delete" /></div>
		<div id="setup_display">
			<!-- the board will go here -->

			<div class="buttons" style="text-align:center;">
				<a href="javascript:;" id="red_laser" style="float:left;">Test Fire Red Laser</a>
				<a href="javascript:;" id="invert">Invert Board</a> |
				<a href="javascript:;" id="clear_laser">Clear Laser</a>
				<a href="javascript:;" id="silver_laser" style="float:right;">Test Fire Silver Laser</a>
			</div> <!-- .buttons -->
		</div>

		<div><input type="submit" value="Save Edited Setup" /></div>
	</div></form>

EOF;

}

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

