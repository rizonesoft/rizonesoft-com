<?php

/*
This file handles the output on the SDM individual download page (Custom Post Type)
*/

//Handle the title for the SDM download type post
add_filter('the_title', 'filter_gdm_post_type_title', 10, 2);
function filter_gdm_post_type_title($title, $id = null) {
	if(is_null($id) || !is_numeric($id)) {
		//This is not a gdm_downloads type post. Return the title as is.
		return $title;
	}
	//Check if this is for a gdm_downloads type post.
    if (get_post_type($id) == "gdm_downloads"){
		//This is a gdm_downloads type post. Lets get the title of the download item.
		//You can use the $post object if needed (don't use get_the_title() as it will cause infinite loop).
		$title = isset($title) ? sanitize_text_field($title) : $title;//Sanitize the title before returning it.
	}
    return $title;
}

//Handle the main post content for hte SDM download type post
add_filter( 'the_content', 'filter_gdm_post_type_content' );
function filter_gdm_post_type_content( $content ) {   
	global $post;
	if ( isset( $post->post_type ) && $post->post_type == 'gdm_downloads' ) {//Handle the content for gdm_downloads type post
		//$download_id = $post->ID;
		//$args = array('id' => $download_id, 'fancy' => '1');
		//$content = gdm_create_download_shortcode($args);
		$id = $post->ID;

		//Check if the single download page is disabled.
		$gdm_item_disable_single_download_page = get_post_meta( $id, 'gdm_item_disable_single_download_page', true );
		if ( $gdm_item_disable_single_download_page ) {
			//Page is disabled. Show message and return.
			$content .= '<div class="gdm_post_single_download_page_disabled_msg">';
			$msg      = __( 'The admin of this site has disabled this download item page.', 'gluon-download-manager' );
			$content .= apply_filters( 'gdm_post_single_download_page_disabled_msg', $msg );
			$content .= '</div>';
			return $content;
		}

		//Check to see if the download link cpt is password protected
		$get_cpt_object  = get_post( $id );
		$cpt_is_password = ! empty( $get_cpt_object->post_password ) ? 'yes' : 'no';  // yes = download is password protected;
		//Get item thumbnail
		$item_download_thumbnail  = get_post_meta( $id, 'gdm_upload_thumbnail', true );
		$thumbnail_alt = get_the_title();
                $thumbnail_alt = apply_filters ( 'gdm_post_single_download_page_thumbnail_alt', $thumbnail_alt, $id );
		$isset_download_thumbnail = isset( $item_download_thumbnail ) && ! empty( $item_download_thumbnail ) ? '<img class="gdm_post_thumbnail_image" src="' . esc_url($item_download_thumbnail) . '" alt = "' . esc_html($thumbnail_alt) . '" />' : '';

		//Get item title
		$item_title = get_the_title( $id );
		$isset_item_title = isset( $item_title ) && ! empty( $item_title ) ? sanitize_text_field($item_title) : '';

		//Get item description
		$isset_item_description = gdm_get_item_description_output( $id );

		//$isset_item_description = apply_filters('the_content', $isset_item_description);
		//Get item file size
		$item_file_size       = get_post_meta( $id, 'gdm_item_file_size', true );
		$isset_item_file_size = isset( $item_file_size ) ? $item_file_size : '';

		//Get item version
		$item_version       = get_post_meta( $id, 'gdm_item_version', true );
		$isset_item_version = isset( $item_version ) ? $item_version : '';

		//Check if show published date is enabled
		$show_date_fd = get_post_meta( $id, 'gdm_item_show_date_fd', true );
		//Get published date
		$published_date = get_the_date( get_option( 'date_format' ), $id );

		// See if user color option is selected
		$main_opts = get_option( 'gdm_downloads_options' );
		$color_opt = isset( $main_opts['download_button_color'] ) ? $main_opts['download_button_color'] : null;
		$def_color = isset( $color_opt ) ? str_replace( ' ', '', strtolower( $color_opt ) ) : __( 'green', 'gluon-download-manager' );

		//Download counter
		//$dl_counter = gdm_create_counter_shortcode(array('id'=>$id));
		//*** Generate the download now button code ***
		$button_text_string = gdm_get_default_download_button_text( $post->ID );

		// See if new window parameter is set
		$new_window    = get_post_meta( $id, 'gdm_item_new_window', true );
		$window_target = empty( $new_window ) ? '_self' : '_blank';
		$window_target = apply_filters('gdm_download_window_target', $window_target);
		
		$download_url = gdm_get_standard_download_url_from_id($id);
		$download_button_code = '<a href="' . $download_url . '" class="gdm_download ' . $def_color . '" title="' . esc_html($isset_item_title) . '" target="' . $window_target . '">' . esc_attr($button_text_string) . '</a>';

		$main_advanced_opts = get_option( 'gdm_advanced_options' );

		//Check if Terms & Condition enabled
		$termscond_enable = isset( $main_advanced_opts['termscond_enable'] ) ? true : false;
		if ( $termscond_enable ) {
			$download_button_code = gdm_get_download_form_with_termsncond( $id, array(), 'gdm_download ' . $def_color );
		}

		//Check if reCAPTCHA enabled
		if ( gdm_is_any_recaptcha_enabled() && $cpt_is_password == 'no' ) {
			$download_button_code = gdm_get_download_form_with_recaptcha( $id, array(), 'gdm_download ' . $def_color );
		}

		if ( $cpt_is_password !== 'no' ) {//This is a password protected download so replace the download now button with password requirement
			$download_button_code = gdm_get_password_entry_form( $id, array(), 'gdm_download ' . $def_color );
		}

		// Check if we only allow the download for logged-in users
		//        if (isset($main_opts['only_logged_in_can_download'])) {
		//            if ($main_opts['only_logged_in_can_download'] && gdm_get_logged_in_user()===false) {
		//                // User not logged in, let's display the message
		//                $download_button_code = __('You need to be logged in to download this file.','gluon-download-manager');
		//            }
		//        }

		$db_count              = gdm_get_download_count_for_post( $id );
		$string                = ( $db_count == '1' ) ? __( 'Download', 'gluon-download-manager' ) : __( 'Downloads', 'gluon-download-manager' );
		$download_count_string = '<span class="gdm_post_count_number">' . $db_count . '</span><span class="gdm_post_count_string"> ' . $string . '</span>';

		//Output the download item details
		$content  = '<div class="gdm_post_item">';
		$content .= '<div class="gdm_post_item_top">';

		$content .= '<div class="gdm_post_item_top_left">';
		$content .= '<div class="gdm_post_thumbnail">' . $isset_download_thumbnail . '</div>';
		$content .= '</div>'; //end .gdm_post_item_top_left

		$content .= '<div class="gdm_post_item_top_right">';
		$content .= '<div class="gdm_post_title">' . esc_html($isset_item_title) . '</div>';

		if ( ! isset( $main_opts['general_hide_donwload_count'] ) ) {//The hide download count is enabled.
			$content .= '<div class="gdm_post_download_count">' . $download_count_string . '</div>';
		}

		$content .= '<div class="gdm_post_description">' . $isset_item_description . '</div>';

		//This hook can be used to add content below the description
		$params   = array( 'id' => $id );
		$content .= apply_filters( 'gdm_cpt_below_download_description', '', $params );

		//Check if the button of the single download page is disabled.
		$gdm_item_hide_dl_button_single_download_page = get_post_meta( $id, 'gdm_item_hide_dl_button_single_download_page', true );
		if ( $gdm_item_hide_dl_button_single_download_page ) {
			//the download button is disabled.
			$content .= '<div class="gdm_post_single_download_page_disabled_dl_button_msg">';
			$msg      = '<p>' . __( 'The admin of this site has disabled the download button for this page.', 'gluon-download-manager' ) . '</p>';
			$content .= apply_filters( 'gdm_post_single_download_page_disabled_dl_button_msg', $msg );
			$content .= '</div>';
		} else {

			//Filter hook to allow other plugins to add their own HTML code before the download button
			$extra_html_before_button = apply_filters( 'gdm_before_download_button', '', $id, $params );
			$content .= $extra_html_before_button;

			$download_link = '<div class="gdm_download_link">' . $download_button_code . '</div>';
			$content      .= '<div class="gdm_post_download_section">' . apply_filters(
				'gdm_single_page_dl_link',
				$download_link,
				array(
					'id'          => $id,
					'button_text' => $button_text_string,
				)
			) . '</div>';
		}

		if ( ! empty( $isset_item_file_size ) ) {//Show file size info
			$content .= '<div class="gdm_post_download_file_size">';
			$content .= '<span class="gdm_post_download_size_label">' . __( 'Size: ', 'gluon-download-manager' ) . '</span>';
			$content .= '<span class="gdm_post_download_size_value">' . $isset_item_file_size . '</span>';
			$content .= '</div>';
		}

		if ( ! empty( $isset_item_version ) ) {//Show version info
			$content .= '<div class="gdm_post_download_version">';
			$content .= '<span class="gdm_post_download_version_label">' . __( 'Version: ', 'gluon-download-manager' ) . '</span>';
			$content .= '<span class="gdm_post_download_version_value">' . $isset_item_version . '</span>';
			$content .= '</div>';
		}

		if ( $show_date_fd ) {//Show Published date
			$content .= '<div class="gdm_post_download_published_date">';
			$content .= '<span class="gdm_post_download_published_date_label">' . __( 'Published: ', 'gluon-download-manager' ) . '</span>';
			$content .= '<span class="gdm_post_download_published_date_value">' . $published_date . '</span>';
			$content .= '</div>';
		}

		//$content .= '<div class="gdm_post_meta_section"></div>';//TODO - Show post meta (category and tags)
		$content .= '</div>'; //end .gdm_post_item_top_right

		$content .= '</div>'; //end of .gdm_download_item_top

		$content .= '<div style="clear:both;"></div>';

		$content .= '</div>'; //end .gdm_post_item

		return $content;
	}

	return $content;
}

