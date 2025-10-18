<?php
/**
 * GDM Tools Page - Migration, Backup & Restore
 */

class gdm_Admin_Tools_Page {

	public function __construct() {
		// Add the tools submenu page
		add_action( 'admin_menu', array( $this, 'add_tools_menu' ), 20 );
		
		// Handle AJAX requests
		add_action( 'wp_ajax_gdm_migrate_from_sdm', array( $this, 'ajax_migrate_from_sdm' ) );
		add_action( 'wp_ajax_gdm_backup_data', array( $this, 'ajax_backup_data' ) );
		add_action( 'wp_ajax_gdm_restore_data', array( $this, 'ajax_restore_data' ) );
	}

	public function add_tools_menu() {
		$gdm_admin_access_permission = get_gdm_admin_access_permission();
		
		add_submenu_page(
			'edit.php?post_type=gdm_downloads',
			__( 'Tools', 'gluon-download-manager' ),
			__( 'Tools', 'gluon-download-manager' ),
			$gdm_admin_access_permission,
			'gdm-tools',
			array( $this, 'render_tools_page' )
		);
	}

	public function render_tools_page() {
		// Get current tab
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'migration';
		
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'GDM Tools', 'gluon-download-manager' ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="?post_type=gdm_downloads&page=gdm-tools&tab=migration" 
				   class="nav-tab <?php echo $active_tab === 'migration' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Migration', 'gluon-download-manager' ); ?>
				</a>
				<a href="?post_type=gdm_downloads&page=gdm-tools&tab=backup" 
				   class="nav-tab <?php echo $active_tab === 'backup' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Backup', 'gluon-download-manager' ); ?>
				</a>
				<a href="?post_type=gdm_downloads&page=gdm-tools&tab=restore" 
				   class="nav-tab <?php echo $active_tab === 'restore' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Restore', 'gluon-download-manager' ); ?>
				</a>
			</h2>

			<div class="gdm-tools-content">
				<?php
				switch ( $active_tab ) {
					case 'migration':
						$this->render_migration_tab();
						break;
					case 'backup':
						$this->render_backup_tab();
						break;
					case 'restore':
						$this->render_restore_tab();
						break;
				}
				?>
			</div>
		</div>

		<style>
			.gdm-tools-content { margin-top: 20px; }
			.gdm-tool-box { 
				background: #fff; 
				border: 1px solid #ccd0d4; 
				padding: 20px; 
				margin-bottom: 20px;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
			}
			.gdm-tool-box h2 { margin-top: 0; }
			.gdm-progress { 
				display: none; 
				margin-top: 15px;
				padding: 10px;
				background: #f0f0f1;
				border-left: 4px solid #2271b1;
			}
			.gdm-result { 
				display: none; 
				margin-top: 15px;
				padding: 10px;
			}
			.gdm-result.success { 
				background: #d7ffd9;
				border-left: 4px solid #00a32a;
			}
			.gdm-result.error { 
				background: #ffd7d7;
				border-left: 4px solid #d63638;
			}
			.gdm-migration-stats { 
				background: #f0f0f1; 
				padding: 15px; 
				margin: 15px 0;
				border-radius: 4px;
			}
			.gdm-migration-stats strong { display: inline-block; min-width: 150px; }
		</style>
		<?php
	}

