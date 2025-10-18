<?php

/**
 * This is the class that adds the theme settings page on admin
 */
// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Basepress_Default_Theme_Settings {
    /**
     * basepress_sections_page constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', array($this, 'add_theme_page'), 20 );
        add_action( 'init', array($this, 'add_ajax_callbacks') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts') );
    }

    /**
     * Adds the Sections page on admin
     *
     * @since 1.0.0
     */
    public function add_theme_page() {
        //Check that the user has the required capability
        if ( current_user_can( 'manage_options' ) ) {
            //Add a submenu on basepress for the theme
            add_submenu_page(
                'basepress',
                'BasePress Theme Settings',
                'Theme Settings',
                'manage_options',
                'basepress_default_theme',
                array($this, 'display_screen')
            );
        }
    }

    /**
     * Defines the ajax calls for this screen
     *
     * @since 1.0.0
     */
    public function add_ajax_callbacks() {
        add_action( 'wp_ajax_basepress_default_theme_save', array($this, 'basepress_default_theme_save') );
    }

    /**
     * Enqueues admin scripts for this screen
     *
     * @since 1.0.0
     *
     * @param $hook
     */
    public function enqueue_admin_scripts( $hook ) {
        global $basepress_utils;
        //Enqueue admin script
        if ( 'basepress_page_basepress_default_theme' == $hook ) {
            $script_path = $basepress_utils->get_theme_file_uri( 'settings/js/basepress-default-theme.js' );
            $css_path = $basepress_utils->get_theme_file_uri( 'settings/style.css' );
            wp_enqueue_media();
            wp_enqueue_style( 'wp-color-picker' );
            wp_register_script(
                'basepress-default-theme-js',
                $script_path,
                array('jquery', 'wp-color-picker'),
                BASEPRESS_VER,
                true
            );
            wp_enqueue_script( 'basepress-default-theme-js' );
            wp_enqueue_style(
                'basepress-default-theme-settings-css',
                $css_path,
                array(),
                BASEPRESS_VER
            );
        }
    }

    /**
     * Generates the page content
     *
     * @since 1.0.0
     */
    public function display_screen() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }
        $defaults = array(
            'enable_settings'      => 1,
            'sticky_sidebar'       => '',
            'sticky_toc'           => '',
            'sidebar_threshold'    => '100px',
            'use_theme_colors'     => 1, // Default to using theme colors
            'accent_color'         => '#78ad68',
            'buttons_text_color'   => '#ffffff',
            'custom_css'           => "/* Ensure BasePress inherits all typography and colors from theme */\n.bpress-main .bpress-article-content,\n.bpress-main .bpress-article-content p,\n.bpress-main .bpress-article-content li,\n.bpress-main .bpress-article-content strong,\n.bpress-main .bpress-article-content b {\n    font-family: inherit;\n    font-size: inherit;\n    line-height: inherit;\n    letter-spacing: inherit;\n    color: inherit;\n}\n",
        );
        $settings = get_option( 'basepress_default_theme' );
        $settings = wp_parse_args( $settings, $defaults );
        ?>
		<div class="wrap">
			<h1>Theme Settings</h1>
			<div class="bpmt-body">
				<form id="bpmt-default-theme" method="post">
					<?php wp_nonce_field( 'bp-theme-nonce', 'nonce' ); ?>
					
					<div class="bpmt-card settings-card">
						<table class="form-table">
							<tbody>
								<tr>
									<th class="setting-title">Make Sidebar Sticky</th>
									<td class="setting-control">
										<input type="checkbox" value="1" name="sticky_sidebar" <?php checked( $settings['sticky_sidebar'], 1 ); ?>>
										<p class="description">If activated the sidebar will remain fixed in the page while scrolling.</p>
									</td>
								</tr>

								<tr>
									<th class="setting-title">Sticky Elements Threshold</th>
									<td class="setting-control">
										<input type="text" value="<?php echo esc_attr( $settings['sidebar_threshold'] ); ?>" name="sidebar_threshold">
										<p class="description">Distance from top of screen including unit( px, %, em etc.) before sidebar becomes sticky.</p>
									</td>
								</tr>

								<tr>
									<th class="setting-title">Use Theme Colors</th>
									<td class="setting-control">
										<input type="checkbox" id="use-theme-colors" name="use_theme_colors" value="1" <?php checked( $settings['use_theme_colors'], 1 ); ?>>
										<p class="description">Automatically use your WordPress theme's accent color. Uncheck to define custom colors below.</p>
									</td>
								</tr>

								<tr id="accent-color-wrap" class="<?php echo ($settings['use_theme_colors'] ? 'hidden' : ''); ?>">
									<th class="setting-title">Accent Color</th>
									<td class="setting-control">
										<input name="accent_color" type="text" value="<?php echo esc_attr( $settings['accent_color'] ); ?>" class="bp-color-field" data-default-color="#78ad68" />
										<p class="description">This color will be used for links, buttons, and other accent elements.</p>
									</td>
								</tr>

								<tr id="buttons-text-color-wrap" class="<?php echo ($settings['use_theme_colors'] ? 'hidden' : ''); ?>">
									<th class="setting-title">Buttons Text Color</th>
									<td class="setting-control">
										<input name="buttons_text_color" type="text" value="<?php echo esc_attr( $settings['buttons_text_color'] ); ?>" class="bp-color-field" data-default-color="#ffffff" />
										<p class="description">This color will be used for text on buttons and other interactive elements.</p>
									</td>
								</tr>

								<tr>
									<th class="setting-title">Custom Css</th>
									<td class="setting-control">
										<textarea name="custom_css" rows="10" cols="100"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					
					<p class="submit">
						<input id="save-settings" type="submit" name="submit" class="button button-primary" value="Save Settings">
					</p>
				</form>
			</div>
		</div>
		
		<style>
			/* Basic styles needed for the form */
			.hidden {
				display: none;
			}
		</style>
	<?php }

    /**
     * Saves Theme Settings with optimized processing
     *
     * @since 1.0.0
     * @updated 3.0.0.3
     */
    public function basepress_default_theme_save() {
        // Initial security check
        if (empty($_POST['settings'])) {
            wp_send_json_error('No settings data received.');
            return;
        }
        
        // Parse form data
        $form_data = array();
        parse_str($_POST['settings'], $form_data);
        
        // Nonce verification
        if (!isset($form_data['nonce']) || !wp_verify_nonce(
            sanitize_text_field(wp_unslash($form_data['nonce'])), 
            'bp-theme-nonce')) {
            wp_send_json_error('The settings could not be saved due to security validation failure.');
            return;
        }
        
        // Get existing settings to preserve any settings not in the form
        $settings = get_option('basepress_default_theme', array());
        
        // Process checkbox fields (not included in form data when unchecked)
        $checkboxes = array('use_theme_colors', 'sticky_sidebar', 'sticky_toc');
        foreach ($checkboxes as $key) {
            $settings[$key] = isset($form_data[$key]) ? 1 : 0;
        }
        
        // Process other fields
        foreach ($form_data as $key => $value) {
            // Skip nonce and submit fields
            if (in_array($key, array('nonce', 'submit'))) {
                continue;
            }
            
            // Use appropriate sanitization based on field type
            switch ($key) {
                case 'custom_css':
                    $settings[$key] = wp_strip_all_tags($value);
                    break;
                    
                case 'sidebar_threshold':
                    // Ensure it has a valid CSS unit
                    $value = sanitize_text_field($value);
                    if (!preg_match('/^[0-9]+(%|px|em|rem|vh|vw|pt)$/', $value) && $value !== '0') {
                        // Add px if it's just a number
                        if (is_numeric($value)) {
                            $value .= 'px';
                        } else {
                            $value = '100px'; // Default if invalid
                        }
                    }
                    $settings[$key] = $value;
                    break;
                    
                case 'accent_color':
                case 'buttons_text_color':
                    // Sanitize color values
                    $value = sanitize_hex_color($value);
                    if (empty($value)) {
                        // Use default if invalid
                        $value = $key === 'accent_color' ? '#78ad68' : '#ffffff';
                    }
                    $settings[$key] = $value;
                    break;
                    
                default:
                    $settings[$key] = sanitize_text_field($value);
            }
        }
        
        // Always enable settings
        $settings['enable_settings'] = 1;
        
        // Update database with optimized settings
        update_option('basepress_default_theme', $settings, false);
        
        // Return success message for the WordPress notice
        wp_send_json_success('Settings saved successfully!');
    }

}

//End Class
new Basepress_Default_Theme_Settings();