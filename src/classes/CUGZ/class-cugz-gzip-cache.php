<?php

namespace CUGZ;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

class GzipCache
{
    /**
     * WordPress options group.
     *
     * @var string
     */
    public static $options_group = 'cugz_options_group';

    /**
     * Link to compare plans.
     *
     * @var string
     */
    public static $learn_more = 'https://wpgzipcache.com/compare-plans/';

    /**
     * Options page url.
     *
     * @var string
     */
    public static $options_page_url = 'tools.php?page=cugz_gzip_cache';

    /**
     * Plugin options array.
     *
     * @var array
     */
    public static $options = [
        'cugz_plugin_post_types' => [
            'name' => 'Cache these types:',
            'type' => 'plugin_post_types',
            'description' => 'Ctrl + click to select/deselect multiple post types.',
            'default_value' => ['post', 'page'],
            'sanitize_callback' => 'CUGZ\GzipCache::cugz_sanitize_array',
        ],
        'cugz_status' => [
            'type' => 'skip_settings_field',
            'default_value' => 'empty',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'cugz_inline_js_css' => [
            'name' => 'Move css/js inline:',
            'type' => 'checkbox',
            'description' => 'Removes links to local css/js and replaces with their contents. This may or may not break some theme layouts or functionality.',
            'default_value' => 0,
            'sanitize_callback' => 'CUGZ\GzipCache::cugz_sanitize_number',
        ],
        'cugz_auto_preload' => [
            'name' => 'Auto preload:',
            'type' => 'select',
            'description' => 'Refresh the entire cache automatically at the specified interval',
            'options' => ['never', 'hourly', 'twicedaily', 'daily', 'weekly'],
            'default_value' => 'never',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'cugz_auto_preload_only' => [
            'name' => 'Only auto preload these types:',
            'type' => 'plugin_post_types',
            'is_enterprise' => true,
            'description' => 'Ctrl + click to select/deselect multiple post types.',
            'default_value' => ['page'],
            'sanitize_callback' => 'CUGZ\GzipCache::cugz_sanitize_array',
        ],
        'cugz_never_cache' => [
            'name' => 'Never cache:',
            'type' => 'text',
            'is_premium' => true,
            'description' => 'Pipe separated list of slugs. Example: my-great-page|another-page|a-third-page-slug',
            'default_value' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'cugz_include_archives' => [
            'name' => 'Cache archives on preload, update, publish:',
            'type' => 'checkbox',
            'is_premium' => true,
            'description' => 'This could increase preload time significantly if you have many categories/tags',
            'default_value' => 0,
            'sanitize_callback' => 'CUGZ\GzipCache::cugz_sanitize_number',
        ],
        'cugz_datepicker' => [
            'name' => 'Don\'t cache items before:',
            'type' => 'datepicker',
            'is_enterprise' => true,
            'description' => 'If you have a large number of pages/posts/etc., specify a date before which items will not be cached.',
            'default_value' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ],
    ];

    /**
     * Post types to be cached.
     *
     * @var array
     */
    public $cugz_plugin_post_types = [];

    /**
     * Cron options for refreshing the home and blog pages.
     *
     * @var array
     */
    public $cugz_auto_preload = [];

    /**
     * Determine which post types to auto preload.
     *
     * @var array
     */
    public $cugz_auto_preload_only = [];

    /**
     * Whether to place CSS inline on cached page.
     *
     * @var int
     */
    public $cugz_inline_js_css = 0;

    /**
     * Current status of cache. preloaded, empty, processing.
     *
     * @var string
     */
    public $cugz_status = '';

    /**
     * WordPress website hostname.
     *
     * @var string
     */
    public $host = '';

    /**
     * Directory path on server in which cache files will be stored.
     *
     * @var string
     */
    public $cache_dir = '';

    /**
     * WordPress website url.
     *
     * @var string
     */
    public $site_url = '';

    /**
     * Current plugin version.
     *
     * @var string
     */
    public $plugin_version = '';

    /**
     * The plugin name.
     *
     * @var string
     */
    public $plugin_name = '';

    /**
     * Complete options page url including admin url.
     *
     * @var string
     */
    public $settings_url = '';

    /**
     * Whether the PHP ZLIB extension is enabled on the server.
     *
     * @var bool
     */
    public $zlib_enabled = true;

    /**
     * Pipe seperated list of page slugs to never cache.
     *
     * @var string
     */
    public $cugz_never_cache = '';

    /**
     * Whether to include archive pages in cache preload.
     *
     * @var int
     */
    public $cugz_include_archives = 0;

    /**
     * A date before which items will not be cached.
     *
     * @var string
     */
    public $cugz_datepicker = '';

    /**
     * Constructor for the class.
     * Initializes class variables and sets up action hooks for option updates.
     */
    public function __construct()
    {
        $plugin_data = get_file_data(CUGZ_PLUGIN_PATH, [
            'Version' => 'Version',
            'Name' => 'Plugin Name',
        ], 'plugin');

        $this->plugin_version = $plugin_data['Version'];

        $this->plugin_name = $plugin_data['Name'];

        $this->site_url = get_site_url();

        $this->host = $this->get_host();

        $this->cache_dir = WP_CONTENT_DIR.'/cugz_gzip_cache/'.$this->host;

        $this->settings_url = admin_url(self::$options_page_url);

        $this->cugz_set_props_from_options();

        $this->cugz_zlib_check();
    }

    /**
     * Sanitizes a given input and returns it as an integer value.
     *
     * @param mixed $input the input to be sanitized
     *
     * @return int the sanitized input as an integer
     */
    public static function cugz_sanitize_number($input)
    {
        return intval($input);
    }

    /**
     * Sanitizes an array by applying the sanitize_text_field function to each element.
     *
     * @param array $input the array to be sanitized
     *
     * @return array the sanitized array
     */
    public static function cugz_sanitize_array($input)
    {
        if (!is_array($input)) {
            return [];
        }

        return array_map('sanitize_text_field', $input);
    }

    /**
     * Adds necessary actions for the plugin.
     */
    public function cugz_add_actions()
    {
        add_action('init', [$this, 'cugz_get_filesystem']);

        add_action('admin_init', [$this, 'cugz_register_settings']);

        add_action('wp_ajax_cugz_callback', [$this, 'cugz_callback']);

        add_action('before_woocommerce_init', [$this, 'cugz_wc_declare_compatibility']);

        add_action('transition_post_status', [$this, 'cugz_transition_post_status'], 10, 3);

        add_action('admin_enqueue_scripts', [$this, 'cugz_enqueue_admin_scripts']);

        add_action('wp_enqueue_scripts', [$this, 'cugz_dequeue_scripts'], 21);

        add_action('cugz_post_options_page', [$this, 'cugz_post_options_page']);

        add_action('cugz_cron_auto_preload', function () {
            $this->cugz_plugin_post_types = $this->cugz_auto_preload_only;
            $this->cugz_preload_cache(false);
        });

        add_action('cugz_options_page_next_auto_preload', [$this, 'cugz_options_page_next_auto_preload']);

        if ($this->zlib_enabled) {
            add_action('admin_menu', [$this, 'cugz_register_options_page']);
        }

        if (!defined('CUGZ_DISABLE_COMMENT') || !CUGZ_DISABLE_COMMENT) {
            add_action('wp_head', [$this, 'cugz_print_comment'], 1);
        }

        if ($cugz_notice = get_transient('cugz_notice')) {
            add_action('admin_notices', function () use ($cugz_notice) {
                $this->cugz_notice($cugz_notice['message'], $cugz_notice['type']);

                delete_transient('cugz_notice');
            });
        }
    }

    /**
     * Adds filters for the plugin.
     *
     * This function checks if zlib is enabled and adds a filter for the plugin action links and plugin row meta.
     */
    public function cugz_add_filters()
    {
        if ($this->zlib_enabled) {
            add_filter('plugin_action_links_'.plugin_basename(CUGZ_PLUGIN_PATH), [$this, 'cugz_settings_link']);
        }

        add_filter('plugin_row_meta', [$this, 'cugz_plugin_row_meta'], 10, 2);
    }

    /**
     * Displays a message on the options page with the next scheduled auto preload, if applicable.
     */
    public function cugz_options_page_next_auto_preload()
    {
        if ($cugz_cron_auto_preload = wp_next_scheduled('cugz_cron_auto_preload')) {
            $dt = new \DateTime("@{$cugz_cron_auto_preload}");
            $dt->setTimezone(wp_timezone());
            echo '<strong>Next auto preload:</strong> <code>'.esc_html($dt->format('F j, Y, g:i a')).'</code>';
        }
    }

    /**
     * Clears the cached value for the specified option.
     *
     * @param mixed  $old_value   the old value of the option
     * @param mixed  $new_value   the new value of the option
     * @param string $option_name the name of the option to clear the cache for
     */
    public static function cugz_on_update_option($old_value, $new_value, $option_name)
    {
        if (array_key_exists($option_name, self::$options)) {
            wp_cache_delete($option_name, 'options');
        }

        switch ($option_name) {
            case 'cugz_auto_preload':
                if ($time = wp_next_scheduled('cugz_cron_auto_preload')) {
                    wp_unschedule_event($time, 'cugz_cron_auto_preload');
                }

                if ('never' !== $new_value) {
                    wp_schedule_event(time(), self::cugz_get_option($option_name), 'cugz_cron_auto_preload');
                }

                break;

            default:
                break;
        }
    }

    /**
     * Retrieves the value of a specific option.
     *
     * @param string $option_name the name of the option to retrieve
     *
     * @return false|mixed the value of the option, or false if the option is skipped
     */
    public static function cugz_get_option($option_name)
    {
        if (isset(self::$options[$option_name]) && self::cugz_skip_option(self::$options[$option_name])) {
            return false;
        }

        $cached_value = wp_cache_get($option_name, 'options');

        if (false === $cached_value) {
            $option_value = get_option($option_name);

            if (false === $option_value) {
                $option_value = self::$options[$option_name]['default_value'] ?? false;

                add_option($option_name, $option_value, '', false);
            }

            wp_cache_set($option_name, $option_value, 'options');

            return $option_value;
        }

        return maybe_unserialize($cached_value);
    }

    /**
     * Displays a notice message on the screen.
     *
     * @param string $message the message to be displayed
     * @param string $type    the type of notice to be displayed
     */
    public function cugz_notice($message, $type)
    {
        ?>
	    <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
	        <p><?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?></p>
	    </div>
	    <?php
    }

    /**
     * Retrieves the WordPress filesystem for use in caching with gzip.
     *
     * @return bool|WP_Filesystem the WordPress filesystem if successful, false otherwise
     */
    public function cugz_get_filesystem()
    {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH.'/wp-admin/includes/file.php';
        }

        $url = wp_nonce_url(self::$options_page_url, 'cache-using-gzip');

        $creds = request_filesystem_credentials($url, '', false, false, null);

        if (!WP_Filesystem($creds)) {
            request_filesystem_credentials($url, '', true, false, null);
        }
    }

    /**
     * Dequeues scripts and styles for Contact Form 7 if the post does not contain a contact form.
     *
     * @global WP_Post $post The current post object.
     */
    public function cugz_dequeue_scripts()
    {
        global $post;

        if ($post) {
            if (false === strpos($post->post_content, '[contact-form-7')) {
                add_filter('wpcf7_load_js', '__return_false');

                add_filter('wpcf7_load_css', '__return_false');

                wp_dequeue_script('google-recaptcha');

                wp_dequeue_script('wpcf7-recaptcha');
            }
        }
    }

    /**
     * Activates the Cache Using Gzip plugin.
     *
     * This function modifies the .htaccess file, sets a transient notice, and updates all plugin options to their default values.
     */
    public function cugz_plugin_activation()
    {
        $this->cugz_modify_htaccess(1);

        set_transient('cugz_notice', [
            'message' => "You may need to preload your cache after activating or deactivating a new plugin or theme. Visit Cache Using Gzip plugin <a href='".esc_url($this->settings_url)."'>settings</a>.",
            'type' => 'success',
        ], 3600);

        foreach (self::$options as $option => $array) {
            if (self::cugz_skip_option($array)) {
                continue;
            }

            update_option($option, $array['default_value'], '', false);
        }
    }

    /**
     * Deactivates the plugin by modifying the .htaccess file, deleting the cache directory, and clearing cached options.
     */
    public function cugz_plugin_deactivation()
    {
        $this->cugz_modify_htaccess();

        $this->cugz_delete_cache_dir(dirname($this->cache_dir));

        foreach (self::$options as $option => $array) {
            wp_cache_delete($option, 'options');

            delete_option($option);
        }

        wp_unschedule_event(wp_next_scheduled('cugz_cron_auto_preload'), 'cugz_cron_auto_preload');
    }

    /**
     * Enqueues necessary scripts and styles for the admin pages.
     *
     * @param string $hook the current admin page hook
     */
    public function cugz_enqueue_admin_scripts($hook)
    {
        if ('edit.php' !== $hook && 'tools_page_cugz_gzip_cache' !== $hook) {
            return;
        }

        $local_args = [
            'nonce' => wp_create_nonce('ajax-nonce'),
            'is_settings_page' => false,
            'options_page_url' => self::$options_page_url,
            'admin_url' => admin_url(),
            'ajax_url' => admin_url('admin-ajax.php'),
        ];

        wp_enqueue_script('cugz_js', plugin_dir_url(CUGZ_PLUGIN_PATH).'js/main.min.js', ['jquery'], $this->plugin_version, true);

        wp_enqueue_style('cugz_css', plugin_dir_url(CUGZ_PLUGIN_PATH).'css/style.min.css', [], $this->plugin_version);

        if ('tools_page_cugz_gzip_cache' === $hook) {
            wp_enqueue_script('jquery-ui-datepicker');

            wp_enqueue_style('jquery-ui-datepicker-style', plugin_dir_url(CUGZ_PLUGIN_PATH).'css/jquery-ui.min.css', [], $this->plugin_version);

            $local_args['is_settings_page'] = true;
        }

        wp_localize_script('cugz_js', 'cugz_ajax_var', $local_args);
    }

    /**
     * Displays the options page for the CUGZ plugin.
     * This page allows users to download the plugin's configuration template.
     */
    public function cugz_post_options_page()
    {
        echo '<a class="button button-float-right" href="'.esc_url(self::cugz_get_config_template_link()).'" target="_blank">Download config</a>';
    }

    /**
     * Returns a string of HTML options for a select input, based on the given value.
     *
     * @param array $value an array of post types to be selected
     *
     * @return string a string of HTML options for a select input
     */
    public static function cugz_get_post_type_select_options($value)
    {
        $options = '';

        $value = $value ?: [];

        $post_types = ['post', 'page'];

        foreach ($post_types as $post_type) {
            $key = array_search($post_type, $value);

            $selected = isset($value[$key]) ? selected($post_type, $value[$key], false) : '';

            $options .= "<option value='{$post_type}' {$selected}>{$post_type}</option>";
        }

        return $options;
    }

    /**
     * This function handles the transition of a post's status.
     *
     * @param string $new_status the new status of the post
     * @param string $old_status the old status of the post
     * @param object $post       the post object
     */
    public function cugz_transition_post_status($new_status, $old_status, $post)
    {
        $status_array = [
            'trash',
            'draft',
            'publish',
        ];

        if (
            'trash' === $old_status
            || 'product' === $post->post_type
            || !in_array($new_status, $status_array)
            || !in_array($post->post_type, $this->cugz_plugin_post_types)
        ) {
            return;
        }

        switch ($new_status) {
            case 'trash':
            case 'draft':
                $clone = clone $post;

                $clone->post_status = 'publish';

                $permalink = str_replace('__trashed', '', get_permalink($clone));

                if ($dir = $this->cugz_create_folder_structure_from_url($permalink)) {
                    $this->cugz_clean_dir($dir);
                }

                break;

            case 'publish':
                $url = get_permalink($post);

                if ($dir = $this->cugz_create_folder_structure_from_url($url)) {
                    $this->cugz_cache_page($url, $dir);
                }

                break;

            default:
                // do nothing

                break;
        }

        $this->cugz_refresh_archives($post);
    }

    /**
     * Refreshes the archives for a given post.
     *
     * @param WP_Post $post the post to refresh the archives for
     */
    public function cugz_refresh_archives($post)
    {
        if (!get_post_type_archive_link($post->post_type)) {
            return;
        }

        foreach ($this->cugz_get_links($post) as $url) {
            if ($dir = $this->cugz_create_folder_structure_from_url($url)) {
                $this->cugz_cache_page($url, $dir);
            }
        }
    }

    /**
     * Declares compatibility for the Custom Order Tables feature in the plugin.
     */
    public function cugz_wc_declare_compatibility()
    {
        if (class_exists(FeaturesUtil::class)) {
            FeaturesUtil::declare_compatibility('custom_order_tables', CUGZ_PLUGIN_PATH);
        }
    }

    /**
     * Retrieves an array of links for the given post.
     *
     * @param null|WP_Post $post Optional. The post object to retrieve links for. Defaults to null.
     *
     * @return array an array of links for the given post
     */
    public function cugz_get_links($post = null)
    {
        global $GzipCachePluginExtras;

        $is_preload = null === $post;

        $links = [];

        $term_ids = [];

        $cat_ids = [];

        if ($is_preload) {
            $links = array_merge($links, $this->cugz_get_posts());
        } else {
            $post_id = method_exists($post, 'get_id') ? $post->get_id() : $post->ID;

            foreach (wp_get_post_tags($post_id) as $tag) {
                $term_ids[] = $tag->term_id;
            }

            $cat_ids = wp_get_post_categories($post_id);
        }

        if (isset($GzipCachePluginExtras)) {
            $links = $GzipCachePluginExtras->get_archive_links($links, $term_ids, $cat_ids);
        }

        return $links;
    }

    /**
     * Creates a folder structure from the given URL.
     *
     * @param string $url the URL to create the folder structure from
     *
     * @return bool|string the path to the created directory, or false if the URL is set to never be cached
     */
    public function cugz_create_folder_structure_from_url($url)
    {
        global $GzipCachePluginExtras;

        if (isset($GzipCachePluginExtras) && $GzipCachePluginExtras->cugz_never_cache($url)) {
            return false;
        }

        $url_parts = wp_parse_url($url);

        $path = isset($url_parts['path']) ? $url_parts['path'] : '';

        $path = trim($path, '/');

        $path_parts = explode('/', $path);

        $current_directory = $this->cache_dir;

        foreach ($path_parts as $part) {
            $part = preg_replace('/[^a-zA-Z0-9-_]/', '', $part);

            $current_directory .= '/'.$part;

            if (!file_exists($current_directory)) {
                wp_mkdir_p($current_directory);
            }
        }

        return $current_directory;
    }

    /**
     * Caches a page by retrieving its HTML content and saving it to a specified directory.
     *
     * @param string $url the URL of the page to be cached
     * @param string $dir The directory where the cached page will be saved. Defaults to the cache directory specified in the class.
     *
     * @return bool returns true if the page was successfully cached, false otherwise
     */
    public function cugz_cache_page($url, $dir = '')
    {
        global $wp_filesystem;

        $dir = $dir ?: $this->cache_dir;

        $url = $url.'?t='.time();

        $args = [];

        if (function_exists('getenv_docker')) {
            $site_url_parsed = wp_parse_url($this->site_url);

            $host = $site_url_parsed['host'] ?? 'localhost';

            $url = str_replace($host, 'host.docker.internal', $url);

            $args = ['timeout' => 15];
        }

        $response = wp_remote_get($url, $args);

        $html = wp_remote_retrieve_body($response);

        if ('1' === $this->cugz_inline_js_css) {
            $html = $this->cugz_parse_html($html);
        }

        $wp_filesystem->put_contents($dir.$this->cugz_get_filename(), $html);

        return $wp_filesystem->put_contents($dir.$this->cugz_get_filename('_gz'), gzencode($html, 9)) ? true : false;
    }

    /**
     * Handles preloading.
     *
     * This function cleans the cache directory, caches the blog and all other relevent pages
     *
     * @param mixed $clean_dir
     */
    public function cugz_preload_cache($clean_dir = true)
    {
        if ($clean_dir) {
            $this->cugz_clean_dir();
        }

        self::update_option('cugz_status', 'processing');

        $this->cugz_cache_blog_page();

        foreach ($this->cugz_get_links() as $url) {
            if ($dir = $this->cugz_create_folder_structure_from_url($url)) {
                $this->cugz_cache_page($url, $dir);
            }
        }

        self::update_option('cugz_status', 'preloaded');
    }

    /**
     * Handles the AJAX callback for the plugin.
     *
     * This function checks for security nonce, and then performs various actions based on the 'do' parameter passed in the AJAX request.
     * The possible actions are: check_status, empty, regen, and single.
     */
    public function cugz_callback()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ajax-nonce')) {
            wp_die('Security check failed');
        }

        $do = isset($_POST['do'])
            ? sanitize_text_field(wp_unslash($_POST['do']))
            : '';

        switch ($do) {
            case 'check_status':
                echo esc_js($this->cugz_status);

                break;

            case 'empty':
                $this->cugz_clean_dir();

                self::update_option('cugz_status', 'empty');

                break;

            case 'regen':
                $this->cugz_preload_cache();

                break;

            case 'single':
                $post_id = isset($_POST['post_id'])
                    ? absint($_POST['post_id'])
                    : 0;

                $post = get_post($post_id);

                $url = get_permalink($post);

                if ($dir = $this->cugz_create_folder_structure_from_url($url)) {
                    $this->cugz_cache_page($url, $dir);

                    $this->cugz_refresh_archives($post);
                }

                break;

            default:
                // do nothing

                break;
        }

        exit;
    }

