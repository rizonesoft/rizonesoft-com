<?php
/**
 * Rizonesoft Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Rizonesoft
 * @since 1.0.1
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_RIZONESOFT_VERSION', '1.0.1' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {
	wp_enqueue_style( 'rizonesoft-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_RIZONESOFT_VERSION, 'all' );
    
    // Enqueue custom BasePress styles - only if the plugin is active and file exists
    if (class_exists('Basepress') && file_exists(get_stylesheet_directory() . '/basepress-custom.css')) {
        wp_enqueue_style( 'rizonesoft-basepress-css', get_stylesheet_directory_uri() . '/basepress-custom.css', array('rizonesoft-theme-css'), CHILD_THEME_RIZONESOFT_VERSION, 'all' );
    }
}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

function rizonesoft_theme_enqueue_scripts() {
    wp_enqueue_script(
        'my-custom-scripts', // Unique handle for your script
        get_stylesheet_directory_uri() . '/js/custom-scripts.js', // Path to your JS file
        array(), // Dependencies (like 'jquery'), leave empty if none
        filemtime( get_stylesheet_directory() . '/js/custom-scripts.js' ), // Version number (uses file modification time for cache busting)
        true // Load script in the footer (recommended)
    );
}
add_action( 'wp_enqueue_scripts', 'rizonesoft_theme_enqueue_scripts' );

/**
 * Add Google Funding Choices ad blocking recovery script
 * These scripts require specific attributes and structure so we use wp_head
 */
function rizonesoft_add_header_scripts() {
    ?>

    <!-- BEGIN AD BLOCKING RECOVERY -->
    <script async src="https://fundingchoicesmessages.google.com/i/pub-6605208886607337?ers=1" 
            nonce="<?php echo esc_attr(wp_create_nonce('google-funding-choices')); ?>"
            data-privacy="true"
            data-cookies-control="auto"></script>
    <script nonce="<?php echo esc_attr(wp_create_nonce('google-funding-choices')); ?>">
    (function() {
        function signalGooglefcPresent() {
            if (!window.frames['googlefcPresent']) {
                if (document.body) {
                    const iframe = document.createElement('iframe'); 
                    iframe.style = 'width: 0; height: 0; border: none; z-index: -1000; left: -1000px; top: -1000px;';
                    iframe.style.display = 'none';
                    iframe.name = 'googlefcPresent';
                    document.body.appendChild(iframe);
                } else {
                    setTimeout(signalGooglefcPresent, 0);
                }
            }
        }
        signalGooglefcPresent();
    })();
    </script>

    <!-- Additional ad-blocking recovery script is removed to avoid warnings -->
    <?php
}

// Custom function to retrieve Simple Download Monitor file version by ID
function get_sdm_file_version($download_id) {
    $version = get_post_meta($download_id, 'sdm_item_version', true);
    return esc_html($version);
}

// Shortcode to display file version
add_shortcode('sdm_version', function($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'sdm_version');
    $version = get_sdm_file_version(intval($atts['id']));
    if ($version) {
        return $version;
    } else {
        return 'Version not set';
    }
});

/**
 * Set custom autosave interval
 * 
 * @param int $seconds Default autosave interval in seconds
 * @return int Modified autosave interval
 */
function rizonesoft_set_autosave_interval($seconds) {
    // Set autosave to 2 minutes (120 seconds)
    // You can change this value to your preferred interval
    return 120; 
}
add_filter('autosave_interval', 'rizonesoft_set_autosave_interval');

/**
 * Suppress ResizeObserver JavaScript errors in browser console
 * These are benign errors that don't affect functionality
 */
add_action('wp_footer', function() {
    ?>
    <script>
    // Suppress ResizeObserver loop limit exceeded errors
    (function() {
        const originalError = window.onerror;
        window.onerror = function(message, source, lineno, colno, error) {
            // Filter out ResizeObserver errors
            if (message && message.includes('ResizeObserver loop')) {
                return true; // Suppress the error
            }
            // Pass other errors to the original handler
            if (originalError) {
                return originalError(message, source, lineno, colno, error);
            }
            return false;
        };
    })();
    </script>
    <?php
}, 999);