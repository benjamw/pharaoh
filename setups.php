<?php

require_once 'includes/inc.global.php';

if (isset($_POST['setup'])) {
	test_token( );

	try {
		$Setup = new Setup( );
		$setup_id = $Setup->create( );
		Flash::store('Setup Created Successfully');
	}
	catch (MyException $e) {
		Flash::store('Setup Creation FAILED !\n'.$e->outputMessage( ), false);
	}
}

$groups = array(
	'Normal' => array(0, 0),
	'Eye of Horus' => array(0, 1),
	'Tower of Kadesh' => array(1, 0),
	'Tower & Horus' => array(1, 1),
);
$group_names = array_keys($groups);
$group_markers = array_values($groups);

$setups = Setup::get_list( );
$setup_selection = '<option value="">-- Choose --</option><option value="0">Blank</option>';
$setup_javascript = '';
$cur_group = false;
$group_open = false;
foreach ($setups as $setup) {
	$marker = array((int) $setup['has_tower'], (int) $setup['has_horus']);
	$group_index = array_search($marker, $group_markers, true);

	if ($cur_group !== $group_names[$group_index]) {
		if ($group_open) {
			$setup_selection .= '</optgroup>';
			$group_open = false;
		}

		$cur_group = $group_names[$group_index];
		$setup_selection .= '<optgroup label="'.$cur_group.'">';
		$group_open = true;
	}

	$setup_selection .= '
		<option value="'.$setup['setup_id'].'">'.$setup['name'].'</option>';
	$setup_javascript .= "'".$setup['setup_id']."' : '".expandFEN($setup['board'])."',\n";
}
$setup_javascript = substr(trim($setup_javascript), 0, -1);

if ($group_open) {
	$setup_selection .= '</optgroup>';
}

$reflection_selection = '';

$meta['title'] = 'Create Game Setup';
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />

	<script type="text/javascript">
		var setups = {
			\'0\' : \''.str_repeat('0', 80).'\',
			'.$setup_javascript.'
		};
	</script>

	<script type="text/javascript" src="scripts/board.js"></script>
	<script type="text/javascript" src="scripts/setups.js"></script>
';

$hints = array(
	'Create a new game setup by choosing a layout to edit, chosing your settings, and editing the layout.' ,
	'Click on a piece to select it, and then click anywhere on the board to place that piece.' ,
	'Click on the Cancel button to cancel piece selelction.' ,
	'Click on the Delete button, and then click on the piece to delete to delete an existing piece.' ,
	'The reflection setting will also place the reflected pieces along with the pieces you place, making board creation faster and less prone to errors.' ,
	'The reflections setting will also validate, so if you have a few non-reflected pieces in your layout, set reflection to "None" before submitting.' ,
	'Setups will be immediately available for use after successful creation.' ,
	'When choosing an initializing layout, the name will also change, please be aware of this and edit the name before you submit.' ,
	'Only unique names and layouts will be accepted.' ,
);

$contents = <<< EOF

	<form method="post" action="{$_SERVER['REQUEST_URI']}" id="send"><div class="formdiv">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="player_id" value="{$_SESSION['player_id']}" />
		<input type="hidden" name="setup" id="setup" value="" />

		<div>
			<div><label for="init_setup">Initial Setup</label><select id="init_setup" name="init_setup">{$setup_selection}</select></div>
			<div><label for="name">Setup Name</label><input id="name" name="name" value="" /></div>
			<div><label for="reflection">Piece Reflection</label><select id="reflection" name="reflection">
				<option value="">None</option>
				<option>Origin</option>
				<option>Long</option>
				<option>Short</option>
			</select></div>
			<div><input type="submit" id="create" value="Create Setup" /></div>
		</div>

	</div></form>

	<div id="pieces_display">
		<img src="images/cancel.png" alt="Cancel" title="Cancel Selection" /><img src="images/delete.png" alt="Delete" title="Delete Piece" />
	</div>
	<div id="setup_display"></div>

EOF;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

