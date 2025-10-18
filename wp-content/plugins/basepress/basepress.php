<?php

/**
 * Plugin Name: BasePress
 * Plug URI: https://rizonepress.com/
 * Description: The perfect Knowledge Base plugin for WordPress
 * Version: 3.0.0.3
 * Author: Rizonepress
 * Author URI: https://rizonepress.com/
 * Text Domain: basepress
 * Domain Path: /languages
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */
// Exit if called directly.
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BASEPRESS_DIR', plugin_dir_path( __FILE__ ) );
define( 'BASEPRESS_URI', plugin_dir_url( __FILE__ ) );
define( 'BASEPRESS_VER', '3.0.0.3' );
define( 'BASEPRESS_DB_VER', 2.1 );
define( 'BASEPRESS_PLAN', 'lite' );

// Disable plugin update checks for this plugin
add_filter('site_transient_update_plugins', 'basepress_disable_plugin_updates');
function basepress_disable_plugin_updates($value) {
    if (isset($value) && is_object($value)) {
        $plugin_file = plugin_basename(__FILE__);
        if (isset($value->response[$plugin_file])) {
            unset($value->response[$plugin_file]);
        }
        if (isset($value->no_update[$plugin_file])) {
            unset($value->no_update[$plugin_file]);
        }
    }
    return $value;
}

