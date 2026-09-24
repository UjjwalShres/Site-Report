<?php
if (!defined('ABSPATH')) exit;
define('SITE_REPORT_ADMIN_URL', plugin_dir_url(__FILE__));


class Site_Report_Admin {

    public static function init() {
        add_menu_page(
            'Site Report',
            'Site Report',
            'manage_options',
            'site-report',
            [__CLASS__, 'render_dashboard'],
            'dashicons-analytics',
            3
        );
    }

    private static function badge_class($state) {
        if ($state === 'good') return 'badge-ok';
        if ($state === 'warn') return 'badge-warn';
        return 'badge-bad';
    }

    private static function get_report() {
        $report = get_transient('sr_last_report');

        if (!$report) {
            $report = Site_Report_Builder::generate();
            set_transient('sr_last_report', $report, 300);
        }

        return $report;
    }


    private static function card($title, $value, $state = 'good', $tooltip = null) {

    $tooltip_html = '';

    if ($tooltip && is_array($tooltip)) {

    $tooltip_html = '
    <div class="sr-tooltip">
        <span class="sr-tooltip-icon sr-tooltip-icon-'.esc_attr($state).'">i</span>
        <div class="sr-tooltip-content">';

    foreach ($tooltip as $key => $message) {

        $icon = '';
        if ($key === 'good') {
            $icon = '<span class="good">✓ </span>';
        } elseif ($key === 'warn') {
            $icon = '<span class="warn">! </span>';
        } elseif ($key === 'bad') {
            $icon = '<span class="bad">X </span>';
        }

        $tooltip_html .= '<p>'.$icon.esc_html($message).'</p>';
    }

    $tooltip_html .= '
        </div>
    </div>';
    }


    echo '
    <div class="sr-card">
        <h3>'.esc_html($title).'</h3>
        <span class="sr-value">'.esc_html($value).'</span>
        <div class="sr-card-'.esc_attr($state).'"></div>
        '.$tooltip_html.'
    </div>';
}


    public static function render_dashboard() {

    $tabs = [
        'db' => 'Database',
        'performance' => 'Performance',
        'security' => 'Security',
        'files' => 'Files',
        'plugins' => 'Plugins',
        'export' => 'Export',
    ];

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab switch, not a form submission or state change.
    $current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'db';

    /* ======================
       HEADER + LOGO
    ====================== */

    echo '<div class="sr-header-container">
        <div class="sr-header">
            <img src="' . esc_url(SITE_REPORT_ADMIN_URL . 'assets/site-report-logo-dark.png') . '">
            <div class="sr-brand-name">Site Report</div>
          </div>';



    /* ======================
       TABS
    ====================== */

    echo '<div class="sr-tabs">';

    foreach ($tabs as $key => $label) {

        $active = ($current_tab === $key) ? 'sr-tab-active' : '';

        echo '<a class="sr-tab '.esc_attr($active).'" href="'.esc_url('?page=site-report&tab='.$key).'">'.esc_html($label).'</a>';
    }

    echo '</div>
    </div>';

    echo '<div class="wrap site-report-wrap">';

    echo '<div class="site-report-content">';

    switch($current_tab) {
        case 'db': self::render_db_tab(); break;
        case 'performance': self::render_performance_tab(); break;
        case 'security': self::render_security_tab(); break;
        case 'files': self::render_files_tab(); break;
        case 'plugins': self::render_plugins_tab(); break;
        case 'export': self::render_export_tab(); break;
    }

    echo '</div></div>';
}

/* -----------------------Database tab ---------------------------*/

    private static function render_db_tab() {

    $report = self::get_report();
    $db = $report['database'];

    $tables = $db['tables'];
    $total_rows = $db['total_rows'];

    $size_mb = $db['total_size_mb'];
    $overhead_mb = $db['total_overhead_mb'];

    $revisions = $db['revisions'];
    $transients = $db['transients'];
    $spam = $db['spam_comments'];

    $req_size_mb = Site_Report_Health::db_size_mb($size_mb);
    $req_overhead_size_mb = Site_Report_Health::db_overhead_size_mb($overhead_mb);
    $req_revisions = Site_Report_Health::db_revisions($revisions);
    $req_transients = Site_Report_Health::db_transients($transients);
    $req_spam = Site_Report_Health::db_spam($spam);

    /* ======================
       STAT CARDS
    ====================== */

    echo '<div class="sr-card-grid">';
    self::card(
            'Total DB Size',
            $size_mb. ' MB',
            $req_size_mb['state'],
            [
                'good' => '< 100 MB',
                'warn' => '100 – 300 MB',
                'bad'  => '> 300 MB'
            ]
        );

    self::card(
            'Overhead',
            $overhead_mb. ' MB',
            $req_overhead_size_mb['state'],
            [
                'good' => '< 5 MB',
                'warn' => '< 20 MB',
                'bad'  => '> 20 MB'
            ]
        );

    self::card(
            'Revisions',
            $revisions,
            $req_revisions['state'],
            [
                'good' => '< 500',
                'warn' => '500 – 2000',
                'bad'  => '> 2000'
            ]
        );

    self::card(
            'Transients',
            $transients,
            $req_transients['state'],
            [
                'good' => '< 50',
                'warn' => '50 – 300',
                'bad'  => '> 300'
            ]
        );

    self::card(
            'Spam Comments',
            $spam,
            $req_spam['state'],
            [
                'good' => '< 5',
                'warn' => '5 – 30',
                'bad'  => '> 30'
            ]
        );

    echo '</div>';


    /* ======================
       TABLE
    ====================== */

    echo '<div class="table-container">
    <table class="widefat striped sr-table">
    <thead>
        <tr>
            <th>Table</th>
            <th>Rows</th>
            <th>Size (MB)</th>
            <th>Overhead (MB)</th>
        </tr>
    </thead><tbody>';

    foreach($tables as $table){
        echo '<tr>
            <td>'.esc_html($table['name']).'</td>
            <td>'.esc_html($table['rows']).'</td>
            <td>'.esc_html(round($table['size']/1024/1024,2)).'</td>
            <td>'.esc_html(round($table['overhead']/1024/1024,2)).'</td>
        </tr>';
    }

    echo '<tr class="sr-total-row">
        <td>Total</td>
        <td>'.esc_html($total_rows).'</td>
        <td>'.esc_html($size_mb).'</td>
        <td>'.esc_html($overhead_mb).'</td>
    </tr>';

    echo '</tbody></table>';


    /* ======================
       ORPHANS
    ====================== */

    $orphans = Site_Report_DB::get_orphan_tables();

    if(empty($orphans)){
        echo '<div class="orphan-tables">✔ No orphan tables detected</div>';
    } else {
        echo '<div class="orphan-tables"><strong>Orphan Tables Detected:</strong><ul>';
        foreach($orphans as $table){
            echo '<li>'.esc_html($table).'</li>';
        }
        echo '</ul></div>';
    }

    echo '</div>';
}

/* -----------------------Performance tab ---------------------------*/

