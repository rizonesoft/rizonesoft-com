<?php
/*
 * Creates/adds the other admin menu page links to the main SDM custom post type menu
 */

 function gdm_handle_admin_menu() {
	$gdm_admin_access_permission =  get_gdm_admin_access_permission();
	$gdm_pages_capability = apply_filters("gdm_pages_capability", $gdm_admin_access_permission);

	// Create the 'logs' and 'settings' submenu pages
	add_submenu_page( 'edit.php?post_type=gdm_downloads', __( 'Logs', 'gluon-download-manager' ), __( 'Logs', 'gluon-download-manager' ), $gdm_pages_capability, 'gdm-logs', 'gdm_create_logs_page' );
	add_submenu_page( 'edit.php?post_type=gdm_downloads', __( 'Stats', 'gluon-download-manager' ), __( 'Stats', 'gluon-download-manager' ), $gdm_pages_capability, 'gdm-stats', 'gdm_create_stats_page' );
	add_submenu_page( 'edit.php?post_type=gdm_downloads', __( 'Settings', 'gluon-download-manager' ), __( 'Settings', 'gluon-download-manager' ), $gdm_pages_capability, 'gdm-settings', 'gdm_create_settings_page' );
	add_submenu_page( 'edit.php?post_type=gdm_downloads', __( 'Add-ons', 'gluon-download-manager' ), __( 'Add-ons', 'gluon-download-manager' ), $gdm_pages_capability, 'gdm-addons', 'gdm_create_addons_page' );
}

add_filter( 'allowed_options', 'gdm_admin_menu_function_hook' );

add_action( 'admin_enqueue_scripts', 'gdm_admin_menu_enqueue_scripts' );

function gdm_admin_menu_enqueue_scripts( $hook_suffix ) {
	switch ( $hook_suffix ) {
		case 'gdm_downloads_page_gdm-stats':
			wp_register_script( 'gdm-admin-stats', WP_GLUON_DL_MANAGER_URL . '/js/gdm_admin_stats.js', array( 'jquery' ), WP_GLUON_DL_MANAGER_VERSION, true );
			wp_enqueue_script( 'gdm-admin-stats' );
			break;
		default:
			break;
	}
}

/**
 * Its hook for add advanced tab, and working on saving options to db, if not used, you receive error "options page not found"
 *
 * @param array $allowed_options
 * @return string
 */
function gdm_admin_menu_function_hook( $allowed_options = array() ) {
	$allowed_options['recaptcha_v3_options_section'] = array( 'gdm_advanced_options' );
	$allowed_options['recaptcha_options_section'] = array( 'gdm_advanced_options' );
	$allowed_options['termscond_options_section'] = array( 'gdm_advanced_options' );
	$allowed_options['adsense_options_section']   = array( 'gdm_advanced_options' );
	$allowed_options['maps_api_options_section']  = array( 'gdm_advanced_options' );

	return $allowed_options;
}

/*
 * Settings menu page
 */

