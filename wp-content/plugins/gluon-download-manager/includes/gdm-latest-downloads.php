<?php

function gdm_show_latest_downloads( $args ) {

	extract(
		shortcode_atts(
			array(
				'number'        => '5',
				'fancy'         => '0',
				'button_text'   => '',
				'new_window'    => '',
				'orderby'       => 'post_date',
				'order'         => 'DESC',
				'category_slug' => '',
			),
			$args
		)
	);

	$query_args = array(
		'post_type'      => 'gdm_downloads',
		'show_posts'     => -1,
		'posts_per_page' => $number,
		'orderby'        => $orderby,
		'order'          => $order,
	);

	//Check if the query needs to be for a category
	if ( ! empty( $category_slug ) ) {
		$field = 'slug';
		$terms = $category_slug;

		//Add the category slug parameters for the query args
		$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering in latest downloads
			array(
				'taxonomy' => 'gdm_categories',
				'field'    => $field,
				'terms'    => $terms,
			),
		);
	}

	// Query cpt's based on arguments above
	$get_posts = get_posts( $query_args );

	// If no cpt's are found
	if ( ! $get_posts ) {
		return '<p style="color: red;">' . __( 'There are no download items matching this shortcode criteria.', 'gluon-download-manager' ) . '</p>';
	}
	// Else iterate cpt's
	else {

		$output = '';
		if ( $fancy == '0' ) {
			include_once WP_GLUON_DL_MANAGER_PATH . 'includes/templates/fancy0/gdm-fancy-0.php';
			$output .= gdm_generate_fancy0_latest_downloads_display_output( $get_posts, $args );
		}
		if ( $fancy == '1' ) {
			include_once WP_GLUON_DL_MANAGER_PATH . 'includes/templates/fancy1/gdm-fancy-1.php';
			$output .= gdm_generate_fancy1_latest_downloads_display_output( $get_posts, $args );
		} elseif ( $fancy == '2' ) {
			include_once WP_GLUON_DL_MANAGER_PATH . 'includes/templates/fancy2/gdm-fancy-2.php';
			$output .= gdm_generate_fancy2_latest_downloads_display_output( $get_posts, $args );
		} elseif ( $fancy == '3' ) {
			include_once WP_GLUON_DL_MANAGER_PATH . 'includes/templates/fancy3/gdm-fancy-3.php';
			$output .= gdm_generate_fancy3_latest_downloads_display_output( $get_posts, $args );
		}

		// Return results
		return apply_filters( 'gdm_latest_downloads_shortcode_output', $output, $args, $get_posts );
	}  // End else iterate cpt's

}