    public static function render_performance_tab() {
    
    $report = self::get_report();
    $data = $report['performance'];
    
    $req_health  = Site_Report_Health::requests($data['requests']);
    $size_health = Site_Report_Health::page_size_mb($data['page_size_mb']);
    $cache_state = ($data['cache_plugin'] === 'No Cache Plugin') ? 'bad' : 'good';
    $php_health = Site_Report_Health::php_version($data['php_version']);
    $memory_health = Site_Report_Health::memory_limit_mb($data['memory_limit']);

        echo '<div class="sr-card-grid">';

        self::card(
            'Requests',
            $data['requests'],
            $req_health['state'],
            [
                'good' => '< 60',
                'warn' => '60 – 120',
                'bad'  => '> 120'
            ]
        );
        self::card(
            'Page Size',
            $data['page_size_mb'] . ' MB',
            $size_health['state'],
            [
                'good' => '< 2MB',
                'warn' => '2MB – 4MB',
                'bad'  => '> 4MB'
            ]
        );
        self::card(
            'Cache',
            $data['cache_plugin'],
            $cache_state,
            [
                'good' => '1 Cache Plugin',
                'warn' => 'More than 1 Cache Plugins',
                'bad'  => 'No Cache Plugin'
            ]
            );
        self::card('Load Time', 'Test Needed', 'warn');

        echo '</div>';


?> <div class="bar-chart-section">
    <div class="bar-chart">
        <h3>Largest Resources</h3>

        <div class="chart-container">
            <?php
                    $largest = $data['largest_resources'];
                    // $largest = [
                    // ['name' => 'jquery.js', 'size' => 1048576],       // 1 MB
                    // //['name' => 'style.css', 'size' => 524288],        // 0.5 MB
                    // ['name' => 'main.js', 'size' => 2097152],         // 2 MB
                    // ['name' => 'plugin.js', 'size' => 3145728],       // 3 MB
                    // ['name' => 'extra.css', 'size' => 1572864],       // 1.5 MB
                    // ['name' => 'extra-large.css', 'size' => 9097152],   
                    //     ];
                    $max_size = max(array_column($largest, 'size')); // largest file size
                    $y_steps = 6; // number of horizontal lines on Y-axis
                    $step_value = ceil($max_size / $y_steps); // size per step
                    ?>
            <!-- Y-axis -->
            <div class="chart-y-axis">
                <?php for($i = $y_steps; $i >= 0; $i--):
                        $value_bytes = $i * $step_value;
                        // Convert to MB with 1 decimal
                        $value_mb = $value_bytes / (1024*1024);
                        $label = ($value_mb >= 1) ? round($value_mb, 1).' MB' : round($value_bytes / 1024, 0).' KB';
                    ?>
                <div class="y-label"><?php echo esc_html($label); ?></div>
                <?php endfor; ?>
            </div>

            <!-- Bars -->
            <div class="chart-bars">
                <?php foreach($largest as $item):
                        $height_percent = ($item['size'] / ($y_steps * $step_value)) * 100;
                    ?>
                <div class="bar" style="height: <?php echo esc_attr($height_percent); ?>%">
                    <div class="bar-value"><?php echo esc_html(size_format($item['size'])); ?></div>
                </div>
                <?php endforeach; ?>

                <div class="bar-label-group">
                    <?php foreach($largest as $label): ?>
                    <span class="bar-label"><?php echo esc_html($label['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="bar-side-section">
        <?php 
            self::card(
                'PHP Version',
                $data['php_version'],
                $php_health['state'],
                [
                    'good' => '>= 8.1',
                    'warn' => '8.0',
                    'bad'  => '< 8.0'
                ]
            );

            self::card(
                'Memory Usage',
                $data['memory_limit'] . 'B',
                $memory_health['state'],
                [
                    'good' => '>= 256MB',
                    'warn' => '128MB – 255MB',
                    'bad'  => '< 128MB'
                ]
            );
            ?>
    </div>

</div>

<?php

    }


/* -----------------------Security tab ---------------------------*/
    public static function render_security_tab() {

    $data = self::get_security_data();
    $security = Site_Report_Health::security_plugins_status($data['security_plugins']);
    $firewall_status = Site_Report_Health::firewall_status($data['firewall']);
    $outdated_plugins_status = Site_Report_Health::outdated_plugins_status($data['updates']['plugins']);
    $outdated_themes_status = Site_Report_Health::outdated_themes_status($data['updates']['themes']);
    $login_safety_status = Site_Report_Health::login_safety_status($data['login']);

    echo '<div class="sr-card-grid">';

    self::card(
        'Security Plugins',
        $security['value'],
        $security['state'],
        $security['tooltip']
    );
    self::card(
        'Firewall', 
        $firewall_status['value'],
        $firewall_status['state'],
        $firewall_status['tooltip']
    );
    self::card(
        'Outdated Plugins', 
        $outdated_plugins_status['value'],
        $outdated_plugins_status['state'],
        $outdated_plugins_status['tooltip']
        );
    self::card(
        'Outdated Themes', 
        $outdated_themes_status['value'],
        $outdated_themes_status['state'],
        $outdated_themes_status['tooltip']
        );
    self::card('Login Safety', $login_safety_status['value'], $login_safety_status['state'], $login_safety_status['tooltip']);

    echo '</div>';

    echo '<div class="sr-security-large-section">
    <div class="sr-suspicious-list"><h3>Suspicious Files</h3>';

    if ($data['suspicious']) {
        echo '<ul>';
        foreach ($data['suspicious'] as $file) {
            echo '<li>'.esc_html($file).'</li>';
        }
        echo '</ul>';
    } else {
        echo 'No suspicious patterns found';
    }

    echo '</div>
    
    <div class="sr-security-side-section">';
        self::card(
            'Debug',
            $data['debug_mode']['value'],
            $data['debug_mode']['state'],
            [
                'good' => 'Disabled',
                'bad'  => 'Enabled'
            ]
        );

        // self::card(
        //     'Debug Log',
        //     $data['debug_log']['value'],
        //     $data['debug_log']['state'],
        //     [
        //         'good' => 'Disabled',
        //         'warn' => 'Enabled'
        //     ]
        // );

        self::card(
            'File Editor',
            $data['file_edit']['value'],
            $data['file_edit']['state'],
            [
                'good' => 'Disabled',
                'bad'  => 'Enabled'
            ]
        );
                
    echo '</div>

    </div>';


    }

    private static function get_security_data() {

    return [
        'security_plugins' => self::detect_security_plugins(),
        'updates' => self::check_updates(),
        'firewall' => self::detect_firewall(),
        'login' => self::check_login_security(),
        'suspicious' => self::scan_suspicious_files(),
        'debug_mode' => self::check_debug_mode(),
        //'debug_log'  => self::check_debug_log(),
        'file_edit'  => self::check_file_edit()
    ];
    }

    private static function detect_security_plugins() {

    $known = [
        'wordfence/wordfence.php' => 'Wordfence',
        'defender-security/wp-defender.php' => 'Defender',
        'ithemes-security-pro/ithemes-security-pro.php' => 'iThemes Security',
        'sucuri-scanner/sucuri.php' => 'Sucuri'
    ];

    $results = [];

    foreach ($known as $file => $name) {
        if (is_plugin_active($file)) {
            $results[] = $name . ' (Active)';
        }
    }

    if (!$results) {
        $results[] = 'No security plugin detected';
    }

    return $results;
    }

    private static function detect_firewall() {

        if (file_exists(ABSPATH . '.htaccess')) {
            $ht = file_get_contents(ABSPATH . '.htaccess');

            if (strpos($ht, 'Wordfence') !== false) return 'Wordfence Firewall Active';
            if (strpos($ht, 'LiteSpeed') !== false) return 'LiteSpeed Server Firewall';

            return 'Basic .htaccess rules detected';
        }

        return 'No firewall rules detected';
    }

    private static function check_updates() {

    require_once ABSPATH . 'wp-admin/includes/update.php';

    wp_update_plugins();
    wp_update_themes();

    $plugins = get_site_transient('update_plugins');
    $themes  = get_site_transient('update_themes');

    return [
        'plugins' => count($plugins->response ?? []),
        'themes'  => count($themes->response ?? [])
    ];
    }

    private static function check_login_security() {

    $issues = [];

    if (get_option('users_can_register')) {
        $issues[] = 'Anyone can register';
    }

    if (!file_exists(ABSPATH . '.htaccess')) {
        $issues[] = 'No server protection (.htaccess missing)';
    }

    if (!$issues) $issues[] = 'Basic protection OK';

    return $issues;
    }

    private static function scan_suspicious_files() {

    $suspects = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(ABSPATH)
    );

    $patterns = ['base64_decode(', 'eval(', 'gzinflate(', 'shell_exec('];

    foreach ($iterator as $file) {

        if ($file->getExtension() !== 'php') continue;

        $content = @file_get_contents($file->getPathname());

        foreach ($patterns as $p) {
            if (strpos($content, $p) !== false) {
                $suspects[] = $file->getFilename();
                break;
            }
        }

        if (count($suspects) >= 10) break;
    }

    return $suspects;
    }

    private static function check_debug_mode() {

    if (defined('WP_DEBUG') && WP_DEBUG) {
        return [
            'value' => 'Enabled',
            'state' => 'bad'
        ];
    }

    return [
        'value' => 'Disabled',
        'state' => 'good'
    ];
    }

    private static function check_file_edit() {

    if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
        return [
            'value' => 'Disabled',
            'state' => 'good'
        ];
    }

    return [
        'value' => 'Enabled',
        'state' => 'bad'
    ];
    }



/* -----------------------Files tab ---------------------------*/
    public static function render_files_tab() {

    $data = self::get_filesystem_data();
    $site_size_mb_status = Site_Report_Health::site_size_mb($data['total']);
    $uploads_size_status = Site_Report_Health::uploads_size($data['uploads']);
    $temp_size_status = Site_Report_Health::temp_size($data['temp']);
    $log_size_status = Site_Report_Health::log_size($data['logs']['total']);

    echo '<div class="sr-card-grid">';

    //self::card('Total Site Size', size_format($data['total']));
    self::card(
        'Total Site Size',
        $site_size_mb_status['value'] . ' MB',
        $site_size_mb_status['state'],
        $site_size_mb_status['tooltip']
    );
    //self::card('Uploads', size_format($data['uploads']));
    self::card(
        'Uploads',
        $uploads_size_status['value'] . ' MB',
        $uploads_size_status['state'],
        $uploads_size_status['tooltip']
    );
    //self::card('Temp/Cache', size_format($data['temp']));
    self::card(
        'Temp/Cache',
        $temp_size_status['value'] . ' MB',
        $temp_size_status['state'],
        $temp_size_status['tooltip']
    );
    //self::card('Log Files', count($data['logs']));
    self::card(
        'Log Files',
        $log_size_status['value'] . ' MB',
        $log_size_status['state'],
        $log_size_status['tooltip']
    );

    echo '</div>';

    echo '<div class="sr-files-large-section">
    <div class="sr-log-list"><h3>Log Files</h3>';

    if ($data['logs']['files']) {
        echo '<ul>';
        foreach ($data['logs']['files'] as $log) {
            echo '<li>'.esc_html($log['name']).' ('. esc_html(size_format($log['size'])).')</li>';
        }
        echo '</ul>';
    } else {
        echo 'No log files found';
    }

    echo '<h3>Large Files (>10MB)</h3>';

    if ($data['large']) {
        echo '<ul>';
        foreach ($data['large'] as $file) {
            echo '<li>'.esc_html($file['name']).' ('. esc_html(size_format($file['size'])).')</li>';
        }
        echo '</ul>';
    } else {
        echo 'No unusually large files found';
    }

    echo '</div>
    
    <div class="sr-files-side-section">';

    self::gauge($data['total']);

    echo '</div>';

    echo '</div>';

    }

