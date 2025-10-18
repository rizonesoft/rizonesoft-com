<?php

function gdm_generate_card_template_popular_downloads_display_output( $get_posts, $args ) {
	wp_enqueue_style( 'gdm_generate_card_template_popular_downloads_display_styles', WP_GLUON_DL_MANAGER_URL . '/includes/templates/template-card/gdm-template-card-styles.css' , array(), WP_GLUON_DL_MANAGER_VERSION );
    $output	 = "";

    $count = 1;
    foreach ( $get_posts as $item ) {
	$opts		 = $args;
	$opts[ 'id' ]	 = $item->ID;
	$output		 .= gdm_generate_card_template_display_output( $opts );

	if ( $count % 3 == 0 ) {//Clear after every 3 items in the grid
	    $output .= '<div class="gdm_clear_float"></div>';
	}
	$count ++;
    }
    $output .= '<div class="gdm_clear_float"></div>';
    return $output;
}

function gdm_generate_card_template_latest_downloads_display_output( $get_posts, $args ) {
	wp_enqueue_style( 'gdm_generate_card_template_latest_downloads_display_styles', WP_GLUON_DL_MANAGER_URL . '/includes/templates/template-card/gdm-template-card-styles.css' , array(), WP_GLUON_DL_MANAGER_VERSION );
    $output	 = "";

    $count = 1;
    foreach ( $get_posts as $item ) {
	$output .= gdm_generate_card_template_display_output(
	array_merge( $args, array( 'id' => $item->ID ) )
	);

	if ( $count % 3 == 0 ) {//Clear after every 3 items in the grid
	    $output .= '<div class="gdm_clear_float"></div>';
	}
	$count ++;
    }
    $output .= '<div class="gdm_clear_float"></div>';
    return $output;
}

function gdm_generate_card_template_category_display_output( $get_posts, $args ) {
	wp_enqueue_style( 'gdm_generate_card_template_category_display_styles', WP_GLUON_DL_MANAGER_URL . '/includes/templates/template-card/gdm-template-card-styles.css' , array(), WP_GLUON_DL_MANAGER_VERSION );
    $output	 = "";

    $count = 1;
    foreach ( $get_posts as $item ) {
        
        // Create a new array to prevent affecting the next item by the modified value of the current item in the loop. 
        $args_fresh = array_merge([], $args); 
        
        /**
         * Get the download button text.
         * Prioritize category shortcode param over custom button text from edit page.
         */
        if (empty($args_fresh['button_text'])) {
            $args_fresh['button_text'] = gdm_get_dl_button_text($item->ID);
        }
        
        $tpl_data = array_merge( $args_fresh, array( 'id' => $item->ID ) );
        $output .= gdm_load_template('card', $tpl_data, false);

        if ( $count % 3 == 0 ) {//Clear after every 3 items in the grid
            $output .= '<div class="gdm_clear_float"></div>';
        }
        $count ++;
    }
    $output .= '<div class="gdm_clear_float"></div>';
    return $output;
}

/*
 * Generates the output of a single item using card template style
 * $args array can have the following parameters
 * id, template, button_text, new_window
 */

