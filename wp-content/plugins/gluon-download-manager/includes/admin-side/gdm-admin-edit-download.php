<?php

class gdm_Admin_Edit_Download {

	public function __construct() {
		add_action( 'add_meta_boxes_gdm_downloads', array( $this, 'add_meta_boxes_handler' ) );  // Create metaboxes
		add_action( 'save_post_gdm_downloads', array( $this, 'save_post_handler' ), 10, 3 );
		// Grabs the inserted post data so we can sanitize/modify it.
		add_filter( 'wp_insert_post_data' , array( $this, 'insert_post_gdm_post_title' ), '99', 1 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_gdm_remove_thumbnail_image', array( $this, 'remove_thumbnail_image_ajax_handler' ) );
		}
	}

	public function add_meta_boxes_handler( $post ) {
		add_meta_box( 'gdm_description_meta_box', __( 'Description', 'gluon-download-manager' ), array( $this, 'display_gdm_description_meta_box' ), 'gdm_downloads', 'normal', 'default' );
		add_meta_box( 'gdm_upload_meta_box', __( 'Downloadable File (Visitors will download this item)', 'gluon-download-manager' ), array( $this, 'display_gdm_upload_meta_box' ), 'gdm_downloads', 'normal', 'default' );
		add_meta_box( 'gdm_dispatch_meta_box', __( 'PHP Dispatch or Redirect', 'gluon-download-manager' ), array( $this, 'display_gdm_dispatch_meta_box' ), 'gdm_downloads', 'normal', 'default' );
		add_meta_box( 'gdm_misc_properties_meta_box', __( 'Miscellaneous Download Item Properties', 'gluon-download-manager' ), array( $this, 'display_gdm_misc_properties_meta_box' ), 'gdm_downloads', 'normal', 'default' ); // Meta box for misc properies/settings
		add_meta_box( 'gdm_thumbnail_meta_box', __( 'File Thumbnail (Optional)', 'gluon-download-manager' ), array( $this, 'display_gdm_thumbnail_meta_box' ), 'gdm_downloads', 'normal', 'default' );
		add_meta_box( 'gdm_stats_meta_box', __( 'Statistics', 'gluon-download-manager' ), array( $this, 'display_gdm_stats_meta_box' ), 'gdm_downloads', 'normal', 'default' );
		do_action( 'gdm_admin_add_edit_download_before_other_details_meta_box_action' );
		add_meta_box( 'gdm_other_details_meta_box', __( 'Other Details (Optional)', 'gluon-download-manager' ), array( $this, 'display_gdm_other_details_meta_box' ), 'gdm_downloads', 'normal', 'default' );
		add_meta_box( 'gdm_shortcode_meta_box', __( 'Shortcodes', 'gluon-download-manager' ), array( $this, 'display_gdm_shortcode_meta_box' ), 'gdm_downloads', 'normal', 'default' );
	}

	public function remove_thumbnail_image_ajax_handler() {

		// terminates the script if the nonce verification fails.
		check_ajax_referer( 'gdm_remove_thumbnail_nonce_action', 'gdm_remove_thumbnail_nonce' );

		$dashboard_access_role = get_gdm_admin_access_permission();
		if ( ! current_user_can( $dashboard_access_role ) ) {
			//not permissions for current user
			wp_die( 'You do not have permission to access this settings page.' );
		}

		// Go ahead with the thumbnail removal
		$post_id    = filter_input( INPUT_POST, 'post_id_del', FILTER_SANITIZE_NUMBER_INT );
		$post_id    = empty( $post_id ) ? 0 : intval( $post_id );
		$key_exists = metadata_exists( 'post', $post_id, 'gdm_upload_thumbnail' );
		if ( $key_exists ) {
			$success = delete_post_meta( $post_id, 'gdm_upload_thumbnail' );
			if ( $success ) {
				$response = array( 'success' => true );
			}
		} else {
			// in order for frontend script to not display "Ajax error", let's return some data
			$response = array( 'not_exists' => true );
		}

		wp_send_json( $response );
	}

	public function display_gdm_description_meta_box( $post ) {
		wp_nonce_field( 'gdm_admin_edit_download_' . $post->ID, 'gdm_admin_edit_download' );

		// Description metabox
		esc_html_e( 'Add a description for this download item.', 'gluon-download-manager' );
		echo '<br /><br />';

		$old_description       = get_post_meta( $post->ID, 'gdm_description', true );
		$gdm_description_field = array( 'textarea_name' => 'gdm_description' );
		wp_editor( $old_description, 'gdm_description_editor_content', $gdm_description_field );
	}

