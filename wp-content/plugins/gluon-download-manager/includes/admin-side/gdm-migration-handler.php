<?php
/**
 * GDM Migration Handler - Migrates data from SDM to GDM
 */

class gdm_Migration_Handler {

	private $migrated = array(
		'downloads' => 0,
		'logs' => 0,
		'settings' => 0,
		'categories' => 0,
		'tags' => 0,
		'skipped' => 0,
	);

	public function migrate_from_sdm( $options = array() ) {
		global $wpdb;

		set_time_limit( 300 ); // 5 minutes

		$migrate_logs = isset( $options['migrate_logs'] ) && $options['migrate_logs'] == '1';
		$migrate_settings = isset( $options['migrate_settings'] ) && $options['migrate_settings'] == '1';
		$migrate_taxonomies = isset( $options['migrate_taxonomies'] ) && $options['migrate_taxonomies'] == '1';
		$skip_existing = isset( $options['skip_existing'] ) && $options['skip_existing'] == '1';

		try {
			// 1. Migrate Taxonomies first (so we can assign them to posts)
			if ( $migrate_taxonomies ) {
				$this->migrate_categories();
				$this->migrate_tags();
			}

			// 2. Migrate Download Items
			$this->migrate_downloads( $skip_existing );

			// 3. Migrate Logs
			if ( $migrate_logs ) {
				$this->migrate_logs();
			}

			// 4. Migrate Settings
			if ( $migrate_settings ) {
				$this->migrate_settings();
			}

			$message = $this->get_success_message();

			return array(
				'success' => true,
				'message' => $message,
				'migrated' => $this->migrated,
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => '<strong>' . __( 'Migration failed:', 'gluon-download-manager' ) . '</strong> ' . $e->getMessage(),
			);
		}
	}

	private function migrate_downloads( $skip_existing = false ) {
		// Get all SDM downloads
		$sdm_posts = get_posts( array(
			'post_type' => 'sdm_downloads',
			'posts_per_page' => -1,
			'post_status' => 'any',
		) );

		foreach ( $sdm_posts as $sdm_post ) {
			// Check if already migrated
			if ( $skip_existing ) {
				$existing = get_page_by_title( $sdm_post->post_title, OBJECT, 'gdm_downloads' );
				if ( $existing ) {
					$this->migrated['skipped']++;
					continue;
				}
			}

			// Create GDM post
			$gdm_post_id = wp_insert_post( array(
				'post_title' => $sdm_post->post_title,
				'post_content' => $sdm_post->post_content,
				'post_status' => $sdm_post->post_status,
				'post_author' => $sdm_post->post_author,
				'post_date' => $sdm_post->post_date,
				'post_date_gmt' => $sdm_post->post_date_gmt,
				'post_modified' => $sdm_post->post_modified,
				'post_modified_gmt' => $sdm_post->post_modified_gmt,
				'post_type' => 'gdm_downloads',
				'post_password' => $sdm_post->post_password,
			), true );

			if ( is_wp_error( $gdm_post_id ) ) {
				continue;
			}

			// Migrate all post meta
			$this->migrate_post_meta( $sdm_post->ID, $gdm_post_id );

			// Migrate taxonomies
			$this->migrate_post_taxonomies( $sdm_post->ID, $gdm_post_id );

			// Store reference to original SDM ID
			update_post_meta( $gdm_post_id, '_migrated_from_sdm_id', $sdm_post->ID );

			$this->migrated['downloads']++;
		}
	}

