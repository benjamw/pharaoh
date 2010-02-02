
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

function rotate_piece(piece, rotation) {
	var rotate,
		lower = (piece != piece.toUpperCase( ));

	if (undefined == rotation) {
		rotation = 'origin';
	}

	switch (rotation.toLowerCase( )) {
		case 'short' :
			break;

		case 'long' :
			break;

		case 'origin' :
		default :
			rotate = {
				'A' : 'C',
				'B' : 'D',
				'C' : 'A',
				'D' : 'B',
			};
			break;
	}

	piece = piece.toUpperCase( );
	if (undefined != rotate[piece]) {
		piece = rotate[piece];
	}

	if (lower) {
		piece = piece.toLowerCase( );
	}

	return piece;
}

function create_board(xFEN, flip) {
	flip = !! flip;

	var i,j,n,
		row,piece,color,
		letters = 'ABCDEFGHIJ',
		html = '',
		classes = [],
		silver = 'silver',
		red = 'red',
		image_dir = '../images/pieces/',
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

			'T' : 'tower'
		},
		temp;

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

	html += '<div id="the_board">'
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

			html += '<div id="idx_'+((i * 10) + j)+'"';

			if ((0 == j) || ((0 == i) && (8 == j)) || ((7 == i) && (8 == j))) {
				classes.push(red);
			}
			else if ((9 == j) || ((0 == i) && (1 == j)) || ((7 == i) && (1 == j))) {
				classes.push(silver);
			}

			if ('0' != piece) {
				classes.push('piece');
				classes.push(color+'_'+pieces[piece.toUpperCase( )].toLowerCase( ));
			}

			if (classes.length) {
				html += ' class="'+classes.join(' ')+'"';
			}

			html += '></div>';
		} // end foreach piece loop

		html += '<div class="header vert">'+(9 - n)+'</div>';
	} // end foreach row loop

	html += '<div class="header corner">&nbsp;</div>';

	for (i = 0; i < 10; ++i) {
		html += '<div class="header horz">'+letters.charAt(i).toLowerCase( )+'</div>';
	}

	html += '<div class="header corner">&nbsp;</div>'
		+ '</div> <!-- #the_board -->';

	return html;
}


function fire_laser(path) {
	var i,j,
		dir,next_dir,nodes,
		length = path.length;

	if ( ! length) {
		return false;
	}

	for (i = 0; i < length; ++i) {
		nodes = path[i].length;
		for (j = 0; j < nodes; ++j) {
			// grab the next node and see where we went
			if (path[i][j]) {
				dir = path[i][j][1];
				if (path[i + 1]) {
					next_dir = path[i + 1][j][1];
				}
				else {
					next_dir = dir;
				}
				alert(dir+'-'+dc(dir));
				alert(next_dir+'-'+dc(next_dir));
			}
		}
	}

	return false;
}

// converts dir index to cardinal point
function dc(dir) {
	switch (dir) {
		case -10: return 'n';
		case  10: return 's';
		case  -1: return 'w';
		case   1: return 'e';
	}

	return false;
}
