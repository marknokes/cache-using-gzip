jQuery(document).ready(function($) {

	var isProcessing = false,
		$processing = $("p#processing"),
		$complete = $("p#complete"),
		$clean = $("p#clean");

	if(cugz_ajax_var.is_settings_page) {

		$('#datepicker').datepicker({
	        dateFormat: 'yy-mm-dd',
	        onSelect: function(dateText) {
	            $(this).attr('value', dateText);
	        }
	    });

		updateStatus();

		setInterval(function() {
			updateStatus();
		}, 5000);
	}

	/*
	*	Show a Wordpress UI admin notice
	*/
	function showMsg(msg = "", $type = "notice-success") {
		
		$("#wpcontent").prepend('<div style="z-index: 9999; position: fixed; width: 82%" class="notice ' + $type + ' is-dismissible single-cached"><p>' + msg + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
		
		setTimeout(function(){
			$(".notice-dismiss").parent().fadeOut( "slow", function() {
				$(this).remove();
			});
		}, 5000);
		
		$(".notice-dismiss").click(function(e) {
			$(this).parent().remove();
		});
	}

	/*
	*	Update the cache status on the cache settings page.
	*/
	function updateStatus() {
		$.ajax({
			'type': "POST",
			'url': cugz_ajax_var.ajax_url,
			'data': {
				'action': 'cugz_callback',
				'nonce': cugz_ajax_var.nonce,
				'do': 'check_status'
			},
			success: function(r) {
				$('.cache-status').hide();
				if("preloaded" === r && !isProcessing) {
					$complete.show();
				} else if("empty" === r && !isProcessing) {
					$clean.show();
				} else {
					$processing.show();
				}
			}
		});
	}

	/*
	*	Click handler for settings page. Used to preload or empty cache.
	*	Actions are defined by the button element's id
	*/
	$("#empty,#regen").click(function(e) {

		e.preventDefault();
		
		var action = $(this).attr("id");
		
		$('.cache-status').hide();

		$processing.show();
		
		if(false === isProcessing) {

			isProcessing = true;

			if("regen" === action) {

				showMsg("Cache is preloading. You may exit this page.");

			}

			$.ajax({
				'type': "POST",
				'url': cugz_ajax_var.ajax_url,
				'data': {
					'action': 'cugz_callback',
					'nonce': cugz_ajax_var.nonce,
					'do': action
				},
				success: function(r) {

					isProcessing = false;

					updateStatus();

				},
				error: function() {

					location.href = cugz_ajax_var.admin_url + cugz_ajax_var.options_page_url;

				}
			});

		} else {

			showMsg("Please wait for the current process to finish.", "notice-warning");

		}
	});

	/*
	*	Click handler for cache link on posts, pages, and custom post types.
	*	Used to cache a single item.
	*/
	$(".cache-using-gzip-single-page").click(function(e) {

		e.preventDefault();
		
		$spinner = $('<span class="spinner is-active"></span>');
		
		$(this).after($spinner);
		
		$.ajax({
			'type': "POST",
			'url': cugz_ajax_var.ajax_url,
			'data': {
				'action': 'cugz_callback',
				'nonce': cugz_ajax_var.nonce,
				'do': 'single',
				'post_id': $(this).data("post-id")
			},
			success: function(r) {

				showMsg("Cached!");
				
				$spinner.fadeOut( "slow", function() {
					$(this).remove();
				});
			
			}
		});
	});
});