    private static function get_filesystem_data() {

    return [
        'total'   => self::folder_size(ABSPATH),
        'uploads' => self::folder_size(wp_upload_dir()['basedir']),
        'logs'    => self::scan_logs(),
        'temp'    => self::scan_temp(),
        'large'   => self::scan_large_files(ABSPATH)
    ];
}

private static function folder_size($path) {

    $size = 0;

    if (!is_dir($path)) return 0;

    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    ) as $file) {
        $size += $file->getSize();
    }

    return $size;
}

private static function gauge($bytes) {

    // Convert to MB for logic
    $value_mb = $bytes / 1024 / 1024;

    // Max 3GB (in MB)
    $max_mb = 3072;

    $percentage = min(100, ($value_mb / $max_mb) * 100);

    // Determine color
    if ($value_mb < 300) {
        $color = '#ceeac2'; // green
    } elseif ($value_mb < 1000) {
        $color = '#ffd0b3'; // orange
    } else {
        $color = '#ffc6c6'; // red
    }

    // Format display nicely
    $display = size_format($bytes, 1);

    ob_start();
    ?>

<svg viewBox="0 0 200 120">

    <!-- Background arc -->
    <path d="M20 100 A80 80 0 0 1 180 100" stroke="#eee" stroke-width="14" fill="none" />

    <!-- Value arc -->
    <path d="M20 100 A80 80 0 0 1 180 100" stroke="<?php echo esc_attr($color); ?>" stroke-width="14" fill="none"
        stroke-dasharray="<?php echo esc_attr($percentage * 2.83); ?> 999" stroke-linecap="round" />

    <!-- Center Value -->
    <text x="100" y="85" text-anchor="middle" class="sr-gauge-value">
        <?php echo esc_html($display); ?>
    </text>
    <text x="100" y="100" text-anchor="middle" class="sr-gauge-text">
        Total Site Size
    </text>

</svg>

<?php
    echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup; dynamic values above are escaped individually.
}



