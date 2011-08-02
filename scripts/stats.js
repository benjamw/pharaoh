
$(document).ready( function( ) {
	// hide the setup div
	$('div#setup_display').hide( );

	// change the colors for the win ratios
	$('td.color, .color td').each( function(i, elem) {
		var $elem = $(elem);
		var text = parseFloat($elem.text( ));

		if (0 < text) {
			$elem.css('color', 'green');
		}
		else if (0 > text) {
			$elem.css('color', 'red');
		}
	});

	// show the setup in a fancybox
	$('tr.setup td:not(.action)').fancybox({
		type : 'inline',
		href : '#setup_display',
		padding : 10,
		onStart : function(elem) {
			$('div#setup_display')
				.empty( )
				.append(create_board(setups[$(elem).parent( ).attr('id').slice(2)]))
				.show( );
		},
		onClosed :function( ) {
			$('div#setup_display').hide( );
		}
	}).css('cursor', 'pointer');
});

