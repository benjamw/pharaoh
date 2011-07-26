
var reload = true; // do not change this

function check_fieldset_box( ) {
	$('input.fieldset_box').each( function(i, elem) {
		var $this = $(this);
		var id = $this.attr('id').slice(0,-4);

		if ($this.prop('checked')) {
			$('div#'+id).show( );
		}
		else {
			$('div#'+id).hide( );
		}
	});
}

$(document).ready( function( ) {
	// hide the setup div
	$('div#setup_display').hide( );

	// show the setup div in a fancybox
	$('a#show_setup').fancybox({
		padding : 10,
		onStart : function( ) {
			$('div#setup_display')
				.empty( )
				.append(create_board(setups[$('select#setup').val( )]))
				.show( );
		},
		onClosed :function( ) {
			$('div#setup_display').hide( );
		}
	});

	// only show the setup link when a setup is selected
	$('select#setup').change( function( ) {
		show_link( );
	});
	show_link( );

	// show the setup div for the invites
	$('a.setup').fancybox({
		padding : 10,
		onStart : function(arr, idx, opts) {
			$('div#setup_display')
				.empty( )
				.append(create_board(setups[$(arr[idx]).attr('id').slice(2)]))
				.show( );
		},
		onClosed :function( ) {
			$('div#setup_display').hide( );
		}
	});

	$('form#send').submit( function( ) {
		if ( ! $('select#setup').val( )) {
			alert('You must select a setup');
			return false;
		}

		return true;
	});

	// hide the collapsable fieldsets
	$('input.fieldset_box').change( function( ) {
		check_fieldset_box( );
	});
	check_fieldset_box( );

	// this runs all the ...vites
	$('div#invites input').click( function( ) {
		var id = $(this).attr('id').split('-');

		if ('accept' == id[0]) { // invites and openvites
			// accept the invite
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+'invite=accept&game_id='+id[1];
				return;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: 'invite=accept&game_id='+id[1],
				success: function(msg) {
					window.location = 'game.php?id='+msg+debug_query_;
					return;
				}
			});
		}
		else if ('resend' == id[0]) { // resends outvites
			// resend the invite
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+'invite=resend&game_id='+id[1];
				return;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: 'invite=resend&game_id='+id[1],
				success: function(msg) {
					alert(msg);
					if (reload) { window.location.reload( ); }
					return;
				}
			});
		}
		else { // invites decline and outvites withdraw
			// delete the invite
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+'invite=delete&game_id='+id[1];
				return;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: 'invite=delete&game_id='+id[1],
				success: function(msg) {
					alert(msg);
					if (reload) { window.location.reload( ); }
					return;
				}
			});
		}
	});
});

function show_link( ) {
	if (0 != $('select#setup').val( )) {
		$('a#show_setup').show( );
	}
	else {
		$('a#show_setup').hide( );
	}
}