    /**
     * Adds custom links to the plugin row meta on the plugin screen.
     *
     * @param array  $links an array of plugin row meta links
     * @param string $file  the plugin file path
     *
     * @return array the modified array of plugin row meta links
     */
    public function cugz_plugin_row_meta($links, $file)
    {
        if (plugin_basename(CUGZ_PLUGIN_PATH) !== $file) {
            return $links;
        }

        $upgrade = [
            'docs' => '<a href="'.esc_url(self::$learn_more).'" target="_blank"><span class="dashicons dashicons-star-filled" style="font-size: 14px; line-height: 1.5"></span>Upgrade</a>',
        ];

        $bugs = [
            'bugs' => '<a href="https://github.com/marknokes/cache-using-gzip/issues/new?assignees=marknokes&labels=bug&template=bug_report.md" target="_blank">Submit a bug</a>',
        ];

        return !CUGZ_PLUGIN_EXTRAS ? array_merge($links, $upgrade, $bugs) : array_merge($links, $bugs);
    }

    /**
     * Adds a settings link to the plugin's page on the WordPress admin menu.
     *
     * @param array $links an array of existing links for the plugin
     *
     * @return array the modified array of links with the added settings link
     */
    public function cugz_settings_link($links)
    {
        $settings_link = ["<a href='".esc_url($this->settings_url)."'>Settings</a>"];

        return array_merge($settings_link, $links);
    }