function gdm_generate_card_template_display_output( $args ) {

    $shortcode_atts = sanitize_gdm_create_download_shortcode_atts(
    shortcode_atts( array(
	'id'		 => '',
	'button_text'	 => __( 'Download Now!', 'gluon-download-manager' ),
	'new_window'	 => '',
	'color'		 => '',
	'css_class'	 => 'gdm_fancy2_grid',
	'show_size'	 => '',
	'show_version'	 => '',
    'more_details_url' => '', 
    'more_details_anchor' => '',
    ), $args )
    );

    // Make shortcode attributes available in function local scope.
    extract( $shortcode_atts );

    // Check the download ID
    if ( empty( $id ) ) {
	return '<div class="gdm_error_msg">Error! The shortcode is missing the ID parameter. Please refer to the documentation to learn the shortcode usage.</div>';
    }

    $id = intval( $id );

    $more_details_url = esc_url_raw($more_details_url);
    $more_details_anchor = gdm_sanitize_text($more_details_anchor);

    
    // See if new window parameter is set
    if ( empty( $new_window ) ) {
	$new_window = get_post_meta( $id, 'gdm_item_new_window', true );
    }
    $window_target = empty( $new_window ) ? '_self' : '_blank';
    $window_target = apply_filters('gdm_download_window_target', $window_target);

    // Get the download URL and generate the download button code.
    $download_url = gdm_get_standard_download_url_from_id($id);
    $download_button_code = '<a href="' . $download_url . '" class="gdm_fancy2_download_dl_link" target="' . $window_target . '">' . $button_text . '</a>';

    // Check to see if the download link cpt is password protected
    $get_cpt_object	 = get_post( $id );
    $cpt_is_password = ! empty( $get_cpt_object->post_password ) ? 'yes' : 'no';  // yes = download is password protected;
    // Read plugin settings
    $main_opts	 = get_option( 'gdm_downloads_options' );

    $main_advanced_opts = get_option( 'gdm_advanced_options' );

    //Check if Terms & Condition enabled
    $termscond_enable = isset( $main_advanced_opts[ 'termscond_enable' ] ) ? true : false;
    if ( $termscond_enable ) {
	$download_button_code = gdm_get_download_form_with_termsncond( $id, $shortcode_atts, 'gdm_fancy2_download_dl_link' );
    }

	//Check if reCAPTCHA enabled
	$recaptcha_enable = gdm_is_any_recaptcha_enabled();
	if ( $recaptcha_enable && $cpt_is_password == 'no' ) {
		$download_button_code = gdm_get_download_form_with_recaptcha( $id, $shortcode_atts, 'gdm_download ' );
	}

    if ( $cpt_is_password !== 'no' ) {//This is a password protected download so replace the download now button with password requirement
	$download_button_code = gdm_get_password_entry_form( $id, $shortcode_atts, 'gdm_fancy2_download_dl_link' );
    }

    // Get item title
    $item_title = get_the_title( $id );
    
    // Get item thumbnail
    $thumbnail_alt = apply_filters ( 'gdm_download_card_template_thumbnail_alt', $item_title, $id );//Trigger a filter for the thumbnail alt
    $item_download_thumbnail	 = get_post_meta( $id, 'gdm_upload_thumbnail', true );
    $isset_download_thumbnail	 = isset( $item_download_thumbnail ) && ! empty( $item_download_thumbnail ) ? '<img class="gdm_fancy2_thumb_image" src="' . esc_url($item_download_thumbnail) . '" alt = "' . esc_html($thumbnail_alt) . '" />' : '';
    $isset_download_thumbnail	 = apply_filters( 'gdm_download_card_template_thumbnail', $isset_download_thumbnail, $args ); //Apply filter so it can be customized.

    // Get item description
    $isset_item_description = gdm_get_item_description_output( $id );

    //Get item file size
    $item_file_size = get_post_meta( $id, 'gdm_item_file_size', true );
    //Check if show file size is enabled
    if ( empty( $show_size ) ) {
	//Disabled in shortcode. Lets check if it is enabled in the download meta.
	$show_size = get_post_meta( $id, 'gdm_item_show_file_size_fd', true );
    }
    $isset_item_file_size	 = ($show_size && isset( $item_file_size )) ? $item_file_size : ''; //check if show_size is enabled and if there is a size value
    //Get item version
    $item_version		 = get_post_meta( $id, 'gdm_item_version', true );
    //Check if show version is enabled
    if ( empty( $show_version ) ) {
	//Disabled in shortcode. Lets check if it is enabled in the download meta.
	$show_version = get_post_meta( $id, 'gdm_item_show_item_version_fd', true );
    }
    $isset_item_version	 = ($show_version && isset( $item_version )) ? $item_version : ''; //check if show_version is enabled and if there is a version value
    // check show date in fancy display
    $show_date_fd = get_post_meta( $id, 'gdm_item_show_date_fd', true );
    // Get item date
    $download_date = get_the_date( get_option( 'date_format' ), $id );

    $output	 = '';
    $output	 .= '<div class="gdm_fancy_template_wrapper">';//For block themes
    $output	 .= '<div class="gdm_fancy2_item gdm_template_card ' . esc_attr($css_class) . '">';
    $output	 .= '<div class="gdm_fancy2_wrapper">';
    
    $output	 .= '<div class="gdm_fancy2_download_item_top">';
    $output	 .= '<div class="gdm_fancy2_download_thumbnail">' . $isset_download_thumbnail . '</div>';
    $output	 .= '</div>'; //End of .gdm_download_item_top

    $output .= '<div class="gdm_fancy2_download_title">' . esc_html($item_title) . '</div>';

    //Show file size info if specified in the shortcode
    if ( ! empty( $isset_item_file_size ) ) {
	$output	 .= '<div class="gdm_fancy2_download_size">';
	$output	 .= '<span class="gdm_fancy2_download_size_label">' . __( 'Size: ', 'gluon-download-manager' ) . '</span>';
	$output	 .= '<span class="gdm_fancy2_download_size_value">' . $isset_item_file_size . '</span>';
	$output	 .= '</div>';
    }

    //Show version info if specified in the shortcode
    if ( ! empty( $isset_item_version ) ) {
        $output	 .= '<div class="gdm_fancy2_download_version">';
        $output	 .= '<span class="gdm_fancy2_download_version_label">' . __( 'Version: ', 'gluon-download-manager' ) . '</span>';
        $output	 .= '<span class="gdm_fancy2_download_version_value">' . $isset_item_version . '</span>';
        $output	 .= '</div>';
    }

    //Show date info if specified in the shortcode
    if ( $show_date_fd ) {
        $output	 .= '<div class="gdm_fancy2_download_date">';
        $output	 .= '<span class="gdm_fancy2_download_date_label">' . __( 'Published: ', 'gluon-download-manager' ) . '</span>';
        $output	 .= '<span class="gdm_fancy2_download_date_value">' . $download_date . '</span>';
        $output	 .= '</div>';
    }

    //Show more details link if specified in the shortcode
    if ( ! empty( $more_details_url ) ) {
        $output .= '<div class="gdm_fancy2_more_details_link_section">';
        $output .= '<a href="'. $more_details_url . '" class="gdm_fancy2_more_details_link">' . $more_details_anchor . '</a>';
        $output .= '</div>';
    }

    //Filter hook to allow other plugins to add their own HTML code before the download button.
    $extra_html_before_button = apply_filters( 'gdm_before_download_button', '', $id, $args );
    $output .= $extra_html_before_button;

    //The download button section.
    //Filter hook to allow other plugins to customize the download button code.
    $download_button_code = apply_filters( 'gdm_download_button_code_html', $download_button_code );    
    $output .= '<div class="gdm_fancy2_download_link">';
    $output .= $download_button_code;
    $output .= '</div>';

    $output .= '</div>'; //end .gdm_fancy2_wrapper
    $output .= '</div>'; //end .gdm_fancy2_item
    $output .= '</div>'; //end .gdm_fancy_template_wrapper

    //Filter to allow overriding the output
    $output = apply_filters( 'gdm_generate_card_template_display_output_html', $output, $args );

    return $output;
}

// Backward compatibility aliases (fancy2 → card)
function gdm_generate_fancy2_popular_downloads_display_output( $get_posts, $args ) {
    return gdm_generate_card_template_popular_downloads_display_output( $get_posts, $args );
}

function gdm_generate_fancy2_latest_downloads_display_output( $get_posts, $args ) {
    return gdm_generate_card_template_latest_downloads_display_output( $get_posts, $args );
}

function gdm_generate_fancy2_category_display_output( $get_posts, $args ) {
    return gdm_generate_card_template_category_display_output( $get_posts, $args );
}

function gdm_generate_fancy2_display_output( $args ) {
    return gdm_generate_card_template_display_output( $args );
}