	public function display_gdm_upload_meta_box( $post ) {
		// File Upload metabox
		$old_upload = get_post_meta( $post->ID, 'gdm_upload', true );
		$old_value  = isset( $old_upload ) ? $old_upload : '';

		// Trigger filter to allow "gdm_upload" field validation override.
		$url_validation_override = apply_filters( 'gdm_file_download_url_validation_override', '' );
		if ( ! empty( $url_validation_override ) ) {
			// This site has customized the behavior and overriden the "gdm_upload" field validation. It can be useful if you are offering app download URLs (that has unconventional URL patterns).
		} else {
			// Do the normal URL validation.
			$old_value = esc_url( $old_value );
		}

		esc_html_e( 'Manually enter a valid URL of the file in the text box below, or click "Select File" button to upload (or choose) the downloadable file.', 'gluon-download-manager' );
		echo '<br /><br />';

		echo '<div class="gdm-download-edit-file-url-section">';
		echo '<input id="gdm_upload" type="text" style="width: 95%" name="gdm_upload" value="' . esc_attr( $old_value ) . '" placeholder="http://..." />';
		echo '</div>';

		echo '<br />';
		echo '<input id="upload_image_button" type="button" class="button-primary" value="' . esc_attr__( 'Select File', 'gluon-download-manager' ) . '" />';

		echo '<br /><br />';
		esc_html_e( 'Steps to upload a file or choose one from your media library:', 'gluon-download-manager' );
		echo '<ol>';
		echo '<li>' . esc_html__( 'Hit the "Select File" button.', 'gluon-download-manager' ) . '</li>';
		echo '<li>' . esc_html__( 'Upload a new file or choose an existing one from your media library.', 'gluon-download-manager' ) . '</li>';
		echo '<li>' . esc_html__( 'Click the "Insert" button, this will populate the uploaded file\'s URL in the above text field.', 'gluon-download-manager' ) . '</li>';
		echo '</ol>';
	}

	public function display_gdm_dispatch_meta_box( $post ) {
		$dispatch = get_post_meta( $post->ID, 'gdm_item_dispatch', true );

		if ( $dispatch === '' ) {
			// No value yet (either new item or saved with older version of plugin)
			$screen = get_current_screen();

			if ( $screen->action === 'add' ) {
				// New item: set default value as per plugin settings.
				$main_opts = get_option( 'gdm_downloads_options' );
				$dispatch  = isset( $main_opts['general_default_dispatch_value'] ) && $main_opts['general_default_dispatch_value'];
			}
		}

		echo '<input id="gdm_item_dispatch" type="checkbox" name="gdm_item_dispatch" value="yes"' . checked( true, $dispatch, false ) . ' />';
		echo '<label for="gdm_item_dispatch">' . esc_html__( 'Dispatch the file via PHP directly instead of redirecting to it. PHP Dispatching keeps the download URL hidden. Dispatching works only for local files (files that you uploaded to this site via this plugin or media library).', 'gluon-download-manager' ) . '</label>';
	}