function gdm_create_settings_page() {

	echo '<div class="wrap">';
	//echo '<div id="poststuff"><div id="post-body">';
	?>
	<style>
		div.gdm-settings-grid {
		display: inline-block;
		}
		div.gdm-main-cont {
		width: 80%;
		}
		div.gdm-sidebar-cont {
		width: 19%;
		float: right;
		}
		div#poststuff {
		min-width: 19%;
		}
		.gdm-stars-container {
		text-align: center;
		margin-top: 10px;
		}
		.gdm-stars-container span {
		vertical-align: text-top;
		color: #ffb900;
		}
		.gdm-stars-container a {
		text-decoration: none;
		}
		@media (max-width: 782px) {
		div.gdm-settings-grid {
			display: block;
			float: none;
			width: 100%;
		}
		}
	</style>
	<h1><?php esc_html_e( 'Gluon Download Manager Settings', 'gluon-download-manager' ); ?></h1>

	<?php
	$wpgdm_plugin_tabs = array(
		'gdm-settings'                          => __( 'General Settings', 'gluon-download-manager' ),
		'gdm-settings&action=advanced-settings' => __( 'Advanced Settings', 'gluon-download-manager' ),
		'gdm-settings&action=file-protection' 	=> __( 'Enhanced File Protection', 'gluon-download-manager' ),
	);
	$current           = '';
	if ( isset( $_GET['page'] ) ) {
                $current = isset( $_GET['page'] ) ? sanitize_text_field( stripslashes ( $_GET['page'] ) ) : '';
		if ( isset( $_GET['action'] ) ) {
                        $action = isset( $_GET['action'] ) ? sanitize_text_field( stripslashes ( $_GET['action'] ) ) : '';
			$current .= '&action=' . $action;
		}
	}
	$nav_tabs  = '';
	$nav_tabs .= '<h2 class="nav-tab-wrapper">';
	foreach ( $wpgdm_plugin_tabs as $location => $tabname ) {
		if ( $current === $location ) {
			$class = ' nav-tab-active';
		} else {
			$class = '';
		}
		$nav_tabs .= '<a class="nav-tab' . esc_attr( $class ) . '" href="?post_type=gdm_downloads&page=' . esc_attr( $location ) . '">' . esc_attr( $tabname ) . '</a>';
	}
	$nav_tabs .= '</h2>';

	echo wp_kses_post( $nav_tabs );
	?>
	<div class="gdm-settings-cont">
		<div class="gdm-settings-grid gdm-main-cont">
		<?php
		if ( isset( $_GET['action'] ) ) {
            $action = isset( $_GET['action'] ) ? sanitize_text_field( stripslashes ( $_GET['action'] ) ) : '';
			switch ( $action ) {
				case 'advanced-settings':
					gdm_admin_menu_advanced_settings();
					break;
				case 'file-protection':
					include_once WP_GLUON_DL_MANAGER_PATH . 'includes/admin-side/gdm-admin-file-protection-settings-page.php';
					new gdm_Admin_File_Protection_Settings_Page();
					break;
			}
		} else {
			gdm_admin_menu_general_settings();
		}
		?>
		</div>
		<div id="poststuff" class="gdm-settings-grid gdm-sidebar-cont">
		<div class="postbox" style="min-width: inherit;">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Plugin Documentation', 'gluon-download-manager' ); ?></label></h3>
			<div class="inside">
			<?php
			echo wp_kses(
				// translators: %s = URL to documentation page
				sprintf( __( 'Please read the <a target="_blank" href="%s">Simple Download Monitor</a> plugin setup instructions and tutorials to learn how to configure and use it.', 'gluon-download-manager' ), 'https://simple-download-monitor.com/download-monitor-tutorials/' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
			</div>
		</div>
		<div class="postbox" style="min-width: inherit;">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Add-ons', 'gluon-download-manager' ); ?></label></h3>
			<div class="inside">
			<?php
			echo wp_kses(
				// translators: %s = URL to add-ons page
				sprintf( __( 'Want additional functionality? Check out our <a target="_blank" href="%s">Add-Ons!</a>', 'gluon-download-manager' ), 'edit.php?post_type=gdm_downloads&page=gdm-addons' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
			</div>
		</div>
		<div class="postbox" style="min-width: inherit;">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Help Us Keep the Plugin Free & Maintained', 'gluon-download-manager' ); ?></label></h3>
			<div class="inside">
			<?php
			echo wp_kses(
				// translators: %s = URL to rating page
				sprintf( __( 'Like the plugin? Please give it a good <a href="%s" target="_blank">rating!</a>', 'gluon-download-manager' ), 'https://wordpress.org/support/plugin/simple-download-monitor/reviews/?filter=5' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
			<div class="gdm-stars-container">
				<a href="https://wordpress.org/support/plugin/simple-download-monitor/reviews/?filter=5" target="_blank">
				<span class="dashicons dashicons-star-filled"></span>
				<span class="dashicons dashicons-star-filled"></span>
				<span class="dashicons dashicons-star-filled"></span>
				<span class="dashicons dashicons-star-filled"></span>
				<span class="dashicons dashicons-star-filled"></span>
				</a>
			</div>
			</div>
		</div>
		<div class="postbox" style="min-width: inherit;">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Our Other Plugins', 'gluon-download-manager' ); ?></label></h3>
			<div class="inside">
			<?php
			echo wp_kses(
				// translators: %s = URL to other plugins page
				sprintf( __( 'Check out <a target="_blank" href="%s">our other plugins</a>', 'gluon-download-manager' ), 'https://www.tipsandtricks-hq.com/development-center' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
			</div>
		</div>
		<div class="postbox" style="min-width: inherit;">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Want to Sell Digital Downloads?', 'gluon-download-manager' ); ?></label></h3>
			<div class="inside">
			<?php
                        _e( 'Check out the fast and simple ', 'gluon-download-manager' );
			echo wp_kses(
				// translators: %s = Twitter URL
				sprintf( __( '<a target="_blank" href="%s">WP Express Checkout</a> plugin.', 'gluon-download-manager' ), 'https://wordpress.org/plugins/wp-express-checkout/' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
			</div>
		</div>
		</div>
	</div>

	<div class="gdm_yellow_box">
		<p>
			<?php esc_html_e( 'If you need an easy to use and supported plugin for selling your digital items then check out our ', 'gluon-download-manager' ); ?>
			<a href="https://wordpress.org/plugins/wp-express-checkout/" target="_blank"><?php esc_html_e( 'WP Express Checkout', 'gluon-download-manager' ); ?></a>
			or <a href="https://wordpress.org/plugins/stripe-payments/" target="_blank"><?php esc_html_e( 'Stripe Payments', 'gluon-download-manager' ); ?></a>
			or <a href="https://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059" target="_blank"><?php esc_html_e( 'WP eStore', 'gluon-download-manager' ); ?></a> Plugin.
		</p>
	</div>

	<?php
	echo '</div>'; //end of wrap
}

function gdm_admin_menu_general_settings() {
	?>
	<!-- BEGIN GENERAL OPTIONS -->
	<form method="post" action="options.php">
	<?php
	settings_fields( 'gdm_downloads_options' );
	do_settings_sections( 'general_options_section' );
	submit_button();
	?>
	</form>
	<!-- END GENERAL OPTIONS -->

	<!-- BEGIN USER LOGIN OPTIONS -->
	<form method="post" action="options.php">
	<?php
	settings_fields( 'gdm_downloads_options' );
	do_settings_sections( 'user_login_options_section' );
	submit_button();
	?>
	</form>
	<!-- END USER LOGIN OPTIONS -->

	<!-- BEGIN ADMIN OPTIONS & COLORS -->
	<form method="post" action="options.php">
	<?php
	settings_fields( 'gdm_downloads_options' );
	do_settings_sections( 'admin_options_section' );
	do_settings_sections( 'gdm_colors_section' );
	submit_button();
	?>
	</form>
	<!-- END ADMIN OPTIONS & COLORS -->

	<!-- BEGIN DEBUG OPTIONS -->
	<form method="post" action="options.php">
	<?php
	settings_fields( 'gdm_downloads_options' );
	do_settings_sections( 'gdm_debug_section' );
	submit_button();
	?>
	</form>
	<!-- END DEBUG OPTIONS -->
	
	<!-- BEGIN DELDATA OPTIONS -->
	<?php
	do_settings_sections( 'gdm_deldata_section' );

	$deldataNonce = wp_create_nonce( 'gdm_delete_data' );
	?>
	<!-- END DELDATA OPTIONS -->

	<script>
		jQuery('button#gdmDeleteData').click(function (e) {
		e.preventDefault();
		jQuery(this).attr('disabled', 'disabled');
		if (confirm("<?php echo esc_js( __( "Are you sure want to delete all plugin's data and deactivate plugin?", 'gluon-download-manager' ) ); ?>")) {
			jQuery.post(ajaxurl,
				{'action': 'gdm_delete_data', 'nonce': '<?php echo esc_js( $deldataNonce ); ?>'},
				function (result) {
				if (result === '1') {
					alert('<?php echo esc_js( __( 'Data has been deleted and plugin deactivated. Click OK to go to Plugins page.', 'gluon-download-manager' ) ); ?>');
					jQuery(location).attr('href', '<?php echo esc_js( get_admin_url() . 'plugins.php' ); ?>');
					return true;
				} else {
					alert('<?php echo esc_js( __( 'Error occurred.', 'gluon-download-manager' ) ); ?>');
				}
				});
		} else {
			jQuery(this).removeAttr('disabled');
		}
		});
		jQuery('a#gdm-reset-log').click(function (e) {
		e.preventDefault();
		jQuery.post(ajaxurl,
			{'action': 'gdm_reset_log', 'nonce': '<?php echo esc_js( $deldataNonce ); ?>'},
			function (result) {
				if (result === '1') {
				alert('Log has been reset.');
				}
			});
		});
	</script>
	<?php
}

function gdm_admin_menu_advanced_settings() {
	//More advanced options will be added here in the future.
	// Each section has its own form with save button
	?>
	
	<!-- BEGIN RECAPTCHA V3 OPTIONS -->
	<form method="post" action="options.php">
	<?php
	settings_fields( 'recaptcha_v3_options_section' );
	do_settings_sections( 'recaptcha_v3_options_section' );
	submit_button();
	?>
	</form>
	<!-- END RECAPTCHA V3 OPTIONS -->

	<!-- BEGIN RECAPTCHA V2 OPTIONS -->
	<form method="post" action="options.php">
	<?php
	settings_fields( 'recaptcha_options_section' );
	do_settings_sections( 'recaptcha_options_section' );
	submit_button();
	?>
	</form>
	<!-- END RECAPTCHA V2 OPTIONS -->

	<!-- BEGIN TERMS & CONDITIONS OPTIONS -->
	<form method="post" action="options.php">
	<?php
	settings_fields( 'termscond_options_section' );
	do_settings_sections( 'termscond_options_section' );
	submit_button();
	?>
	</form>
	<!-- END TERMS & CONDITIONS OPTIONS -->

	<!-- BEGIN ADSENSE OPTIONS -->
	<form method="post" action="options.php">
	<?php
	settings_fields( 'adsense_options_section' );
	do_settings_sections( 'adsense_options_section' );
	submit_button();
	?>
	</form>
	<!-- END ADSENSE OPTIONS -->

	<!-- BEGIN MAPS API OPTIONS -->
	<form method="post" action="options.php">
	<?php
	settings_fields( 'maps_api_options_section' );
	do_settings_sections( 'maps_api_options_section' );
	submit_button();
	?>
	</form>
	<!-- END MAPS API OPTIONS -->
	
	<?php
}

/**
 * Logs menu page
 */
function gdm_create_logs_page() {
	$dashboard_access_role = get_gdm_admin_access_permission();
	if ( ! current_user_can( $dashboard_access_role ) ) {
		wp_die( 'You do not have permission to access this settings page.' );
	}

	echo '<div class="wrap">';

	$gdm_logs_menu_tabs = array(
		'gdm-logs' => array(
			'name' => __( 'Main Logs', 'gluon-download-manager' ),
			'title' =>__( 'Download Logs', 'gluon-download-manager' ),
		),
		'gdm-logs-by-download' => array(
			'name' => __( 'Specific Item Logs', 'gluon-download-manager' ),
			'title' =>__( 'Specific Download Item Logs', 'gluon-download-manager' ),
		),
		'gdm-logs-export' => array(
			'name' =>  __( 'Export', 'gluon-download-manager' ),
			'title' =>__( 'Export Download Log Entries', 'gluon-download-manager' ),
		),
	);
	
	$current = 'gdm-logs';
	if ( isset( $_GET['page'] ) && isset( $_GET['tab'] ) ) {
		$current = sanitize_text_field( $_GET['tab'] );
	}

	$content = '';
	foreach ( $gdm_logs_menu_tabs as $tab_slug => $tab ) {
		$tab_query = '&tab=' . $tab_slug;
		if ( $current === $tab_slug ) {
			$class = ' nav-tab-active';
		} else {
			$class = '';
		}
		$content .= '<a class="nav-tab' . $class . '" href="?post_type=gdm_downloads&page=gdm-logs' . $tab_query . '">' . $tab['name'] . '</a>';
	}

	echo "<h2>" . esc_html__( $gdm_logs_menu_tabs[$current]['title'], 'gluon-download-manager' )."</h2>";

	echo '<h2 class="nav-tab-wrapper">';
	echo wp_kses(
		$content,
		array(
			'a' => array(
				'href'  => array(),
				'class' => array(),
			),
		)
	);
	echo '</h2>';

	if ( isset( $_GET['tab'] ) ) {
		switch ( $_GET['tab'] ) {
			case 'gdm-logs-by-download':
				include_once WP_GLUON_DL_MANAGER_PATH . 'includes/admin-side/gdm-admin-individual-item-logs-page.php';
				gdm_handle_individual_logs_tab_page();
				break;
			case 'gdm-logs-export':
				include_once WP_GLUON_DL_MANAGER_PATH . 'includes/admin-side/gdm-admin-export-logs.php';
				gdm_logs_export_tab_page();
				break;
			default:
				gdm_handle_logs_main_tab_page();
				break;
		}
	} else {
		gdm_handle_logs_main_tab_page();
	}

	echo '</div>'; //<!-- end of wrap -->
}

function gdm_handle_logs_main_tab_page() {
	global $wpdb;
	$advanced_options = get_option( 'gdm_advanced_options' );

	if ( isset( $_POST['gdm_reset_log_entries'] ) && check_admin_referer( null, 'gdm_delete_all_logs_nonce' ) ) {
		//Reset log entries BUT preserve download counts
		
		// First, ensure all download counts are cached in post meta before deleting logs
		$download_posts = get_posts( array(
			'post_type' => 'gdm_downloads',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'fields' => 'ids'
		) );
		
		foreach ( $download_posts as $download_id ) {
			// Force recalculation and caching of current count
			$table = $wpdb->prefix . 'gdm_downloads';
			$db_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table . ' WHERE post_id=%d', $download_id ) );
			$get_offset = get_post_meta( $download_id, 'gdm_count_offset', true );
			if ( $get_offset && $get_offset != '' ) {
				$db_count = $db_count + intval( $get_offset );
			}
			update_post_meta( $download_id, 'gdm_download_count', $db_count );
		}
		
		// Now truncate the logs table (download counts are safely stored in post meta)
		$table_name = $wpdb->prefix . 'gdm_downloads';
		$query      = "TRUNCATE $table_name";
		$result     = $wpdb->query( $query );
		
		echo '<div id="message" class="updated fade"><p>';
		esc_html_e( 'Download log entries deleted! Download counts have been preserved.', 'gluon-download-manager' );
		echo '</p></div>';
	}

	if ( isset( $_POST['gdm_trim_log_entries'] ) && check_admin_referer( null, 'gdm_delete_logs_nonce' ) ) {
		//Trim log entries
		$interval_val  = intval( $_POST['gdm_trim_log_entries_days'] );
		$interval_unit = 'DAY';
		$cur_time      = current_time( 'mysql' );

		//Save the interval value for future use on this site.
		$advanced_options ['gdm_trim_log_entries_days_saved'] = $interval_val;
		update_option( 'gdm_advanced_options', $advanced_options );

		//Trim entries in the DB table.
		$table_name = $wpdb->prefix . 'gdm_downloads';
		$cond       = " DATE_SUB('$cur_time',INTERVAL '$interval_val' $interval_unit) > date_time";
		$result     = $wpdb->query( "DELETE FROM $table_name WHERE $cond", OBJECT );

		echo '<div id="message" class="updated fade"><p>';
		esc_html_e( 'Download log entries trimmed!', 'gluon-download-manager' );
		echo '</p></div>';
	}

	if ( isset( $_POST['gdm_initialize_cached_counts'] ) && check_admin_referer( null, 'gdm_init_counts_nonce' ) ) {
		// Initialize cached download counts for all downloads
		$download_posts = get_posts( array(
			'post_type' => 'gdm_downloads',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'fields' => 'ids'
		) );
		
		$updated_count = 0;
		foreach ( $download_posts as $download_id ) {
			$table = $wpdb->prefix . 'gdm_downloads';
			$db_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table . ' WHERE post_id=%d', $download_id ) );
			$get_offset = get_post_meta( $download_id, 'gdm_count_offset', true );
			if ( $get_offset && $get_offset != '' ) {
				$db_count = $db_count + intval( $get_offset );
			}
			update_post_meta( $download_id, 'gdm_download_count', $db_count );
			$updated_count++;
		}
		
		echo '<div id="message" class="updated fade"><p>';
		printf( esc_html__( 'Cached download counts initialized for %d downloads!', 'gluon-download-manager' ), $updated_count );
		echo '</p></div>';
	}

	//Set the default log trim days value
	$trim_log_entries_days_default_val = isset( $advanced_options ['gdm_trim_log_entries_days_saved'] ) ? $advanced_options ['gdm_trim_log_entries_days_saved'] : '30';

	/* Display the logs table */
	//Create an instance of our package class...
	$sdmListTable = new gdm_List_Table();
	//Fetch, prepare, sort, and filter our data...
	$sdmListTable->prepare_items();
	?>

	<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
		<p><?php esc_html_e( 'This page lists all tracked downloads.', 'gluon-download-manager' ); ?></p>
	</div>

	<div id="poststuff"><div id="post-body">

		<!-- Log reset button -->
		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Reset Download Log Entries', 'gluon-download-manager' ); ?></label></h3>
			<div class="inside">
			<form method="post" action="" onSubmit="return confirm('Are you sure you want to reset all the log entries?');" >
				<div class="submit">
				<input type="submit" class="button" name="gdm_reset_log_entries" value="<?php esc_html_e( 'Reset Log Entries', 'gluon-download-manager' ); ?>" />
						<p class="description"><?php esc_html_e( 'This button will reset all log entries. It can useful if you want to export all your log entries then reset them.', 'gluon-download-manager' ); ?></p>
				</div>
				<?php wp_nonce_field( null, 'gdm_delete_all_logs_nonce' ); ?>
			</form>

			<form method="post" action="" onSubmit="return confirm('Are you sure you want to trim log entries?');" >
				<div class="submit">
						<?php esc_html_e( 'Delete Log Entries Older Than ', 'gluon-download-manager' ); ?><input name="gdm_trim_log_entries_days" type="text" size="4" value="<?php echo esc_attr( $trim_log_entries_days_default_val ); ?>"/><?php esc_html_e( ' Days', 'gluon-download-manager' ); ?>
				<input type="submit" class="button" name="gdm_trim_log_entries" value="<?php esc_html_e( 'Trim Log Entries', 'gluon-download-manager' ); ?>" />
						<p class="description"><?php esc_html_e( 'This option can be useful if you want to delete older log entries. Enter a number of days value then click the Trim Log Entries button.', 'gluon-download-manager' ); ?></p>
				</div>
				<?php wp_nonce_field( null, 'gdm_delete_logs_nonce' ); ?>
			</form>

			<hr style="margin: 20px 0;" />

			<form method="post" action="">
				<div class="submit">
				<input type="submit" class="button button-primary" name="gdm_initialize_cached_counts" value="<?php esc_html_e( 'Initialize Cached Download Counts', 'gluon-download-manager' ); ?>" />
						<p class="description"><?php esc_html_e( 'Click this button to cache download counts in post meta. This improves performance and preserves counts when logs are deleted. Run this once after updating the plugin.', 'gluon-download-manager' ); ?></p>
				</div>
				<?php wp_nonce_field( null, 'gdm_init_counts_nonce' ); ?>
			</form>
			</div>
		</div>

		</div></div><!-- end of .poststuff and .post-body -->

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="gdm_downloads-filter" method="post">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
		<!-- Now we can render the completed list table -->
	<?php $sdmListTable->display(); ?>
	</form>

	<script type="text/javascript">
		jQuery(document).ready(function ($) {
		$('.fade').click(function () {
			$(this).fadeOut('slow');
		});
		});
	</script>
	<?php
}

function gdm_create_stats_page() {

	$main_opts = get_option( 'gdm_downloads_options' );

	if ( isset( $main_opts['admin_no_logs'] ) ) {
		?>
	<div class="notice notice-warning"><p><b>Download Logs are disabled in <a href="?post_type=gdm_downloads&page=settings">plugin settings</a>. Please enable Download Logs to see current stats.</b></p></div>
		<?php
	}
	wp_enqueue_script( 'gdm_google_charts' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style( 'gdm_jquery_ui_style' );

	if ( isset( $_POST['gdm_stats_start_date'] ) ) {
		$start_date = sanitize_text_field( $_POST['gdm_stats_start_date'] );
	} else {
		// default start date is 30 days back
		$start_date = date( 'Y-m-d', time() - 60 * 60 * 24 * 30 );
	}

	if ( isset( $_POST['gdm_stats_end_date'] ) ) {
		$end_date = sanitize_text_field( $_POST['gdm_stats_end_date'] );
	} else {
		$end_date = date( 'Y-m-d', time() );
	}
	if ( isset( $_REQUEST['gdm_active_tab'] ) && ! empty( $_REQUEST['gdm_active_tab'] ) ) {
		$active_tab = sanitize_text_field( $_REQUEST['gdm_active_tab'] );
	} else {
		$active_tab = 'datechart';
	}
	$downloads_by_date = gdm_get_downloads_by_date( $start_date, $end_date, false );

	$downloads_by_country = gdm_get_downloads_by_country( $start_date, $end_date, false );

	$adv_opts = get_option( 'gdm_advanced_options' );

	$api_key = '';
	if ( isset( $adv_opts['maps_api_key'] ) ) {
		$api_key = $adv_opts['maps_api_key'];
	}
	?>
	<style>
		#gdm-api-key-warning {
		padding: 5px 0;
		width: auto;
		margin: 5px 0;
		display: none;
		}
	</style>
	<div class="wrap">
		<h2><?php esc_html_e( 'Stats', 'gluon-download-manager' ); ?></h2>
		<div id="poststuff"><div id="post-body">

			<div class="postbox">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Choose Date Range (yyyy-mm-dd)', 'gluon-download-manager' ); ?></label></h3>
			<div class="inside">
				<form id="gdm_choose_date" method="post">
				<input type="hidden" name="gdm_active_tab" value="<?php echo esc_attr( gdm_sanitize_text( $active_tab ) ); ?>">
				<?php esc_html_e( 'Start Date: ', 'gluon-download-manager' ); ?><input type="text" class="datepicker" name="gdm_stats_start_date" value="<?php echo esc_attr( gdm_sanitize_text( $start_date ) ); ?>">
				<?php esc_html_e( 'End Date: ', 'gluon-download-manager' ); ?><input type="text" class="datepicker" name="gdm_stats_end_date" value="<?php echo esc_attr( gdm_sanitize_text( $start_date ) ); ?>">
				<p id="gdm_date_buttons">
					<button type="button" data-start-date="<?php echo esc_attr( date( 'Y-m-01' ) ); ?>" data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'This Month', 'gluon-download-manager' ); ?></button>
					<button type="button" data-start-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'first day of last month' ) ) ); ?>" data-end-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'last day of last month' ) ) ); ?>"><?php esc_html_e( 'Last Month', 'gluon-download-manager' ); ?></button>
					<button button type="button" data-start-date="<?php echo esc_attr( date( 'Y-01-01' ) ); ?>" data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'This Year', 'gluon-download-manager' ); ?></button>
					<button button type="button" data-start-date="<?php echo esc_attr( date( 'Y-01-01', strtotime( '-1 year' ) ) ); ?>" data-end-date="<?php echo esc_attr( date( 'Y-12-31', strtotime( 'last year' ) ) ); ?>"><?php esc_html_e( 'Last Year', 'gluon-download-manager' ); ?></button>
					<button button type="button" data-start-date="<?php echo '1970-01-01'; ?>" data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'All Time', 'gluon-download-manager' ); ?></button>
				</p>
				<div class="submit">
					<input type="submit" class="button-primary" value="<?php esc_html_e( 'View Stats', 'gluon-download-manager' ); ?>">
				</div>
				</form>
			</div>
			</div>
			<div class="nav-tab-wrapper gdm-tabs">
			<a href="edit.php?post_type=gdm_downloads&page=gdm-stats&gdm_active_tab=datechart" class="nav-tab<?php echo ( $active_tab === 'datechart' ? ' nav-tab-active' : '' ); ?>" data-tab-name="datechart"><?php esc_html_e( 'Downloads by date', 'gluon-download-manager' ); ?></a>
			<a href="edit.php?post_type=gdm_downloads&page=stats&gdm_active_tab=geochart" href="" class="nav-tab<?php echo ( $active_tab === 'geochart' ? ' nav-tab-active' : '' ); ?>" data-tab-name="geochart"><?php esc_html_e( 'Downloads by country', 'gluon-download-manager' ); ?></a>
				<a href="edit.php?post_type=gdm_downloads&page=stats&gdm_active_tab=countrylistchart" href="" class="nav-tab<?php echo ( $active_tab === 'countrylistchart' ? ' nav-tab-active' : '' ); ?>" data-tab-name="countrylistchart"><?php esc_html_e( 'Downloads by country list', 'gluon-download-manager' ); ?></a>
			<a href="edit.php?post_type=gdm_downloads&page=stats&gdm_active_tab=browserList" href="" class="nav-tab<?php echo ( $active_tab === 'browserList' ? ' nav-tab-active' : '' ); ?>" data-tab-name="browserList"><?php esc_html_e( 'Downloads by browser', 'gluon-download-manager' ); ?></a>
			<a href="edit.php?post_type=gdm_downloads&page=stats&gdm_active_tab=osList" href="" class="nav-tab<?php echo ( $active_tab === 'osList' ? ' nav-tab-active' : '' ); ?>" data-tab-name="osList"><?php esc_html_e( 'Downloads by OS', 'gluon-download-manager' ); ?></a>
			<a href="edit.php?post_type=gdm_downloads&page=stats&gdm_active_tab=userList" href="" class="nav-tab<?php echo ( $active_tab === 'userList' ? ' nav-tab-active' : '' ); ?>" data-tab-name="userList"><?php esc_html_e( 'Downloads by User', 'gluon-download-manager' ); ?></a>
			<a href="edit.php?post_type=gdm_downloads&page=stats&gdm_active_tab=topDownloads" href="" class="nav-tab<?php echo ( $active_tab === 'topDownloads' ? ' nav-tab-active' : '' ); ?>" data-tab-name="topDownloads"><?php esc_html_e( 'Top Downloads', 'gluon-download-manager' ); ?></a>
			</div>
			<div class="gdm-tabs-content-wrapper" style="height: 500px;margin-top: 10px;">
			<div data-tab-name="datechart" class="gdm-tab"<?php echo ( $active_tab === 'datechart' ? '' : ' style="display:none;"' ); ?>>
				<div id="downloads_chart" style="width: auto; max-width: 700px"></div>
			</div>
			<div data-tab-name="geochart" class="gdm-tab"<?php echo ( $active_tab === 'geochart' ? '' : ' style="display:none;"' ); ?>>
					<div id="gdm-api-key-warning">
						<div class="gdm_yellow_box">
							<span class="dashicons dashicons-warning" style="color: #ffae42;"></span>
								<?php
								echo wp_kses(
									__( 'Enter your Google Maps API Key <a href="edit.php?post_type=gdm_downloads&page=gdm-settings&action=advanced-settings#maps_api_key" target="_blank">in the settings</a> to properly display the chart.', 'gluon-download-manager' ),
									array(
										'a' => array(
											'target' => array(),
											'href'   => array(),
										),
									)
								);
								?>
						</div>
					</div>

				<div id="country_chart" style="width: auto; max-width: 700px; height:437px;"></div>
			</div>

				<div data-tab-name="countrylistchart" class="gdm-tab"<?php echo ( $active_tab === 'countrylistchart' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php esc_html_e( 'Country Name', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</thead>
							<tbody>
								<?php
								//An array containing the downloads.
								$downloads_by_country_array = gdm_get_downloads_by_country( $start_date, $end_date, false );
								foreach ( $downloads_by_country_array as $item ) {
									if ( empty( $item['country'] ) ) {
										//Lets skip any unknown country rows
										continue;
									}
									echo '<tr>';
									echo '<td>' . esc_html( $item['country'] ) . '</td>';
									echo '<td>' . esc_html( $item['cnt'] ) . '</td>';
									echo '</tr>';
								}
								?>
							</tbody>
							<tfoot>
							<th><strong><?php esc_html_e( 'Country Name', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of countrylistchart -->

				<div data-tab-name="browserList"
					 class="gdm-tab"<?php echo( $active_tab === 'browserList' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php esc_html_e( 'Browser', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</thead>
							<tbody>
							<?php
							$downloads_by_browser_array = gdm_get_all_downloads_by_browser( $start_date, $end_date );
							foreach ( $downloads_by_browser_array as $name => $count ) {
								?>
								<tr>
									<td><?php echo esc_html( $name ); ?></td>
									<td><?php echo esc_html( $count ); ?></td>
								</tr>
							<?php } ?>
							</tbody>
							<tfoot>
							<th><strong><?php esc_html_e( 'Browser', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of browserList tab-->

				<div data-tab-name="osList"
					 class="gdm-tab"<?php echo( $active_tab === 'osList' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php esc_html_e( 'Operating System', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</thead>
							<tbody>
							<?php
							$downloads_by_os_array = gdm_get_all_downloads_by_os( $start_date, $end_date );
							foreach ( $downloads_by_os_array as $name => $count ) {
								?>
								<tr>
									<td><?php echo esc_html( $name ); ?></td>
									<td><?php echo esc_html( $count ); ?></td>
								</tr>
							<?php } ?>
							</tbody>
							<tfoot>
							<th><strong><?php esc_html_e( 'Operating System', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of osList tab-->
 
				<div data-tab-name="userList" class="gdm-tab"<?php echo( $active_tab === 'userList' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php _e( 'User', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php _e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</thead>
							<tbody>
							<?php
							$downloads_by_count = gdm_get_top_users_by_download_count( $start_date, $end_date, 25 );
							foreach ( $downloads_by_count as $item ) {
								?>
								<tr>
									<td><?php echo esc_html( $item['visitor_name'] ); ?></td>
									<td><?php echo esc_html( $item['cnt'] ); ?></td>
								</tr>
							<?php } ?>
							</tbody>
							<tfoot>
							<th><strong><?php _e( 'User', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php _e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of top userList tab-->

				<div data-tab-name="topDownloads"
					 class="gdm-tab"<?php echo( $active_tab === 'topDownloads' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php esc_html_e( 'Download Item', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</thead>
							<tbody>
							<?php
							$downloads_by_count = gdm_get_top_downloads_by_count( $start_date, $end_date, 15 );
							foreach ( $downloads_by_count as $item ) {
								?>
								<tr>
									<td><?php echo esc_html( $item['post_title'] ); ?></td>
									<td><?php echo esc_html( $item['cnt'] ); ?></td>
								</tr>
							<?php } ?>
							</tbody>
							<tfoot>
							<th><strong><?php esc_html_e( 'Download Item', 'gluon-download-manager' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'gluon-download-manager' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of top downloads tab-->

			</div>
		</div></div>
	</div>

	<?php

	$dbd_prop = array();

	foreach ( $downloads_by_date as $dbd ) {
		$dbd_prop[] = array( $dbd['day'], intval( $dbd['cnt'] ) );
	}

	$dbc_prop = array();

	$dbc_prop[] = array( __( 'Country', 'gluon-download-manager' ), __( 'Downloads', 'gluon-download-manager' ) );

	foreach ( $downloads_by_country as $dbc ) {
		$dbc_prop[] = array( $dbc['country'], intval( $dbc['cnt'] ) );
	}

		wp_localize_script(
			'gdm-admin-stats',
			'sdmAdminStats',
			array(
				'activeTab'  => $active_tab,
				'apiKey'     => $api_key,
				'dByDate'    => $dbd_prop,
				'dByCountry' => $dbc_prop,
				'str'        => array(
					'downloadsByDate'   => __( 'Downloads by Date', 'gluon-download-manager' ),
					'date'              => __( 'Date', 'gluon-download-manager' ),
					'numberOfDownloads' => __( 'Number of downloads', 'gluon-download-manager' ),
					'downloads'         => __( 'Downloads', 'single-download-monitor' ),
				),
			)
		);
}

function gdm_create_addons_page() {
	include WP_GLUON_DL_MANAGER_PATH . 'includes/admin-side/gdm-admin-add-ons-page.php';
}
