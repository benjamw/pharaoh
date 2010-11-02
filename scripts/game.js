
var reload = true; // do not change this
var old_board = false;

$(document).ready( function( ) {
	// show the board
	// this will show the current board if no old board is set
	show_old_board(true);

	// show move button
	$('a#show_move').click( function( ) {
		show_old_board( );
		blink_move( ); // calls show_new_board when done
		return false;
	});

	// clear move button
	$('a#clear_move').click( function( ) {
		show_new_board( );
		return false;
	});

	// fire laser button
	$('a#fire_laser').click( function( ) {
		if (false != timer) {
			return false;
		}

		show_new_board( );

		// show the hit piece
		$('img.hit').show( ).fadeTo(50, 0.75);

		fire_laser(prev_turn[1]);
		return false;
	});

	// clear laser button
	$('a#clear_laser').click( function( ) {
		clear_laser( );
		return false;
	});

	// nudge button
	$('#nudge').click( function( ) {
		if (confirm('Are you sure you wish to nudge this person?')) {
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+$('form#game').serialize( )+'&nudge=1';
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

					if (reload) { window.location.reload( ); }
					return;
				}
			});
		}
	});

	// chat box functions
	$('#chatbox form').submit( function( ) {
		if ('' == $.trim($('#chatbox input#chat').val( ))) {
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
				// if something happened, just reload
				if ('{' != msg[0]) {
					alert('ERROR: AJAX failed');
					if (reload) { window.location.reload( ); }
				}

				var reply = JSON.parse(msg);

				if (reply.error) {
					alert(reply.error);
				}
				else {
					var entry = '<dt><span>'+reply.create_date+'</span> '+reply.username+'</dt>'+
						'<dd'+(('1' == reply.private) ? ' class="private"' : '')+'>'+reply.message+'</dd>';

					$('#chats').prepend(entry);
					$('#chatbox input#chat').val('');
				}
			}
		});

		return false;
	});
});


function show_old_board(cont) {
	cont = !! (cont || false);

	if ('' == prev_board) {
		show_new_board(cont);
		return false;
	}

	$('div#board').empty( ).append(create_board(prev_board, invert));
	old_board = true;

	if (cont) {
		setTimeout('blink_move(true);', 2000);
	}

	return true;
}


function blink_move(cont) {
	cont = !! (cont || false);

	if ('undefined' == typeof prev_turn[0][0]) {
		return false;
	}

	if ( ! old_board) {
		show_old_board( );
	}

	// flash the moved piece
	$('div#idx_'+prev_turn[0][0]+' img.piece')
		.delay(250).fadeIn(50).delay(250).fadeOut(50)
		.delay(250).fadeIn(50).delay(250).fadeOut(50)
		.delay(250).fadeIn(50).delay(250).fadeOut(50)
		.delay(250).fadeIn(50).delay(250).fadeOut(50)
		.delay(250).fadeIn(50, function( ) {
			show_new_board(cont);
		});

	return true;
}


function show_new_board(cont) {
	cont = !! (cont || false);

	if ( ! old_board) {
		return true;
	}

	$('div#board').empty( ).append(create_board(board, invert));
	old_board = false;

	// add the hit piece (if any)
	if ('undefined' != typeof prev_turn[2]['hits']) {
		var piece, i;
		for (i in prev_turn[2]['hits']) {
			piece = prev_turn[2]['pieces'][i];
			if (invert) {
				piece = rotate_piece(piece);
			}

			$('div#idx_'+prev_turn[2]['hits'][i]).append(create_piece(piece, true));
		}
	}

	enable_moves( );

	if (cont) {
		setTimeout('fire_laser(prev_turn[1]);', 2000);
	}
	else {
		// hide the hit piece
		$('img.hit').hide( );
	}

	return true;
}


var timer = false;
function fire_laser(path, i) {
	if ( ! path) {
		return false;
	}

	i = i || 0;

	var flip = !! (invert || false);

	var j,
		dir,next_dir,add_dir,nodes,
		timeout = 200,
		dir_flip = {'N':'S','E':'W','S':'N','W':'E'},
		length = path.length;

	if ( ! length) {
		return false;
	}

	nodes = path[i].length;
	for (j = 0; j < nodes; ++j) {
		// grab the next node and see where we went
		// if it's an actual node, and not an endpoint
		if ('boolean' != typeof path[i][j]) {
			dir = -path[i][j][1];

			// if the next node is an endpoint (hit, wall, or looped)
			if ('boolean' == typeof path[i + 1][j]) {
				// if it's a hit
				if (true === path[i + 1][j]) {
					// show the hit (only one direction)
					next_dir = 0;

					// blink then fade the hit piece
					$('img.hit')
						.delay(250).fadeIn(50).delay(250).fadeOut(50)
						.delay(250).fadeIn(50).delay(250).fadeOut(50)
						.delay(250).fadeIn(50).delay(250).fadeOut(50)
						.delay(250).fadeIn(50).delay(250).fadeOut(50)
						.delay(250).fadeIn(50).delay(250).fadeTo(50, 0.25);
				}
				else {
					// just run it into the wall
					next_dir = -dir;
				}
			}
			else { // the next node is a valid node
				next_dir = path[i + 1][j][1];

				// check if we split here, and add the other direction
				add_dir = 0;
				if (path[i + 1][j][2]) {
					add_dir = path[i + 1][path[i + 1][j][2]][1];
				}
			}

			if (flip) {
				dir = -dir;
				next_dir = -next_dir;
				add_dir = -add_dir;
			}

			// add the laser image
			$('#idx_'+path[i][j][0]).append('<img src="'+laser_dir+'new_'+c_sort(dc(dir) + dc(next_dir) + dc(add_dir))+'.png" class="laser new" />');
		}
	}

	if (++i < length) { // increment then read
		path = JSON.stringify(path);
		timer = setTimeout('fade_laser( ); fire_laser('+path+', '+i+');', timeout);
	}
	else {
		timer = setTimeout('fade_laser(true);', timeout);
	}

	return false;
}