if ( !class_exists( 'Basepress' ) ) {
    class Basepress {
        /**
         * Plugin version
         *
         * @var string
         */
        public $ver = '3.0.0.3';

        /**
         * Database version
         *
         * @var int
         */
        public $db_ver = 2.1;

        /**
         * Boot strap the plugin
         *
         * @since 1.0.0
         * @updated 1.4.0, 1.5.0, 1.7.10
         */
        public function bootstrap() {
            $this->define_constants();
            //Add plugin icon on admin menu
            add_action( 'admin_head', array($this, 'add_plugin_icon') );
            //Register the function to run on activation
            register_activation_hook( __FILE__, array($this, 'activate') );
            //Register the function to run on deactivation
            register_deactivation_hook( __FILE__, array($this, 'deactivate') );
            //Add knowledge base post type
            add_action( 'init', array($this, 'register_post_type') );
            //Adds correct links on front-end admin bar to edit KBs and Sections
            add_action( 'admin_bar_menu', array($this, 'basepress_admin_bar_edit'), 81 );
            //Force default theme
            add_action( 'init', array($this, 'load_settings'), 5 );
            //Add basepress_variables class
            require_once BASEPRESS_DIR . 'includes/class-basepress-variables.php';
            //Add basepress_utils class
            require_once BASEPRESS_DIR . 'includes/class-basepress-utils.php';
            if ( is_admin() ) {
                //Check if settings needs updating
                add_action( 'init', array($this, 'init_options') );
                //Add basepress template metabox for posts
                require_once 'admin/class-basepress-template-metabox.php';
                //Add basepress product metabox for posts
                require_once 'admin/class-basepress-product-metabox.php';
                //Add basepress section metabox for posts
                require_once 'admin/class-basepress-section-metabox.php';
                //Add basepress icon metabox for posts
                require_once 'admin/class-basepress-post-icon-metabox.php';
                //Add admin options page
                require_once 'admin/class-basepress-settings.php';
                //Add admin products page
                require_once 'admin/class-basepress-products-page.php';
                //Add admin sections page
                require_once 'admin/class-basepress-sections-page.php';
                //Manage the default terms edit screen for our taxonomy
                require_once 'admin/class-basepress-terms-edit.php';
                //Add icon manager page
                require_once 'admin/icons-manager.php';
                //Enqueue admin scripts and styles
                add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'), 99 );
                
                // Add custom admin menu
                add_action('admin_menu', array($this, 'basepress_admin_menu'));
            }
            //Add Ajax Search
            require_once BASEPRESS_DIR . 'includes/class-basepress-search.php';
            //Add Views count
            require_once BASEPRESS_DIR . 'includes/class-basepress-post-views.php';
            //Add widgets
            require_once 'includes/class-basepress-widgets.php';
            //Add BasePress shortcodes
            require_once 'includes/class-basepress-shortcodes.php';
            //Add Public functions
            require_once BASEPRESS_DIR . 'public/public-functions.php';
            //Gutenberg blocks
            require_once 'blocks/gb-blocks.php';
            //BasePress Debug
            require_once 'includes/class-basepress-debug-output.php';
            
            // Register uninstall hook
            register_uninstall_hook(__FILE__, 'basepress_uninstall_cleanup');
        }
        
        /**
         * Add the main BasePress admin menu
         * 
         * @since 3.0.0.3
         */
        public function basepress_admin_menu() {
            // Add main menu
            add_menu_page(
                'BasePress',
                'BasePress',
                'manage_options',
                'basepress',
                null,
                'dashicons-book',
                4
            );
        }

        /**
         * Define BasePress constants
         *
         * @since 1.7.10
         */
        private function define_constants() {
            $this->define( 'BASEPRESS_DIR', plugin_dir_path( __FILE__ ) );
            $this->define( 'BASEPRESS_URI', plugin_dir_url( __FILE__ ) );
            $this->define( 'BASEPRESS_VER', '3.0.0.3' );
            $this->define( 'BASEPRESS_DB_VER', $this->db_ver );
            $this->define( 'BASEPRESS_PLAN', 'lite' );
        }

        /**
         * Define constant if not already defined
         *
         * @since 1.7.10
         *
         * @param $name
         * @param $value
         */
        private function define( $name, $value ) {
            if ( !defined( $name ) ) {
                define( $name, $value );
            }
        }

        /**
         * Adds plugin icon on Admin screen menu
         *
         * @since 1.7.0
         *
         * @return mixed
         */
        public function add_plugin_icon() {
            echo '<style type="text/css">';
            echo '#toplevel_page_basepress .wp-menu-image:before { content: "\f330"; font-family: dashicons; }';
            echo '</style>';
        }

        /**
         * Functions to run only upon activation of the plugin
         *
         * @since 1.0.0
         */
        public function activate() {
            $this->init_options();
            $this->register_post_type();
            add_action( 'shutdown', array($this, 'flush_rewrite_rules') );
        }

        /**
         * Functions to run only upon deactivation of the plugin
         *
         * @since 1.0.0
         */
        public function deactivate() {
            flush_rewrite_rules();
        }

        /**
         * Flushes rewrite rules. Called during activation from add_action
         *
         * @since 1.0.0
         */
        public function flush_rewrite_rules() {
            flush_rewrite_rules();
        }

        /**
         * Registers the knowledge base post type
         *
         * @since 1.0.0
         */
        public function register_post_type() {
            include_once 'includes/class-basepress-cpt.php';
        }

        /**
         * Saves the plugin version on WP options table on activation
         *
         * @since 1.0.0
         * @updated 1.4.0
         */
        public function init_options() {
            //Initialize default option values
            $options = get_option( 'basepress_settings' );
            if ( empty( $options ) ) {
                $options = (include_once BASEPRESS_DIR . 'options.php');
                update_option( 'basepress_settings', $options );
                update_option( 'basepress_ver', BASEPRESS_VER, true );
                update_option( 'basepress_db_ver', BASEPRESS_DB_VER, true );
                update_option( 'basepress_plan', BASEPRESS_PLAN, true );
                update_option( 'basepress_run_wizard', 1, true );
            }
            $this->maybe_update();
        }

        /**
         * Function to run on plugin update
         *
         * @since 1.7.10
         */
        public function maybe_update() {
            //Load the update file
            require_once __DIR__ . '/update.php';
            //If this is not an ajax call trigger the update function in the included file
            if ( !wp_doing_ajax() ) {
                $old_ver = get_option( 'basepress_ver' );
                $old_db_ver = get_option( 'basepress_db_ver' );
                $old_plan = get_option( 'basepress_plan' );
                if ( empty( $old_ver ) || $old_ver != BASEPRESS_VER || $old_db_ver != BASEPRESS_DB_VER || $old_plan != BASEPRESS_PLAN ) {
                    if ( current_user_can( 'update_plugins' ) ) {
                        basepress_update(
                            $old_ver,
                            $old_db_ver,
                            $old_plan,
                            BASEPRESS_VER,
                            BASEPRESS_DB_VER,
                            BASEPRESS_PLAN
                        );
                    }
                }
            }
        }

        /**
         * Always use the Default theme and optimize for Astra theme
         * 
         * @since 3.0.0.3
         */
        public function load_settings() {
            $options = get_option('basepress_settings');
            if (is_array($options)) {
                // Force 'default' theme
                $options['theme_style'] = 'default';
                
                // Remove any header/footer settings as we now always load theme headers/footers
                if (isset($options['skip_header_footer'])) {
                    unset($options['skip_header_footer']);
                }
                if (isset($options['load_theme_header_footer'])) {
                    unset($options['load_theme_header_footer']);
                }
                
                update_option('basepress_settings', $options);
            }
            
            // Remove other theme settings
            delete_option('basepress_modern_theme');
            delete_option('basepress_zen_theme');
            
            // Remove font_family from default theme settings to ensure theme typography is used
            $default_theme_settings = get_option('basepress_default_theme');
            if (is_array($default_theme_settings)) {
                // Remove typography settings - inherit from theme
                if (isset($default_theme_settings['font_family'])) {
                    unset($default_theme_settings['font_family']);
                }
                if (isset($default_theme_settings['font_size'])) {
                    unset($default_theme_settings['font_size']);
                }
                
                // Don't remove color settings - need them for optional use
                // But make sure 'use_theme_colors' is set if it doesn't exist yet
                if (!isset($default_theme_settings['use_theme_colors'])) {
                    $default_theme_settings['use_theme_colors'] = 1; // Default to using theme colors
                }
                
                // Remove obsolete setting
                if (isset($default_theme_settings['enable_custom_colors'])) {
                    unset($default_theme_settings['enable_custom_colors']);
                }
                
                update_option('basepress_default_theme', $default_theme_settings);
            }
            
            // Optimize for Astra theme
            add_action('wp', array($this, 'optimize_for_astra_theme'));
            
            // Add style optimization
            add_action('wp_enqueue_scripts', array($this, 'optimize_scripts_and_styles'), 999);
            add_action('admin_enqueue_scripts', array($this, 'optimize_admin_scripts_and_styles'), 999);
        }
        
        /**
         * Optimize front-end script and style loading
         *
         * @since 3.0.0.3
         */
        public function optimize_scripts_and_styles() {
            global $basepress_utils;
            
            // Only load BasePress styles on BasePress pages
            if (!$basepress_utils->is_knowledgebase && !is_tax('knowledgebase_cat') && !is_singular('knowledgebase')) {
                wp_dequeue_style('basepress-styles');
                wp_dequeue_script('basepress-script');
                
                // Only load search styles if using the shortcode
                global $post;
                if (!$post || !has_shortcode($post->post_content, 'basepress-search')) {
                    wp_dequeue_style('basepress-search');
                    wp_dequeue_script('basepress-search');
                }
            }
        }
        
        /**
         * Optimize admin script and style loading
         *
         * @since 3.0.0.3
         */
        public function optimize_admin_scripts_and_styles($hook) {
            // Only load BasePress admin styles on BasePress admin pages
            if (strpos($hook, 'basepress') === false && 
                strpos($hook, 'knowledgebase') === false) {
                wp_dequeue_style('basepress-admin');
                wp_dequeue_style('basepress-icons');
                wp_dequeue_script('basepress-admin-js');
            }
        }

        /**
         * Apply optimizations for Astra theme
         * 
         * @since 3.0.0.3
         */
        public function optimize_for_astra_theme() {
            // Check if Astra theme is active
            if ('astra' === get_template()) {
                // Apply Astra-specific optimizations for BasePress
                add_filter('astra_the_title_enabled', function($enabled) {
                    // Don't show Astra's automatic title on BasePress pages
                    if (is_singular('knowledgebase') || is_tax('knowledgebase_cat')) {
                        return false;
                    }
                    return $enabled;
                });
                
                // Use Astra's content layout options
                add_filter('astra_get_content_layout', function($layout) {
                    if (is_singular('knowledgebase') || is_tax('knowledgebase_cat')) {
                        // Let BasePress manage its own sidebar positioning
                        // Content-only forces Astra not to add its own sidebar
                        return 'content-only';
                    }
                    return $layout;
                });
            }
        }

        /**
         * Enqueue admin scripts for back end with optimizations
         *
         * @since 1.0.0
         * @updated 3.0.0.3
         *
         * @param $hook
         */
        public function enqueue_admin_scripts( $hook ) {
            global $basepress_utils;
            $post_type = get_post_type();
            $screen = get_current_screen();
            
            // Define screens where we need specific scripts
            $post_screens = array('edit.php', 'post.php', 'post-new.php');
            $icons_screens = array(
                'post.php',
                'post-new.php',
                'knowledgebase_page_basepress_sections',
                'basepress_page_basepress_icons_manager'
            );
            
            // Only load post editor styles for knowledge base post type
            if ( 'knowledgebase' == $post_type && in_array( $hook, $post_screens ) ) {
                wp_enqueue_style(
                    'basepress-post-editor',
                    plugins_url( 'admin/css/post-editor.css', __FILE__ ),
                    array(),
                    BASEPRESS_VER
                );
            }
            
            // Only load icon styles when needed
            if ( in_array( $hook, $icons_screens ) || strpos($hook, 'basepress') !== false ) {
                //Get icons url from active theme
                $theme_icons = $basepress_utils->get_icons_uri();
                wp_enqueue_style(
                    'basepress-icons',
                    $theme_icons,
                    array(),
                    BASEPRESS_VER
                );
            }
            
            // Only load admin styles on BasePress admin pages
            if ( strpos($hook, 'basepress') !== false || strpos($hook, 'knowledgebase') !== false ) {
                wp_enqueue_style(
                    'basepress-admin',
                    plugins_url( 'style.css', __FILE__ ),
                    array(),
                    BASEPRESS_VER
                );
            }
        }

        /**
         * Modifies the edit links on front-end admin bar to point to BasePress custom pages to edit KBs and Sections
         *
         * @since 1.0.0
         *
         * @param $wp_admin_bar
         */
        public function basepress_admin_bar_edit( $wp_admin_bar ) {
            if ( is_tax( 'knowledgebase_cat' ) ) {
                //If there is no edit button on the admin bar return
                if ( !$wp_admin_bar->get_node( 'edit' ) ) {
                    return;
                }
                $queried_object = get_queried_object();
                //Edit menu for products
                if ( 0 == $queried_object->parent ) {
                    $href = get_admin_url() . 'edit.php?post_type=knowledgebase&page=basepress_manage_kbs&product=' . $queried_object->term_id;
                    $title = 'Edit Knowledge Base';
                } else {
                    //Edit menu for sections
                    $href = get_admin_url() . 'edit.php?post_type=knowledgebase&page=basepress_sections&section=' . $queried_object->term_id;
                    $title = 'Edit Section';
                }
                //Modify the admin menu
                $wp_admin_bar->add_node( array(
                    'id'    => 'edit',
                    'title' => $title,
                    'href'  => $href,
                ) );
            }
        }
    }

    //Class end
    global $basepress;
    $basepress = new Basepress();
    $basepress->bootstrap();
}

