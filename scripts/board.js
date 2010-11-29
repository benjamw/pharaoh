
// creates and manipulates the game board
/*
jQuery('<a/>', {
    id: 'foo',
    href: 'http://google.com',
    title: 'Become a Googler',
    rel: 'external',
    text: 'Go to Google!'
});

P - pharaoh (target)
A - pyramid (1-sided mirror, NE .\ ) - needs 4 characters to show orientation
	B - SE `/
	C - SW \`
	D - NW /.
X - djed (2-sided mirror, NE-SW \ ) - needs 2 characters to show orientation
	Y - NW-SE /
V - Obelisk (defense block) - needs 2 characters to show stacking
	W - double stacked obelisks
H - eye of horus (splitter, NE-SW \ ) - needs 2 characters to show orientation
	I - NW-SE /
T - Tower

0000wpwb0000c0000000000D000000a0C0xy0b0Db0D0YX0a0C000000b0000000000A0000DWPW0000
*/

var image_dir = 'images/pieces/',
	laser_dir = 'images/laser/',
	pieces = {
		'P' : 'pharaoh',

		'A' : 'pyramid_ne',
		'B' : 'pyramid_se',
		'C' : 'pyramid_sw',
		'D' : 'pyramid_nw',

		'X' : 'djed_ne',
		'Y' : 'djed_nw',

		'V' : 'obelisk',
		'W' : 'obelisk_stack',

		'H' : 'horus_ne',
		'I' : 'horus_nw',

//		'T' : 'tower'
	};

var timer = false;


function rotate_piece(piece, rotation, toggle_color) {
	var rotate,
		lower = (piece !== piece.toUpperCase( ));

	rotation = rotation || 'origin';
	toggle_color = !! (toggle_color || false);

	switch (rotation.toLowerCase( )) {
		case 'short' :
			rotate = {
				'A' : 'D',
				'B' : 'C',
				'C' : 'B',
				'D' : 'A',

				'X' : 'Y',
				'Y' : 'X',

				'H' : 'I',
				'I' : 'H'
			};
			break;

		case 'long' :
			rotate = {
				'A' : 'B',
				'B' : 'A',
				'C' : 'D',
				'D' : 'C',

				'X' : 'Y',
				'Y' : 'X',

				'H' : 'I',
				'I' : 'H'
			};
			break;

		case 'origin' :
		default :
			rotate = {
				'A' : 'C',
				'B' : 'D',
				'C' : 'A',
				'D' : 'B'
			};
			break;
	}

	piece = piece.toUpperCase( );
	if (undefined != rotate[piece]) {
		piece = rotate[piece];
	}

	if (lower) {
		piece = ( ! toggle_color) ? piece.toLowerCase( ) : piece.toUpperCase( );
	}
	else {
		piece = (toggle_color) ? piece.toLowerCase( ) : piece.toUpperCase( );
	}

	return piece;
}


function create_board(xFEN, blank) {
	blank = !! (blank || false);

	if ( ! xFEN) {
		return false;
	}

	var flip = ('undefined' !== typeof invert) ? !! invert : false;

	var i,j,n,idx,
		row,piece,color,img,
		letters = 'ABCDEFGHIJ',
		html = '',
		classes = [],
		silver = 'silver',
		red = 'red',
		temp,
		id = ' id="the_board"',
		idx_id;

	// chunk the xFEN into rows
	xFEN = xFEN.match(RegExp('.{1,10}','g'));

	if (flip) {
		letters = letters.split('');
		letters = letters.reverse( );
		letters = letters.join('');

		temp = silver;
		silver = red;
		red = temp;

		xFEN = xFEN.reverse( );
	}

	if (blank) {
		id = '';
	}

	html += '<div'+id+' class="a_board">'
			+'<div class="header corner">&nbsp;</div>';

	for (i = 0; i < 10; ++i) {
		html += '<div class="header horz">'+letters.charAt(i).toLowerCase( )+'</div>';
	}

	html += '<div class="header corner">&nbsp;</div>';

	for (i in xFEN) {
		i = parseInt(i);
		row = xFEN[i];

		n = 1 + i;
		if (flip) {
			n = 8 - i;

			row = row.split('');
			row = row.reverse( );
			row = row.join('');
		}

		html += '<div class="header vert">'+(9 - n)+'</div>';

		for (j = 0; j < 10; ++j) {
			classes = [];
			piece = row.charAt(j);
			if (flip) {
				piece = rotate_piece(piece);
			}
			color = (piece == piece.toUpperCase( )) ? 'silver' : 'red';

			idx = ((i * 10) + j);
			if (flip) {
				idx = 79 - idx;
			}

			idx_id = ' id="idx_'+idx+'"';
			if (blank) {
				idx_id = '';
			}

			html += '<div'+idx_id;

			if ((0 == j) || ((0 == i) && (8 == j)) || ((7 == i) && (8 == j))) {
				classes.push('c_'+red);
			}
			else if ((9 == j) || ((0 == i) && (1 == j)) || ((7 == i) && (1 == j))) {
				classes.push('c_'+silver);
			}

			img = '';
			if ('0' != piece) {
				classes.push('piece');
				classes.push('p_'+color);
				classes.push('i_'+piece);
				classes.push(pieces[piece.toUpperCase( )]);

				img = create_piece(piece);
			}

			if (classes.length) {
				html += ' class="'+classes.join(' ')+'"';
			}

			html += '>'+img+'</div>';
		} // end foreach piece loop

		html += '<div class="header vert">'+(9 - n)+'</div>';
	} // end foreach row loop

	html += '<div class="header corner">&nbsp;</div>';

	for (i = 0; i < 10; ++i) {
		html += '<div class="header horz">'+letters.charAt(i).toLowerCase( )+'</div>';
	}

	html += '<div class="header corner">&nbsp;</div>'
		+ '</div> <!-- .a_board -->';

	if ( ! blank) {
		html += ' <!-- #the_board -->';
	}

	return html;
}


function create_piece(piece, hit) {
	hit = !! (hit || false);

	var color = (piece == piece.toUpperCase( )) ? 'silver' : 'red';
	var hit = (hit) ? ' hit' : '';
	return '<img src="'+image_dir+color+'_'+pieces[piece.toUpperCase( )].toLowerCase( )+'.png" alt="'+piece+'" class="piece'+hit+'" />';
}


function fire_laser(path, i) {
	if ( ! path) {
		return false;
	}

	i = parseInt(i) || 0;

	var flip = ('undefined' !== typeof invert) ? !! invert : false;

	var j,
		dir,next_dir,add_dir,nodes,
		timeout = 150,
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
	end = !! (end || false);

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

