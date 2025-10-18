jQuery(function($) {
	$(document).ready(function() {
		// Cache DOM elements for better performance
		var $colorFields = $('.bp-color-field');
		var $themeColorsToggle = $('#use-theme-colors');
		var $colorWrappers = $('#accent-color-wrap, #buttons-text-color-wrap');
		var $saveButton = $('#save-settings');
		var $form = $('#bpmt-default-theme');
		
		// Initialize color pickers efficiently
		function initColorPickers() {
			$colorFields.each(function() {
				// Only initialize if not already initialized
				if (!$(this).hasClass('wp-color-picker')) {
					$(this).wpColorPicker();
				}
			});
		}
		
		// Initialize color pickers on document ready
		initColorPickers();
		
		// Toggle color fields visibility with clean UI
		$themeColorsToggle.on('change', function() {
			if ($(this).is(':checked')) {
				$colorWrappers.slideUp(200);
			} else {
				$colorWrappers.slideDown(200);
				// Make sure color pickers are initialized when shown
				initColorPickers();
			}
		});
		
		// Optimize form submission
		$form.on('submit', function(e) {
			e.preventDefault();
			
			// Show loading state
			$saveButton.addClass('saving').prop('disabled', true);
			
			// Handle unchecked checkboxes
			if (!$themeColorsToggle.is(':checked')) {
				// Add a hidden input to explicitly set use_theme_colors to 0
				$form.append('<input type="hidden" name="use_theme_colors" value="0">');
			}
			
			// Use optimized AJAX
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'basepress_default_theme_save',
					settings: $form.serialize()
				},
				success: function(response) {
					// Remove any existing notices
					$('.basepress-settings-notice').fadeOut(200, function() {
						$(this).remove();
					});
					
					// Get response message
					var message = '';
					var noticeType = 'success';
					
					// Handle JSON response from wp_send_json_success/error
					if (typeof response === 'object') {
						if (response.success) {
							message = response.data;
						} else {
							message = response.data;
							noticeType = 'error';
						}
					} else {
						// Fallback for plain text response
						message = response;
					}
					
					// Show message with WordPress styling
					$('<div class="notice notice-' + noticeType + ' is-dismissible basepress-settings-notice"><p>' + 
					  message + '</p></div>').hide().insertAfter('.wrap h1').fadeIn(200);
					
					// Make notices dismissible
					$(document).trigger('wp-updates-notice-added');
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error(errorThrown);
					$('<div class="notice notice-error is-dismissible basepress-settings-notice"><p>' + 
					  'Error saving settings. Please try again.' + '</p></div>').insertAfter('.wrap h1');
					$(document).trigger('wp-updates-notice-added');
				},
				complete: function() {
					// Restore button state
					$saveButton.removeClass('saving').prop('disabled', false);
					
					// Remove any temporary hidden inputs
					$form.find('input[name="use_theme_colors"][value="0"]').remove();
				}
			});
		});
	});
});