<?php
/**
 * Gluon Download Manager - Migration Script
 * 
 * Migrates data from Simple Download Monitor (SDM) to Gluon Download Manager (GDM)
 * This script preserves all SDM data while creating new GDM data structures
 * 
 * Usage: Access via WordPress admin: yoursite.com/wp-admin/admin.php?page=gdm-migrate-from-sdm
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
 * Main migration class
 */
class GDM_Migration_From_SDM {
    
    private $migration_log = array();
    private $errors = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_migration_page'));
    }
    
    /**
     * Add migration page to admin menu
     */
    public function add_migration_page() {
        add_submenu_page(
            null, // Hidden from menu
            'Migrate SDM to GDM',
            'Migrate SDM to GDM',
            'manage_options',
            'gdm-migrate-from-sdm',
            array($this, 'render_migration_page')
        );
    }
    
    /**
     * Render migration page
     */
    public function render_migration_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        ?>
        <div class="wrap">
            <h1>Migrate Simple Download Monitor to Gluon Download Manager</h1>
            
            <?php if (isset($_POST['gdm_migrate_now'])): ?>
                <?php
                check_admin_referer('gdm_migration_nonce');
                $this->run_migration();
                $this->display_results();
                ?>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><strong>Important:</strong> This migration will copy all data from Simple Download Monitor to Gluon Download Manager.</p>
                    <p>Your original SDM data will remain intact. Both plugins can coexist during the transition period.</p>
                </div>
                
                <div class="card">
                    <h2>What will be migrated?</h2>
                    <ul>
                        <li>✓ Download logs table (<code>sdm_downloads</code> → <code>gdm_downloads</code>)</li>
                        <li>✓ Plugin settings and options</li>
                        <li>✓ Post type data (<code>sdm_downloads</code> → <code>gdm_downloads</code>)</li>
                        <li>✓ Categories taxonomy (<code>sdm_categories</code> → <code>gdm_categories</code>)</li>
                        <li>✓ Tags taxonomy (<code>sdm_tags</code> → <code>gdm_tags</code>)</li>
                        <li>✓ All post metadata</li>
                    </ul>
                    
                    <h2>Before you begin:</h2>
                    <ol>
                        <li>✓ Backup your database</li>
                        <li>✓ Ensure Gluon Download Manager plugin is active</li>
                        <li>✓ Keep Simple Download Monitor active during migration</li>
                    </ol>
                </div>
                
                <form method="post" action="">
                    <?php wp_nonce_field('gdm_migration_nonce'); ?>
                    <p>
                        <input type="submit" name="gdm_migrate_now" class="button button-primary button-large" 
                               value="Start Migration" onclick="return confirm('Have you backed up your database? This process cannot be undone.');">
                    </p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Run the full migration
     */
    private function run_migration() {
        global $wpdb;
        
        $this->log('Starting migration from Simple Download Monitor to Gluon Download Manager...');
        
        // Step 1: Migrate download logs table
        $this->migrate_downloads_table();
        
        // Step 2: Migrate plugin options
        $this->migrate_options();
        
        // Step 3: Migrate posts (downloads)
        $this->migrate_posts();
        
        // Step 4: Migrate taxonomies
        $this->migrate_taxonomies();
        
        // Step 5: Update database version
        update_option('gdm_db_version', '1.4');
        update_option('gdm_migration_completed', current_time('mysql'));
        
        $this->log('Migration completed successfully!');
    }
    
    /**
     * Migrate downloads table
     */
    private function migrate_downloads_table() {
        global $wpdb;
        
        $this->log('Migrating downloads table...');
        
        $old_table = $wpdb->prefix . 'sdm_downloads';
        $new_table = $wpdb->prefix . 'gdm_downloads';
        
        // Check if old table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") != $old_table) {
            $this->error('Old SDM downloads table not found. Skipping table migration.');
            return;
        }
        
        // Create new table
        $sql = "CREATE TABLE IF NOT EXISTS $new_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_title mediumtext NOT NULL,
            file_url mediumtext NOT NULL,
            visitor_ip mediumtext NOT NULL,
            date_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            visitor_country mediumtext NOT NULL,
            visitor_name mediumtext NOT NULL,
            user_agent mediumtext NOT NULL,
            referrer_url mediumtext NOT NULL,
            UNIQUE KEY id (id)
        ) {$wpdb->get_charset_collate()};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check if table was created
        if ($wpdb->get_var("SHOW TABLES LIKE '$new_table'") != $new_table) {
            $this->error('Failed to create new GDM downloads table.');
            return;
        }
        
        // Copy data from old table to new table
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $old_table");
        
        if ($count > 0) {
            // Clear existing data in new table (in case of re-migration)
            $wpdb->query("TRUNCATE TABLE $new_table");
            
            // Copy data
            $result = $wpdb->query("
                INSERT INTO $new_table (post_id, post_title, file_url, visitor_ip, date_time, visitor_country, visitor_name, user_agent, referrer_url)
                SELECT post_id, post_title, file_url, visitor_ip, date_time, visitor_country, visitor_name, user_agent, referrer_url
                FROM $old_table
            ");
            
            if ($result === false) {
                $this->error('Failed to copy data to new downloads table: ' . $wpdb->last_error);
            } else {
                $this->log("Successfully migrated $count download log entries");
            }
        } else {
            $this->log('No download logs to migrate');
        }
    }
    
    /**
     * Migrate plugin options
     */
    private function migrate_options() {
        $this->log('Migrating plugin options...');
        
        // Migrate main options
        $sdm_options = get_option('sdm_downloads_options');
        if ($sdm_options !== false) {
            update_option('gdm_downloads_options', $sdm_options);
            $this->log('Migrated main download options');
        }
        
        // Migrate advanced options
        $sdm_advanced = get_option('sdm_advanced_options');
        if ($sdm_advanced !== false) {
            update_option('gdm_advanced_options', $sdm_advanced);
            $this->log('Migrated advanced options');
        }
        
        // Migrate activation time
        $activation_time = get_option('sdm_plugin_activated_time');
        if ($activation_time !== false) {
            update_option('gdm_plugin_activated_time', $activation_time);
            $this->log('Migrated plugin activation time');
        }
    }
    
    /**
     * Migrate posts (downloads)
     */
    private function migrate_posts() {
        $this->log('Migrating download posts...');
        
        global $wpdb;
        
        // Get all SDM posts
        $sdm_posts = get_posts(array(
            'post_type' => 'sdm_downloads',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        if (empty($sdm_posts)) {
            $this->log('No SDM download posts found to migrate');
            return;
        }
        
        $migrated_count = 0;
        $skipped_count = 0;
        
        foreach ($sdm_posts as $post) {
            // Check if this post was already migrated
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_gdm_migrated_from_sdm' AND meta_value = %d LIMIT 1",
                $post->ID
            ));
            
            if ($existing) {
                $skipped_count++;
                continue;
            }
            
            // Create new GDM post
            $new_post_data = array(
                'post_title' => $post->post_title,
                'post_content' => $post->post_content,
                'post_status' => $post->post_status,
                'post_author' => $post->post_author,
                'post_date' => $post->post_date,
                'post_date_gmt' => $post->post_date_gmt,
                'post_type' => 'gdm_downloads',
                'post_password' => $post->post_password,
                'menu_order' => $post->menu_order
            );
            
            $new_post_id = wp_insert_post($new_post_data);
            
            if (is_wp_error($new_post_id)) {
                $this->error("Failed to create GDM post for SDM post ID {$post->ID}: " . $new_post_id->get_error_message());
                continue;
            }
            
            // Copy all post meta
            $post_meta = get_post_meta($post->ID);
            foreach ($post_meta as $meta_key => $meta_values) {
                foreach ($meta_values as $meta_value) {
                    add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
                }
            }
            
            // Add migration marker
            add_post_meta($new_post_id, '_gdm_migrated_from_sdm', $post->ID);
            add_post_meta($post->ID, '_sdm_migrated_to_gdm', $new_post_id);
            
            // Update download logs to point to new post ID
            $wpdb->update(
                $wpdb->prefix . 'gdm_downloads',
                array('post_id' => $new_post_id),
                array('post_id' => $post->ID),
                array('%d'),
                array('%d')
            );
            
            $migrated_count++;
        }
        
        $this->log("Migrated $migrated_count download posts (skipped $skipped_count already migrated)");
    }
    
    /**
     * Migrate taxonomies
     */
    private function migrate_taxonomies() {
        $this->log('Migrating taxonomies...');
        
        // Migrate categories
        $this->migrate_taxonomy('sdm_categories', 'gdm_categories', 'sdm_downloads', 'gdm_downloads');
        
        // Migrate tags
        $this->migrate_taxonomy('sdm_tags', 'gdm_tags', 'sdm_downloads', 'gdm_downloads');
    }
    
    /**
     * Migrate a single taxonomy
     */
    private function migrate_taxonomy($old_tax, $new_tax, $old_post_type, $new_post_type) {
        global $wpdb;
        
        $terms = get_terms(array(
            'taxonomy' => $old_tax,
            'hide_empty' => false
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            $this->log("No terms found in $old_tax taxonomy");
            return;
        }
        
        $migrated_count = 0;
        
        foreach ($terms as $term) {
            // Check if term already exists in new taxonomy
            $existing_term = get_term_by('slug', $term->slug, $new_tax);
            
            if ($existing_term) {
                $new_term_id = $existing_term->term_id;
            } else {
                // Create new term
                $new_term = wp_insert_term(
                    $term->name,
                    $new_tax,
                    array(
                        'description' => $term->description,
                        'slug' => $term->slug,
                        'parent' => 0 // Will update parent later
                    )
                );
                
                if (is_wp_error($new_term)) {
                    $this->error("Failed to create term {$term->name} in $new_tax: " . $new_term->get_error_message());
                    continue;
                }
                
                $new_term_id = $new_term['term_id'];
                
                // Copy term meta
                $term_meta = get_term_meta($term->term_id);
                foreach ($term_meta as $meta_key => $meta_values) {
                    foreach ($meta_values as $meta_value) {
                        add_term_meta($new_term_id, $meta_key, maybe_unserialize($meta_value));
                    }
                }
            }
            
            // Get all posts with this term in old taxonomy
            $posts = get_posts(array(
                'post_type' => $old_post_type,
                'posts_per_page' => -1,
                'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for migration, runs once
                    array(
                        'taxonomy' => $old_tax,
                        'field' => 'term_id',
                        'terms' => $term->term_id
                    )
                )
            ));
            
            // Assign term to corresponding GDM posts
            foreach ($posts as $post) {
                $gdm_post_id = get_post_meta($post->ID, '_sdm_migrated_to_gdm', true);
                if ($gdm_post_id) {
                    wp_set_object_terms($gdm_post_id, $new_term_id, $new_tax, true);
                }
            }
            
            $migrated_count++;
        }
        
        $this->log("Migrated $migrated_count terms from $old_tax to $new_tax");
    }
    
    /**
     * Log a message
     */
    private function log($message) {
        $this->migration_log[] = array(
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
     * Display migration results
     */
    private function display_results() {
        echo '<div class="card" style="margin-top: 20px;">';
        echo '<h2>Migration Results</h2>';
        
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
        
        if (!empty($this->migration_log)) {
            echo '<div class="notice notice-success">';
            echo '<h3>Migration Log:</h3>';
            echo '<ul>';
            foreach ($this->migration_log as $log) {
                echo '<li><strong>' . esc_html($log['time']) . ':</strong> ' . esc_html($log['message']) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        echo '<h3>Next Steps:</h3>';
        echo '<ol>';
        echo '<li>Verify that all downloads are accessible in Gluon Download Manager</li>';
        echo '<li>Update all shortcodes from <code>[sdm_*]</code> to <code>[gdm_*]</code></li>';
        echo '<li>Test download functionality thoroughly</li>';
        echo '<li>Once verified, you can run the cleanup script to remove old SDM data</li>';
        echo '</ol>';
        echo '</div>';
    }
}

// Initialize migration class
new GDM_Migration_From_SDM();
