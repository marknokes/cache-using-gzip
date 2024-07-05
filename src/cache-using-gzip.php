<?php
/**
 * Plugin Name: Cache Using Gzip
 * Version: 2.7.6
 * Description: Creates gzipped files on your server to immensly improve page speed for site visitors
 * Author: Cache Using Gzip
 * Author URI: https://wpgzipcache.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 8.6.0
 * WC tested up to: 8.7.0
 * Requires at least: 6.4.3
 * Tested up to: 6.5.5
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

use CUGZ\GzipCache;

use CUGZ\GzipCachePluginExtras;

use CUGZ\GzipCacheEnterprise;

require_once 'autoload.php';

spl_autoload_register('cugz_autoload');

define('CUGZ_PLUGIN_PATH', __FILE__);

define('CUGZ_PLUGIN_EXTRAS', class_exists(GzipCachePluginExtras::class));

define('CUGZ_ENTERPRISE', class_exists(GzipCacheEnterprise::class));

new GzipCache(true);
