<?php

class GDM_Admin_File_Protection_Settings_Page {
	public function __construct() {
		$this->show_file_protection_settings();
	}

	public function show_file_protection_settings() {
        if (isset($_POST['GDM_file_protection_settings_submit']) && check_admin_referer('GDM_file_protection_settings_nonce_action')){
	        $settings = get_option('GDM_global_options', array());

            // Prepare settings array to save.
            $settings['file_protection_enable'] = isset($_POST['file_protection_enable']) ? 'checked="checked"' : '';

	        // Save the settings.
	        update_option('GDM_global_options', $settings);

            // Show settings update message.
	        echo '<div class="notice notice-success"><p>' . esc_html__('File protection settings updated successfully.', 'gluon-download-manager') . '</p></div>';

            // Trigger a action on settings update.
            do_action('GDM_file_protection_settings_updated');
        }

		$settings = get_option('GDM_global_options', array());

        $enable_file_protection = isset($settings['file_protection_enable']) && !empty($settings['file_protection_enable']) ? $settings['file_protection_enable'] : '';

		?>

        <div class="GDM_blue_box">
        <p>
            <a href="https://simple-download-monitor.com/enhanced-file-protection-securing-your-downloads/" target="_blank"><?php esc_html_e('Refer to this guide', 'gluon-download-manager') ?></a><?php esc_html_e(' to learn more about the enhanced file protection feature.', 'gluon-download-manager') ?>
        </p>
        </div>

        <h2><?php esc_html_e('File Protection Settings (Beta)', 'gluon-download-manager') ?></h2>

        <form action="" method="post">
	        <?php if ( GDM_Utils_File_System_Related::is_nginx_server() ) { ?>
                <div class="notice inline notice-warning notice-alt">
                    <p>
				        <?php esc_html_e( 'Your website is using an Nginx server. To enable this file protection feature, please update the server configuration manually. ', 'gluon-download-manager' ) ?>
                    </p>
                    <p>
				        <?php esc_html_e( 'Add the following rule to your virtual host configuration file:', 'gluon-download-manager' ) ?>
                    </p>

                    <textarea rows="3" cols="50" readonly class="" style="white-space: pre; font-family: monospace; overflow: hidden; padding: 5px 8px; resize:none;">
location ~ ^/wp-content/uploads/<?php echo esc_attr( GDM_File_Protection_Handler::get_protected_dir_name() ) ?>/ {
	deny all;
}
				</textarea>
                    <p>
				        <?php echo wp_kses( __( '<a href="https://simple-download-monitor.com/enhanced-file-protection-securing-your-downloads/#server-configuration-requirements" target="_blank">Read the full documentation</a> on how to configure the file protection feature on an Nginx server.', 'gluon-download-manager' ), array( 'a' => array( 'href' => array(), 'target' => array() ) ) ); ?>
                    </p>
                </div>
	        <?php } ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable File Protection (Beta)', 'gluon-download-manager') ?></th>
                        <td>
                            <input name="file_protection_enable" id="file_protection_enable" type="checkbox" <?php echo esc_attr($enable_file_protection) ?>>
                            <p class="description"><?php esc_html_e('Check this box to enable the enhanced file protection feature.', 'gluon-download-manager') ?></p>
                        </td>
                    </tr>

                    <?php do_action('GDM_after_file_protection_settings_fields'); ?>
                    
                </tbody>
            </table>

            <?php wp_nonce_field('GDM_file_protection_settings_nonce_action') ?>

            <p class="submit">
                <input type="submit" name="GDM_file_protection_settings_submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'gluon-download-manager') ?>">
            </p>

        </form>
		<?php
	}
}