	private function migrate_post_meta( $sdm_post_id, $gdm_post_id ) {
		$meta_keys_map = array(
			'sdm_description' => 'gdm_description',
			'sdm_upload' => 'gdm_upload',
			'sdm_upload_thumbnail' => 'gdm_upload_thumbnail',
			'sdm_item_file_size' => 'gdm_item_file_size',
			'sdm_item_version' => 'gdm_item_version',
			'sdm_download_button_text' => 'gdm_download_button_text',
			'sdm_item_dispatch' => 'gdm_item_dispatch',
			'sdm_item_new_window' => 'gdm_item_new_window',
			'sdm_item_show_date_fd' => 'gdm_item_show_date_fd',
			'sdm_item_show_file_size_fd' => 'gdm_item_show_file_size_fd',
			'sdm_item_show_item_version_fd' => 'gdm_item_show_item_version_fd',
			'sdm_count_offset' => 'gdm_count_offset',
			'sdm_item_no_log' => 'gdm_item_no_log',
			'sdm_item_anonymous_can_download' => 'gdm_item_anonymous_can_download',
			'sdm_item_disable_single_download_page' => 'gdm_item_disable_single_download_page',
			'sdm_item_hide_dl_button_single_download_page' => 'gdm_item_hide_dl_button_single_download_page',
		);

		foreach ( $meta_keys_map as $sdm_key => $gdm_key ) {
			$value = get_post_meta( $sdm_post_id, $sdm_key, true );
			if ( $value !== '' && $value !== false ) {
				update_post_meta( $gdm_post_id, $gdm_key, $value );
			}
		}
	}

	private function migrate_post_taxonomies( $sdm_post_id, $gdm_post_id ) {
		// Migrate categories
		$sdm_categories = wp_get_post_terms( $sdm_post_id, 'sdm_categories', array( 'fields' => 'slugs' ) );
		if ( ! empty( $sdm_categories ) && ! is_wp_error( $sdm_categories ) ) {
			wp_set_post_terms( $gdm_post_id, $sdm_categories, 'gdm_categories' );
		}

		// Migrate tags
		$sdm_tags = wp_get_post_terms( $sdm_post_id, 'sdm_tags', array( 'fields' => 'slugs' ) );
		if ( ! empty( $sdm_tags ) && ! is_wp_error( $sdm_tags ) ) {
			wp_set_post_terms( $gdm_post_id, $sdm_tags, 'gdm_tags' );
		}
	}

	private function migrate_categories() {
		$sdm_categories = get_terms( array(
			'taxonomy' => 'sdm_categories',
			'hide_empty' => false,
		) );

		if ( is_wp_error( $sdm_categories ) || empty( $sdm_categories ) ) {
			return;
		}

		foreach ( $sdm_categories as $sdm_cat ) {
			// Check if category already exists
			$existing = term_exists( $sdm_cat->slug, 'gdm_categories' );
			if ( ! $existing ) {
				$new_term = wp_insert_term(
					$sdm_cat->name,
					'gdm_categories',
					array(
						'slug' => $sdm_cat->slug,
						'description' => $sdm_cat->description,
					)
				);

				if ( ! is_wp_error( $new_term ) ) {
					$this->migrated['categories']++;
				}
			}
		}
	}

	private function migrate_tags() {
		$sdm_tags = get_terms( array(
			'taxonomy' => 'sdm_tags',
			'hide_empty' => false,
		) );

		if ( is_wp_error( $sdm_tags ) || empty( $sdm_tags ) ) {
			return;
		}

		foreach ( $sdm_tags as $sdm_tag ) {
			// Check if tag already exists
			$existing = term_exists( $sdm_tag->slug, 'gdm_tags' );
			if ( ! $existing ) {
				$new_term = wp_insert_term(
					$sdm_tag->name,
					'gdm_tags',
					array(
						'slug' => $sdm_tag->slug,
						'description' => $sdm_tag->description,
					)
				);

				if ( ! is_wp_error( $new_term ) ) {
					$this->migrated['tags']++;
				}
			}
		}
	}

	private function migrate_logs() {
		global $wpdb;

		$sdm_logs_table = $wpdb->prefix . 'sdm_downloads';
		$gdm_logs_table = $wpdb->prefix . 'gdm_downloads';

		// Check if SDM logs table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$sdm_logs_table'" ) != $sdm_logs_table ) {
			return;
		}

		// Get all SDM logs
		$sdm_logs = $wpdb->get_results( "SELECT * FROM $sdm_logs_table" );

