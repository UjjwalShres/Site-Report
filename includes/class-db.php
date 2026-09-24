<?php
if (!defined('ABSPATH')) exit;

class Site_Report_DB {

    /*
    ---------------------------------
    Get all table stats + totals
    ---------------------------------
    */
    public static function get_db_stats() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- live table sizes are the entire point of a site-health report; caching would show stale data. The full report is already cached for 5 minutes via the sr_last_report transient.
        $tables = $wpdb->get_results("SHOW TABLE STATUS", ARRAY_A);

        $data = [];
        $total_size = 0;
        $total_overhead = 0;
        $total_rows = 0;

        foreach($tables as $table){

            $size = $table['Data_length'] + $table['Index_length'];
            $overhead = $table['Data_free'];
            $rows = $table['Rows'];

            $total_size += $size;
            $total_overhead += $overhead;
            $total_rows += $rows;

            $data[] = [
                'name' => $table['Name'],
                'rows' => $table['Rows'],
                'size' => $size,
                'overhead' => $overhead
            ];
        }

        return [
            'tables' => $data,
            'total_size' => $total_size,
            'total_overhead' => $total_overhead,
            'total_rows' => $total_rows
        ];
    }


    /*
    ---------------------------------
    Core WordPress tables
    ---------------------------------
    */
    private static function get_core_tables() {
        global $wpdb;

        return [
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->users,
            $wpdb->usermeta,
            $wpdb->comments,
            $wpdb->commentmeta,
            $wpdb->terms,
            $wpdb->termmeta,
            $wpdb->term_taxonomy,
            $wpdb->term_relationships,
            $wpdb->options,
            $wpdb->links
        ];
    }


    /*
    ---------------------------------
    Detect orphan tables
    (not core WP tables)
    ---------------------------------
    */
    public static function get_orphan_tables() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- listing current tables to detect orphans; caching would risk missing a table added/removed since the cache was set. No core WP API returns the raw table list.
        $all_tables = $wpdb->get_col("SHOW TABLES");
        $core_tables = self::get_core_tables();

        $orphans = [];

        foreach ($all_tables as $table) {
            if (!in_array($table, $core_tables)) {
                $orphans[] = $table;
            }
        }

        return $orphans;
    }


    /*
    ---------------------------------
    Counts
    ---------------------------------
    */
    public static function get_post_revisions() {
        global $wpdb;

        $count = wp_cache_get('sr_post_revisions', 'site-report');
        if (false === $count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- no core WP API returns a revision count; result is cached below.
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type='revision'");
            wp_cache_set('sr_post_revisions', $count, 'site-report', 60);
        }

        return $count;
    }

    public static function get_transients() {
        global $wpdb;

        $count = wp_cache_get('sr_transients_count', 'site-report');
        if (false === $count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- no core WP API returns a transient count; result is cached below.
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
            wp_cache_set('sr_transients_count', $count, 'site-report', 60);
        }

        return $count;
    }

    public static function get_spam_comments() {
        global $wpdb;

        $count = wp_cache_get('sr_spam_comments', 'site-report');
        if (false === $count) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- no core WP API returns a spam comment count; result is cached below.
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved='spam'");
            wp_cache_set('sr_spam_comments', $count, 'site-report', 60);
        }

        return $count;
    }
}