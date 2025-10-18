<?php
/**
 * GDM Backup & Restore Handler
 */

class gdm_Backup_Handler {

	private $backup_dir;

	public function __construct() {
		// Set backup directory in uploads folder
		$upload_dir = wp_upload_dir();
		$this->backup_dir = $upload_dir['basedir'] . '/gdm-backups';
		
		// Create backup directory if it doesn't exist
		if ( ! file_exists( $this->backup_dir ) ) {
			wp_mkdir_p( $this->backup_dir );
			
			// Create .htaccess to protect backup files
			$htaccess_content = "Order deny,allow\nDeny from all";
			file_put_contents( $this->backup_dir . '/.htaccess', $htaccess_content );
		}
	}

	public function create_backup( $options = array() ) {
		global $wpdb;

		set_time_limit( 300 ); // 5 minutes

		$backup_logs = isset( $options['backup_logs'] ) && $options['backup_logs'] == '1';
		$backup_settings = isset( $options['backup_settings'] ) && $options['backup_settings'] == '1';
		$backup_taxonomies = isset( $options['backup_taxonomies'] ) && $options['backup_taxonomies'] == '1';

		try {
			$backup_data = array(
				'version' => '1.0',
				'plugin' => 'gluon-download-manager',
				'date' => current_time( 'mysql' ),
				'site_url' => get_site_url(),
				'data' => array(),
			);

			// 1. Backup Downloads
			$backup_data['data']['downloads'] = $this->backup_downloads();

			// 2. Backup Logs
			if ( $backup_logs ) {
				$backup_data['data']['logs'] = $this->backup_logs();
			}

			// 3. Backup Settings
			if ( $backup_settings ) {
				$backup_data['data']['settings'] = $this->backup_settings();
			}

			// 4. Backup Taxonomies
			if ( $backup_taxonomies ) {
				$backup_data['data']['categories'] = $this->backup_categories();
				$backup_data['data']['tags'] = $this->backup_tags();
			}

			// Create backup file
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Intentionally using date() for filename timestamp in local timezone
			$filename = 'gdm-backup-' . date( 'Y-m-d-His' ) . '.json';
			$filepath = $this->backup_dir . '/' . $filename;
			
			$json_data = wp_json_encode( $backup_data, JSON_PRETTY_PRINT );
			file_put_contents( $filepath, $json_data );

			// Create download URL (using admin-ajax for authenticated download)
			$download_url = admin_url( 'admin-ajax.php?action=gdm_download_backup&file=' . urlencode( $filename ) . '&nonce=' . wp_create_nonce( 'gdm_download_backup_' . $filename ) );

			$message = sprintf(
				/* translators: %s: backup filename */
				__( 'Backup created successfully! File: %s', 'gluon-download-manager' ),
				'<strong>' . $filename . '</strong>'
			);

			return array(
				'success' => true,
				'message' => $message,
				'file_path' => $filepath,
				'file_url' => $download_url,
				'filename' => $filename,
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => '<strong>' . __( 'Backup failed:', 'gluon-download-manager' ) . '</strong> ' . $e->getMessage(),
			);
		}
	}

	private function backup_downloads() {
		$downloads = array();
		
		$posts = get_posts( array(
			'post_type' => 'gdm_downloads',
			'posts_per_page' => -1,
			'post_status' => 'any',
		) );

		foreach ( $posts as $post ) {
			$download_data = array(
				'post_data' => array(
					'post_title' => $post->post_title,
					'post_content' => $post->post_content,
					'post_status' => $post->post_status,
					'post_author' => $post->post_author,
					'post_date' => $post->post_date,
					'post_date_gmt' => $post->post_date_gmt,
					'post_modified' => $post->post_modified,
					'post_modified_gmt' => $post->post_modified_gmt,
					'post_password' => $post->post_password,
				),
				'meta_data' => get_post_meta( $post->ID ),
				'categories' => wp_get_post_terms( $post->ID, 'gdm_categories', array( 'fields' => 'slugs' ) ),
				'tags' => wp_get_post_terms( $post->ID, 'gdm_tags', array( 'fields' => 'slugs' ) ),
			);

			$downloads[] = $download_data;
		}

		return $downloads;
	}

	private function backup_logs() {
		global $wpdb;
		
		$logs_table = $wpdb->prefix . 'gdm_downloads';
		$logs = $wpdb->get_results( "SELECT * FROM $logs_table", ARRAY_A );

		return $logs ? $logs : array();
	}

	private function backup_settings() {
		$settings = array();
		
		$option_names = array(
			'gdm_downloads_options',
			'gdm_advanced_options',
			'gdm_global_options',
		);

		foreach ( $option_names as $option_name ) {
			$value = get_option( $option_name );
			if ( $value !== false ) {
				$settings[ $option_name ] = $value;
			}
		}

		return $settings;
	}

