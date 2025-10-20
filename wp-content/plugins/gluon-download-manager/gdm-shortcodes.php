<?php

add_filter( 'widget_text', 'do_shortcode' ); //Enable shortcode filtering in standard text widget

/*
 * * Register and handle Shortcode
 */

function gdm_register_shortcodes() {

	//Note: shortcode name should use underscores (not dashes). Some of the shortcodes have dashes for backwards compatibility.

	add_shortcode( 'gdm_download', 'gdm_create_download_shortcode' );  // For download shortcode (underscores)
	add_shortcode( 'gdm-download', 'gdm_create_download_shortcode' );  // For download shortcode (for backwards compatibility)
	add_shortcode( 'gdm_download_counter', 'gdm_create_counter_shortcode' );  // For counter shortcode (underscores)
	add_shortcode( 'gdm-download-counter', 'gdm_create_counter_shortcode' );  // For counter shortcode (for backwards compatibility)
	add_shortcode( 'gdm_latest_downloads', 'gdm_show_latest_downloads' ); // For showing X number of latest downloads
	add_shortcode( 'gdm-latest-downloads', 'gdm_show_latest_downloads' );  // For showing X number of latest downloads(for backwards compatibility)
	add_shortcode( 'gdm_popular_downloads', 'gdm_show_popular_downloads' ); // For showing X number of popular downloads

	add_shortcode( 'gdm_download_link', 'gdm_create_simple_download_link' );

	add_shortcode( 'gdm_show_all_dl', 'gdm_handle_show_all_dl_shortcode' ); // For show all downloads shortcode

	add_shortcode( 'gdm_show_dl_from_category', 'gdm_handle_category_shortcode' ); //For category shortcode
	add_shortcode( 'gdm_download_categories', 'gdm_download_categories_shortcode' ); // Ajax file tree browser

	add_shortcode( 'gdm_download_categories_list', 'gdm_download_categories_list_shortcode' );
	add_shortcode( 'gdm_search_form', 'gdm_search_form_shortcode' );

	add_shortcode( 'gdm_show_download_info', 'gdm_show_download_info_shortcode' );
}

/**
 * Process (sanitize) download button shortcode attributes:
 * - convert "id" to absolute integer
 * - set "color" to color from settings or default color, if empty
 *
 * @param array $atts
 * @return array
 */
function sanitize_gdm_create_download_shortcode_atts( $atts ) {

	// Sanitize download item ID
	$atts['id'] = absint( $atts['id'] );

	// See if user color option is selected
	$main_opts = get_option( 'gdm_downloads_options' );

	if ( empty( $atts['color'] ) ) {
		// No color provided by shortcode, read color from plugin settings.
		$atts['color'] = isset( $main_opts['download_button_color'] ) ? strtolower( $main_opts['download_button_color'] ) // default values needs to be lowercased
		: 'green';
	}

	// Remove spaces from color key to make a proper CSS class name.
	$atts['color'] = str_replace( ' ', '', $atts['color'] );

	return $atts;
}