private static function scan_logs() {

    $log_files = [];
    $total_size = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(ABSPATH, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {

        if (!$file->isFile()) continue;

        $filename  = $file->getFilename();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Only real .log files
        if ($extension === 'log') {

            $size = $file->getSize();

            $log_files[] = [
                'name' => $filename,
                'size' => $size
            ];

            $total_size += $size;
        }
    }

    return [
        'files' => $log_files,
        'total' => $total_size
    ];
}


private static function scan_temp() {

    $folders = [
        'cache',
        'tmp',
        'temp',
        'wp-content/cache',
        'wp-content/litespeed'
    ];

    $total = 0;

    foreach ($folders as $folder) {
        $path = ABSPATH . $folder;
        if (is_dir($path)) {
            $total += self::folder_size($path);
        }
    }

    return $total;
}

private static function scan_large_files($path, $limit = 10485760) {

    $large = [];

    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    ) as $file) {

        if ($file->getSize() > $limit) {
            $large[] = [
                'name' => $file->getFilename(),
                'size' => $file->getSize()
            ];
        }

        if (count($large) >= 10) break;
    }

    return $large;
}

/* -----------------------Plugins tab ---------------------------*/
    public static function render_plugins_tab() {

    $data = self::get_plugins_data();

    $active_count = count(array_filter($data['plugins'], fn($p) => $p['active']));
    $total_plugins = count($data['plugins']);
    $plugin_count_status = Site_Report_Health::plugin_count($active_count);
    $inactive_plugin_count_status = Site_Report_Health::inactive_plugins($total_plugins - $active_count);
    $wp_version_status = Site_Report_Health::wp_version($data['compat']['wp']);

    echo '<div class="sr-card-grid">';

    self::card(
        'Active Plugins',
        $plugin_count_status['value'],
        $plugin_count_status['state'],
        $plugin_count_status['tooltip']
    );
    self::card(
        'Inactive Plugins',
        $inactive_plugin_count_status['value'],
        $inactive_plugin_count_status['state'],
        $inactive_plugin_count_status['tooltip']
    );
    self::card(
        'WordPress Version',
        $wp_version_status['value'],
        $wp_version_status['state'],
        $wp_version_status['tooltip']
    );

    echo '</div>';

    echo '<div class="sr-table-container">';
    echo '<div class="sr-first-table-card"><h3>Plugins</h3>';

    echo '<table class="sr-plugin-table">
    <tr><th>Name</th><th>Version</th><th>Status</th></tr>';

    foreach ($data['plugins'] as $p) {

        $status = $p['active']
            ? '<span class="sr-plugin-active">Active</span>'
            : '<span class="sr-plugin-inactive">Inactive</span>';

        echo "<tr>
                <td>" . esc_html($p['name']) . "</td>
                <td>" . esc_html($p['version']) . "</td>
                <td>{$status}</td>
            </tr>";
    }

    echo '</table></div>';

    echo '<div class="sr-second-table-card"><h3>Themes</h3>';

    echo '<table class="sr-theme-table">
    <tr><th>Name</th><th>Version</th><th>Status</th></tr>';

    foreach ($data['themes'] as $t) {

        $status = $t['active']
            ? '<span class="sr-theme-active">Active</span>'
            : '<span class="sr-theme-inactive">Inactive</span>';

        echo "<tr>
                <td>" . esc_html($t['name']) . "</td>
                <td>" . esc_html($t['version']) . "</td>
                <td>{$status}</td>
            </tr>";
    }

    echo '</table></div>';
    echo '</div>';

    }

    private static function get_plugins_data() {

    return [
        'plugins' => self::get_plugins_list(),
        'themes'  => self::get_themes_list(),
        'compat'  => self::compatibility_check()
    ];
}

