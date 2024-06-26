=== Cache Using Gzip ===
Contributors: marknokes
Tags: gzip, cache, speed, performance, nginx
Requires at least: 6.4.3
Tested up to: 6.5.5
Requires PHP: 7.4
Stable tag: 2.7.4
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates gzipped files on your server to immensly improve page speed for site visitors

== Description ==

The [Cache Using Gzip](https://wpgzipcache.com/) plugin for WordPress improves page speed for site visitors. Preload the
cache in settings, download the required config settings and add to your Apache or Nginx server config, and all of
your posts and pages will be delivered to site visitors blazingly fast!

== Frequently Asked Questions ==

= Where can I report bugs? =

Please report confirmed bugs on the Cache Using Gzip [github](https://github.com/marknokes/cache-using-gzip/issues/new?assignees=marknokes&labels=bug&template=bug_report.md) directly. Include any screenshots and as much detail as possible.

= On what web servers can this plugin be installed =

The Cache Using Gzip plugin works on Apache and Nginx servers. After the plugin is intalled and activated, you may
download the required configuration from the plugin settings page.

== Installation ==

= Requirements =

* WordPress Version 6.4.3 or newer (installed)
* PHP Version 7.2 or newer

= Installation instructions =

1. Log in to WordPress admin.
2. Go to **Plugins > Add New**.
3. Search for the **Cache Using Gzip** plugin.
4. Click on **Install Now** and wait until the plugin is installed successfully.
5. Activate the plugin by clicking **Activate** now on the success page.
6. Visit the plugin settings page to download the reqired apache/nginx config rules.
7. Add the rules to your .htaccess or nginx.conf 

== Screenshots ==

1. Main settings screen
2. Single page cache link (pro feature)

== Upgrade Notice ==

Automatic updates should work generally smoothly, but we still recommend you back up your site.

== Changelog ==

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
