
var reload = true;
var selected = false;
var board = false;

$(document).ready( function($) {
	// show the pieces
	var piece;
	var $pieces_div = $('#pieces_display');
	for (piece in pieces) {
		$pieces_div.append(create_piece(piece));
		$pieces_div.append(create_piece(piece.toLowerCase( )));
	}

	// make the pieces clickable
	$pieces_div.find('img').click( function( ) {
		$('.selected').removeClass('selected');
		selected = $(this).addClass('selected').attr('alt');

		if ('Cancel' == selected) {
			selected = false;
			$('.selected').removeClass('selected');
		}
	});

	// create the board upon selection
	$('#init_setup').change( function( ) {
		var $this = $(this);
		var val = $this.val( );

		if ('' === val) {
			return;
		}

		if ((false === board) || confirm('This will delete all changes.\nAre you sure?')) {
			board = setups[val];
			$('#setup_display').empty( ).append(create_board(board));
			$('#name').val($this.find('option:selected').text( ) + ' Edit');
		}

		$this.val('');
	});

	// make the board clicks work
	$('#the_board div').live('click', function( ) {
		var $this = $(this);
		var id = $this.attr('id').slice(4);
		var reflection = $('#reflection').val( );

		if ( ! selected) {
			return;
		}
		if ('Delete' == selected) {
			$this.empty( );
			board = board.replaceAt(id, '0')

			if (reflection) {
				id = get_reflected_id(id, reflection);
				$('#idx_'+id).empty( );
				board = board.replaceAt(id, '0')
			}

			return;
		}

		if ('p' == selected.toLowerCase( )) {
			if (-1 !== board.indexOf(selected)) {
				alert('Only one pharaoh of each color is allowed.\nPlease delete the current pharaoh to place a new one.');
				return;
			}
		}

		$this.empty( ).append(create_piece(selected));
		board = board.replaceAt(id, selected);

		// place the reflected piece
		if (reflection) {
			id = get_reflected_id(id, reflection);

			new_selected = rotate_piece(selected, reflection, true);

			$('#idx_'+id).empty( ).append(create_piece(new_selected));
			board = board.replaceAt(id, new_selected);
		}
	});

	// initialize a blank board
	board = setups[0];
	$('#setup_display').empty( ).append(create_board(board));

	// upon form submission, ajax validate the board first
	// so we don't lose all the board data
	// people might get a little upset about that
	$('#create').click( function(event) {
		// store the current board in the form
		$('#setup').val(board);

		// prevent default completely
		// we will fake a submit if the ajax test passes
		event.preventDefault( );

		if (debug) {
			window.location = 'ajax_helper.php'+debug_query+'&'+$('#send').serialize( )+'&test_setup=1';
			return false;
		}

		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: $('#send').serialize( )+'&test_setup=1',
			success: function(msg) {
				var reply = JSON.parse(msg);
				response = reply;

				if (reply.error) {
					alert(reply.error);
					return false;
				}
				else {
					// run our actual form submit here
					// because everything looks good
					$('#send').submit( );
				}
			}
		});
	});
});


function get_reflected_id(id, reflection) {
	id = parseInt(id);

	switch (reflection) {
		case 'Origin' :
			id = 79 - id;
			break;

		case 'Long' :
			var tens = Math.floor(id / 10);
			id = ((7 - tens) * 10) + (id % 10);
			break;

		case 'Short' :
			var tens = Math.floor(id / 10);
			id = (tens * 10) + (9 - (id % 10));
			break;

		default :
			// do nothing
			break;
	}

	return id;
}


String.prototype.replaceAt = function(index, char) {
	index = parseInt(index);
	return this.slice(0, index) + char + this.slice(index + char.length);
}


String.prototype.repeat = function(num) {
	num = parseInt(num);
    return new Array(num + 1).join(this);
}