private static function get_plugins_list() {

    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    $all_plugins = get_plugins();
    $active = get_option('active_plugins');

    $list = [];

    foreach ($all_plugins as $path => $plugin) {

        $list[] = [
            'name' => $plugin['Name'],
            'version' => $plugin['Version'],
            'active' => in_array($path, $active)
        ];
    }

    return $list;
}

private static function get_themes_list() {

    $themes = wp_get_themes();
    $active = wp_get_theme()->get_stylesheet();

    $list = [];

    foreach ($themes as $slug => $theme) {

        $list[] = [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'active' => ($slug === $active)
        ];
    }

    return $list;
}

private static function compatibility_check() {

    return [
        'php' => PHP_VERSION,
        'wp'  => get_bloginfo('version'),
        'php_ok' => version_compare(PHP_VERSION, '8.0', '>='),
        'wp_ok'  => version_compare(get_bloginfo('version'), '6.0', '>=')
    ];
}

/* -----------------------Export tab ---------------------------*/
    public static function render_export_tab() {

    echo '<div class="sr-export-container">';
        echo '<div class="sr-export-large-sec">';
            echo '<h2>Export Site Report</h2>';

            echo '<div class="sr-export-options">
            <label>
                <input type="radio" name="sr_export_type" value="html" checked>
                HTML Report
            </label>

            <label>
                <input type="radio" name="sr_export_type" value="txt">
                TXT Report
            </label>

            <label>
                <input type="radio" name="sr_export_type" value="json">
                JSON Report
            </label>

            <label>
                <input type="radio" name="sr_export_type" value="csv">
                CSV Report
            </label>

            <label>
                <input type="radio" name="sr_export_type" value="pdf">
                PDF (Print)
            </label>

        </div>';

        echo '<input type="hidden" id="sr-export-nonce" value="' . esc_attr(wp_create_nonce('sr_export')) . '">';

        /* ACTION BUTTONS */
        echo '<div class="sr-export-actions">
            <button id="sr-download" class="button button-primary">Download</button>
            <a id="sr-preview" class="button" target="_blank">Preview</a>
        </div>';

            
        echo '</div>';
        echo '<div class="sr-export-small-sec">';
            echo '<h2>Helpful Links</h2>';

            echo '<a>';
            echo '<div class="sr-links-sec">';
            echo '<p><span class="icon-alert"></span>Request Feature</p>';
            echo '</div>';
            echo '</a>';

            echo '<a>';
            echo '<div class="sr-links-sec">';
            echo '<p><span class="icon-question"></span>Get Support</p>';
            echo '</div>';
            echo '</a>';

            echo '<a>';
            echo '<div class="sr-links-sec">';
            echo '<p><span class="icon-tick"></span>Rate our Plugin</p>';
            echo '</div>';
            echo '</a>';

        echo '</div>';
    echo '</div>';
}