	public function render_migration_tab() {
		// Check if SDM plugin is active
		$sdm_active = $this->is_sdm_active();
		
		// Get migration stats
		$stats = $this->get_migration_stats();
		
		?>
		<div class="gdm-tool-box">
			<h2><?php esc_html_e( 'Migrate from Simple Download Monitor', 'gluon-download-manager' ); ?></h2>
			
			<?php if ( ! $sdm_active ) : ?>
				<div class="notice notice-warning">
					<p><strong><?php esc_html_e( 'Warning:', 'gluon-download-manager' ); ?></strong> 
					<?php esc_html_e( 'Simple Download Monitor plugin is not installed or activated.', 'gluon-download-manager' ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php esc_html_e( 'This tool will migrate all your download items, logs, settings, categories, and tags from Simple Download Monitor to Gluon Download Manager.', 'gluon-download-manager' ); ?></p>
			
			<div class="gdm-migration-stats">
				<h3><?php esc_html_e( 'Migration Overview', 'gluon-download-manager' ); ?></h3>
				<p><strong><?php esc_html_e( 'SDM Downloads:', 'gluon-download-manager' ); ?></strong> <?php echo esc_html( $stats['sdm_downloads'] ); ?></p>
				<p><strong><?php esc_html_e( 'SDM Log Entries:', 'gluon-download-manager' ); ?></strong> <?php echo esc_html( $stats['sdm_logs'] ); ?></p>
				<p><strong><?php esc_html_e( 'SDM Categories:', 'gluon-download-manager' ); ?></strong> <?php echo esc_html( $stats['sdm_categories'] ); ?></p>
				<p><strong><?php esc_html_e( 'SDM Tags:', 'gluon-download-manager' ); ?></strong> <?php echo esc_html( $stats['sdm_tags'] ); ?></p>
				<hr>
				<p><strong><?php esc_html_e( 'GDM Downloads:', 'gluon-download-manager' ); ?></strong> <?php echo esc_html( $stats['gdm_downloads'] ); ?></p>
				<p><strong><?php esc_html_e( 'GDM Log Entries:', 'gluon-download-manager' ); ?></strong> <?php echo esc_html( $stats['gdm_logs'] ); ?></p>
			</div>

			<div class="gdm-migration-options">
				<h3><?php esc_html_e( 'Migration Options', 'gluon-download-manager' ); ?></h3>
				<p>
					<label>
						<input type="checkbox" id="gdm_migrate_downloads" checked disabled>
						<?php esc_html_e( 'Migrate Download Items (Posts & Meta)', 'gluon-download-manager' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" id="gdm_migrate_logs" checked>
						<?php esc_html_e( 'Migrate Download Logs', 'gluon-download-manager' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" id="gdm_migrate_settings" checked>
						<?php esc_html_e( 'Migrate Settings', 'gluon-download-manager' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" id="gdm_migrate_taxonomies" checked>
						<?php esc_html_e( 'Migrate Categories & Tags', 'gluon-download-manager' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" id="gdm_skip_existing">
						<?php esc_html_e( 'Skip items that already exist (check by title)', 'gluon-download-manager' ); ?>
					</label>
				</p>
			</div>

			<p>
				<button type="button" class="button button-primary button-large" id="gdm_start_migration" <?php echo ! $sdm_active ? 'disabled' : ''; ?>>
					<?php esc_html_e( 'Start Migration', 'gluon-download-manager' ); ?>
				</button>
			</p>

			<div class="gdm-progress" id="gdm_migration_progress">
				<p><strong><?php esc_html_e( 'Migration in progress...', 'gluon-download-manager' ); ?></strong></p>
				<div id="gdm_migration_status"></div>
			</div>

			<div class="gdm-result" id="gdm_migration_result"></div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#gdm_start_migration').on('click', function() {
				if (!confirm('<?php esc_html_e( 'Are you sure you want to start the migration? This may take a few minutes.', 'gluon-download-manager' ); ?>')) {
					return;
				}

				var $btn = $(this);
				var $progress = $('#gdm_migration_progress');
				var $status = $('#gdm_migration_status');
				var $result = $('#gdm_migration_result');

				$btn.prop('disabled', true).text('<?php esc_html_e( 'Migrating...', 'gluon-download-manager' ); ?>');
				$progress.show();
				$result.hide();
				$status.html('<p><?php esc_html_e( 'Starting migration...', 'gluon-download-manager' ); ?></p>');

				var data = {
					action: 'gdm_migrate_from_sdm',
					nonce: '<?php echo esc_attr( wp_create_nonce( 'gdm_migrate_from_sdm' ) ); ?>',
					migrate_logs: $('#gdm_migrate_logs').is(':checked') ? 1 : 0,
					migrate_settings: $('#gdm_migrate_settings').is(':checked') ? 1 : 0,
					migrate_taxonomies: $('#gdm_migrate_taxonomies').is(':checked') ? 1 : 0,
					skip_existing: $('#gdm_skip_existing').is(':checked') ? 1 : 0
				};

				$.post(ajaxurl, data, function(response) {
					$btn.prop('disabled', false).text('<?php esc_html_e( 'Start Migration', 'gluon-download-manager' ); ?>');
					$progress.hide();
					
					if (response.success) {
						$result.removeClass('error').addClass('success').html(response.data.message).show();
						// Reload page after 2 seconds to show updated stats
						setTimeout(function() {
							location.reload();
						}, 2000);
					} else {
						$result.removeClass('success').addClass('error').html(response.data).show();
					}
				}).fail(function() {
					$btn.prop('disabled', false).text('<?php esc_html_e( 'Start Migration', 'gluon-download-manager' ); ?>');
					$progress.hide();
					$result.removeClass('success').addClass('error').html('<?php esc_html_e( 'An error occurred. Please try again.', 'gluon-download-manager' ); ?>').show();
				});
			});
		});
		</script>
		<?php
	}

	public function render_backup_tab() {
		?>
		<div class="gdm-tool-box">
			<h2><?php esc_html_e( 'Backup GDM Data', 'gluon-download-manager' ); ?></h2>
			<p><?php esc_html_e( 'Export all your GDM download items, logs, and settings to a JSON file.', 'gluon-download-manager' ); ?></p>
			
			<div class="gdm-backup-options">
				<h3><?php esc_html_e( 'Backup Options', 'gluon-download-manager' ); ?></h3>
				<p>
					<label>
						<input type="checkbox" id="gdm_backup_downloads" checked disabled>
						<?php esc_html_e( 'Backup Download Items', 'gluon-download-manager' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" id="gdm_backup_logs" checked>
						<?php esc_html_e( 'Backup Download Logs', 'gluon-download-manager' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" id="gdm_backup_settings" checked>
						<?php esc_html_e( 'Backup Settings', 'gluon-download-manager' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" id="gdm_backup_taxonomies" checked>
						<?php esc_html_e( 'Backup Categories & Tags', 'gluon-download-manager' ); ?>
					</label>
				</p>
			</div>

			<p>
				<button type="button" class="button button-primary button-large" id="gdm_create_backup">
					<?php esc_html_e( 'Create Backup', 'gluon-download-manager' ); ?>
				</button>
			</p>

			<div class="gdm-progress" id="gdm_backup_progress">
				<p><strong><?php esc_html_e( 'Creating backup...', 'gluon-download-manager' ); ?></strong></p>
			</div>

			<div class="gdm-result" id="gdm_backup_result"></div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#gdm_create_backup').on('click', function() {
				var $btn = $(this);
				var $progress = $('#gdm_backup_progress');
				var $result = $('#gdm_backup_result');

				$btn.prop('disabled', true).text('<?php esc_html_e( 'Creating...', 'gluon-download-manager' ); ?>');
				$progress.show();
				$result.hide();

				var data = {
					action: 'gdm_backup_data',
					nonce: '<?php echo esc_attr( wp_create_nonce( 'gdm_backup_data' ) ); ?>',
					backup_logs: $('#gdm_backup_logs').is(':checked') ? 1 : 0,
					backup_settings: $('#gdm_backup_settings').is(':checked') ? 1 : 0,
					backup_taxonomies: $('#gdm_backup_taxonomies').is(':checked') ? 1 : 0
				};

				$.post(ajaxurl, data, function(response) {
					$btn.prop('disabled', false).text('<?php esc_html_e( 'Create Backup', 'gluon-download-manager' ); ?>');
					$progress.hide();
					
					if (response.success) {
						var downloadUrl = response.data.file_url;
						var message = '<p><strong><?php esc_html_e( 'Backup created successfully!', 'gluon-download-manager' ); ?></strong></p>';
						message += '<p><a href="' + downloadUrl + '" class="button button-primary" download><?php esc_html_e( 'Download Backup File', 'gluon-download-manager' ); ?></a></p>';
						$result.removeClass('error').addClass('success').html(message).show();
					} else {
						$result.removeClass('success').addClass('error').html(response.data).show();
					}
				}).fail(function() {
					$btn.prop('disabled', false).text('<?php esc_html_e( 'Create Backup', 'gluon-download-manager' ); ?>');
					$progress.hide();
					$result.removeClass('success').addClass('error').html('<?php esc_html_e( 'An error occurred. Please try again.', 'gluon-download-manager' ); ?>').show();
				});
			});
		});
		</script>
		<?php
	}