//The following filters are applied to the output of the SDM description field.
add_filter( 'gdm_downloads_description', 'do_shortcode' );
add_filter( 'gdm_downloads_description', 'wptexturize' );
add_filter( 'gdm_downloads_description', 'convert_smilies' );
add_filter( 'gdm_downloads_description', 'convert_chars' );
add_filter( 'gdm_downloads_description', 'wpautop' );
add_filter( 'gdm_downloads_description', 'shortcode_unautop' );
add_filter( 'gdm_downloads_description', 'prepend_attachment' );

function gdm_get_item_description_output( $id ) {
	$item_description       = get_post_meta( $id, 'gdm_description', true );
	$isset_item_description = isset( $item_description ) && ! empty( $item_description ) ? $item_description : '';

	//Sanitize the description before applying the filters (this makes sure that shortcode rendering works).
	$allowed = gdm_sanitize_allowed_tags_expanded();
	$isset_item_description = wp_kses($isset_item_description, $allowed);

	//Lets apply all the filters like do_shortcode, wptexturize, convert_smilies, wpautop etc.
	//$isset_item_description = apply_filters('the_content', $isset_item_description);
	$filtered_item_description = apply_filters( 'gdm_downloads_description', $isset_item_description );

	return $filtered_item_description;
}

//Add adsense or ad code below the description (if applicable)
add_filter( 'gdm_cpt_below_download_description', 'gdm_add_ad_code_below_description', 10, 2 );
add_filter( 'gdm_fancy1_below_download_description', 'gdm_add_ad_code_below_description', 10, 2 );

function gdm_add_ad_code_below_description( $output, $args ) {
	$main_advanced_opts = get_option( 'gdm_advanced_options' );
	$adsense_below_desc = isset( $main_advanced_opts['adsense_below_description'] ) ? $main_advanced_opts['adsense_below_description'] : '';
	if ( ! empty( $adsense_below_desc ) ) {
		//Ad code is configured in settings. Lets add it to the output.
		$output .= '<div class="gdm_below_description_ad_code">' . $adsense_below_desc . '</div>';
	}
	return $output;
}
// Omit closing PHP tag to prevent "headers already sent" errors
