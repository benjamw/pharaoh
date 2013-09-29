
var reload = true; // do not change this
var new_setup = false;

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

	check_fieldset_box( );
	show_link( );
	show_version( );

	// show the setup div in a fancybox
	$('a#show_setup').fancybox({
		padding : 10,
		onStart : function( ) {
			var setup = setups[$('select#setup').val( )];
			if (new_setup) {
				setup = new_setup;
			}

			$('div#setup_display')
				.empty( )
				.append(create_board(setup))
				.show( );
		},
		onClosed :function( ) {
			$('div#setup_display').hide( );
		}
	});

	// only show the setup link when a setup is selected
	$('select#setup').change( function( ) {
		show_link( );
		show_version( );
	});
	$('input#convert_to_1, input#convert_to_2, input#rand_convert_to_1, input#rand_convert_to_2').change( function( ) {
		show_version( );
	});

	// show the setup div for the invites
	$('a.setup').fancybox({
		type : 'inline',
		href : '#setup_display',
		padding : 10,
		onStart : function(arr, idx, opts) {
			var $elem = $(arr[idx]);
			var setup = setups[$(arr[idx]).attr('id').slice(2)];

			if ('#setup_display' != $elem.attr('href')) {
				setup = $elem.attr('href').slice(1);
			}

			$('div#setup_display')
				.empty( )
				.append(create_board(setup))
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
	$('input.fieldset_box').on('change', function( ) {
		check_fieldset_box( );
	});

	// this runs all the ...vites
	$('div#invites input').click( function( ) {
		var $this = $(this);
		var id = $this.attr('id').split('-');

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
					if ('ERROR' == msg.slice(0, 5)) {
						alert(msg);
						if (reload) { window.location.reload( ); }
					}
					else {
						window.location = 'game.php?id='+msg+debug_query_;
					}
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
					if ('ERROR' == msg.slice(0, 5)) {
						if (reload) { window.location.reload( ); }
					}
					else {
						// remove the resend button
						$this.remove( );
					}
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
					if ('ERROR' == msg.slice(0, 5)) {
						if (reload) { window.location.reload( ); }
					}
					else {
						// remove the parent TR
						$this.parent( ).parent( ).remove( );
					}
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

function show_version( ) {
	if (0 != $('select#setup').val( )) {
		if (setups[$('select#setup').val( )].match(/[efjk]/i)) {
			$('.random_convert').hide( );
			$('.pharaoh_1').hide( );
			$('.pharaoh_2').show( );
			$('.pharaoh_2 .conversion').show( );

			if ($('input#convert_to_1').prop('checked')) {
				$('.pharaoh_2').hide( );
				$('.p2_box').show( );
				convert_setup(1);
			}
			else {
				new_setup = false;
			}
		}
		else {
			$('.random_convert').hide( );
			$('.pharaoh_2').hide( );
			$('.pharaoh_1').show( );

			if ($('input#convert_to_2').prop('checked')) {
				$('.pharaoh_2').show( );
				$('.pharaoh_2 .conversion').hide( );
				convert_setup(2);
			}
			else {
				new_setup = false;
			}
		}
	}
	else {
		$('.pharaoh_1, .pharaoh_2').hide( );
		$('.random_convert').show( );

		if ($('input#rand_convert_to_2').prop('checked')) {
			$('.pharaoh_2').show( );
			$('.pharaoh_2 .conversion').hide( );
		}
	}
}

function convert_setup(to) {
	to = parseInt(to || 1);

	var setup = setups[$('select#setup').val( )];

	// the p1 pieces are repeated here for the TO v1 conversion
	// just make sure that the TO v2 conversion pieces are listed first
	// that way they will get converted and be correct
	var p1 = ['w','w','w','w','W','W','W','W'];
	var p2 = ['n','l','m','o','L','M','N','O'];

	switch (to) {
		case 1 :
			// swap out the anubises with obelisks
			new_setup = str_replace(p2, p1, setup);

			// remove the sphynxes
			new_setup = new_setup.replace(/[efjk]/ig, '0');

			break;

		case 2 :
			// swap out the obelisks with anubises
			new_setup = str_replace(p1, p2, setup);

			// replace anything on the laser corners with a sphynx
			new_setup = new_setup.replaceAt(0, 'j').replaceAt(79, 'E');

			break;
	}

	return new_setup;
}

// http://phpjs.org/functions/str_replace:527
function str_replace(search, replace, subject, count) {
	var i = 0,
		j = 0,
		temp = '',
		repl = '',
		sl = 0,
		fl = 0,
		f = [].concat(search),
		r = [].concat(replace),
		s = subject,
		ra = Object.prototype.toString.call(r) === '[object Array]',
		sa = Object.prototype.toString.call(s) === '[object Array]';

	s = [].concat(s);

	if (count) {
		this.window[count] = 0;
	}

	for (i = 0, sl = s.length; i < sl; i++) {
		if (s[i] === '') {
			continue;
		}

		for (j = 0, fl = f.length; j < fl; j++) {
			temp = s[i] + '';
			repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
			s[i] = (temp).split(f[j]).join(repl);

			if (count && s[i] !== temp) {
				this.window[count] += (temp.length - s[i].length) / f[j].length;
			}
		}
	}

	return sa ? s : s[0];
}

String.prototype.replaceAt = function(index, str) {
	index = parseInt(index);
	return this.slice(0, index) + str + this.slice(index + str.length);
}