	public function render_restore_tab() {
		?>
		<div class="gdm-tool-box">
			<h2><?php esc_html_e( 'Restore GDM Data', 'gluon-download-manager' ); ?></h2>
			<p><?php esc_html_e( 'Import GDM data from a previously created backup file.', 'gluon-download-manager' ); ?></p>
			
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Warning:', 'gluon-download-manager' ); ?></strong> 
				<?php esc_html_e( 'Restoring will add the backed up data to your current data. It will not delete existing items.', 'gluon-download-manager' ); ?></p>
			</div>

			<form id="gdm_restore_form" enctype="multipart/form-data">
				<p>
					<label for="gdm_backup_file">
						<strong><?php esc_html_e( 'Select Backup File:', 'gluon-download-manager' ); ?></strong>
					</label><br>
					<input type="file" id="gdm_backup_file" name="gdm_backup_file" accept=".json" required>
				</p>

				<p>
					<label>
						<input type="checkbox" id="gdm_restore_skip_existing" checked>
						<?php esc_html_e( 'Skip items that already exist (check by title)', 'gluon-download-manager' ); ?>
					</label>
				</p>

				<p>
					<button type="submit" class="button button-primary button-large">
						<?php esc_html_e( 'Restore Backup', 'gluon-download-manager' ); ?>
					</button>
				</p>
			</form>

			<div class="gdm-progress" id="gdm_restore_progress">
				<p><strong><?php esc_html_e( 'Restoring backup...', 'gluon-download-manager' ); ?></strong></p>
			</div>

			<div class="gdm-result" id="gdm_restore_result"></div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#gdm_restore_form').on('submit', function(e) {
				e.preventDefault();

				if (!confirm('<?php esc_html_e( 'Are you sure you want to restore this backup?', 'gluon-download-manager' ); ?>')) {
					return;
				}

				var $form = $(this);
				var $btn = $form.find('button[type="submit"]');
				var $progress = $('#gdm_restore_progress');
				var $result = $('#gdm_restore_result');
				var formData = new FormData();

				var file = $('#gdm_backup_file')[0].files[0];
				if (!file) {
					alert('<?php esc_html_e( 'Please select a backup file.', 'gluon-download-manager' ); ?>');
					return;
				}

				formData.append('action', 'gdm_restore_data');
				formData.append('nonce', '<?php echo esc_attr( wp_create_nonce( 'gdm_restore_data' ) ); ?>');
				formData.append('gdm_backup_file', file);
				formData.append('skip_existing', $('#gdm_restore_skip_existing').is(':checked') ? 1 : 0);

				$btn.prop('disabled', true).text('<?php esc_html_e( 'Restoring...', 'gluon-download-manager' ); ?>');
				$progress.show();
				$result.hide();

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						$btn.prop('disabled', false).text('<?php esc_html_e( 'Restore Backup', 'gluon-download-manager' ); ?>');
						$progress.hide();
						
						if (response.success) {
							$result.removeClass('error').addClass('success').html(response.data.message).show();
							$form[0].reset();
							// Reload page after 2 seconds
							setTimeout(function() {
								location.reload();
							}, 2000);
						} else {
							$result.removeClass('success').addClass('error').html(response.data).show();
						}
					},
					error: function() {
						$btn.prop('disabled', false).text('<?php esc_html_e( 'Restore Backup', 'gluon-download-manager' ); ?>');
						$progress.hide();
						$result.removeClass('success').addClass('error').html('<?php esc_html_e( 'An error occurred. Please try again.', 'gluon-download-manager' ); ?>').show();
					}
				});
			});
		});
		</script>
		<?php
	}

	// Helper Functions

	private function is_sdm_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		return is_plugin_active( 'simple-download-monitor/main.php' ) || 
		       class_exists( 'sdm_downloads' ) ||
		       post_type_exists( 'sdm_downloads' );
	}

	private function get_migration_stats() {
		global $wpdb;
		
		$stats = array(
			'sdm_downloads' => 0,
			'sdm_logs' => 0,
			'sdm_categories' => 0,
			'sdm_tags' => 0,
			'gdm_downloads' => 0,
			'gdm_logs' => 0,
		);

		// Count SDM data
		$stats['sdm_downloads'] = wp_count_posts( 'sdm_downloads' )->publish ?? 0;
		$stats['sdm_logs'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sdm_downloads" );
		
		$sdm_cats = get_terms( array( 'taxonomy' => 'sdm_categories', 'hide_empty' => false ) );
		$stats['sdm_categories'] = is_array( $sdm_cats ) ? count( $sdm_cats ) : 0;
		
		$sdm_tags = get_terms( array( 'taxonomy' => 'sdm_tags', 'hide_empty' => false ) );
		$stats['sdm_tags'] = is_array( $sdm_tags ) ? count( $sdm_tags ) : 0;

		// Count GDM data
		$stats['gdm_downloads'] = wp_count_posts( 'gdm_downloads' )->publish ?? 0;
		$stats['gdm_logs'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gdm_downloads" );

		return $stats;
	}

	// AJAX Handlers (to be implemented in next file)

	public function ajax_migrate_from_sdm() {
		check_ajax_referer( 'gdm_migrate_from_sdm', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'gluon-download-manager' ) );
		}

		// Include the migration handler
		require_once WP_GLUON_DL_MANAGER_PATH . 'includes/admin-side/gdm-migration-handler.php';
		
		$migrator = new gdm_Migration_Handler();
		$result = $migrator->migrate_from_sdm( $_POST );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	public function ajax_backup_data() {
		check_ajax_referer( 'gdm_backup_data', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'gluon-download-manager' ) );
		}

		// Include the backup handler
		require_once WP_GLUON_DL_MANAGER_PATH . 'includes/admin-side/gdm-backup-handler.php';
		
		$backup = new gdm_Backup_Handler();
		$result = $backup->create_backup( $_POST );

		if ( $result['success'] ) {
			wp_send_json_success( array( 
				'message' => $result['message'],
				'file_url' => $result['file_url']
			) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	public function ajax_restore_data() {
		check_ajax_referer( 'gdm_restore_data', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'gluon-download-manager' ) );
		}

		// Include the restore handler
		require_once WP_GLUON_DL_MANAGER_PATH . 'includes/admin-side/gdm-backup-handler.php';
		
		$backup = new gdm_Backup_Handler();
		$result = $backup->restore_backup( $_FILES, $_POST );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}
}

new gdm_Admin_Tools_Page();