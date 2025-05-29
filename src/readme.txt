=== Cache Using Gzip ===
Contributors: marknokes
Tags: gzip, cache, speed, performance, nginx
Requires at least: 6.4.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.8.7
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates gzip files on your server to immensly improve page speed for site visitors

== Description ==

The [Cache Using Gzip](https://wpgzipcache.com/) plugin for WordPress improves page speed for site visitors. Preload the
cache in settings, download the required config settings and add to your Apache or Nginx server config, and all of
your posts and pages will be delivered to site visitors blazingly fast!

= Cache Using Gzip Features =
- Unlimited downloads
- Unlimited domains 

= Cache Using Gzip Premium Features =
- Unlimited downloads
- 1 domain
- Support for custom post types
- A cache link on posts and pages let you cache individual items on the fly
- Cache WooCommerce products and product category/tag archives
- Exclude a list of page slugs from ever being cached
- Use the bulk edit menu for pages, posts, etc. to cache a selection 

= Cache Using Gzip Enterprise Features =
- Unlimited downloads
- Unlimited domains
- Support for custom post types
- A cache link on posts and pages let you cache individual items on the fly
- Cache WooCommerce products and product category/tag archives
- Exclude a list of page slugs from ever being cached
- Use the bulk edit menu for pages, posts, etc. to cache a selection
- Specify a date before which items will not be cached
- Enterprise priority support 

[Compare plans at https://wpgzipcache.com/compare-plans/](https://wpgzipcache.com/compare-plans/)

== Frequently Asked Questions ==

= Where can I report bugs? =

Please report confirmed bugs on the Cache Using Gzip [github](https://github.com/marknokes/cache-using-gzip/issues/new?assignees=marknokes&labels=bug&template=bug_report.md) directly. Include any screenshots and as much detail as possible.

= On what web servers can this plugin be installed =

The Cache Using Gzip plugin works on Apache and Nginx servers. After the plugin is intalled and activated, you may
download the required configuration from the plugin settings page (Tools => Cache Using Gzip).

== Installation ==

= Requirements =

* WordPress Version 6.4.3 or newer (installed)
* Apache or Nginx web server
* PHP Version 7.4 or newer
* PHP ZLIB extension enabled
* The Cache Using Gzip plugin requires a "pretty" URL. Confirm your permalink structure is something other than "Plain" (Settings => Permalinks).

= Installation instructions =

1. Log in to WordPress admin.
2. Go to **Plugins > Add New**.
3. Search for the **Cache Using Gzip** plugin.
4. Click on **Install Now** and wait until the plugin is installed successfully.
5. Activate the plugin by clicking **Activate** now on the success page.
6. If you're running WordPress on an Apache server, the .htaccess file should be modified on activation. If it wasn't, visit the plugin settings page (Tools => Cache Using Gzip) to download the reqired apache/nginx config rules.
7. Add the rules to your .htaccess or nginx.conf, if necessary.

== Screenshots ==

1. Main settings screen
2. Single page cache link (pro feature)

== Upgrade Notice ==

Automatic updates should work generally smoothly, but we still recommend you back up your site.

== Changelog ==

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
