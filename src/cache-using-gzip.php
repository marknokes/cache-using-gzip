<?php
/**
 * Plugin Name: Cache Using Gzip
 * Version: 2.7.3
 * Description: Creates gzipped files on your server to immensly improve page speed for site visitors
 * Author: Cache Using Gzip
 * Author URI: https://wpgzipcache.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 8.6.0
 * WC tested up to: 8.7.0
 * Requires at least: 6.4.3
 * Tested up to: 6.5.4
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

spl_autoload_register(function ($class_name) {

    $file =  __DIR__ . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR  . preg_replace("~[\\\\/]~", DIRECTORY_SEPARATOR, $class_name) . ".php";
    
    if(file_exists($file)) {

    	require_once $file;

    }
    
});

define('CUGZ_PLUGIN_PATH', __FILE__);

define('CUGZ_PERMISSIONS', class_exists('\CUGZ\GzipCachePermissions'));

$GzipCachePermissions = CUGZ_PERMISSIONS ? new \CUGZ\GzipCachePermissions(): NULL;

$GzipCache = \CUGZ\GzipCache::get_instance();
