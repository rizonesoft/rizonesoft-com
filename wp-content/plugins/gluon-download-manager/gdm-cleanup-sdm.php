<?php
/**
 * Gluon Download Manager - SDM Cleanup Script
 * 
 * Removes all Simple Download Monitor (SDM) data after migration to Gluon Download Manager
 * 
 * WARNING: This script permanently deletes SDM data. Only run after verifying GDM migration is complete.
 * 
 * Usage: Access via WordPress admin: yoursite.com/wp-admin/admin.php?page=gdm-cleanup-sdm
 * 
 * @package Gluon Download Manager
 * @author Rizonepress
 * @link https://rizonepress.com
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SDM cleanup class
 */
class GDM_Cleanup_SDM {
    
    private $cleanup_log = array();
    private $errors = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_cleanup_page'));
    }
    
    /**
     * Add cleanup page to admin menu
     */
    public function add_cleanup_page() {
        add_submenu_page(
            null, // Hidden from menu
            'Cleanup SDM Data',
            'Cleanup SDM Data',
            'manage_options',
            'gdm-cleanup-sdm',
            array($this, 'render_cleanup_page')
        );
    }
    
    /**
     * Render cleanup page
     */
    public function render_cleanup_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        ?>
        <div class="wrap">
            <h1>Cleanup Simple Download Monitor Data</h1>
            
            <?php if (isset($_POST['gdm_cleanup_now'])): ?>
                <?php
                check_admin_referer('gdm_cleanup_nonce');
                $this->run_cleanup();
                $this->display_results();
                ?>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p><strong>WARNING:</strong> This action is irreversible!</p>
                    <p>This cleanup script will permanently delete all Simple Download Monitor data including:</p>
                </div>
                
                <div class="card">
                    <h2>What will be deleted?</h2>
                    <ul style="color: #dc3232;">
                        <li>❌ Download logs table (<code>sdm_downloads</code>)</li>
                        <li>❌ All SDM posts (post type: <code>sdm_downloads</code>)</li>
                        <li>❌ All SDM post metadata</li>
                        <li>❌ SDM categories taxonomy (<code>sdm_categories</code>)</li>
                        <li>❌ SDM tags taxonomy (<code>sdm_tags</code>)</li>
                        <li>❌ All SDM plugin options</li>
                        <li>❌ SDM rewrite rules</li>
                    </ul>
                    
                    <h2 style="color: #dc3232;">Before you proceed:</h2>
                    <ol>
                        <li><strong>CRITICAL:</strong> Backup your database first!</li>
                        <li>Verify that Gluon Download Manager is working correctly</li>
                        <li>Verify that all downloads are accessible via GDM</li>
                        <li>Verify that all download logs have been migrated</li>
                        <li>Update all shortcodes from [sdm_*] to [gdm_*]</li>
                        <li>Deactivate Simple Download Monitor plugin</li>
                    </ol>
                    
                    <h2>Verification Checklist:</h2>
                    <?php $this->display_verification_checks(); ?>
                </div>
                
                <form method="post" action="" style="margin-top: 20px;">
                    <?php wp_nonce_field('gdm_cleanup_nonce'); ?>
                    <p>
                        <label>
                            <input type="checkbox" name="gdm_confirm_backup" required> 
                            I have backed up my database
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="gdm_confirm_verified" required> 
                            I have verified that Gluon Download Manager is working correctly
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="gdm_confirm_shortcodes" required> 
                            I have updated all shortcodes to use GDM syntax
                        </label>
                    </p>
                    <p>
                        <input type="submit" name="gdm_cleanup_now" class="button button-primary button-large" 
                               value="Delete SDM Data Permanently" 
                               style="background-color: #dc3232; border-color: #dc3232;"
                               onclick="return confirm('This will permanently delete all Simple Download Monitor data. Are you absolutely sure?');">
                    </p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Display verification checks
     */
    private function display_verification_checks() {
        global $wpdb;
        
        echo '<table class="widefat" style="margin-top: 15px;">';
        echo '<thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead>';
        echo '<tbody>';
        
        // Check if migration was completed
        $migration_time = get_option('gdm_migration_completed');
        $this->display_check_row(
            'Migration Completed',
            !empty($migration_time),
            $migration_time ? "Completed on: $migration_time" : "No migration record found"
        );
        
        // Check SDM posts count
        $sdm_posts_count = wp_count_posts('sdm_downloads');
        $total_sdm = isset($sdm_posts_count->publish) ? $sdm_posts_count->publish : 0;
        
        // Check GDM posts count
        $gdm_posts_count = wp_count_posts('gdm_downloads');
        $total_gdm = isset($gdm_posts_count->publish) ? $gdm_posts_count->publish : 0;
        
        $this->display_check_row(
            'Download Posts',
            $total_gdm >= $total_sdm && $total_gdm > 0,
            "SDM: $total_sdm | GDM: $total_gdm"
        );
        
        // Check download logs
        $sdm_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sdm_downloads");
        $gdm_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gdm_downloads");
        
        $this->display_check_row(
            'Download Logs',
            $gdm_logs >= $sdm_logs,
            "SDM: $sdm_logs | GDM: $gdm_logs"
        );
        
        // Check if SDM plugin is active
        $sdm_active = is_plugin_active('simple-download-monitor/main.php');
        $this->display_check_row(
            'SDM Plugin Deactivated',
            !$sdm_active,
            $sdm_active ? "SDM is still active - please deactivate first" : "SDM is deactivated"
        );
        
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * Display a single check row
     */
    private function display_check_row($label, $passed, $details) {
        $status = $passed ? '✅ Pass' : '❌ Fail';
        $color = $passed ? '#46b450' : '#dc3232';
        
        echo '<tr>';
        echo '<td><strong>' . esc_html($label) . '</strong></td>';
        echo '<td style="color: ' . $color . '; font-weight: bold;">' . $status . '</td>';
        echo '<td>' . esc_html($details) . '</td>';
        echo '</tr>';
    }
    
    /**
     * Run the cleanup
     */
    private function run_cleanup() {
        global $wpdb;
        
        $this->log('Starting cleanup of Simple Download Monitor data...');
        
        // Step 1: Delete SDM posts
        $this->delete_sdm_posts();
        
        // Step 2: Delete SDM taxonomies
        $this->delete_sdm_taxonomies();
        
        // Step 3: Delete SDM options
        $this->delete_sdm_options();
        
        // Step 4: Drop SDM downloads table
        $this->drop_sdm_table();
        
        // Step 5: Unregister post types and taxonomies
        $this->unregister_sdm_types();
        
        // Step 6: Flush rewrite rules
        flush_rewrite_rules();
        
        // Mark cleanup as completed
        update_option('gdm_sdm_cleanup_completed', current_time('mysql'));
        
        $this->log('Cleanup completed successfully!');
    }
    
    /**
     * Delete all SDM posts
     */
    private function delete_sdm_posts() {
        global $wpdb;
        
        $this->log('Deleting SDM posts...');
        
        $posts = $wpdb->get_results(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type='sdm_downloads'",
            ARRAY_A
        );
        
        if (empty($posts)) {
            $this->log('No SDM posts found to delete');
            return;
        }
        
        $deleted_count = 0;
        
        foreach ($posts as $post) {
            $result = wp_delete_post($post['ID'], true);
            if ($result) {
                $deleted_count++;
            } else {
                $this->error("Failed to delete post ID: {$post['ID']}");
            }
        }
        
        $this->log("Deleted $deleted_count SDM posts");
    }
    
    /**
     * Delete SDM taxonomies
     */
    private function delete_sdm_taxonomies() {
        $this->log('Deleting SDM taxonomies...');
        
        // Delete categories
        $this->delete_taxonomy_terms('sdm_categories');
        
        // Delete tags
        $this->delete_taxonomy_terms('sdm_tags');
    }
    
    /**
     * Delete all terms in a taxonomy
     */
    private function delete_taxonomy_terms($taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            $this->log("No terms found in $taxonomy");
            return;
        }
        
        $deleted_count = 0;
        
        foreach ($terms as $term) {
            $result = wp_delete_term($term->term_id, $taxonomy);
            if ($result && !is_wp_error($result)) {
                $deleted_count++;
            } else {
                $this->error("Failed to delete term {$term->name} from $taxonomy");
            }
        }
        
        $this->log("Deleted $deleted_count terms from $taxonomy");
    }
    
    /**
     * Delete SDM options
     */
    private function delete_sdm_options() {
        $this->log('Deleting SDM options...');
        
        $options = array(
            'sdm_downloads_options',
            'sdm_advanced_options',
            'sdm_db_version',
            'sdm_plugin_activated_time'
        );
        
        $deleted_count = 0;
        
        foreach ($options as $option) {
            if (delete_option($option)) {
                $deleted_count++;
            }
        }
        
        $this->log("Deleted $deleted_count SDM options");
    }
    
    /**
     * Drop SDM downloads table
     */
    private function drop_sdm_table() {
        global $wpdb;
        
        $this->log('Dropping SDM downloads table...');
        
        $table_name = $wpdb->prefix . 'sdm_downloads';
        
        $result = $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        if ($result !== false) {
            $this->log("Successfully dropped table: $table_name");
        } else {
            $this->error("Failed to drop table: $table_name");
        }
    }
    
    /**
     * Unregister SDM post types and taxonomies
     */
    private function unregister_sdm_types() {
        $this->log('Unregistering SDM post types and taxonomies...');
        
        unregister_post_type('sdm_downloads');
        unregister_taxonomy('sdm_categories');
        unregister_taxonomy('sdm_tags');
        
        $this->log('Unregistered SDM post types and taxonomies');
    }
    
    /**
     * Log a message
     */
    private function log($message) {
        $this->cleanup_log[] = array(
            'type' => 'success',
            'message' => $message,
            'time' => current_time('mysql')
        );
    }
    
    /**
     * Log an error
     */
    private function error($message) {
        $this->errors[] = array(
            'type' => 'error',
            'message' => $message,
            'time' => current_time('mysql')
        );
    }
    
    /**
     * Display cleanup results
     */
    private function display_results() {
        echo '<div class="card" style="margin-top: 20px;">';
        echo '<h2>Cleanup Results</h2>';
        
        if (!empty($this->errors)) {
            echo '<div class="notice notice-error">';
            echo '<h3>Errors:</h3>';
            echo '<ul>';
            foreach ($this->errors as $error) {
                echo '<li><strong>' . esc_html($error['time']) . ':</strong> ' . esc_html($error['message']) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        if (!empty($this->cleanup_log)) {
            echo '<div class="notice notice-success">';
            echo '<h3>Cleanup Log:</h3>';
            echo '<ul>';
            foreach ($this->cleanup_log as $log) {
                echo '<li><strong>' . esc_html($log['time']) . ':</strong> ' . esc_html($log['message']) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        echo '<h3>Cleanup Complete!</h3>';
        echo '<p>All Simple Download Monitor data has been removed from your database.</p>';
        echo '<p>You can now safely delete the Simple Download Monitor plugin folder.</p>';
        echo '</div>';
    }
}

// Initialize cleanup class
new GDM_Cleanup_SDM();
