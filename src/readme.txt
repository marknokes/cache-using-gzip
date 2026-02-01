=== Cache Using Gzip ===
Contributors: marknokes
Tags: cache, caching, performance, gzip, speed, page cache, site speed, html cache, server cache, http compression, apache, nginx
Requires at least: 6.4.3
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.9.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Lightweight WordPress caching with gzip compression for faster page loads — no complicated settings.

== Description ==

Instantly speed up your WordPress site with simple, lightweight gzip caching. Many users see faster Time to First Byte (TTFB) and reduced server load immediately after activation.

Cache Using Gzip is designed for users who want **noticeable performance improvements** without dealing with complex caching settings or bloated plugins. Activate it and enjoy faster page loads with minimal configuration.

Most caching plugins try to do everything. Cache Using Gzip focuses on doing **one thing well** — serving fast, gzipped cached pages.

### Why use Cache Using Gzip?

* Faster page load times on the front end
* Simple setup — no technical knowledge required
* Lightweight and minimal
* Works with Apache and Nginx
* Ideal for beginners, bloggers, and shared hosting
* Immediate performance improvements after activation

### How it works

The plugin generates static, gzipped versions of your pages and serves them directly to visitors, reducing file size and server processing for faster responses.

No confusing options. No unnecessary features.

### Free version features

* One-click caching
* Gzip compression support
* Static page caching
* Compatible with most WordPress themes
* Clean, simple admin interface

### Premium version (optional)

Upgrade to the premium version if you need more advanced control, including:

* Advanced caching rules and exclusions
* Additional performance optimizations
* Priority support
* Extra features for growing sites

The free version delivers real value on its own — upgrade only when you need more control.

### Server compatibility

* Apache (.htaccess)
* Nginx (configuration snippets provided)

Clear setup instructions are included.

### Who is this plugin for?

* WordPress beginners
* Bloggers and small business websites
* Users on shared hosting
* Anyone overwhelmed by complex caching plugins
* Site owners who want fast results with minimal effort

If Cache Using Gzip helps speed up your site, please consider leaving a review. It really helps the project grow.

== Installation ==

= Requirements =

* WordPress Version 6.4.3 or newer (installed)
* Apache or Nginx web server
* PHP Version 7.4 or newer
* PHP ZLIB extension enabled
* The Cache Using Gzip plugin requires Pretty permalinks. Confirm your permalink structure is something other than "Plain" (Settings => Permalinks).

= Installation instructions =

1. Log in to WordPress admin.
2. Go to **Plugins > Add New**.
3. Search for the **Cache Using Gzip** plugin.
4. Click on **Install Now** and wait until the plugin is installed successfully.
5. Activate the plugin by clicking **Activate** now on the success page.
6. If you're running WordPress on an Apache server, the .htaccess file should be modified on activation. If it wasn't, visit the plugin settings page (Tools => Cache Using Gzip) to download the required apache/nginx config rules.
7. Add the rules to your .htaccess or nginx.conf, if necessary.

== Screenshots ==

1. Main settings screen
2. Single page cache link (pro feature)
3. Page speed before
4. Page speed after

== Upgrade Notice ==

Automatic updates should work generally smoothly, but we still recommend you back up your site.

== Changelog =

= 2.9.3 =
Bugfix: Under certain circumstances the homepage wasn't being cached when it was set to show latest posts

= 2.9.2 =
Improvement: Add turnstile to dequeue for non-cf7 pages

= 2.9.1 =
Bugfix: Allow proper caching when WordPress is installed in a subdirectory

= 2.9.0 =
Bugfix: In specific environments, the hostname is undetermined, causing the wp-content directory to be deleted upon plugin deactivation

= 2.8.9 =
Bugfix: Under certain circumstances getting post type select options cause php error to be logged

= 2.8.8 =
Bugfix: when move css/js inline chosen relative url's in minified css files break
Improvement: add message to options page with next auto preload date and time

= 2.8.7 =
Bugfix: getting default option value fails if get_option returns bool false
Improvement: add automatic preload cache option

= 2.8.6 =
Bugfix: Adding inline scripts causes console error for modules

= 2.8.5 =
Improvement: remove superfluous import statements
Improvement: minor refactor

= 2.8.4 =
Improvement: remove superfluous import statements
Improvement: normalize line endings
Improvement: add docblocks to class properties
Improvement: add docblocks to class methods
Improvement: php cs fixer
Improvement: add gitattributes

= 2.8.3 =
* Improve option handling

= 2.8.2 =

* Add sanitize methods for register_setting

= 2.8.1 =

* Automatically modify .htaccess on plugin activation/deactivation

= 2.8 =

* Move plugin settings page to Tools menu
* Rename localized js var to mitigate possible naming collision

= 2.7.9 =

* Bugfix: dynamic var deprecated notice
* UI improvement for options page
* Refactor admin notice behavior and code readability

= 2.7.8 =

* Bugfix: add_option in combination with wp_cache_set not serializing data properly in redis object cache. Use update_option on plugin activation instead

= 2.7.7 =

* Disable autoloading of options. Test and confirm compatibility with 6.6-RC2.

= 2.7.6 =

* Further in-memory caching improvments and code structure

= 2.7.5 =

* Bugfix: cache not update when update_option called in ajax

= 2.7.4 =

* Add cache delete to plugin deactivation

= 2.7.3 =

* Add in-memory caching support for plugin options

= 2.7.2 =

* Test and confirm compatibility with WordPress 6.5.5 

= 2.7.1 =

* Bugfix: Issue caching homepage when set to display latest posts instead of static page

= 2.7 =

* Add check for zlib extension.
* Remove superflous class method

= 2.6 =
* Initial release.
