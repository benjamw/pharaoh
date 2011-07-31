<?php

require_once 'includes/inc.global.php';

$act = (isset($_REQUEST['act']) ? $_REQUEST['act'] : '');

switch ($act) {
	case 'create' :
	case 'edit' :
	case 'clone' :
		if (isset($_POST['setup'])) {
			test_token( );

			$edit = false;
			try {
				if (isset($_POST['id'])) {
					$edit = true;

					// make sure this user can edit this setup
					$Setup = new Setup((int) $_POST['id']);

					if (((string) $_SESSION['player_id'] !== (string) $Setup->creator) && ! $GLOBALS['Player']->is_admin) {
						Flash::store('You are not allowed to perform this action', 'setups.php');
					}
				}
				else {
					$Setup = new Setup( );
				}

				$setup_id = $Setup->save( );
				Flash::store('Setup '.(($edit) ? 'Edited' : 'Created').' Successfully');
			}
			catch (MyException $e) {
				Flash::store('Setup '.(($edit) ? 'Edit' : 'Creation').' FAILED !\n'.$e->outputMessage( ), false);
				// it will just continue on here...
			}
		}

		$hints = array(
			'Click on a piece to select it, and then click anywhere on the board to place that piece.' ,
			'Click on the Cancel button to cancel piece selection.' ,
			'Click on the Delete button, and then click on the piece to delete an existing piece.' ,
			'The reflection setting will also place the reflected pieces along with the pieces you place, making board creation faster and less prone to errors.' ,
			'The reflections setting will also validate, so if you have a few non-reflected pieces in your layout, set reflection to "None" before submitting.' ,
			'Setups will be immediately available for use after successful creation.' ,
		);

		$edit = false;
		if ( ! empty($_REQUEST['id'])) {
			$hints[] = 'Only unique names and layouts will be accepted.';

			$Setup = new Setup((int) $_REQUEST['id']);

			// make sure this user can edit this setup
			if ('edit' == $act) {
				if (((string) $_SESSION['player_id'] !== (string) $Setup->creator) && ! $GLOBALS['Player']->is_admin) {
					Flash::store('You are not allowed to perform this action', 'setups.php');
				}

				$edit = true;
			}
		}
		else {
			$Setup = new Setup( );
		}

		if ( ! $edit) {
			$id_field = '';
			$create = 'Create';
		}
		else {
			$id_field = "<input type=\"hidden\" name=\"id\" value=\"{$Setup->id}\" />";
			$create = 'Edit';
		}

		$suffix = '';
		if ('clone' == $act) {
			$suffix = ' Clone';
		}

		$meta['title'] = $create.' Game Setup';
		$meta['head_data'] = '
			<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />

			<script type="text/javascript">
				var invert = false;
				var board = "'.expandFEN($Setup->board).'";
			</script>

			<script type="text/javascript" src="scripts/board.js"></script>
			<script type="text/javascript" src="scripts/setups.js"></script>
		';

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
				<input type="hidden" name="act" value="{$act}" />
				<input type="hidden" name="setup" id="setup" value="" />
				{$id_field}

				<div><label for="name">Setup Name</label><input id="name" name="name" value="{$Setup->name}{$suffix}" /></div>
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

				<div><input type="submit" value="{$create} Setup" /></div>
			</div></form>

EOF;

		break;

	case 'delete' :
		if (isset($_POST['id'])) {
			test_token( );

			// make sure this user can delete this setup
			$Setup = new Setup((int) $_POST['id']);

			if ( ! $Setup->creator || (((string) $_SESSION['player_id'] !== (string) $Setup->creator) && ! $GLOBALS['Player']->is_admin)) {
				Flash::store('You are not allowed to perform this action', 'setups.php');
			}

			Setup::delete($Setup->id);
			Flash::store('Setup deleted successfully', 'setups.php');
		}
		elseif (isset($_GET['id'])) {
			// make sure this user can edit / delete this setup
			$Setup = new Setup((int) $_GET['id']);

			if ( ! $Setup->creator || (((string) $_SESSION['player_id'] !== (string) $Setup->creator) && ! $GLOBALS['Player']->is_admin)) {
				Flash::store('You are not allowed to perform this action', 'setups.php');
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
				'If you do not wish to delete your setup, simply go to another section of the site. Or click here to return to the <a href="setups.php">Setup Page</a>' ,
			);

			$contents = <<< EOF

			<form method="post" action="{$_SERVER['REQUEST_URI']}" id="delete_form"><div class="formdiv">
				<input type="hidden" name="token" value="{$_SESSION['token']}" />
				<input type="hidden" name="act" value="delete" />
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

		break;

	default : // setup list
		$setups = Setup::get_list( );
		$setup_selection = '<option value="0">Random</option>';
		$setup_javascript = '';
		foreach ($setups as $setup) {
			$setup_selection .= '
				<option value="'.$setup['setup_id'].'">'.$setup['name'].'</option>';
			$setup_javascript .= "'".$setup['setup_id']."' : '".expandFEN($setup['board'])."',\n";
		}
		$setup_javascript = substr(trim($setup_javascript), 0, -1); // remove trailing comma

		$meta['title'] = 'Game Setups';
		$meta['head_data'] = '
			<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />

			<script type="text/javascript">//<![CDATA[
				var setups = {
					'.$setup_javascript.'
				};
			/*]]>*/</script>

			<script type="text/javascript" src="scripts/board.js"></script>
			<script type="text/javascript" src="scripts/setups.js"></script>
			<script type="text/javascript" src="scripts/stats.js"></script>
		';

		$hints = array(
			'Click on Setup table row to view setup.' ,
			'Clone will take you to the create setup page with that setup pre-filled out' ,
		);

		$table_meta = array(
			'sortable' => true ,
			'no_data' => '<p>There are no setups to show</p>' ,
			'caption' => 'Setups' ,
		);
		$table_format = array(
			array('SPECIAL_CLASS', 'true', 'setup') ,
			array('SPECIAL_HTML', 'true', ' id="s_[[[setup_id]]]"') ,

			array('Setup', 'name') ,
			array('Used', 'used') ,
			array('Horus', '###(([[[has_horus]]]) ? \'Yes\' : \'No\')') ,
		//	array('Tower', '###(([[[has_tower]]]) ? \'Yes\' : \'No\')') , // TOWER
			array('Reflection', 'reflection') ,
			array('Created', '###date(Settings::read(\'long_date\'), strtotime(\'[[[created]]]\'))', null, ' class="date"') ,
			array('Creator', '###((0 == [[[created_by]]]) ? \'Admin\' : $GLOBALS[\'_PLAYERS\'][[[[created_by]]]])') ,
			array('Action', '###((('.$_SESSION['player_id'].' == [[[created_by]]]) || '.(int) $GLOBALS['Player']->is_admin.') ? ((('.$_SESSION['player_id'].' != [[[created_by]]]) && '.(int) $GLOBALS['Player']->is_admin.') ? "<a href=\"setups.php?act=clone&amp;id=[[[setup_id]]]'.$GLOBALS['_&_DEBUG_QUERY'].'\">Clone</a> | " : "") . "<a href=\"setups.php?act=edit&amp;id=[[[setup_id]]]'.$GLOBALS['_&_DEBUG_QUERY'].'\">Edit</a> | <a href=\"setups.php?act=delete&amp;id=[[[setup_id]]]'.$GLOBALS['_&_DEBUG_QUERY'].'\">Delete</a>" : "<a href=\"setups.php?act=clone&amp;id=[[[setup_id]]]'.$GLOBALS['_&_DEBUG_QUERY'].'\">Clone</a>")', null, ' class="action"') ,
		);

		$contents = <<< EOF

			<form method="get" action="{$_SERVER['REQUEST_URI']}" id="create_form"><div class="formdiv">
				<input type="hidden" name="act" value="create" />
				<div><input type="submit" value="Create Setup" /></div>
			</div></form>

EOF;

		$contents .= get_table($table_format, $setups, $table_meta);

		$contents .= <<< EOF

			<div id="setup_display"></div>

EOF;

		break;
}



echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