		foreach ( $sdm_logs as $log ) {
			// Find the corresponding GDM post ID
			$gdm_post_id = $this->get_gdm_post_id_from_sdm_id( $log->post_id );
			
			if ( ! $gdm_post_id ) {
				continue; // Skip if post wasn't migrated
			}

			// Check if log already exists (by IP, post_id, and date_time)
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $gdm_logs_table 
				WHERE post_id = %d 
				AND visitor_ip = %s 
				AND date_time = %s",
				$gdm_post_id,
				$log->visitor_ip,
				$log->date_time
			) );

			if ( ! $existing ) {
				$wpdb->insert(
					$gdm_logs_table,
					array(
						'post_id' => $gdm_post_id,
						'post_title' => get_the_title( $gdm_post_id ),
						'file_url' => $log->file_url,
						'visitor_ip' => $log->visitor_ip,
						'date_time' => $log->date_time,
						'visitor_country' => isset( $log->visitor_country ) ? $log->visitor_country : '',
						'visitor_name' => isset( $log->visitor_name ) ? $log->visitor_name : '',
						'user_agent' => isset( $log->user_agent ) ? $log->user_agent : '',
						'referrer_url' => isset( $log->referrer_url ) ? $log->referrer_url : '',
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
				);

				if ( $wpdb->insert_id ) {
					$this->migrated['logs']++;
				}
			}
		}
	}

	private function migrate_settings() {
		$settings_map = array(
			'sdm_downloads_options' => 'gdm_downloads_options',
			'sdm_advanced_options' => 'gdm_advanced_options',
		);

		foreach ( $settings_map as $sdm_option => $gdm_option ) {
			$sdm_settings = get_option( $sdm_option );
			
			if ( $sdm_settings && is_array( $sdm_settings ) ) {
				// Get existing GDM settings
				$gdm_settings = get_option( $gdm_option, array() );
				
				// Merge settings (GDM settings take precedence)
				$merged_settings = array_merge( $sdm_settings, $gdm_settings );
				
				update_option( $gdm_option, $merged_settings );
				$this->migrated['settings']++;
			}
		}
	}

	private function get_gdm_post_id_from_sdm_id( $sdm_post_id ) {
		// Query posts that have the _migrated_from_sdm_id meta key
		// Using meta_query instead of meta_key/meta_value for better performance
		$gdm_posts = get_posts( array(
			'post_type' => 'gdm_downloads',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for migration, runs rarely
				array(
					'key' => '_migrated_from_sdm_id',
					'value' => $sdm_post_id,
					'compare' => '=',
				),
			),
			'posts_per_page' => 1,
			'fields' => 'ids',
		) );

		return ! empty( $gdm_posts ) ? $gdm_posts[0] : false;
	}

	private function get_success_message() {
		$message = '<h3>' . __( 'Migration Completed Successfully!', 'gluon-download-manager' ) . '</h3>';
		$message .= '<ul style="list-style: disc; margin-left: 20px;">';
		$message .= '<li><strong>' . sprintf( __( 'Downloads migrated: %d', 'gluon-download-manager' ), $this->migrated['downloads'] ) . '</strong></li>';
		$message .= '<li><strong>' . sprintf( __( 'Logs migrated: %d', 'gluon-download-manager' ), $this->migrated['logs'] ) . '</strong></li>';
		$message .= '<li><strong>' . sprintf( __( 'Categories migrated: %d', 'gluon-download-manager' ), $this->migrated['categories'] ) . '</strong></li>';
		$message .= '<li><strong>' . sprintf( __( 'Tags migrated: %d', 'gluon-download-manager' ), $this->migrated['tags'] ) . '</strong></li>';
		$message .= '<li><strong>' . sprintf( __( 'Settings merged: %d', 'gluon-download-manager' ), $this->migrated['settings'] ) . '</strong></li>';
		
		if ( $this->migrated['skipped'] > 0 ) {
			$message .= '<li><em>' . sprintf( __( 'Items skipped (already exist): %d', 'gluon-download-manager' ), $this->migrated['skipped'] ) . '</em></li>';
		}
		
		$message .= '</ul>';
		$message .= '<p>' . __( 'Page will reload in 2 seconds...', 'gluon-download-manager' ) . '</p>';

		return $message;
	}
}