	// Open Download in new window
	public function display_gdm_misc_properties_meta_box( $post ) {

		// Check the open in new window value
		$new_window = get_post_meta( $post->ID, 'gdm_item_new_window', true );
		if ( $new_window === '' ) {
			// No value yet (either new item or saved with older version of plugin)
			$screen = get_current_screen();
			if ( $screen->action === 'add' ) {
				// New item: we can set a default value as per plugin settings. If a general settings is introduced at a later stage.
				// Does nothing at the moment.
			}
		}

		// Check the gdm_item_disable_single_download_page value
		$gdm_item_disable_single_download_page        = get_post_meta( $post->ID, 'gdm_item_disable_single_download_page', true );
		$gdm_item_hide_dl_button_single_download_page = get_post_meta( $post->ID, 'gdm_item_hide_dl_button_single_download_page', true );

		echo '<p> <input id="gdm_item_new_window" type="checkbox" name="gdm_item_new_window" value="yes"' . checked( true, $new_window, false ) . ' />';
		echo '<label for="gdm_item_new_window">' . esc_html__( 'Open download in a new window.', 'gluon-download-manager' ) . '</label> </p>';

		// the new window will have no download button
		echo '<p> <input id="gdm_item_hide_dl_button_single_download_page" type="checkbox" name="gdm_item_hide_dl_button_single_download_page" value="yes"' . checked( true, $gdm_item_hide_dl_button_single_download_page, false ) . ' />';
		echo '<label for="gdm_item_hide_dl_button_single_download_page">';

		$disable_dl_button_label = __( 'Hide the download button on the single download page of this item.', 'gluon-download-manager' );
		echo esc_html( $disable_dl_button_label ) . '</label>';
		echo '</p>';

		echo '<p> <input id="gdm_item_disable_single_download_page" type="checkbox" name="gdm_item_disable_single_download_page" value="yes"' . checked( true, $gdm_item_disable_single_download_page, false ) . ' />';
		echo '<label for="gdm_item_disable_single_download_page">';
		$disable_single_dl_label  = __( 'Disable the single download page for this download item. ', 'gluon-download-manager' );
		$disable_single_dl_label .= __( 'This can be useful if you are using an addon like the ', 'gluon-download-manager' );
		$disable_single_dl_label .= '<a href="https://simple-download-monitor.com/squeeze-form-addon-for-simple-download-monitor/" target="_blank">Squeeze Form</a>.';
		echo wp_kses_post( $disable_single_dl_label ) . '</label>';
		echo '</p>';

		$gdm_item_anonymous_can_download = get_post_meta( $post->ID, 'gdm_item_anonymous_can_download', true );

		echo '<p> <input id="gdm_item_anonymous_can_download" type="checkbox" name="gdm_item_anonymous_can_download" value="yes"' . checked( true, $gdm_item_anonymous_can_download, false ) . ' />';
		echo '<label for="gdm_item_anonymous_can_download">' . esc_html__( 'Ignore "Only Allow Logged-in Users to Download" global setting for this item.', 'gluon-download-manager' ) . '</label> </p>';
	}

	public function display_gdm_thumbnail_meta_box( $post ) {
		// Thumbnail upload metabox
		$old_thumbnail = get_post_meta( $post->ID, 'gdm_upload_thumbnail', true );
		$old_value     = isset( $old_thumbnail ) ? $old_thumbnail : '';
		esc_html_e( 'Manually enter a valid URL, or click "Select Image" to upload (or choose) the file thumbnail image.', 'gluon-download-manager' );
		?>
        <br /><br />
        <input id="gdm_upload_thumbnail" type="text" style="width: 95%" name="gdm_upload_thumbnail" value="<?php echo esc_url_raw( $old_value ); ?>" placeholder="http://..." />
        <br /><br />
        <input id="upload_thumbnail_button" type="button" class="button-primary" value="<?php esc_attr_e( 'Select Image', 'gluon-download-manager' ); ?>" />
        <!--	Creating the nonce field for csrf protection-->
        <input id="gdm_remove_thumbnail_nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'gdm_remove_thumbnail_nonce_action' ) ); ?>"/>
        <input id="remove_thumbnail_button" type="button" class="button" value="<?php esc_attr_e( 'Remove Image', 'gluon-download-manager' ); ?>"/>
        <br /><br />

        <span id="gdm_admin_thumb_preview">
            <?php
            if ( ! empty( $old_value ) ) {
                ?>
            <img id="gdm_thumbnail_image" src="<?php echo esc_url( $old_value ); ?>" style="max-width:200px;" />
                <?php
            }
            ?>
        </span>

		<?php
		echo '<p class="description">';
		esc_html_e( 'This thumbnail image will be used to create a fancy file download box if you want to use it.', 'gluon-download-manager' );
		echo '</p>';
	}

