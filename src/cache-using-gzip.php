<?php
/**
 * Plugin Name: Cache Using Gzip
 * Version: 2.8.3
 * Description: Creates gzip files on your server to immensly improve page speed for site visitors
 * Author: Cache Using Gzip
 * Author URI: https://wpgzipcache.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 8.6.0
 * WC tested up to: 9.3.3
 * Requires at least: 6.4.3
 * Tested up to: 6.7.2
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

$GzipCache = new GzipCache();

if (!extension_loaded('zlib')) {

	$GzipCache->zlib_enabled = false;

    add_action('admin_notices', function() use ($GzipCache) {

		$GzipCache->cugz_notice("Zlib extension is not enabled. You must enable the zlib extension in order to use the <strong>" . esc_html($GzipCache->plugin_name) . "</strong> plugin.", "warning");

	});

}

$GzipCache->GzipCachePluginExtras = CUGZ_PLUGIN_EXTRAS ? new GzipCachePluginExtras(): NULL;

register_activation_hook(CUGZ_PLUGIN_PATH, [$GzipCache, 'cugz_plugin_activation']);

register_deactivation_hook(CUGZ_PLUGIN_PATH, [$GzipCache, 'cugz_plugin_deactivation']);

$GzipCache->cugz_add_actions();

$GzipCache->cugz_add_filters();