// Create Download Shortcode
function gdm_create_download_shortcode( $atts ) {

	$shortcode_atts = sanitize_gdm_create_download_shortcode_atts(
		shortcode_atts(
			array(
				'id'           => '',
				'fancy'        => '0',     // Backward compatibility
				'template'     => '',      // New template parameter
				'button_text'  => gdm_get_default_download_button_text( $atts['id'] ),
				'new_window'   => '',
				'color'        => '',
				'css_class'    => '',
				'show_size'    => '',
				'show_version' => '',
				'more_details_url' => "",
				'more_details_anchor' => __('More Details', 'gluon-download-manager'),
			),
			$atts
		)
	);

	// Make shortcode attributes available in function local scope.
	extract( $shortcode_atts );
	
	// If 'template' parameter is provided, use it; otherwise fall back to 'fancy'
	$template_to_use = ! empty( $template ) ? $template : $fancy;

	if ( empty( $id ) ) {
		return '<p style="color: red;">' . __( 'Error! Please enter an ID value with this shortcode.', 'gluon-download-manager' ) . '</p>';
	}

	$id        = intval( $id );
	$color     = gdm_sanitize_text( $color );
	$css_class = gdm_sanitize_text( $css_class );

	// Check to see if the download link cpt is password protected
	$get_cpt_object  = get_post( $id );
	$cpt_is_password = ! empty( $get_cpt_object->post_password ) ? 'yes' : 'no';  // yes = download is password protected;
	// Get CPT title
	$item_title = get_the_title( $id );

	//*** Generate the download now button code ***
	if ( empty( $new_window ) ) {
		$new_window = get_post_meta( $id, 'gdm_item_new_window', true );
	}
	$window_target = empty( $new_window ) ? '_self' : '_blank';
	$window_target = apply_filters('gdm_download_window_target', $window_target);

	$download_url = gdm_get_standard_download_url_from_id($id);
	$download_button_code = '<a href="' . $download_url . '" class="gdm_download ' . esc_attr($color) . '" title="' . esc_html($item_title) . '" target="' . $window_target . '">' . esc_attr($button_text) . '</a>';

	$main_advanced_opts = get_option( 'gdm_advanced_options' );

	//Check if Terms & Condition enabled
	$termscond_enable = isset( $main_advanced_opts['termscond_enable'] ) ? true : false;
	if ( $termscond_enable ) {
		$download_button_code = gdm_get_download_form_with_termsncond( $id, $shortcode_atts, 'gdm_download ' . $color );
	}

	//Check if reCAPTCHA enabled
	$recaptcha_enable = isset( $main_advanced_opts['recaptcha_enable'] ) ? true : false;
	if ( $recaptcha_enable && $cpt_is_password == 'no' ) {
		$download_button_code = gdm_get_download_form_with_recaptcha( $id, $shortcode_atts, 'gdm_download ' . $color );
	}

	if ( $cpt_is_password !== 'no' ) {//This is a password protected download so replace the download now button with password requirement
		$download_button_code = gdm_get_password_entry_form( $id, $shortcode_atts, 'gdm_download ' . $color );
	}
	//End of download now button code generation

	$output = '';

	$output .= gdm_load_template($template_to_use, $shortcode_atts);
	$output .= '<div class="gdm_clear_float"></div>';

	// TODO: Old code, to be removed later.
	// if (empty($output)) {
	// 	switch ( $fancy ) {
	// 		case '1':
	// 			include_once 'includes/templates/fancy1/gdm-fancy-1.php';
	// 			$output .= gdm_generate_fancy1_display_output( $shortcode_atts );
	// 			$output .= '<div class="gdm_clear_float"></div>';
	// 			break;
	// 		case '2':
	// 			include_once 'includes/templates/fancy2/gdm-fancy-2.php';
	// 			wp_enqueue_style( 'gdm_addons_listing', WP_GLUON_DL_MANAGER_URL . '/includes/templates/fancy2/gdm-fancy-2-styles.css', array(), WP_GLUON_DL_MANAGER_VERSION );
	// 			$output .= gdm_generate_fancy2_display_output( $shortcode_atts );
	// 			$output .= '<div class="gdm_clear_float"></div>';
	// 			break;
	// 		case '3':
	// 			include_once 'includes/templates/fancy3/gdm-fancy-3.php';
	// 			$output .= gdm_generate_fancy3_display_output( $shortcode_atts );
	// 			$output .= '<div class="gdm_clear_float"></div>';
	// 			break;
	// 		default: // Default output is the standard download now button (fancy 0)
	// 			include_once 'includes/templates/fancy0/gdm-fancy-0.php';
	// 			$output .= gdm_generate_fancy0_display_output( $shortcode_atts );
	// 	}
	// }

	return apply_filters( 'gdm_download_shortcode_output', $output, $atts );
}

function gdm_create_simple_download_link( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => '',
		),
		$atts
	);

	$id = isset($atts['id']) ? sanitize_text_field($atts['id']) : '';

	if ( empty( $id ) || !is_numeric($id) ) {
		return '<p style="color: red;">' . __( 'Error! Please enter an ID value with this shortcode.', 'gluon-download-manager' ) . '</p>';
	}

	return WP_GLUON_DL_MANAGER_SITE_HOME_URL . '/?gdm_process_download=1&download_id=' . esc_js($id);
}

