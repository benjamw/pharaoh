
// $Id: game.js 191 2009-09-06 22:50:54Z cchristensen $

var stage_1 = false;
var reload = true; // do not change this

$(document).ready( function( ) {

	// nudge button
	$('#nudge').click( function( ) {
		if (confirm('Are you sure you wish to nudge this person?')) {
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+$('#game_form').serialize( )+'&nudge=1';
				return false;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: $('#game_form').serialize( )+'&nudge=1',
				success: function(msg) {
					var reply = JSON.parse(msg);

					if (reply.error) {
						alert(reply.error);
					}
					else {
						alert('Nudge Sent');
					}

					if (reload) { window.location.reload( ); } else { alert('Reload 7'); }
					return;
				}
			});
		}
	});

	// chat box functions
	$('#chatbox form').submit( function( ) {
		if ('' == $.trim($('#chatbox input').val( ))) {
			return false;
		}

		if (debug) {
			window.location = 'ajax_helper.php'+debug_query+'&'+$('#chatbox form').serialize( );
			return false;
		}

		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: $('#chatbox form').serialize( ),
			success: function(msg) {
				var reply = JSON.parse(msg);

				if (reply.error) {
					alert(reply.error);
				}
				else {
					var entry = '<dt><span>'+reply.create_date+'</span> '+reply.username+'</dt>'+
						'<dd'+(('1' == reply.private) ? ' class="private"' : '')+'>'+reply.message+'</dd>';

					$('#chats').prepend(entry);
					$('#chatbox input').val('');
				}
			}
		});

		return false;
	});

});