function fade_laser(end) {
	if (undefined == typeof end) {
		end = false;
	}

	// find the greatest common multiple and use that image
	// but we only need to search divs with new images
	// (old images are already minified and faded)
	// but don't include hits
	$('img.laser.new').each( function( ) {
		var $div = $(this).parent( ),
			dirs = '';
			hits = '';

		$('img', $div).each( function( ) {
			var match,
				$img = $(this);

			// make a list of all the directions shown (excluding hits)
			if (match = $img.attr('src').match(/laser\/(?:new_)?([nwse]{2,4})\.png/i)) {
				dirs += match[1];
				$img.remove( );
			}

			// loop through all faded hits, and convert them all to faded laser
			// (they'll get filtered out and removed if this is the only dir)
			if (match = $img.attr('src').match(/laser\/([nwse])\.png/i)) {
				dirs += match[1];
				$img.remove( );
			}

			// now loop through any new hits, and convert them to faded hits, but still hits
			// but only if there isn't already a hit image here already
			// (can happen when firing laser over and over again)
			if (match = $img.attr('src').match(/laser\/new_([nwse])\.png/i)) {
				$img.removeClass('new').attr('src', $img.attr('src').replace(/new_/i, ''));
			}
		});

		dirs = c_sort(dirs);

		if (1 < dirs.length) {
			$div.append('<img src="'+laser_dir+dirs+'.png" class="laser" />');
		}
	});

	if (end) {
		clearTimeout(timer);
		timer = false;
	}
}


function clear_laser( ) {
	clearTimeout(timer);
	timer = false;
	$('img.laser').remove( );
	$('img.hit').hide( );
}


function enable_moves( ) {
	if ( ! my_turn || ('finished' == state) || ('draw' == state)) {
		return;
	}

	// make all our pieces clickable
	$('div#board div.piece.p_'+color)
		.click(set_square)
		.css('cursor', 'pointer');
}

function highlight_valid_moves(elem) {
	var $elem = $(elem);

	clear_highlights( );

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
		var to_square_color = to_class.match(/c_[^\s]+/ig);
		var fr_square_color = fr_class.replace(/c_[^\s]+/ig, '').replace(/p_([^\s]+)/ig, 'c_$1').match(/c_[^\s]+/ig);

		if ((null != to_square_color) && (to_square_color[0] != fr_square_color[0])) {
			return;
		}

		// remove all squares with pieces that are not obelisks or pyramids
		// (they may get allowed in the next step)
		if (-1 != to_class.indexOf('piece')) {
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
				// make sure it's moving onto a single stack obelisk of the same color
				if ((-1 != to_class.indexOf('obelisk')) && (-1 == to_class.indexOf('obelisk_stack')) && (fr_color[0] == to_color[0])) {
					// do nothing
				}
				else {
					return;
				}
			}
			else {
				return;
			}

			// make sure if we're swapping pieces that the swapped piece
			// doesn't end up on a color square where they shouldn't be
			var fr_sqr_color = fr_class.match(/c_[^\s]+/ig);
			var to_sqr_color = to_class.replace(/c_[^\s]+/ig, '').replace(/p_([^\s]+)/ig, 'c_$1').match(/c_[^\s]+/ig);
			if ((null != fr_sqr_color) && (to_sqr_color[0] != fr_sqr_color[0])) {
				return;
			}
		}

		// we made it through the gauntlet, set the highlight
		add_highlight($idx);
	});
}


function add_highlight($elem) {
	$elem
		.addClass('highlight');

	if ( ! $elem.is('div.piece.p_'+color)) {
		$elem
			.click(set_square)
			.css('cursor', 'pointer');
	}
}


function clear_highlights( ) {
	$('div.highlight')
		.removeClass('highlight')
		.each( function( ) {
			var $elem = $(this);
			if ( ! $elem.is('div.piece.p_'+color)) {
				$elem
					.unbind('click', set_square)
					.css('cursor', 'default');
			}
		});
}