// Create Counter Shortcode
function gdm_create_counter_shortcode( $atts ) {

	extract(
		shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		)
	);

	if ( empty( $id ) ) {
		return '<p style="color: red;">' . __( 'Error! Please enter an ID value with this shortcode.', 'gluon-download-manager' ) . '</p>';
	}

	// Checks if to show count for all total download or any specific download.
	if ( preg_match( '/^all$/i', $id ) ) {
		$db_count = gdm_get_download_count_for_all_posts();
	} else {
		$db_count = gdm_get_download_count_for_post( $id );
	}

	// Set string for singular/plural results
	$string = ( $db_count == '1' ) ? __( 'Download', 'gluon-download-manager' ) : __( 'Downloads', 'gluon-download-manager' );

	$output = '<div class="gdm_download_count"><span class="gdm_count_number">' . $db_count . '</span><span class="gdm_count_string"> ' . $string . '</span></div>';
	// Return result
	return apply_filters( 'gdm_download_count_output', $output, $atts );
}

// Create Category Shortcode
function gdm_handle_category_shortcode( $args ) {

	extract(
		shortcode_atts(
			array(
				'category_slug' => '',
				'category_id'   => '',
				'fancy'         => '0',
				'button_text'   => '',
				'new_window'    => '',
				'orderby'       => 'post_date',
				'order'         => 'DESC',
				'pagination'    => '',
			),
			$args
		)
	);

	// Define vars
	$field = '';
	$terms = '';

	// If category slug and category id are empty.. return error
	if ( empty( $category_slug ) && empty( $category_id ) && empty( $args['show_all'] ) ) {
		return '<p style="color: red;">' . __( 'Error! You must enter a category slug OR a category id with this shortcode. Refer to the documentation for usage instructions.', 'gluon-download-manager' ) . '</p>';
	}

	// If both category slug AND category id are defined... return error
	if ( ! empty( $category_slug ) && ! empty( $category_id ) ) {
		return '<p style="color: red;">' . __( 'Error! Please enter a category slug OR id; not both.', 'gluon-download-manager' ) . '</p>';
	}

	// Else setup query arguments for category_slug
	if ( ! empty( $category_slug ) && empty( $category_id ) ) {

		$field = 'slug';

		$terms = array_filter(
			explode( ',', $category_slug ),
			function( $value ) {
				return ! empty( $value ) ? trim( $value ) : false;
			}
		);
	}
	// Else setup query arguments for category_id
	elseif ( ! empty( $category_id ) && empty( $category_slug ) ) {

		$field = 'term_id';
		//$terms = $category_id;
		$terms = array_filter(
			explode( ',', $category_id ),
			function( $value ) {
				return ! empty( $value ) ? trim( $value ) : false;
			}
		);
	}

	if ( isset( $args['show_all'] ) ) {
		$tax_query = array();
	} else {
		$tax_query = array(
			array(
				'taxonomy' => 'gdm_categories',
				'field'    => $field,
				'terms'    => $terms,
			),
		);
	}

	// For pagination
	$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;
	if ( isset( $args['pagination'] ) ) {
		if ( ! is_numeric( $args['pagination'] ) ) {
			return '<p style="color: red;">' . __( 'Error! You must enter a numeric number for the "pagination" parameter of the shortcode. Refer to the usage documentation.', 'gluon-download-manager' ) . '</p>';
		}
		$posts_per_page = $args['pagination'];
	} else {
		$posts_per_page = 9999;
	}

	// Query cpt's based on arguments above
	$get_posts_args = array(
		'post_type'      => 'gdm_downloads',
		'show_posts'     => -1,
		'posts_per_page' => $posts_per_page,
		'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering
		'orderby'        => $orderby,
		'order'          => $order,
		'paged'          => $paged,
	);

	$query = new WP_Query();

	$get_posts = $query->query( $get_posts_args );

	// If no cpt's are found
	if ( ! $get_posts ) {
		return '<p style="color: red;">' . __( 'There are no download items matching this category criteria.', 'gluon-download-manager' ) . '</p>';
	}
	// Else iterate cpt's
	else {

		$output = '';

		// See if user color option is selected
		$main_opts = get_option( 'gdm_downloads_options' );
		$color_opt = isset( $main_opts['download_button_color'] ) ? $main_opts['download_button_color'] : null;
		$def_color = isset( $color_opt ) ? str_replace( ' ', '', strtolower( $color_opt ) ) : 'green';

		if ( $fancy == '0' ) {
			include_once 'includes/templates/fancy0/gdm-fancy-0.php';
			$output .= gdm_generate_fancy0_category_display_output( $get_posts, $args );

			// TODO: Old code, to be removed later.
			/*
			// Iterate Download CPTs
			foreach ( $get_posts as $item ) {

				// Set download location
				$id = $item->ID;  // get each cpt ID
				$download_url = gdm_get_standard_download_url_from_id($id);

				// Get each cpt title
				$item_title = get_the_title( $id );
				$item_button_text = $button_text;

				if ( empty( $new_window ) ) {
					$new_window = get_post_meta( $id, 'gdm_item_new_window', true );
				}

				$window_target = empty( $new_window ) ? '_self' : '_blank';

				// Get the download button text.
				// Prioritize category shortcode param over custom button text from edit page.
				// Show default button text if both are empty.

				if (empty($item_button_text)) {
					$item_button_text = gdm_get_dl_button_text($id);
				}

				// Setup download button code
				$download_button_code = '<a href="' . $download_url . '" class="gdm_download ' . $def_color . '" title="' . esc_html($item_title) . '" target="' . $window_target . '">' . esc_attr($item_button_text) . '</a>';

				$main_advanced_opts = get_option( 'gdm_advanced_options' );

				//Check if Terms & Condition enabled
				$termscond_enable = isset( $main_advanced_opts['termscond_enable'] ) ? true : false;
				if ( $termscond_enable ) {
					$download_button_code = gdm_get_download_form_with_termsncond( $id, $args, 'gdm_download ' . $def_color );
				}

				//Check if reCAPTCHA enabled
				$recaptcha_enable = isset( $main_advanced_opts['recaptcha_enable'] ) ? true : false;
				if ( $recaptcha_enable ) {
					$download_button_code = gdm_get_download_form_with_recaptcha( $id, $args, 'gdm_download ' . $def_color );
				}

				// Generate download buttons
				$output .= '<div class="gdm_download_link">' . $download_button_code . '</div><br />';
			}  // End foreach 
			*/
		}
		// Fancy 1 and onwards handles the loop inside the template function
		elseif ( $fancy == '1' ) {
			include_once 'includes/templates/fancy1/gdm-fancy-1.php';
			$output .= gdm_generate_fancy1_category_display_output( $get_posts, $args );
		} elseif ( $fancy == '2' ) {
			include_once 'includes/templates/fancy2/gdm-fancy-2.php';
			$output .= gdm_generate_fancy2_category_display_output( $get_posts, $args );
		} elseif ( $fancy == '3' ) {
			include_once 'includes/templates/fancy3/gdm-fancy-3.php';
			$output .= gdm_generate_fancy3_category_display_output( $get_posts, $args );
		}

		// Pagination related
		if ( isset( $args['pagination'] ) ) {
			$posts_per_page      = $args['pagination'];
			$count_gdm_posts     = $query->found_posts;
			$published_gdm_posts = $count_gdm_posts;
			$total_pages         = ceil( $published_gdm_posts / $posts_per_page );

			$big        = 999999999; // Need an unlikely integer
			$pagination = paginate_links(
				array(
					'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'    => '',
					'add_args'  => '',
					'current'   => max( 1, get_query_var( 'paged' ) ),
					'total'     => $total_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				)
			);
			$output    .= '<div class="gdm_pagination">' . $pagination . '</div>';
		}

		// Return results
		return apply_filters( 'gdm_category_download_items_shortcode_output', $output, $args, $get_posts );
	}  // End else iterate cpt's
}

