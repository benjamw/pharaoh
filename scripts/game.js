
var stage_1 = false;
var reload = true; // do not change this

$(document).ready( function( ) {
	// add the board
	$("div#board").empty( ).append(create_board(board, invert));

	// fire the laser
	setTimeout("fire_laser(prev_turn[1], invert);", 2000);

	// clear laser button
	$("a#clear_laser").click( function( ) {
		clear_laser( );
		return false;
	});

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

	enable_moves( );
});


function enable_moves( ) {
	if ( ! my_turn) {
		return;
	}

	$('div#board div.piece.p_'+color).click( function( ) {
		clear_laser( );
		clear_highlights( );
		highlight_valid_moves(this);
		set_from_square($(this).attr('id').slice(4));
	}).css('cursor', 'pointer');
}


function highlight_valid_moves(elem) {
	var $elem = $(elem);

	// highlight all adjacent non-occupied squares
	var adj = get_adjacent($elem.attr('id').slice(4));

	$.each(adj, function(i, val) {
		var $idx = $('#idx_'+val);
		var to_class = $idx.attr('class');
		var to_color = to_class.match(/p_[^\s]+/ig);
		var fr_class = $elem.attr('class');
		var fr_color = fr_class.match(/p_[^\s]+/ig);

		// if the class is an empty string, just set the class and exit here
		if ('' == to_class) {
			add_highlight($idx);
			return;
		}

		// now run some tests to see if we should be highlighting this square

		// if it's a color square, make sure it's the same color
		if ((null != to_class.match(/c_[^\s]+/ig))
			&& (to_class.match(/c_[^\s]+/ig) != fr_class.replace(/p_([^\s]+)/ig, 'c_$1').match(/c_[^\s]+/ig)))
		{
			return;
		}


		// remove all squares with pieces that are not obelisks or pyramids
		// (they may get allowed in the next step)
		if (-1 != to_class.indexOf('pyramid')) {
			// do nothing
		}
		else if (-1 != to_class.indexOf('obelisk')) {
			// do nothing
		}
		else {
			return;
		}

		// test for djed and eye of horus
		// if it's not one of those, remove all pieces
		// we also want to allow a same colored single stack obelisk
		// if the piece we are moving is an obelisk
		if (-1 != fr_class.indexOf('djed')) {
			// do nothing
		}
		else if (-1 != fr_class.indexOf('horus')) {
			// do nothing
		}
		else if (-1 != fr_class.indexOf('obelisk')) {
			// make sure it's a single stack obelisk of the same color
			if ((-1 != to_class.indexOf('obelisk')) && (-1 == to_class.indexOf('obelisk_stack')) && (fr_color == to_color)) {
				// do nothing
			}
			else {
				return;
			}
		}
		else {
			return;
		}

		add_highlight($idx);
	});
}


function add_highlight($elem) {
	$elem
		.addClass('highlight')
		.click(set_to_square)
		.css('cursor', 'pointer');
}


function clear_highlights( ) {
	$('div.highlight')
		.removeClass('highlight')
		.unbind('click', set_to_square)
		.css('cursor', 'default');
}


function set_from_square(index) {
alert('from set to '+index);
	// TODO
}


var foo;
function set_to_square(event) {
foo = event;
	// TODO

	// then submit the form
}


// TOWER: this is going to get _complicated_
function get_adjacent(index) {
	index = parseInt(index);
	var val;
	var test;
	var adj = [];
	var diff = [
		[-11, -10,  -9],
		[ -1,       +1],
		[ +9, +10, +11]
	];

	for (var i in diff) {
		for (var j in diff[i]) {
			val = diff[i][j];

			// make sure we are not going off the edge of the board
			switch (parseInt(i)) {
				case 0 :
					test = (0 <= (index + val));
					break;

				case 1 :
					test = (((index + val) % 10) == ((index % 10) + val));
					break;

				case 2 :
					test = (80 > (index + val));
					break;
			}

			if (test) {
				adj.push(index + val);
			}
		}
	}

	return adj;
}

