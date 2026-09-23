<?php
if (!defined('ABSPATH')) exit;

class Site_Report_Builder {

    public static function generate() {

        return [

            'database' => Site_Report_Data::database(),

            'performance' => Site_Report_Data::performance(),

            //'security' => Site_Report_Data::security(),

            //'files' => Site_Report_Data::files(),

            //'plugins' => Site_Report_Data::plugins()

        ];

    }

}