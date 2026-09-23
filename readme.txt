=== Site Report ===
Contributors: ujjwalshres
Tags: audit, database, performance, security, reports
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A one-click site health report for your WordPress database, performance, security, and files — exportable as HTML, PDF, JSON, CSV, or TXT.

== Description ==

Site Report gives you a single dashboard to check the health of your WordPress site, and lets you export the findings to share with a client or keep for your own records.

**Database**

* Table count, total size, and per-table row counts and sizes
* Overhead, revisions, transients, and spam comments

**Performance**

* PHP version, memory limits, and active caching
* Largest front-end resources

**Security**

* Detected security plugins and firewall rules
* Outdated plugin/theme counts
* Debug mode and file editor status
* Basic login-safety checks
* A lightweight scan for suspicious PHP patterns

**Files**

* Total site size, uploads folder size, temp/cache size
* Log files and unusually large files (over 10MB)

**Plugins & Themes**

* Active vs. inactive plugin counts
* Full plugin and theme inventory with versions

**Export**

Every report can be exported as HTML, PDF (via the browser's print dialog), JSON, CSV, or plain text — handy for sharing a snapshot with a client or keeping a record over time.

= Privacy =

Site Report does not collect, transmit, or store any data outside of your own WordPress installation. All checks run locally against your site's own database and file system.

== Installation ==

1. Upload the `site-report` folder to the `/wp-content/plugins/` directory, or install the plugin directly through the WordPress admin under Plugins > Add New.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Site Report** in the admin sidebar to view your report.

== Frequently Asked Questions ==

= Does this plugin modify my site or fix issues automatically? =

No. Site Report is read-only — it inspects your database, files, and configuration and reports on them. It does not change any settings or delete any data.

= Where do exported reports go? =

Exports are generated on demand and downloaded directly to your computer. Nothing is stored on the server beyond WordPress's normal temporary output buffering.

= Does the PDF export require any extra libraries? =

No. PDF export opens the report in your browser and triggers the print dialog, so you can save it as a PDF using your browser's built-in "Print to PDF" option.

== Screenshots ==

1. Database overview tab
2. Performance environment tab
3. Security status tab
4. Files overview tab
5. Plugins & themes tab
6. Export options

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