    /**
     * Registers the settings for the plugin.
     */
    public function cugz_register_settings()
    {
        foreach (self::$options as $option => $array) {
            if ('skip_settings_field' === $array['type'] || self::cugz_skip_option($array)) {
                continue;
            }

            // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic
            register_setting(
                self::$options_group,
                $option,
                [
                    'type' => gettype($array['default_value']),
                    'sanitize_callback' => $array['sanitize_callback'],
                ]
            );
        }
    }

    /**
     * Registers the options page for the plugin.
     */
    public function cugz_register_options_page()
    {
        add_management_page('Settings', 'Cache Using Gzip', 'manage_options', 'cugz_gzip_cache', [$this, 'cugz_options_page']);
    }

    /**
     * Displays the options page for the plugin.
     */
    public function cugz_options_page()
    {
        include dirname(CUGZ_PLUGIN_PATH).'/templates/options-page.php';
    }

    /**
     * Retrieves the link to the configuration template based on the server type.
     *
     * @return string the link to the configuration template
     */
    public static function cugz_get_config_template_link()
    {
        $link = '';

        switch (self::cugz_get_server_type()) {
            case 'Nginx':
                $link = plugin_dir_url(CUGZ_PLUGIN_PATH).'templates/nginx.conf.sample';

                break;

            case 'Apache':
            case 'Unknown':
                $link = plugin_dir_url(CUGZ_PLUGIN_PATH).'templates/htaccess.sample';

                break;
        }

        return $link;
    }