// Helper function for category tree walker
function gdm_custom_taxonomy_walker( $taxonomy, $parent = 0 ) {

	// Get terms (check if has parent)
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'parent'     => $parent,
			'hide_empty' => false,
		)
	);

	// If there are terms, start displaying
	if ( count( $terms ) > 0 ) {
		// Displaying as a list
		$out = '<ul>';
		// Cycle though the terms
		foreach ( $terms as $term ) {
			// Secret sauce. Function calls itself to display child elements, if any
			$out .= '<li class="gdm_cat" id="' . $term->slug . '"><span id="' . $term->term_id . '" class="gdm_cat_title" style="cursor:pointer;">' . $term->name . '</span>';
			$out .= '<p class="gdm_placeholder" style="margin-bottom:0;"></p>' . gdm_custom_taxonomy_walker( $taxonomy, $term->term_id );
			$out .= '</li>';
		}
		$out .= '</ul>';
		return $out;
	}
	return;
}

// Create category tree shortcode
function gdm_download_categories_shortcode() {
	return '<div class="gdm_object_tree">' . gdm_custom_taxonomy_walker( 'gdm_categories' ) . '</div>';
}

/**
 * Return HTML list with SDM categories rendered according to $atts.
 *
 * @param array $atts
 * @param int $parent
 * @return string
 */