private static function collect_full_report() {

    // Reuses the exact same data sources as the dashboard tabs
    // (get_report(), get_security_data(), get_filesystem_data(), get_plugins_data())
    // so the exported report always matches what's on screen.
    $report = self::get_report();

    return [
        'database'    => $report['database'],
        'performance' => $report['performance'],
        'security'    => self::get_security_data(),
        'files'       => self::get_filesystem_data(),
        'plugins'     => self::get_plugins_data()
    ];
}



public static function handle_export() {

    if (!current_user_can('manage_options')) {
        wp_die('No permission');
    }

    check_admin_referer('sr_export');

    $type = isset($_GET['type']) ? sanitize_key(wp_unslash($_GET['type'])) : 'html';

    $report = self::collect_full_report();

    // CRITICAL
    while (ob_get_level()) ob_end_clean();

    switch ($type) {

    case 'json':
        self::export_json($report);
        break;

    case 'csv':
        self::export_csv($report);
        break;

    case 'txt':
        self::export_txt($report);
        break;

    case 'pdf':
        self::export_pdf($report);
        break;
    
    case 'preview':
        self::preview_html($report);
        break;


    case 'html':
    default:
        self::export_html($report);
        break;
}

exit;

}



private static function export_json($data) {

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename=site-report.json');

echo json_encode($data, JSON_PRETTY_PRINT);
}

private static function export_csv($data) {

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=site-report.csv');

$out = fopen('php://output', 'w'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streaming a CSV download to php://output, not a real file; WP_Filesystem cannot target this stream.

foreach ($data as $section => $values) {

fputcsv($out, [$section]);

foreach ($values as $k => $v) {

    // simple values
    if (!is_array($v)) {
        fputcsv($out, [$k, $v]);
    }

    // nested arrays → flatten into rows
    else {

        fputcsv($out, [$k]);

        foreach ($v as $item) {

            if (is_array($item)) {
                fputcsv($out, array_values($item));
            } else {
                fputcsv($out, [$item]);
            }
        }
    }
}


fputcsv($out, []);
}

fclose($out); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closing the php://output stream opened above, not a real file handle.
}

private static function export_txt($data) {
    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- this is a plain-text (.txt) file download, not HTML; esc_html() would corrupt the output (e.g. turning "&" into "&amp;").

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename=site-report.txt');

    foreach ($data as $section => $values) {

        echo strtoupper($section) . "\n";
        echo str_repeat("=", 30) . "\n";

        foreach ($values as $k => $v) {

            if (!is_array($v)) {
                echo "$k: $v\n";
            } else {
                echo "$k:\n";
                foreach ($v as $item) {
                    if (is_array($item)) {
                        echo " - " . implode(", ", $item) . "\n";
                    }
                }
            }
        }

        echo "\n\n";
    }
    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
}

private static function preview_html($data) {

    // no content-disposition = browser renders
    header('Content-Type: text/html');

    // reuse same UI
    self::render_html_template($data);

    exit;
}

private static function export_pdf($data) {

    // No server-side PDF library is bundled with this plugin, so "PDF" is
    // implemented as the export tab labels it: "PDF (Print)" — render the
    // same HTML report and open the browser's print dialog automatically,
    // which lets the user save it as a PDF via "Print to PDF".
    header('Content-Type: text/html');

    self::render_html_template($data);

    echo '<script>window.onload = function () { window.print(); };</script>';

    exit;
}

private static function render_html_template($data) {
    $date = wp_date('Y-m-d H:i:s');
    ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Site Report</title>

    <style>
    /* ===============================
            GLOBAL
            ================================ */
    body {
        margin: 0;
        font-family: Inter, Arial, sans-serif;
        background: #F0F0F1;
        color: #e5e7eb;
    }

    /* ===============================
            HEADER
            ================================ */
    .header {
        padding: 40px;
        text-align: center;
        background-color: #fff;
        color: #111;
    }

    h2,
    h3 {
        color: #181C25;
    }

    .card h3 {
        color: #e5e7eb;
    }

    .header h1 {
        margin: 0;
        font-size: 34px;
        font-weight: 700;
    }

    .header small {
        opacity: .8;
    }

    .sr-logo {
        width: 78px;
        height: 78px;
    }

    /* ===============================
            LAYOUT
            ================================ */
    .container {
        padding: 40px;
        max-width: 1200px;
        margin: auto;
    }

    .section {
        margin-bottom: 50px;
    }

    .section h2 {
        font-size: 20px;
        margin-bottom: 15px;
        border-left: 5px solid #ff914d;
        padding-left: 10px;
    }

    /* ===============================
            CARDS
            ================================ */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }

    .cards-margin {
        margin-top: 15px;
    }

    .card {
        background: #181c25;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 0 0 1px #222;
    }

    .card h3 {
        margin: 0 0 8px;
        font-size: 14px;
        opacity: .7;
    }

    .card p {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }

    /* ===============================
            TABLE
            ================================ */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        background: #181c25;
        border-radius: 10px;
        overflow: hidden;
    }

    th,
    td {
        padding: 10px 12px;
        border-bottom: 1px solid #222;
        font-size: 14px;
    }

    th {
        text-align: left;
        background: #20242f;
    }

    tr:last-child td {
        border-bottom: none;
    }

    .total {
        font-weight: 700;
        color: #ff914d;
    }

    /* badges */
    .badge-ok {
        color: #22c55e;
        font-weight: 600;
    }

    .badge-bad {
        color: #ef4444;
        font-weight: 600;
    }

    .badge-warn {
        color: #f59e0b;
        font-weight: 600;
    }

    .footer {
        text-align: center;
        padding: 30px;
        opacity: .5;
        font-size: 12px;
        color: #181C25;
    }
    </style>
