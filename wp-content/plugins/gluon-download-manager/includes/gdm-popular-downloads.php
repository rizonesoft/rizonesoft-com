<?php

function gdm_show_popular_downloads( $args ) {

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

	global $wpdb;
	//Check if the query needs to be for a category
	if ( ! empty( $category_slug ) ) {
		$q = 'SELECT posts.*, downloads.id, downloads.post_id, terms.*, termrel.*, postmeta.*, (COUNT(downloads.post_id) + postmeta.meta_value) AS cnt'
		. ' FROM ' . $wpdb->prefix . 'posts as posts, ' . $wpdb->prefix . 'gdm_downloads as downloads, ' . $wpdb->prefix . 'terms as terms, ' . $wpdb->prefix . 'term_relationships as termrel, ' . $wpdb->prefix . 'postmeta as postmeta WHERE'
		. ' posts.id=downloads.post_id'
		. " AND (postmeta.meta_key='gdm_count_offset' AND postmeta.post_id=downloads.post_id)"
		. ' AND (terms.slug= %s AND termrel.object_id=downloads.post_id AND termrel.term_taxonomy_id=terms.term_id)'
		. ' GROUP BY downloads.post_id'
		. ' ORDER BY cnt DESC, %s %s'
		. ' LIMIT %d;';
		$q = $wpdb->prepare( $q, $category_slug, $orderby, $order, $number );
	} else {
		//no categury_slug present
		$q = 'SELECT posts.*, downloads.id, downloads.post_id, postmeta.*, (COUNT(downloads.post_id) + postmeta.meta_value) AS cnt'
		. ' FROM ' . $wpdb->prefix . 'posts as posts, ' . $wpdb->prefix . 'gdm_downloads as downloads, ' . $wpdb->prefix . 'postmeta as postmeta WHERE'
		. ' posts.id=downloads.post_id'
		. " AND (postmeta.meta_key='gdm_count_offset' AND postmeta.post_id=downloads.post_id)"
		. ' GROUP BY downloads.post_id'
		. ' ORDER BY cnt DESC, %s %s'
		. ' LIMIT %d;';
		$q = $wpdb->prepare( $q, $orderby, $order, $number );
	}

	$get_posts = $wpdb->get_results( $q );

	// If no cpt's are found
	if ( ! $get_posts ) {
		return '<p style="color: red;">' . __( 'There are no download items matching this shortcode criteria.', 'gluon-download-manager' ) . '</p>';
	}
	// Else iterate cpt's
	else {

		$output = '';
		if ( $fancy == '0' ) {
			include_once WP_GLUON_DL_MANAGER_PATH . 'includes/templates/fancy0/gdm-fancy-0.php';
			$output .= gdm_generate_fancy0_popular_downloads_display_output( $get_posts, $args );
		} elseif ( $fancy == '1' ) {
			include_once WP_GLUON_DL_MANAGER_PATH . 'includes/templates/fancy1/gdm-fancy-1.php';
			$output .= gdm_generate_fancy1_popular_downloads_display_output( $get_posts, $args );
		} elseif ( $fancy == '2' ) {
			include_once WP_GLUON_DL_MANAGER_PATH . 'includes/templates/fancy2/gdm-fancy-2.php';
			$output .= gdm_generate_fancy2_popular_downloads_display_output( $get_posts, $args );
		} elseif ( $fancy == '3' ) {
			include_once WP_GLUON_DL_MANAGER_PATH . 'includes/templates/fancy3/gdm-fancy-3.php';
			$output .= gdm_generate_fancy3_popular_downloads_display_output( $get_posts, $args );
		}

		// Return results
		return apply_filters( 'gdm_popular_downloads_shortcode_output', $output, $args, $get_posts );
	}  // End else iterate cpt's
}
