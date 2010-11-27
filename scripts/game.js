
var reload = true; // do not change this
var old_board = false;
var timer = false;

$(document).ready( function( ) {
	// show the board
	// this will show the current board if no old board is available
	show_old_board(true);

	// set our move history active class and disabled review buttons
	update_history( );

	// show full move button
	$('a#show_full').click( function( ) {
		show_old_board(true);
		return false;
	});

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

		fire_laser(game_history[move_index][2]);
		return false;
	});

	// clear laser button
	$('a#clear_laser').click( function( ) {
		clear_laser( );
		return false;
	});

	// move history clicks
	$('table.history td[id^=mv_]').click( function( ) {
		clear_laser( );

		move_index = parseInt($(this).attr('id').slice(3));

		update_history( );

		show_old_board(true);
	}).css('cursor', 'pointer');


	// review button clicks
	$('.review span:not(.disabled)').live('click', review);


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


	// resign button
	$('#resign').click( function( ) {
		if (confirm('Are you sure you wish to resign?')) {
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+$('form#game').serialize( )+'&resign=1';
				return false;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: $('#game_form').serialize( )+'&resign=1',
				success: function(msg) {
					var reply = JSON.parse(msg);

					if (reply.error) {
						alert(reply.error);
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


	// tha fancybox stuff
	$('a.fancybox').fancybox({
		autoDimensions : true,
		onStart : function(link) {
			$($(link).attr('href')).show( );
		},
		onCleanup : function( ) {
			$(this.href).hide( );
		}
	});
});


function show_old_board(cont) {
	move_index = parseInt(move_index) || (move_count - 1);
	cont = ( !! cont) || false;

	clearTimeout(timer);
	timer = false;

	old_board = true;

	if (('undefined' == typeof game_history[move_index - 1]) || ('' == typeof game_history[move_index - 1][0])) {
		show_new_board(cont);
		return false;
	}

	$('div#board').empty( ).append(create_board(game_history[move_index - 1][0], invert));

	if (cont) {
		blink_move(true);
	}

	return true;
}


function blink_move(cont) {
	move_index = parseInt(move_index) || (move_count - 1);
	cont = ( !! cont) || false;

	if ('undefined' == typeof game_history[move_index][1][0]) {
		return false;
	}

	if ( ! old_board) {
		show_old_board( );
	}

	// flash the moved piece
	$('div#idx_'+game_history[move_index][1][0]+' img.piece')
		.delay(125).fadeIn(50).delay(125).fadeOut(50)
		.delay(125).fadeIn(50).delay(125).fadeOut(50)
		.delay(125).fadeIn(50).delay(125).fadeOut(50)
		.delay(125).fadeIn(50).delay(125).fadeOut(50, function( ) {
			show_new_board(cont);
		});

	return true;
}


function show_new_board(cont) {
	move_index = parseInt(move_index) || (move_count - 1);
	cont = ( !! cont) || false;

	clearTimeout(timer);
	timer = false;

	if ( ! old_board) {
		return true;
	}

	$('div#board').empty( ).append(create_board(game_history[move_index][0], invert));
	old_board = false;

	// add the hit piece (if any)
	if ('undefined' != typeof game_history[move_index][3]['hits']) {
		var piece, i;
		for (i in game_history[move_index][3]['hits']) {
			piece = game_history[move_index][3]['pieces'][i];
			if (invert) {
				piece = rotate_piece(piece);
			}

			$('div#idx_'+game_history[move_index][3]['hits'][i]).append(create_piece(piece, true));
		}
	}

	if ((move_count - 1) == move_index) {
		enable_moves( );
	}

	if (cont) {
		timer = setTimeout('fire_laser(game_history[move_index][2]);', 1000);
	}
	else {
		// hide the hit piece
		$('img.hit').hide( );
	}

	return true;
}


function fire_laser(path, i) {
	if ( ! path) {
		return false;
	}

	i = parseInt(i) || 0;

	var flip = ( !! invert) || false;

	var j,
		dir,next_dir,add_dir,nodes,
		timeout = 150,
		dir_flip = {'N':'S','E':'W','S':'N','W':'E'},
		length = path.length;

	if ( ! length) {
		return false;
	}

	nodes = path[i].length;
	for (j = 0; j < nodes; ++j) {
		// grab the next node and see where we went
		// if it's an actual node, and not an endpoint
		if ('boolean' != typeof path[i][j][0]) {
			dir = -path[i][j][1];

			// check if we split here, and add the other direction
			add_dir = 0;
			if (path[i + 1][j][2]) {
				add_dir = path[i + 1][path[i + 1][j][2]][1];
			}

			// if the next node is an endpoint (hit, wall, or looped)
			if ('boolean' == typeof path[i + 1][j][0]) {
				// if it's a hit
				if (true === path[i + 1][j][0]) {
					// show the hit (only one direction)
					next_dir = 0;
				}
				else {
					// just run it into the wall
					next_dir = path[i + 1][j][1];
				}
			}
			else { // the next node is a valid node
				next_dir = path[i + 1][j][1];
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
	end = ( !! end) || false;

	// find the greatest common multiple and use that image
	// but we only need to search divs with new images
	// (old images are already minified and faded)
	// but don't include hits
	$('img.laser.new').each( function( ) {
		var $div = $(this).parent( ),
			dirs = '',
			hits = '';

		$('img.laser', $div).each( function( ) {
			var match,
				$img = $(this);

			// make a list of all the directions shown (excluding hits)
			if (match = $img.attr('src').match(/\/(?:new_)?([nwse]{2,4})\.png/i)) {
				dirs += match[1];
				$img.remove( );
			}

			// loop through all faded hits, and convert them all to faded laser
			// (they'll get filtered out and removed if this is the only dir)
			if (match = $img.attr('src').match(/\/([nwse])\.png/i)) {
				hits += match[1];
				$img.remove( );
			}

			// now loop through any new hits, and convert them to faded hits, but still hits
			// but only if there isn't already a hit image here already
			// (can happen when firing laser over and over again)
			if (match = $img.attr('src').match(/\/new_([nwse])\.png/i)) {
				$img.removeClass('new').attr('src', $img.attr('src').replace(/new_/i, ''));
			}
		});

		dirs = c_sort(dirs);

		if (1 < dirs.length) {
			$div.append('<img src="'+laser_dir+dirs+'.png" class="laser" />');
		}

		for (var i = 0; i < hits.length; ++i) {
			$div.append('<img src="'+laser_dir+hits.charAt(i)+'.png" class="laser" />');
		}
	});

	if (end) {
		clearTimeout(timer);
		timer = false;

		// blink then fade all the hit pieces
		$('img.hit')
			.delay(125).fadeIn(50).delay(125).fadeOut(50)
			.delay(125).fadeIn(50).delay(125).fadeOut(50)
			.delay(125).fadeIn(50).delay(125).fadeOut(50)
			.delay(125).fadeIn(50).delay(125).fadeTo(50, 0.25);
	}
}


function clear_laser( ) {
	if (old_board) {
		show_new_board( );
	}

	clearTimeout(timer);
	timer = false;
	$('img.laser').remove( );
	$('img.hit').hide( );
}


function do_full_move(idx) {
	// stop any previous moves and/or animations
	show_old_board( );
	show_new_board( );
	clear_laser( );

	if (idx > (move_count - 1)) {
		return false;
	}

	// set the global move index
	move_index = parseInt(move_index) || (move_count - 1);

	// and do the move
	show_old_board(true);
}


function review( ) {
	var type = $(this).attr('id');

	switch (type) {
		case 'first' : move_index = 1; break;
		case 'prev5' : move_index -= 5; break;
		case 'prev' : move_index -= 1; break;
		case 'next' : move_index += 1; break;
		case 'next5' : move_index += 5; break;
		case 'last' : move_index = (move_count - 1); break;
	}

	if (move_index < 1) {
		move_index = 1;
	}
	else if (move_index > (move_count - 1)) {
		move_index = (move_count - 1);
	}

	update_history( );

	do_full_move(move_index);
}


function update_history( ) {
	// update our active move history item
	$('table.history td.active').removeClass('active');
	$('td#mv_'+move_index).addClass('active');

	// update our disabled review buttons as needed
	$('.review .disabled').removeClass('disabled');

	if (1 >= move_index) {
		$('#prev, #prev5, #first').addClass('disabled');
	}

	if (move_index >= (move_count - 1)) {
		$('#next, #next5, #last').addClass('disabled');
	}
}


function enable_moves( ) {
	move_index = parseInt(move_index) || (move_count - 1);

	if ( ! my_turn || ('finished' == state) || ('draw' == state) || ((move_count - 1) != move_index)) {
		return;
	}

	if (old_board) {
		show_new_board( );
		return false;
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
	var board_index = $elem.attr('id').slice(4).toLowerCase( );

	if ( ! stage_1) {
		stage_1 = true;
		from_index = board_index;

		highlight_valid_moves($elem);

		// create our two images
		// if the piece is not an obelisk or pharaoh
		var piece_code = $elem.attr('class').match(/i_[^\s]+/ig)[0].slice(2).toLowerCase( );
		if (('v' != piece_code) && ('w' != piece_code) && ('p' != piece_code)) {
			$('div#idx_'+board_index)
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

		$('input#from').val(board_index);
	}
	else {
		// grab some info about the piece
		var $fr_elem = $('div#idx_'+from_index);
		var fr_class = $fr_elem.attr('class');
		var fr_color = fr_class.match(/p_[^\s]+/ig);

		// if we are not rotating the piece, we need
		// to grab some more info about where the piece
		// is going
		if ( ! isNaN(parseInt(board_index))) {
			var $to_elem = $('div#idx_'+board_index);
			var to_class = $to_elem.attr('class');
			var to_color = to_class.match(/p_[^\s]+/ig);
		}

		var rotating = false;
		if (('r' == board_index) || ('l' == board_index)) {
			rotating = true;
		}
		else if (board_index == from_index) {
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
			// if the FROM piece is a djed,
			// or eye of horus, or obelisk
			// moving onto another single stack obelisk...
			var moveable_piece = (fr_color && to_color && (fr_color[0] == to_color[0]));

			// set this piece as the TO index
			// as long as it's okay with the player
			if (moveable_piece && confirm('Do you want to move this piece instead?\n\nOK- Move this new piece | Cancel- Swap these two pieces')) {
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

		// set the TO value and send the form
		$('input#to').val(board_index);

		// if FROM piece is a stacked obelisk and the TO space is empty
		// test for splitting an obelisk and confirm with player
		if ( ! rotating) {
			var stacked_obelisk = (-1 != fr_class.indexOf('obelisk_stack'));
			var empty_to = (-1 == to_class.indexOf('piece'));
			if (stacked_obelisk && empty_to && confirm('Do you want to split this obelisk stack?\n\nOK- Split obelisk stack | Cancel- Move stack as whole')) {
				$('input#to').val(board_index+'-split');
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


// converts dir value to cardinal point
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
function get_adjacent(board_index) {
	board_index = parseInt(board_index);
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
			test = (test && (0 <= (board_index + val))); // top edge
			test = (test && (80 > (board_index + val))); // bottom edge
			test = (test && (2 > Math.abs(((board_index + val) % 10) - (board_index % 10)))); // side edges

			if (test) {
				adj.push(board_index + val);
			}
		}
	}

	return adj;
}

