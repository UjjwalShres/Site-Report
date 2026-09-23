<?php
class Site_Report_Data {

    public static function database() {

        $stats = Site_Report_DB::get_db_stats();

        $size_mb = round($stats['total_size'] / 1024 / 1024, 2);
        $overhead_mb = round($stats['total_overhead'] / 1024 / 1024, 2);

        return [

            'tables' => $stats['tables'],
            'total_rows' => $stats['total_rows'],

            'total_size_bytes' => $stats['total_size'],
            'total_size_mb' => $size_mb,

            'total_overhead_bytes' => $stats['total_overhead'],
            'total_overhead_mb' => $overhead_mb,

            'revisions' => Site_Report_DB::get_post_revisions(),
            'transients' => Site_Report_DB::get_transients(),
            'spam_comments' => Site_Report_DB::get_spam_comments(),

            'orphans' => Site_Report_DB::get_orphan_tables()

        ];
    }

    public static function performance() {

        $raw = Site_Report_Performance::get_performance_data();

        $size_mb = round($raw['size'] / 1024 / 1024, 2);

        return [

            'requests' => $raw['requests'],

            'page_size_bytes' => $raw['size'],
            'page_size_mb' => $size_mb,

            'largest_resources' => $raw['largest'],

            'cache_plugin' => $raw['cache'],

            'php_version' => $raw['php_version'],

            'memory_limit' => $raw['memory_limit']

        ];

    }


}