	public function display_gdm_stats_meta_box( $post ) {
		// Stats metabox
		$old_count = get_post_meta( $post->ID, 'gdm_count_offset', true );
		$value     = isset( $old_count ) && ! empty( $old_count ) ? $old_count : '0';

		// Get checkbox for "disable download logging"
		$no_logs = get_post_meta( $post->ID, 'gdm_item_no_log', true );
		$checked = isset( $no_logs ) && $no_logs === 'on' ? ' checked' : '';

		esc_html_e( 'These are the statistics for this download item.', 'gluon-download-manager' );
		echo '<br /><br />';

		global $wpdb;
		$download_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'gdm_downloads WHERE post_id=%d', $post->ID ) );

		echo '<div class="gdm-download-edit-dl-count">';
		esc_html_e( 'Number of Downloads:', 'gluon-download-manager' );
		echo ' <strong>' . esc_html( $download_count ) . '</strong>';
		echo '</div>';

		echo '<div class="gdm-download-edit-offset-count">';
		esc_html_e( 'Offset Count: ', 'gluon-download-manager' );
		echo '<br />';
		echo ' <input type="text" size="10" name="gdm_count_offset" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter any positive or negative numerical value; to offset the download count shown to the visitors (when using the download counter shortcode).', 'gluon-download-manager' ) . '</p>';
		echo '</div>';

		echo '<br />';
		echo '<div class="gdm-download-edit-disable-logging">';
		echo '<input type="checkbox" name="gdm_item_no_log" ' . esc_attr( $checked ) . ' />';
		echo '<span style="margin-left: 5px;"></span>';
		esc_html_e( 'Disable download logging for this item.', 'gluon-download-manager' );
		echo '</div>';
	}

	public function display_gdm_other_details_meta_box( $post ) {
		// Other details metabox
		$show_date_fd                  = get_post_meta( $post->ID, 'gdm_item_show_date_fd', true );
		$gdm_item_show_file_size_fd    = get_post_meta( $post->ID, 'gdm_item_show_file_size_fd', true );
		$gdm_item_show_item_version_fd = get_post_meta( $post->ID, 'gdm_item_show_item_version_fd', true );

		$file_size = get_post_meta( $post->ID, 'gdm_item_file_size', true );
		$file_size = isset( $file_size ) ? $file_size : '';

		$version = get_post_meta( $post->ID, 'gdm_item_version', true );
		$version = isset( $version ) ? $version : '';

		$download_button_text = get_post_meta( $post->ID, 'gdm_download_button_text', true );
		$download_button_text = isset( $download_button_text ) ? $download_button_text : '';

		echo '<div class="gdm-download-edit-filesize">';
		echo '<strong>' . esc_html__( 'File Size: ', 'gluon-download-manager' ) . '</strong>';
		echo '<br />';
		echo ' <input type="text" name="gdm_item_file_size" value="' . esc_attr( $file_size ) . '" size="20" />';
		echo '<p class="description">' . esc_html__( 'Enter the size of this file (example value: 2.15 MB).', 'gluon-download-manager' ) . '</p>';
		echo '<div class="gdm-download-edit-show-file-size"> <input id="gdm_item_show_file_size_fd" type="checkbox" name="gdm_item_show_file_size_fd" value="yes"' . checked( true, $gdm_item_show_file_size_fd, false ) . ' />';
		echo '<label for="gdm_item_show_file_size_fd">' . esc_html__( 'Show file size in fancy display.', 'gluon-download-manager' ) . '</label> </div>';
		echo '</div>';
		echo '<hr />';

		echo '<div class="gdm-download-edit-version">';
		echo '<strong>' . esc_html__( 'Version: ', 'gluon-download-manager' ) . '</strong>';
		echo '<br />';
		echo ' <input type="text" name="gdm_item_version" value="' . esc_attr( $version ) . '" size="20" />';
		echo '<p class="description">' . esc_html__( 'Enter the version number for this item if any (example value: v2.5.10).', 'gluon-download-manager' ) . '</p>';
		echo '<div class="gdm-download-edit-show-item-version"> <input id="gdm_item_show_item_version_fd" type="checkbox" name="gdm_item_show_item_version_fd" value="yes"' . checked( true, $gdm_item_show_item_version_fd, false ) . ' />';
		echo '<label for="gdm_item_show_item_version_fd">' . esc_html__( 'Show version number in fancy display.', 'gluon-download-manager' ) . '</label> </div>';
		echo '</div>';
		echo '<hr />';

		echo '<div class="gdm-download-edit-show-publish-date">';
		echo '<strong>' . esc_html__( 'Publish Date: ', 'gluon-download-manager' ) . '</strong>';
		echo '<br /> <input id="gdm_item_show_date_fd" type="checkbox" name="gdm_item_show_date_fd" value="yes"' . checked( true, $show_date_fd, false ) . ' />';
		echo '<label for="gdm_item_show_date_fd">' . esc_html__( 'Show download published date in fancy display.', 'gluon-download-manager' ) . '</label>';
		echo '</div>';
		echo '<hr />';

		echo '<div class="gdm-download-edit-button-text">';
		echo '<strong>' . esc_html__( 'Download Button Text: ', 'gluon-download-manager' ) . '</strong>';
		echo '<br />';
		echo '<input id="gdm-download-button-text" type="text" name="gdm_download_button_text" value="' . esc_attr( $download_button_text ) . '" />';
		echo '<p class="description">' . esc_html__( 'You can use this field to customize the download now button text of this item.', 'gluon-download-manager' ) . '</p>';
		echo '</div>';
	}

	public function display_gdm_shortcode_meta_box( $post ) {
		// Shortcode metabox
		esc_html_e( 'The following shortcode can be used on posts or pages to embed a simple download button for this file. You can also use the shortcode inserter (in the post editor) to add this shortcode to a post or page.', 'gluon-download-manager' );
		echo '<br />';
		$shortcode_text = '[gdm_download id="' . $post->ID . '" template="simple"]';
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $shortcode_text ) . "' size='40'>";
		echo '<br /><br />';

		esc_html_e( 'The following shortcode can be used on posts or pages to embed a download box that includes the title, description, thumbnail image and download counter.', 'gluon-download-manager' );
		echo wp_kses(
			__( ' <a href="https://simple-download-monitor.com/basic-usage-creating-a-simple-downloadable-item/" target="_blank">Click here for more documentation</a>.', 'gluon-download-manager' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		);                
		echo '<br />';
		$shortcode_text = '[gdm_download id="' . $post->ID . '" template="box"]';
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $shortcode_text ) . "' size='40'>";
		echo '<br />';
		echo '<span style="color: #666; font-size: 11px;">' . esc_html__( 'Available templates: simple, box, card, compact', 'gluon-download-manager' ) . '</span>';
		echo '<br /><br />';
                
		esc_html_e( 'The following shortcode can be used to show a download counter for this item.', 'gluon-download-manager' );
		echo '<br />';
		$shortcode_text = '[gdm_download_counter id="' . $post->ID . '"]';
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $shortcode_text ) . "' size='40'>";

		echo '<br /><br />';
		esc_html_e( 'Direct Download URL.', 'gluon-download-manager' );
		echo '<br />';
		$direct_download_url = WP_GLUON_DL_MANAGER_SITE_HOME_URL . '/?gdm_process_download=1&download_id=' . $post->ID;
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $direct_download_url ) . "' size='40'>";

		echo '<br /><br />';
		esc_html_e( 'Direct Download URL without Tracking Count (Ignore Logging).', 'gluon-download-manager' );
		echo '<br />';
		$direct_download_url_ignore_logging = add_query_arg( array( 'gdm_ignore_logging' => '1' ), $direct_download_url );
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $direct_download_url_ignore_logging ) . "' size='40'>";

		// Allow other plugins to add extra content to the shortcode meta box
		$shortcode_meta_box_content = apply_filters( 'gdm_shortcode_meta_box_content', '', $post->ID );
		if ( ! empty( $shortcode_meta_box_content ) ) {
			echo wp_kses_post( $shortcode_meta_box_content );
		}

		echo '<br /><br />';
		echo wp_kses(
			__( 'Read the full shortcode <a href="https://simple-download-monitor.com/miscellaneous-shortcodes-and-shortcode-parameters/" target="_blank">usage documentation here</a>.', 'gluon-download-manager' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		);
	}

	public function insert_post_gdm_post_title( $data ) {
		//Edit the core post data (example: title) at the point it's inserted, rather than updating it afterwards. 
		//It also avoids the danger of creating an infinite loop by triggering update_post within save_post.
		if( isset($data['post_type']) && $data['post_type'] == 'gdm_downloads') { 
			//This is a download item post. Let's modify the title.
			if( isset($data['post_title'])){
				//gdm_Debug::log( 'Post title before: ' . $data['post_title'] );
				$data['post_title'] = sanitize_text_field(stripslashes($data['post_title']));
			}
		}
		return $data; // Returns the modified data.
	}

	public function save_post_handler( $post_id, $post, $update ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! $update || empty( $post_id ) ) {
			return;
		}

        $action = isset( $_POST['action'] ) ? sanitize_text_field( stripslashes ( $_POST['action'] ) ) : '';

		if ( empty( $action ) ) {
			return;
		}
                
		if ( $action == 'inline-save' ){
			//This is a quick edit action. The nonce comes from WordPress's ajax-actions.php.
			//The default wordpress post_save action will handle the standard post data update (for example: the title, slug, date etc).
			check_ajax_referer( 'inlineeditnonce', '_inline_edit' );
		} else {
			//Full post edit. Do the normal nonce check
			check_admin_referer( 'gdm_admin_edit_download_' . $post_id, 'gdm_admin_edit_download' );
		}

		// *** Description  ***
		if ( isset( $_POST['gdm_description'] ) ) {
			update_post_meta( $post_id, 'gdm_description', wp_kses_post( wp_unslash( $_POST['gdm_description'] ) ) );
		}

		// *** File Upload ***
		if ( isset( $_POST['gdm_upload'] ) ) {
			update_post_meta( $post_id, 'gdm_upload', esc_url_raw( $_POST['gdm_upload'], array( 'http', 'https', 'dropbox' ) ) );
		}

		// *** PHP Dispatch or Redirect ***
		$value = filter_input( INPUT_POST, 'gdm_item_dispatch', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'gdm_item_dispatch', $value );

		// *** Miscellaneous Download Item Properties ***
		// Get POST-ed data as boolean value
		$new_window_open                              = filter_input( INPUT_POST, 'gdm_item_new_window', FILTER_VALIDATE_BOOLEAN );
		$gdm_item_hide_dl_button_single_download_page = filter_input( INPUT_POST, 'gdm_item_hide_dl_button_single_download_page', FILTER_VALIDATE_BOOLEAN );
		$gdm_item_disable_single_download_page        = filter_input( INPUT_POST, 'gdm_item_disable_single_download_page', FILTER_VALIDATE_BOOLEAN );
		$gdm_item_anonymous_can_download              = filter_input( INPUT_POST, 'gdm_item_anonymous_can_download', FILTER_VALIDATE_BOOLEAN );

		// Save the data
		update_post_meta( $post_id, 'gdm_item_new_window', $new_window_open );
		update_post_meta( $post_id, 'gdm_item_hide_dl_button_single_download_page', $gdm_item_hide_dl_button_single_download_page );
		update_post_meta( $post_id, 'gdm_item_disable_single_download_page', $gdm_item_disable_single_download_page );
		update_post_meta( $post_id, 'gdm_item_anonymous_can_download', $gdm_item_anonymous_can_download );

		// *** File Thumbnail ***
		if ( isset( $_POST['gdm_upload_thumbnail'] ) ) {
			update_post_meta( $post_id, 'gdm_upload_thumbnail', sanitize_url( $_POST['gdm_upload_thumbnail'] ) );
		}

		// *** Statistics ***
		if ( isset( $_POST['gdm_count_offset'] ) && is_numeric( $_POST['gdm_count_offset'] ) ) {
			update_post_meta( $post_id, 'gdm_count_offset', intval( $_POST['gdm_count_offset'] ) );
		}

		// Checkbox for disabling download logging for this item
		if ( isset( $_POST['gdm_item_no_log'] ) ) {
			update_post_meta( $post_id, 'gdm_item_no_log', sanitize_text_field( wp_unslash( $_POST['gdm_item_no_log'] ) ) );
		} else {
			delete_post_meta( $post_id, 'gdm_item_no_log' );
		}

		// *** Other Details ***
		$show_date_fd = filter_input( INPUT_POST, 'gdm_item_show_date_fd', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'gdm_item_show_date_fd', $show_date_fd );

		$gdm_item_show_file_size_fd = filter_input( INPUT_POST, 'gdm_item_show_file_size_fd', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'gdm_item_show_file_size_fd', $gdm_item_show_file_size_fd );

		$gdm_item_show_item_version_fd = filter_input( INPUT_POST, 'gdm_item_show_item_version_fd', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'gdm_item_show_item_version_fd', $gdm_item_show_item_version_fd );

		if ( isset( $_POST['gdm_item_file_size'] ) ) {
			update_post_meta( $post_id, 'gdm_item_file_size', sanitize_text_field( wp_unslash( $_POST['gdm_item_file_size'] ) ) );
		}

		if ( isset( $_POST['gdm_item_version'] ) ) {
			update_post_meta( $post_id, 'gdm_item_version', sanitize_text_field( wp_unslash( $_POST['gdm_item_version'] ) ) );
		}

		if ( isset( $_POST['gdm_download_button_text'] ) ) {
			update_post_meta( $post_id, 'gdm_download_button_text', sanitize_text_field( wp_unslash( $_POST['gdm_download_button_text'] ) ) );
		}

		// Adding a marker so that this Download CPT can identified as a protected download.
		// The other alternative is to check if the download URL contains 'gdm-downloads' in it.
		$is_file_protection_enabled = gdm_File_Protection_Handler::is_file_protection_enabled() ? 'yes' : 'no';
		update_post_meta( $post_id, 'gdm_is_protected_download', $is_file_protection_enabled );
	}
}

new gdm_Admin_Edit_Download();