/**
 * Unistallation function. Must be outside of class
 *
 * @since 1.6.0
 *
 * @return mixed
 */
function basepress_uninstall_cleanup() {
    global $wpdb;
    $options = get_site_option( 'basepress_settings' );
    $remove_all = ( isset( $options['remove_all_uninstall'] ) ? true : false );
    if ( !$remove_all ) {
        return;
    }
    if ( !is_multisite() ) {
        /*
         * Delete all Articles from database
         */
        $args = array(
            'post_type'              => 'knowledgebase',
            'post_status'            => array(
                'publish',
                'pending',
                'draft',
                'auto-draft',
                'future',
                'private',
                'inherit',
                'trash'
            ),
            'suppress_filters'       => true,
            'cache_results'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'no_found_rows'          => true,
            'fields'                 => 'ids',
        );
        $wp_query = new WP_Query();
        $bp_posts = $wp_query->query( $args );
        foreach ( $bp_posts as $post ) {
            wp_delete_post( $post, true );
        }
        /*
         * Remove all Products and Sections
         */
        foreach ( array('knowledgebase_cat') as $taxonomy ) {
            // Prepare & excecute SQL
            $terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.name ASC", $taxonomy ) );
            // Delete Terms
            if ( $terms ) {
                foreach ( $terms as $term ) {
                    $wpdb->delete( $wpdb->term_taxonomy, array(
                        'term_taxonomy_id' => $term->term_taxonomy_id,
                    ) );
                    $wpdb->delete( $wpdb->term_relationships, array(
                        'term_taxonomy_id' => $term->term_taxonomy_id,
                    ) );
                    $wpdb->delete( $wpdb->termmeta, array(
                        'term_id' => $term->term_id,
                    ) );
                    $wpdb->delete( $wpdb->terms, array(
                        'term_id' => $term->term_id,
                    ) );
                }
            }
            // Delete Taxonomy
            $wpdb->delete( $wpdb->term_taxonomy, array(
                'taxonomy' => $taxonomy,
            ), array('%s') );
        }
        /*
         * Remove single site options
         */
        delete_option( 'widget_basepress_products_widget' );
        delete_option( 'widget_basepress_sections_widget' );
        delete_option( 'widget_basepress_related_articles_widget' );
        delete_option( 'widget_basepress_popular_articles_widget' );
        delete_option( 'widget_basepress_toc_widget' );
        delete_option( 'widget_basepress_tag_cloud' );
        delete_option( 'widget_basepress_nav_widget' );
        delete_option( 'knowledgebase_cat_children' );
        delete_option( 'basepress_default_theme' );
        //Remove sidebars widgets
        $sidebars = get_option( 'sidebars_widgets' );
        if ( isset( $sidebars['basepress-sidebar'] ) ) {
            unset($sidebars['basepress-sidebar']);
        }
        update_option( 'sidebars_widgets', $sidebars );
    }
    /*
     * Remove single and multisite options
     */
    delete_site_option( 'basepress_settings' );
    delete_site_option( 'basepress_ver' );
    delete_site_option( 'basepress_db_ver' );
    delete_site_option( 'basepress_plan' );
}