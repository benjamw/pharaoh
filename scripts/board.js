
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


function create_board(xFEN, flip, blank) {
	flip = !! (flip || false);
	blank = !! (blank || false);

	if ( ! xFEN) {
		return false;
	}

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