    /**
     * Prints a comment in the HTML source code indicating that the performance has been optimized by using Cache Using Gzip.
     */
    public function cugz_print_comment()
    {
        printf("\n\t<!-- %s -->\n", esc_html(sprintf(
            'Performance optimized by Cache Using Gzip. Learn more: %s',
            esc_url(self::$learn_more)
        )));
    }

    /**
     * Sets class properties using self::$options and adds an update_option_{option_name} hook for each one.
     */
    protected function cugz_set_props_from_options()
    {
        foreach (self::$options as $option => $array) {
            if (self::cugz_skip_option($array)) {
                continue;
            }

            $this->{$option} = self::cugz_get_option($option);

            add_action("update_option_{$option}", [$this, 'cugz_on_update_option'], 10, 3);
        }
    }

    /**
     * Check if the zlib extension is enabled and display an admin notice if not.
     */
    protected function cugz_zlib_check()
    {
        if (!extension_loaded('zlib')) {
            $this->zlib_enabled = false;

            set_transient('cugz_notice', [
                'message' => 'Zlib extension is not enabled. You must enable the zlib extension in order to use the <strong>'.esc_html($this->plugin_name).'</strong> plugin.',
                'type' => 'warning',
            ], 3600);
        }
    }

    /**
     * Checks if the given array contains the necessary information to skip a certain option.
     *
     * @param array $array the array containing the necessary information
     *
     * @return bool returns true if the option should be skipped, false otherwise
     */
    protected static function cugz_skip_option($array)
    {
        return isset($array['is_premium']) && $array['is_premium'] && !CUGZ_PLUGIN_EXTRAS
               || isset($array['is_enterprise']) && $array['is_enterprise'] && !CUGZ_ENTERPRISE;
    }