</head>

<body>

    <div class="header">
        <div class="sr-header">
            <img class="sr-logo" src="<?php echo esc_url(SITE_REPORT_ADMIN_URL . 'assets/site-report-logo-white.png'); ?>">
        </div>
        <h1>Site Report</h1>
        <small>Generated on <?php echo esc_html($date); ?></small>
    </div>

    <div class="container">

        <?php
            /* =================================================
            DATABASE
            ================================================= */
            $db = $data['database'];
            ?>

        <div class="section">
            <h2>Database Overview</h2>

            <div class="cards">
                <div class="card">
                    <h3>Total Tables</h3>
                    <p><?php echo esc_html(count($db['tables'])); ?></p>
                </div>

                <div class="card">
                    <h3>Total Database Size</h3>
                    <p><?php echo esc_html($db['total_size_mb']);  ?> MB</p>
                </div>
            </div>

            <div class="cards cards-margin">
                <div class="card">
                    <h3>Overhead</h3>
                    <p><?php echo esc_html($db['total_overhead_mb']);  ?> MB</p>
                </div>

                <div class="card">
                    <h3>Revisions</h3>
                    <p><?php echo esc_html($db['revisions']);  ?></p>
                </div>

                <div class="card">
                    <h3>Transients</h3>
                    <p><?php echo esc_html($db['transients']);  ?></p>
                </div>

                <div class="card">
                    <h3>Spam Comments</h3>
                    <p><?php echo esc_html($db['spam_comments']);  ?></p>
                </div>
            </div>

            <h3>Database Table</h3>
            <table>
                <tr>
                    <th>Table</th>
                    <th>Rows</th>
                    <th>Size</th>
                </tr>

                <?php foreach($db['tables'] as $t): ?>
                <tr>
                    <td><?php echo esc_html($t['name']); ?></td>
                    <td><?php echo esc_html($t['rows']); ?></td>
                    <td><?php echo esc_html($t['size']); ?></td>
                </tr>
                <?php endforeach; ?>

                <tr class="total">
                    <td>TOTAL</td>
                    <td><?php echo esc_html($db['total_rows']); ?></td>
                    <td><?php echo esc_html($db['total_size_mb']); ?> MB</td>
                </tr>
            </table>
        </div>


        <?php
            /* =================================================
            PERFORMANCE
            ================================================= */
            $perf = $data['performance'];
            ?>

        <div class="section">
            <h2>Performance Environment</h2>

            <div class="cards">
                <div class="card">
                    <h3>Requests</h3>
                    <p><?php echo esc_html($perf['requests']); ?></p>
                </div>

                <div class="card">
                    <h3>Page Size</h3>
                    <p><?php echo esc_html($perf['page_size_mb']); ?> MB</p>
                </div>
            </div>

            <div class="cards cards-margin">
                <div class="card">
                    <h3>Cache</h3>
                    <p><?php echo esc_html($perf['cache_plugin']); ?></p>
                </div>

                <div class="card">
                    <h3>PHP Version</h3>
                    <p><?php echo esc_html($perf['php_version']); ?></p>
                </div>

                <div class="card">
                    <h3>Memory Usage</h3>
                    <p><?php echo esc_html($perf['memory_limit']); ?></p>
                </div>
            </div>

            <h3>Largest Resources</h3>
            <table>
                <tr>
                    <th>Resource</th>
                    <th>Size</th>
                </tr>

                <?php foreach($perf['largest_resources'] as $t): ?>
                <tr>
                    <?php $resource_size = round($t['size']/(1024*1024), 2) ?>
                    <td><?php echo esc_html($t['name']); ?></td>
                    <td><?php echo esc_html($resource_size); ?> MB</td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>


        <?php
            /* =================================================
            SECURITY
            ================================================= */
            $s = $data['security'];

            $security_plugins_status = Site_Report_Health::security_plugins_status($s['security_plugins']);
            $outdated_plugins_status = Site_Report_Health::outdated_plugins_status($s['updates']['plugins']);
            $outdated_themes_status  = Site_Report_Health::outdated_themes_status($s['updates']['themes']);
            $login_safety_status     = Site_Report_Health::login_safety_status($s['login']);
            ?>

        <div class="section">
            <h2>Security Status</h2>

            <div class="cards">
                <div class="card">
                    <h3>Security Plugins</h3>
                    <p class="<?php echo esc_attr(self::badge_class($security_plugins_status['state'])); ?>"><?php echo esc_html($security_plugins_status['value']); ?></p>
                </div>
                <div class="card">
                    <h3>Firewall</h3>
                    <p><?php echo esc_html($s['firewall']); ?></p>
                </div>
                <div class="card">
                    <h3>Outdated Plugins</h3>
                    <p class="<?php echo esc_attr(self::badge_class($outdated_plugins_status['state'])); ?>"><?php echo esc_html($outdated_plugins_status['value']); ?></p>
                </div>
                <div class="card">
                    <h3>Outdated Themes</h3>
                    <p class="<?php echo esc_attr(self::badge_class($outdated_themes_status['state'])); ?>"><?php echo esc_html($outdated_themes_status['value']); ?></p>
                </div>
                <div class="card">
                    <h3>Login Safety</h3>
                    <p class="<?php echo esc_attr(self::badge_class($login_safety_status['state'])); ?>"><?php echo esc_html($login_safety_status['value']); ?></p>
                </div>
                <div class="card">
                    <h3>Debug Mode</h3>
                    <p class="<?php echo esc_attr(self::badge_class($s['debug_mode']['state'])); ?>"><?php echo esc_html($s['debug_mode']['value']); ?></p>
                </div>
                <div class="card">
                    <h3>File Editor</h3>
                    <p class="<?php echo esc_attr(self::badge_class($s['file_edit']['state'])); ?>"><?php echo esc_html($s['file_edit']['value']); ?></p>
                </div>
            </div>

            <h3>Suspicious Files</h3>
            <?php if ($s['suspicious']): ?>
            <ul>
                <?php foreach ($s['suspicious'] as $file): ?>
                <li><?php echo esc_html($file); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p>No suspicious patterns found</p>
            <?php endif; ?>
        </div>
        <?php
            /* =================================================
            FILES
            ================================================= */
            $f = $data['files'];
            ?>

        <div class="section">
            <h2>Files Overview</h2>

            <div class="cards">
                <div class="card">
                    <h3>Total Site Size</h3>
                    <p><?php echo esc_html(size_format($f['total'])); ?></p>
                </div>

                <div class="card">
                    <h3>Uploads</h3>
                    <p><?php echo esc_html(size_format($f['uploads'])); ?></p>
                </div>

                <div class="card">
                    <h3>Temp/Cache</h3>
                    <p><?php echo esc_html(size_format($f['temp'])); ?></p>
                </div>

                <div class="card">
                    <h3>Log Files</h3>
                    <p><?php echo esc_html(count($f['logs']['files'])); ?></p>
                </div>
            </div>

            <!-- Logs Table -->
            <?php if(!empty($f['logs']['files'])): ?>
            <table>
                <tr>
                    <th>File</th>
                    <th>Size</th>
                </tr>
                <?php foreach($f['logs']['files'] as $log): ?>
                <tr>
                    <td><?php echo esc_html($log['name']); ?></td>
                    <td><?php echo esc_html(size_format($log['size'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <!-- Large Files Table -->
            <?php if(!empty($f['large'])): ?>
            <h3>Large Files (&gt;10MB)</h3>
            <table>
                <tr>
                    <th>File</th>
                    <th>Size</th>
                </tr>
                <?php foreach($f['large'] as $file): ?>
                <tr>
                    <td><?php echo esc_html($file['name']); ?></td>
                    <td><?php echo esc_html(size_format($file['size'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php
            /* =================================================
            PLUGINS & THEMES
            ================================================= */
            $p = $data['plugins'];
            ?>

        <div class="section">
            <h2>Plugins Overview</h2>

            <div class="cards">
                <div class="card">
                    <h3>Active Plugins</h3>
                    <p><?php echo esc_html(count(array_filter($p['plugins'], fn($pl) => $pl['active']))); ?></p>
                </div>

                <div class="card">
                    <h3>Inactive Plugins</h3>
                    <p><?php echo esc_html(count($p['plugins']) - count(array_filter($p['plugins'], fn($pl) => $pl['active']))); ?>
                    </p>
                </div>
            </div>

            <!-- Plugins Table -->
            <table>
                <tr>
                    <th>Name</th>
                    <th>Version</th>
                    <th>Status</th>
                </tr>
                <?php foreach($p['plugins'] as $pl): ?>
                <tr>
                    <td><?php echo esc_html($pl['name']); ?></td>
                    <td><?php echo esc_html($pl['version']); ?></td>
                    <td><?php echo esc_html($pl['active'] ? 'Active' : 'Inactive'); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <!-- Themes Table -->
            <h3>Themes</h3>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Version</th>
                    <th>Status</th>
                </tr>
                <?php foreach($p['themes'] as $t): ?>
                <tr>
                    <td><?php echo esc_html($t['name']); ?></td>
                    <td><?php echo esc_html($t['version']); ?></td>
                    <td><?php echo esc_html($t['active'] ? 'Active' : 'Inactive'); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>


    </div>

    <div class="footer">
        Generated by Site Report Plugin
    </div>

</body>

</html>


<?php
}




private static function export_html($data) {

    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename=site-report-report.html');

    self::render_html_template($data);
    exit;
}



}

/* style enqueue */
add_action('admin_enqueue_scripts', function($hook){
if($hook !== 'toplevel_page_site-report') return;

wp_enqueue_style(
'site-report-admin',
SITE_REPORT_URL . 'admin/css/admin-style.css',
[],
'1.2'
);

/* script enqueue */
wp_enqueue_script(
    'sr-export-js',
    SITE_REPORT_URL . 'admin/js/export.js',
    [],
    '1.0',
    true
);

});