function gdm_download_categories_list_walker( $atts, $parent = 0 ) {

	$count        = (bool) $atts['count'];
	$hierarchical = (bool) $atts['hierarchical'];
	$show_empty   = (bool) $atts['empty'];
	$list_tag     = $atts['numbered'] ? 'ol' : 'ul';

	// Get terms (check if has parent)
	$terms = get_terms(
		array(
			'taxonomy'   => 'gdm_categories',
			'parent'     => $parent,
			'hide_empty' => ! $show_empty,
		)
	);

	// Return empty string, if no terms found.
	if ( empty( $terms ) ) {
		return '';
	}

	// Produce list of download categories under $parent.
	$out = '<' . $list_tag . '>';

	foreach ( $terms as $term ) {
		$out .= '<li>'
		. '<a href="' . get_term_link( $term ) . '">' . $term->name . '</a>' // link
		. ( $count ? ( ' <span>(' . $term->count . ')</span>' ) : '' ) // count
		. ( $hierarchical ? gdm_download_categories_list_walker( $atts, $term->term_id ) : '' ) // subcategories
		. '</li>';
	}

	$out .= '</' . $list_tag . '>';

	return $out;
}

/**
 * Return output of `gdm_download_categories_list` shortcode.
 *
 * @param array $attributes
 * @return string
 */
function gdm_download_categories_list_shortcode( $attributes ) {

	$atts = shortcode_atts(
		array(
			'class'        => 'gdm-download-categories', // wrapper class
			'empty'        => '0', // show empty categories
			'numbered'     => '0', // use <ol> instead of <ul> to wrap the list
			'count'        => '0', // display count of items in every category
			'hierarchical' => '1', // display subcategories as well
		),
		$attributes
	);

	return '<div class="' . esc_attr( $atts['class'] ) . '">'
	. gdm_download_categories_list_walker( $atts )
	. '</div>';
}

function gdm_show_download_info_shortcode( $args ) {
	extract(
		shortcode_atts(
			array(
				'id'            => '',
				'download_info' => '',
			),
			$args
		)
	);

	if ( empty( $id ) || empty( $download_info ) ) {
		return '<div class="gdm_shortcode_error">Error! you must enter a value for "id" and "download_info" parameters.</div>';
	}

	//Available values: title, description, download_url, thumbnail, file_size, file_version, download_count

	$id             = absint( $id );
	$get_cpt_object = get_post( $id );

	if ( $download_info == 'title' ) {//download title
		$item_title = get_the_title( $id );
		return $item_title;
	}

	if ( $download_info == 'description' ) {//download description
		$item_description = gdm_get_item_description_output( $id );
		return $item_description;
	}

	if ( $download_info == 'download_url' ) {//download URL
		$download_link = get_post_meta( $id, 'gdm_upload', true );
		return $download_link;
	}

	if ( $download_info == 'thumbnail' ) {//download thumb
		$download_thumbnail = get_post_meta( $id, 'gdm_upload_thumbnail', true );
		$download_thumbnail = '<img class="gdm_download_thumbnail_image" src="' . esc_url($download_thumbnail) . '" />';
		return $download_thumbnail;
	}

	if ( $download_info == 'thumbnail_url' ) {//download thumbnail raw URL
		$download_thumbnail = get_post_meta( $id, 'gdm_upload_thumbnail', true );
		return $download_thumbnail;
	}

	if ( $download_info == 'file_size' ) {//download file size
		$file_size = get_post_meta( $id, 'gdm_item_file_size', true );
		return $file_size;
	}

	if ( $download_info == 'file_version' ) {//download file version
		$file_version = get_post_meta( $id, 'gdm_item_version', true );
		return $file_version;
	}

	if ( $download_info == 'download_count' ) {//download count
		$dl_count = gdm_get_download_count_for_post( $id );
		return $dl_count;
	}

	return '<div class="gdm_shortcode_error">Error! The value of "download_info" field does not match any availalbe parameters.</div>';
}

function gdm_handle_show_all_dl_shortcode( $args ) {
	if ( isset( $args['category_id'] ) ) {
		unset( $args['category_id'] );
	}
	if ( isset( $args['category_slug'] ) ) {
		unset( $args['category_slug'] );
	}
	$args['show_all'] = 1;
	return gdm_handle_category_shortcode( $args );
}
// Omit closing PHP tag to prevent "headers already sent" errors