	private function backup_categories() {
		$categories = array();
		
		$terms = get_terms( array(
			'taxonomy' => 'gdm_categories',
			'hide_empty' => false,
		) );

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = array(
					'name' => $term->name,
					'slug' => $term->slug,
					'description' => $term->description,
				);
			}
		}

		return $categories;
	}

	private function backup_tags() {
		$tags = array();
		
		$terms = get_terms( array(
			'taxonomy' => 'gdm_tags',
			'hide_empty' => false,
		) );

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$tags[] = array(
					'name' => $term->name,
					'slug' => $term->slug,
					'description' => $term->description,
				);
			}
		}

		return $tags;
	}

	public function restore_backup( $files, $options = array() ) {
		global $wpdb;

		set_time_limit( 300 ); // 5 minutes

		$skip_existing = isset( $options['skip_existing'] ) && $options['skip_existing'] == '1';

		// Validate file upload
		if ( ! isset( $files['gdm_backup_file'] ) || $files['gdm_backup_file']['error'] !== UPLOAD_ERR_OK ) {
			return array(
				'success' => false,
				'message' => __( 'Error uploading backup file.', 'gluon-download-manager' ),
			);
		}

		$file = $files['gdm_backup_file'];

		// Validate file type
		if ( $file['type'] !== 'application/json' && substr( $file['name'], -5 ) !== '.json' ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid file type. Please upload a JSON backup file.', 'gluon-download-manager' ),
			);
		}

		// Read and decode backup file
		$json_content = file_get_contents( $file['tmp_name'] );
		$backup_data = json_decode( $json_content, true );

		if ( ! $backup_data || ! isset( $backup_data['data'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid backup file format.', 'gluon-download-manager' ),
			);
		}

		try {
			$restored = array(
				'downloads' => 0,
				'logs' => 0,
				'settings' => 0,
				'categories' => 0,
				'tags' => 0,
				'skipped' => 0,
			);

			// 1. Restore Categories first
			if ( isset( $backup_data['data']['categories'] ) ) {
				$restored['categories'] = $this->restore_categories( $backup_data['data']['categories'] );
			}

			// 2. Restore Tags
			if ( isset( $backup_data['data']['tags'] ) ) {
				$restored['tags'] = $this->restore_tags( $backup_data['data']['tags'] );
			}

			// 3. Restore Downloads
			if ( isset( $backup_data['data']['downloads'] ) ) {
				$result = $this->restore_downloads( $backup_data['data']['downloads'], $skip_existing );
				$restored['downloads'] = $result['restored'];
				$restored['skipped'] = $result['skipped'];
			}

			// 4. Restore Logs
			if ( isset( $backup_data['data']['logs'] ) ) {
				$restored['logs'] = $this->restore_logs( $backup_data['data']['logs'] );
			}

			// 5. Restore Settings
			if ( isset( $backup_data['data']['settings'] ) ) {
				$restored['settings'] = $this->restore_settings( $backup_data['data']['settings'] );
			}

			$message = '<h3>' . __( 'Restore Completed Successfully!', 'gluon-download-manager' ) . '</h3>';
			$message .= '<ul style="list-style: disc; margin-left: 20px;">';
			/* translators: %d: number of downloads restored */
			$message .= '<li><strong>' . sprintf( __( 'Downloads restored: %d', 'gluon-download-manager' ), $restored['downloads'] ) . '</strong></li>';
			/* translators: %d: number of logs restored */
			$message .= '<li><strong>' . sprintf( __( 'Logs restored: %d', 'gluon-download-manager' ), $restored['logs'] ) . '</strong></li>';
			/* translators: %d: number of categories restored */
			$message .= '<li><strong>' . sprintf( __( 'Categories restored: %d', 'gluon-download-manager' ), $restored['categories'] ) . '</strong></li>';
			/* translators: %d: number of tags restored */
			$message .= '<li><strong>' . sprintf( __( 'Tags restored: %d', 'gluon-download-manager' ), $restored['tags'] ) . '</strong></li>';
			/* translators: %d: number of settings restored */
			$message .= '<li><strong>' . sprintf( __( 'Settings restored: %d', 'gluon-download-manager' ), $restored['settings'] ) . '</strong></li>';
			
			if ( $restored['skipped'] > 0 ) {
				/* translators: %d: number of items skipped */
				$message .= '<li><em>' . sprintf( __( 'Items skipped (already exist): %d', 'gluon-download-manager' ), $restored['skipped'] ) . '</em></li>';
			}
			
			$message .= '</ul>';
			$message .= '<p>' . __( 'Page will reload in 2 seconds...', 'gluon-download-manager' ) . '</p>';

			return array(
				'success' => true,
				'message' => $message,
				'restored' => $restored,
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => '<strong>' . __( 'Restore failed:', 'gluon-download-manager' ) . '</strong> ' . $e->getMessage(),
			);
		}
	}

	private function restore_downloads( $downloads, $skip_existing = false ) {
		$restored = 0;
		$skipped = 0;

		foreach ( $downloads as $download_data ) {
			// Check if already exists
			if ( $skip_existing ) {
				$existing = get_page_by_title( $download_data['post_data']['post_title'], OBJECT, 'gdm_downloads' );
				if ( $existing ) {
					$skipped++;
					continue;
				}
			}

			// Create post
			$post_id = wp_insert_post( array_merge(
				$download_data['post_data'],
				array( 'post_type' => 'gdm_downloads' )
			), true );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			// Restore meta data
			if ( isset( $download_data['meta_data'] ) && is_array( $download_data['meta_data'] ) ) {
				foreach ( $download_data['meta_data'] as $meta_key => $meta_values ) {
					// Skip WordPress internal meta
					if ( substr( $meta_key, 0, 1 ) === '_' && $meta_key !== '_migrated_from_sdm_id' ) {
						continue;
					}
					
					foreach ( $meta_values as $meta_value ) {
						add_post_meta( $post_id, $meta_key, maybe_unserialize( $meta_value ) );
					}
				}
			}

			// Restore categories
			if ( isset( $download_data['categories'] ) && ! empty( $download_data['categories'] ) ) {
				wp_set_post_terms( $post_id, $download_data['categories'], 'gdm_categories' );
			}

			// Restore tags
			if ( isset( $download_data['tags'] ) && ! empty( $download_data['tags'] ) ) {
				wp_set_post_terms( $post_id, $download_data['tags'], 'gdm_tags' );
			}

			$restored++;
		}

		return array( 'restored' => $restored, 'skipped' => $skipped );
	}

	private function restore_logs( $logs ) {
		global $wpdb;
		
		$logs_table = $wpdb->prefix . 'gdm_downloads';
		$restored = 0;

		foreach ( $logs as $log ) {
			// Check if log already exists (by IP, post_id, and date_time)
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $logs_table 
				WHERE post_id = %d 
				AND visitor_ip = %s 
				AND date_time = %s",
				isset( $log['post_id'] ) ? $log['post_id'] : 0,
				isset( $log['visitor_ip'] ) ? $log['visitor_ip'] : '',
				isset( $log['date_time'] ) ? $log['date_time'] : ''
			) );

			if ( ! $existing ) {
				$wpdb->insert(
					$logs_table,
					array(
						'post_id' => isset( $log['post_id'] ) ? $log['post_id'] : 0,
						'post_title' => isset( $log['post_title'] ) ? $log['post_title'] : '',
						'file_url' => isset( $log['file_url'] ) ? $log['file_url'] : '',
						'visitor_ip' => isset( $log['visitor_ip'] ) ? $log['visitor_ip'] : '',
						'date_time' => isset( $log['date_time'] ) ? $log['date_time'] : current_time( 'mysql' ),
						'visitor_country' => isset( $log['visitor_country'] ) ? $log['visitor_country'] : '',
						'visitor_name' => isset( $log['visitor_name'] ) ? $log['visitor_name'] : '',
						'user_agent' => isset( $log['user_agent'] ) ? $log['user_agent'] : '',
						'referrer_url' => isset( $log['referrer_url'] ) ? $log['referrer_url'] : '',
					)
				);

				if ( $wpdb->insert_id ) {
					$restored++;
				}
			}
		}

		return $restored;
	}

	private function restore_settings( $settings ) {
		$restored = 0;

		foreach ( $settings as $option_name => $option_value ) {
			update_option( $option_name, $option_value );
			$restored++;
		}

		return $restored;
	}

	private function restore_categories( $categories ) {
		$restored = 0;

		foreach ( $categories as $category ) {
			// Check if category already exists
			$existing = term_exists( $category['slug'], 'gdm_categories' );
			if ( ! $existing ) {
				$result = wp_insert_term(
					$category['name'],
					'gdm_categories',
					array(
						'slug' => $category['slug'],
						'description' => $category['description'],
					)
				);

				if ( ! is_wp_error( $result ) ) {
					$restored++;
				}
			}
		}

		return $restored;
	}

	private function restore_tags( $tags ) {
		$restored = 0;

		foreach ( $tags as $tag ) {
			// Check if tag already exists
			$existing = term_exists( $tag['slug'], 'gdm_tags' );
			if ( ! $existing ) {
				$result = wp_insert_term(
					$tag['name'],
					'gdm_tags',
					array(
						'slug' => $tag['slug'],
						'description' => $tag['description'],
					)
				);

				if ( ! is_wp_error( $result ) ) {
					$restored++;
				}
			}
		}

		return $restored;
	}
}

// Handle backup file download
add_action( 'wp_ajax_gdm_download_backup', 'gdm_handle_backup_download' );
function gdm_handle_backup_download() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'gluon-download-manager' ) );
	}

	$filename = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';
	$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

	if ( ! $filename || ! wp_verify_nonce( $nonce, 'gdm_download_backup_' . $filename ) ) {
		wp_die( esc_html__( 'Invalid request.', 'gluon-download-manager' ) );
	}

	$upload_dir = wp_upload_dir();
	$backup_dir = $upload_dir['basedir'] . '/gdm-backups';
	$filepath = $backup_dir . '/' . $filename;

	if ( ! file_exists( $filepath ) ) {
		wp_die( esc_html__( 'Backup file not found.', 'gluon-download-manager' ) );
	}

	// Send file for download
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . filesize( $filepath ) );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );
	
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- readfile() is appropriate for streaming file download
	readfile( $filepath );
	exit;
}