var stage_1 = false;
var from_index = -1;
function set_square(event) {
	// don't allow the event to bubble up the DOM tree
	event.stopPropagation( );

	clear_laser( );

	var $elem = $(event.currentTarget);
	var index = $elem.attr('id').slice(4).toLowerCase( );

	if ( ! stage_1) {
		stage_1 = true;
		from_index = index;

		highlight_valid_moves($elem);

		// create our two images
		// if the piece is not an obelisk or pharaoh
		var piece_code = $elem.attr('class').match(/i_[^\s]+/ig)[0].slice(2).toLowerCase( );
		if (('v' != piece_code) && ('w' != piece_code) && ('p' != piece_code)) {
			$('div#idx_'+index)
				.append($('<img/>', {
					'id' : 'rot_r',
					'class' : 'rotate cw',
					'src' : 'images/rotate_cw.png',
					'alt' : '->',
					'click' : set_square
				}))
				.append($('<img/>', {
					'id' : 'rot_l',
					'class' : 'rotate ccw',
					'src' : 'images/rotate_ccw.png',
					'alt' : '<-',
					'click' : set_square
				}));
		}

		$('input#from').val(index);
	}
	else {
		var $fr_elem = $('div#idx_'+from_index);
		var fr_class = $fr_elem.attr('class');
		var fr_color = fr_class.match(/p_[^\s]+/ig);

		if ( ! isNaN(parseInt(index))) {
			var $to_elem = $('div#idx_'+index);
			var to_class = $to_elem.attr('class');
			var to_color = to_class.match(/p_[^\s]+/ig);
		}

		var rotating = false;
		if (('r' == index) || ('l' == index)) {
			rotating = true;
		}
		else if (index == from_index) {
			// reset
			stage_1 = false;
			from_index = -1;
			clear_highlights( );
			$('img.rotate').remove( );
			return;
		}
		else if (-1 == $elem.attr('class').indexOf('highlight')) {
			// reset
			stage_1 = false;
			from_index = -1;
			clear_highlights( );
			$('img.rotate').remove( );

			// perform the click again
			$to_elem.click( );
			return;
		}
		else {
			// if the from piece is a djed,
			// or eye of horus, or obelisk
			// moving onto another single stack obelisk...
			var moveable_piece = (fr_color == to_color);

			// set this piece as the to index
			// as long as it's okay with the player
			if (moveable_piece && confirm('Do you want to move this piece instead?\n\nOK- Move this piece | Cancel- Swap this piece')) {
				// reset
				stage_1 = false;
				from_index = -1;
				clear_highlights( );
				$('img.rotate').remove( );

				// perform the click again
				$to_elem.click( );
				return;
			}
		}

		// set the to value and send the form
		$('input#to').val(index);

		// if from piece is a stacked obelisk and the to space is empty
		// test for splitting an obelisk and confirm with player
		if ( ! rotating) {
			var stacked_obelisk = (-1 != fr_class.indexOf('obelisk_stack'));
			var empty_to = (-1 == to_class.indexOf('piece'));
			if (stacked_obelisk && empty_to && confirm('Do you want to split this obelisk stack?\n\nOK- Split obelisk stack | Cancel- Move stack as whole')) {
				$('input#to').val(index+'-split');
			}
		}

		if (debug) {
			window.location = 'ajax_helper.php'+debug_query+'&'+$('form#game').serialize( )+'&turn=1';
			return false;
		}

		// ajax off the form
		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: $('form#game').serialize( )+'&turn=1',
			success: function(msg) {
				// if something happened, just reload
				if ('{' != msg[0]) {
					alert('ERROR: AJAX failed');
					if (reload) { window.location.reload( ); }
				}

				var reply = JSON.parse(msg);

				if (reply.error) {
					alert(reply.error);
				}

				if (reload) { window.location.reload( ); }

				running = false;
				return;
			}
		});
	}
}


// converts dir index to cardinal point
function dc(dir) {
	switch (dir) {
		case -10: return 'n';
		case  -1: return 'w';
		case  10: return 's';
		case   1: return 'e';
	}

	return '';
}


// sorts the cardinal points for a laser beam
function c_sort(dirs) {
	var output = '';

	if (-1 != dirs.indexOf('n')) { output += 'n'; }
	if (-1 != dirs.indexOf('w')) { output += 'w'; }
	if (-1 != dirs.indexOf('s')) { output += 's'; }
	if (-1 != dirs.indexOf('e')) { output += 'e'; }

	return output;
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
			test = true; // innocent until proven guilty
			test = (test && (0 <= (index + val))); // top edge
			test = (test && (80 > (index + val))); // bottom edge
			test = (test && (2 > Math.abs(((index + val) % 10) - (index % 10)))); // side edges

			if (test) {
				adj.push(index + val);
			}
		}
	}

	return adj;
}