    /**
     * Gets the directory name if wordpress is installed in a subdir.
     *     *
     * @return string name of subdirectory or /
     */
    protected function get_install_dir()
    {
        $wp_path = ABSPATH;

        $doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/').'/';

        $subdirectory = '/';

        if (0 === strpos($wp_path, $doc_root)) {
            $relative_path = trim(str_replace($doc_root, '', $wp_path), '/');

            if ('' !== $relative_path) {
                $subdirectory = '/'.$relative_path.'/';
            }
        }

        return $subdirectory;
    }

    /**
     * Modifies the .htaccess file for the plugin.
     *
     * @param int $action Optional. Determines whether to add or remove the plugin's directives from the .htaccess file.
     *
     * @return bool true on success, false on failure
     */
    protected function cugz_modify_htaccess($action = 0)
    {
        $this->cugz_get_filesystem();

        global $wp_filesystem;

        $file_path = ABSPATH.'.htaccess';

        if (!file_exists($file_path)) {
            return false;
        }

        $existing_content = $wp_filesystem->get_contents($file_path);

        $start_tag = "# BEGIN {$this->plugin_name}";

        $end_tag = "# END {$this->plugin_name}";

        $start_pos = strpos($existing_content, $start_tag);

        $end_pos = strpos($existing_content, $end_tag);

        if (!$action) {
            if (false === $start_pos || false === $end_pos) {
                return false;
            }

            $end_pos += strlen($end_tag) + 1;

            $before_block = substr($existing_content, 0, $start_pos);

            $after_block = substr($existing_content, $end_pos);

            $new_content = $before_block.$after_block;

            if (false === $wp_filesystem->put_contents($file_path, ltrim($new_content))) {
                return false;
            }
        } else {
            if (false !== $start_pos || false !== $end_pos) {
                return false;
            }

            $template = plugin_dir_url(CUGZ_PLUGIN_PATH).'templates/htaccess.sample';

            $directives = $wp_filesystem->get_contents($template);

            $directives = str_replace('[CUGZ_REWRITE_BASE]', $this->get_install_dir(), $directives);

            $new_content = $directives."\n\n".$existing_content;

            if (false === $wp_filesystem->put_contents($file_path, $new_content)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the filename for the specified type, taking into account whether the current connection is secure or not.
     *
     * @param string $type Optional. The type of file to retrieve. Default empty.
     *
     * @return string the filename for the specified type, with the appropriate protocol prefix
     */
    protected function cugz_get_filename($type = '')
    {
        return is_ssl() ? "/index-https.html{$type}" : "/index.html{$type}";
    }

    /**
     * Retrieves an array of post links for the specified post types.
     *
     * @return array an array of post links
     */
    protected function cugz_get_posts()
    {
        $links = [];

        $args = [
            'post_type' => $this->cugz_plugin_post_types,
            'post_status' => 'publish',
            'numberposts' => -1,
        ];

        $args = CUGZ_ENTERPRISE ? GzipCacheEnterprise::get_additional_post_args($args) : $args;

        foreach (get_posts($args) as $item) {
            $links[] = get_permalink($item);
        }

        return $links;
    }

    /**
     * Accepts a string of CSS and replaces relative URL's with absolute ULR's.
     *
     * @param string $css     CSS content
     * @param string $css_url URL to css file
     *
     * @return string returns CSS with corrected URL's before minifying and placing inline
     */
    protected function fix_css_urls($css, $css_url)
    {
        return preg_replace_callback('/url\((["\']?)(?!data:|https?:|\/\/)([^"\')]+)(["\']?)\)/i', function ($matches) use ($css_url) {
            $quote = $matches[1];
            $relative_path = $matches[2];
            $parsed_url = wp_parse_url($css_url);
            $base_url = $parsed_url['scheme'].'://'.$parsed_url['host'];

            if (!empty($parsed_url['port'])) {
                $base_url .= ':'.$parsed_url['port'];
            }

            $base_path = rtrim(dirname($parsed_url['path']), '/').'/';
            $full_path = $base_path.$relative_path;
            $normalized_path = [];
            $parts = explode('/', $full_path);

            foreach ($parts as $part) {
                if ('..' === $part) {
                    array_pop($normalized_path);
                } elseif ('.' !== $part && '' !== $part) {
                    $normalized_path[] = $part;
                }
            }

            $final_path = '/'.implode('/', $normalized_path);

            return 'url('.$quote.$base_url.$final_path.$quote.')';
        }, $css);
    }

    /**
     * Accepts a string on unminified CSS and removes spaces and comments.
     *
     * @param string $css unminified CSS
     *
     * @return string Returns minified CSS
     */
    protected function cugz_minify_css($css)
    {
        $css = preg_replace('/\s+/', ' ', $css); // Remove multiple spaces

        $css = preg_replace('/\/\*(.*?)\*\//', '', $css); // Remove comments

        $css = str_replace(': ', ':', $css); // Remove spaces after colons

        $css = str_replace('; ', ';', $css); // Remove spaces after semicolons

        $css = str_replace(' {', '{', $css); // Remove spaces before opening braces

        $css = str_replace('{ ', '{', $css); // Remove spaces after opening braces

        $css = str_replace('} ', '}', $css); // Remove spaces before closing braces

        $css = str_replace(', ', ',', $css); // Remove spaces after commas

        return trim($css);
    }

    /**
     * Checks if the given source is a local script by comparing it to the host.
     *
     * @param string $src the source to be checked
     *
     * @return bool returns true if the source is a local script, false otherwise
     */
    protected function cugz_is_local_script($src)
    {
        return false !== strpos($src, $this->host);
    }

    /**
     * Parses the given HTML string, minifying any inline CSS and local CSS and JavaScript files.
     *
     * @param string $html the HTML string to be parsed
     *
     * @return string the parsed HTML string
     */
    protected function cugz_parse_html($html)
    {
        $pattern_inline_css = '/<style\b[^>]*>(.*?)<\/style>/s';

        $pattern_css_link = '/<link[^>]+rel\s*=\s*[\'"]?stylesheet[\'"]?[^>]+href\s*=\s*[\'"]?([^\'" >]+)/i';

        $pattern_script_link = '/<script[^>]*\s+src\s*=\s*[\'"]?([^\'" >]+)[^>]*>/i';

        preg_match_all($pattern_inline_css, $html, $matches_inline_css);

        preg_match_all($pattern_css_link, $html, $matches_css_links);

        preg_match_all($pattern_script_link, $html, $matches_script_links);

        foreach ($matches_inline_css[0] as $inline_css) {
            $html = str_replace($inline_css, $this->cugz_minify_css($inline_css), $html);
        }

        foreach ($matches_css_links[1] as $css_link) {
            if ($this->cugz_is_local_script($css_link)) {
                $css_content = wp_remote_retrieve_body(wp_remote_get($css_link));

                if (false === $css_content) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('Error: Unable to retrieve css content from '.$css_link);
                }

                $css_content = $this->fix_css_urls($css_content, $css_link);

                $html = preg_replace('/<link[^>]+rel\s*=\s*[\'"]?stylesheet[\'"]?[^>]+href\s*=\s*[\'"]?'.preg_quote($css_link, '/').'[\'"]?[^>]*>/i', '<style>'.$this->cugz_minify_css($css_content).'</style>', $html);
            }
        }

        return preg_replace_callback($pattern_script_link, function ($matches) {
            if ($this->cugz_is_local_script($matches[1])) {
                $script_content = wp_remote_retrieve_body(wp_remote_get($matches[1]));

                if (false === $script_content) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('Error: Unable to retrieve script content from '.$matches[1]);
                }

                if (0 === strpos($script_content, 'import')) {
                    return '<script type="module">'.$script_content;
                }

                return '<script>'.$script_content;
            }

            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
            return '<script src="'.$matches[1].'">';
        }, $html);
    }

    /**
     * Deletes a cache directory and all its contents.
     *
     * @param string $dir the directory path to be deleted
     *
     * @return bool true if the directory was successfully deleted, false otherwise
     */
    protected function cugz_delete_cache_dir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        global $wp_filesystem;

        $files = glob($dir.'/*');

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->cugz_delete_cache_dir($file);
            } else {
                wp_delete_file($file);
            }
        }

        return $wp_filesystem->rmdir($dir);
    }

    /**
     * Cleans a given directory by removing all files and subdirectories within it.
     *
     * @param string $dir The directory to be cleaned. If left empty, the cache directory will be used.
     */
    protected function cugz_clean_dir($dir = '')
    {
        if ('' === $dir) {
            $dir = $this->cache_dir;
        }

        global $wp_filesystem;

        if (file_exists($dir)) {
            $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($ri as $file) {
                $file->isDir() ? $wp_filesystem->rmdir($file) : wp_delete_file($file);
            }
        } else {
            wp_mkdir_p($dir);
        }
    }

    /**
     * Caches the blog page by creating a folder structure from the given URL and caching the page.
     */
    protected function cugz_cache_blog_page()
    {
        $url = get_post_type_archive_link('post');

        if ($dir = $this->cugz_create_folder_structure_from_url($url)) {
            $this->cugz_cache_page($url, $dir);
        }
    }

    /**
     * Retrieves the type of server software being used.
     *
     * @return string the type of server software, either "Apache", "Nginx", or "Unknown"
     */
    protected static function cugz_get_server_type()
    {
        $server_software = isset($_SERVER['SERVER_SOFTWARE'])
            ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']))
            : '';

        if (false !== strpos($server_software, 'Apache')) {
            return 'Apache';
        }
        if (false !== strpos($server_software, 'nginx')) {
            return 'Nginx';
        }

        return 'Unknown';
    }

    /**
     * Retrieves the hostname for use in the cache directory file path.
     *
     * @return string the hostname or localhost if unable to determine
     */
    private function get_host()
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } elseif ($env_host = getenv('HTTP_HOST')) {
            $host = $env_host;
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        } else {
            $host = 'localhost';
        }

        $host = strtolower($host);
        $host = preg_replace('/:\d+$/', '', $host); // remove port if present
        $host = preg_replace('/[^a-z0-9\-\.]/', '_', $host); // replace unwanted chars

        return $host;
    }

    /**
     * Updates the value of a specified option in the WordPress database.
     *
     * @param string $option the name of the option to be updated
     * @param mixed  $value  the new value for the option
     */
    private static function update_option($option, $value)
    {
        self::cugz_on_update_option('', $value, $option);

        update_option($option, $value, false);
    }
}
