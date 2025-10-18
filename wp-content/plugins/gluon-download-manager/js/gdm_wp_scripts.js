// Simple Download Monitor frontend scripts

function gdm_is_ie() {

    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))
    {
	return true;
    }

    return false;
}

jQuery(document).ready(function ($) {

    // Populate all nested titles and links 
    $('li.gdm_cat').each(function () {

	var $this = $(this);
	this_slug = $this.attr('id');
	this_id = $this.children('.gdm_cat_title').attr('id');

	// Run ajax
	$.post(
		gdm_ajax_script.ajaxurl,
		{
		    action: 'gdm_pop_cats',
		    cat_slug: this_slug,
		    parent_id: this_id
		},
		function (response) {

		    // Loop array returned from ajax function
		    $.each(response.final_array, function (key, value) {

			// Populate each matched post title and permalink
			$this.children('.gdm_placeholder').append('<a href="' + value['permalink'] + '"><span class="gdm_post_title" style="cursor:pointer;">' + value['title'] + '</span></a>');
		    });

		    $this.children('span').append('<span style="margin-left:5px;" class="gdm_arrow">&#8616</span>');
		}
	);
    });

    // Hide results on page load
    $('li.gdm_cat').children('.gdm_placeholder').hide();

    // Slide toggle for each list item
    $('body').on('click', '.gdm_cat_title', function (e) {

	// If there is any html.. then we have more elements
	if ($(this).next().html() != '') {

	    $(this).next().slideToggle(); // toggle div titles
	}
    });

    // Download buttons with terms or captcha has this class applied to it
    $('.gdm_download_with_condition').on('click', function (e) {
	e.preventDefault();
	$(this).closest('form').trigger('submit');
    });

    // Check if terms checkbox is enabled.
    if ($('.gdm-termscond-checkbox').length) {

	$.each($('.gdm-termscond-checkbox'), function () {
	    if (!$(this).is(':checked')) {
		var cur = $(this).children(':checkbox');
		var btn = $(cur).closest('form').find('a.gdm_download,a.gdm_download_with_condition');
		$(btn).addClass('gdm_disabled_button');
	    }
	});

	$.each($('.gdm-download-form'), function () {
	    var form = $(this);
	    form.on('submit', function () {
		if ($('.agree_termscond', form).is(':checked')) {
		    $('.gdm-termscond-checkbox', form).removeClass('gdm_general_error_msg');
		    return true;
		} else {
		    $('.gdm-termscond-checkbox', form).addClass('gdm_general_error_msg');
		}
		return false;
	    });
	});

	$.each($('.agree_termscond'), function () {
	    var element = $(this);
	    var form = element.closest('form');
	    element.on('click', function () {
		if (element.is(':checked')) {
		    $('.gdm_download_with_condition', form).removeClass('gdm_disabled_button');
		    $('.gdm-termscond-checkbox', form).removeClass('gdm_general_error_msg');
		} else {
		    $('.gdm_download_with_condition', form).addClass('gdm_disabled_button');
		    $('.gdm-termscond-checkbox', form).addClass('gdm_general_error_msg');
		}
	    });
	});
    }
});