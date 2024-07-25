<?php

namespace CUGZ;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

use CUGZ\GzipCacheEnterprise;

class GzipCache
{
	public static $options_group = "cugz_options_group";

	public static $learn_more = "https://wpgzipcache.com/compare-plans/";

	public static $options_page_url = "tools.php?page=cugz_gzip_cache";

	public static $options = [
		'cugz_plugin_post_types' => [
			'name' => 'Cache these types:',
			'type' => 'plugin_post_types',
			'description' => 'Ctrl + click to select/deselect multiple post types.',
			'default_value' => ['post', 'page']
		],
		'cugz_status' => [
			'type' => 'skip_settings_field',
			'default_value' => 'empty'
		],
		'cugz_inline_js_css' => [
            'name' => 'Move css/js inline',
            'type' => 'checkbox',
            'description' => 'Removes links to local css/js and replaces with their contents. This may or may not break some theme layouts or functionality.',
            'default_value' => 0
        ],
        'cugz_never_cache' => [
            'name' => 'Never cache:',
            'type' => 'text',
            'is_premium' => true,
            'description' => 'Pipe separated list of slugs. Example: my-great-page|another-page|a-third-page-slug',
            'default_value' => ''
        ],
        'cugz_include_archives' => [
            'name' => 'Cache archives on preload, update, publish',
            'type' => 'checkbox',
            'is_premium' => true,
            'description' => 'This could increase preload time significantly if you have many categories/tags',
            'default_value' => 0
        ],
        'cugz_datepicker' => [
        	'name' => 'Don\'t cache items before',
        	'type' => 'datepicker',
        	'is_enterprise' => true,
        	'description' => 'If you have a large number of pages/posts/etc., specify a date before which items will not be cached.',
        	'default_value' => '' 
        ]
	];

	public $cugz_plugin_post_types,
		   $cugz_inline_js_css,
		   $cugz_status,
		   $host,
		   $cache_dir,
		   $site_url,
		   $plugin_version,
		   $plugin_name,
		   $settings_url,
		   $zlib_enabled = true,
		   $cugz_never_cache,
		   $cugz_include_archives,
		   $cugz_datepicker,
		   $GzipCachePluginExtras;

	public function __construct()
	{
		foreach (self::$options as $option => $array)
        {
        	if(self::cugz_skip_option($array)) continue;

        	$this->$option = self::cugz_get_option($option);

            add_action("update_option_$option", [$this, 'cugz_clear_option_cache'], 10, 3);
        }

		$plugin_data = get_file_data(CUGZ_PLUGIN_PATH, [
            'Version' => 'Version',
            'Name'    => 'Plugin Name'
        ], 'plugin');

        $this->plugin_version = $plugin_data['Version'];

        $this->plugin_name = $plugin_data['Name'];

		$this->site_url = get_site_url();

		$this->host = getenv('HTTP_HOST');

		$this->cache_dir = strtok(WP_CONTENT_DIR . "/cugz_gzip_cache/" . $this->host, ':');

		$this->settings_url = admin_url(self::$options_page_url);
	}

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

		if($this->zlib_enabled) {

			add_action('admin_menu', [$this, 'cugz_register_options_page']);

		}

		if (!defined('CUGZ_DISABLE_COMMENT') || !CUGZ_DISABLE_COMMENT) {

            add_action('wp_head', [$this, 'cugz_print_comment'], 1);

        }

        if($cugz_notice = get_transient('cugz_notice')) {

            add_action('admin_notices', function() use ($cugz_notice) {

                $this->cugz_notice($cugz_notice['message'], $cugz_notice['type']);

                delete_transient('cugz_notice');

            });

        }
    }

    public function cugz_add_filters()
    {
    	if($this->zlib_enabled) {

    		add_filter('plugin_action_links_' . plugin_basename(CUGZ_PLUGIN_PATH), [$this, 'cugz_settings_link']);

		}

    	add_filter('plugin_row_meta', [$this, 'cugz_plugin_row_meta'], 10, 2);
    }

    public static function cugz_clear_option_cache($old_value, $new_value, $option_name)
	{
		if (array_key_exists($option_name, self::$options)) {
			
			wp_cache_delete($option_name, 'options');

		}
	}

	private static function update_option($option, $value)
	{
		self::cugz_clear_option_cache('', $value, $option);
		
		update_option($option, $value, false);
	}

	public static function cugz_get_option($option_name)
	{
		if(self::cugz_skip_option(self::$options[$option_name])) return false;

	    $cached_value = wp_cache_get($option_name, 'options');
	    
	    if ($cached_value === false) {

	        $option_value = get_option($option_name);

	        if ($option_value !== false) {

	            wp_cache_set($option_name, $option_value, 'options');

	        }

	        return $option_value;

	    } else {

	        return $cached_value;

	    }
	}

	public function cugz_notice($message, $type)
	{
		?>
	    <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
	        <p><?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	    </div>
	    <?php
	}

    public function cugz_get_filesystem()
    {
    	if (!function_exists('WP_Filesystem')) {
    		
			require_once ABSPATH . '/wp-admin/includes/file.php';
			
		}

		$url = wp_nonce_url(self::$options_page_url, 'cache-using-gzip');

		$creds = request_filesystem_credentials($url, '', false, false, null);

		if (!WP_Filesystem($creds)) {

			request_filesystem_credentials($url, '', true, false, null);

		}
    }

    public function cugz_dequeue_scripts()
    {
	    global $post;

	    if($post) {
	    
		    if (strpos($post->post_content, '[contact-form-7') === false) {

		        add_filter('wpcf7_load_js', '__return_false');

				add_filter('wpcf7_load_css', '__return_false');

				wp_dequeue_script('google-recaptcha');

				wp_dequeue_script('wpcf7-recaptcha');

		    }
	    }
	}

	protected static function cugz_skip_option($array)
	{
		return isset($array['is_premium']) && $array['is_premium'] && !CUGZ_PLUGIN_EXTRAS ||
			   isset($array['is_enterprise']) && $array['is_enterprise'] && !CUGZ_ENTERPRISE;
	}

	protected function cugz_modify_htaccess($action = 0)
	{
		$this->cugz_get_filesystem();

		global $wp_filesystem;

	    $file_path = ABSPATH . ".htaccess";

	    if (!file_exists($file_path)) {

	        return false;

	    }

	    $existing_content = $wp_filesystem->get_contents($file_path);
	    
	    $start_tag = "# BEGIN {$this->plugin_name}";

	    $end_tag = "# END {$this->plugin_name}";
	    
	    $start_pos = strpos($existing_content, $start_tag);

	    $end_pos = strpos($existing_content, $end_tag);

	    if(!$action) {

	    	if ($start_pos === false || $end_pos === false) {

		        return false;

		    }

	    	$end_pos += strlen($end_tag) + 1;

		    $before_block = substr($existing_content, 0, $start_pos);

		    $after_block = substr($existing_content, $end_pos);

		    $new_content = $before_block . $after_block;
		    
		    if ($wp_filesystem->put_contents($file_path, ltrim($new_content)) === false) {

		        return false;

		    }

	    } else {

	    	if ($start_pos !== false || $end_pos !== false) {

		        return false;

		    }

	    	$template = plugin_dir_url(CUGZ_PLUGIN_PATH) . "templates/htaccess.sample";

	    	$directives = $wp_filesystem->get_contents($template);

	    	$new_content = $directives . "\n\n" . $existing_content;

		    if ($wp_filesystem->put_contents($file_path, $new_content) === false) {

		        return false;

		    }

	    }

	    return true;
	}

	public function cugz_plugin_activation()
    {
    	$this->cugz_modify_htaccess(1);

    	set_transient('cugz_notice', [
            'message' => "You may need to preload your cache after activating or deactivating a new plugin or theme. Visit Cache Using Gzip plugin <a href='" . esc_url($this->settings_url) . "'>settings</a>.",
            'type'    => "success"
        ], 3600);

    	foreach (self::$options as $option => $array)
		{
			if(self::cugz_skip_option($array)) continue;

			update_option($option, $array['default_value'], '', false);
		}
    }

    public function cugz_plugin_deactivation()
    {
    	$this->cugz_modify_htaccess();

    	$this->cugz_delete_cache_dir(dirname($this->cache_dir));

        foreach (self::$options as $option => $array)
        {
            wp_cache_delete($option, 'options');
            
            delete_option($option);
        }
    }

	protected function cugz_get_filename($type = "")
	{
		return is_ssl() ? "/index-https.html$type": "/index.html$type";
	}

	public function cugz_enqueue_admin_scripts($hook)
	{
		if ('edit.php' !== $hook && 'tools_page_cugz_gzip_cache' !== $hook) {

			return;

		}

		$local_args = [
			'nonce' => wp_create_nonce('ajax-nonce'),
			'is_settings_page' => false,
			'options_page_url' => self::$options_page_url
		];

		wp_enqueue_script('cugz_js', plugin_dir_url(CUGZ_PLUGIN_PATH) . 'js/main.min.js', ['jquery'], $this->plugin_version, true);

		wp_enqueue_style('cugz_css', plugin_dir_url(CUGZ_PLUGIN_PATH) . 'css/style.min.css', [], $this->plugin_version);

		if('tools_page_cugz_gzip_cache' === $hook) {

			wp_enqueue_script('jquery-ui-datepicker');

    		wp_enqueue_style('jquery-ui-datepicker-style', plugin_dir_url(CUGZ_PLUGIN_PATH) . 'css/jquery-ui.min.css', [], $this->plugin_version);

    		$local_args['is_settings_page'] = true;
    		
		}

		wp_localize_script('cugz_js', 'cugz_ajax_var', $local_args);
	}

	public function cugz_post_options_page()
    {
        echo '<a class="button button-float-right" href="' . esc_url(self::cugz_get_config_template_link()) . '" target="_blank">Download config</a>';
    }

	public static function cugz_get_post_type_select_options($value)
    {
        $options = "";

        $value = $value ?: [];

        $post_types = ['post', 'page'];

        foreach($post_types as $post_type)
        {   
            $key = array_search($post_type, $value);

            $selected = selected($post_type, $value[$key], false);

            $options .= "<option value='$post_type' $selected>$post_type</option>";
        }

        return $options;
    }

	public function cugz_transition_post_status($new_status, $old_status, $post)
	{
		$status_array = [
			'trash',
			'draft',
			'publish'
		];

		if(
			$old_status === "trash" ||
			$post->post_type === "product" ||
			!in_array($new_status, $status_array) ||
			!in_array($post->post_type, $this->cugz_plugin_post_types)
		) {

			return;

		}

		switch ($new_status)
		{
			case 'trash':
			case 'draft':

				$clone = clone $post;

				$clone->post_status = 'publish';

				$permalink = str_replace("__trashed", "", get_permalink($clone));
				
				if ($dir = $this->cugz_create_folder_structure_from_url($permalink)) {

					$this->cugz_clean_dir($dir);

				}

				break;

			case 'publish':

				$url = get_permalink($post);

				if($dir = $this->cugz_create_folder_structure_from_url($url)) {

					$this->cugz_cache_page($url, $dir);

				}

				break;
			
			default:

				// do nothing
				
				break;
		}

		$this->cugz_refresh_archives($post);
	}

	public function cugz_refresh_archives($post)
	{
		if(!get_post_type_archive_link($post->post_type)) {

			return;

		}

	    foreach ($this->cugz_get_links($post) as $url)
	    {
	    	if($dir = $this->cugz_create_folder_structure_from_url($url)) {
				
				$this->cugz_cache_page($url, $dir);

	    	}
	    }
	}

	public function cugz_wc_declare_compatibility()
	{
		if (class_exists(FeaturesUtil::class)) {

			FeaturesUtil::declare_compatibility('custom_order_tables', CUGZ_PLUGIN_PATH);
			
		}
	}

	public function cugz_get_links($post = null)
	{
		$is_preload = $post === null;

	    $links = [];

	    $term_ids = [];

	    $cat_ids = [];

	    if($is_preload) {

		    $links = array_merge($links, $this->cugz_get_posts());

	    } else {

	    	$post_id = method_exists($post, 'get_id') ? $post->get_id() : $post->ID;

	    	foreach (wp_get_post_tags($post_id) as $tag)
			{
				$term_ids[] = $tag->term_id;
			}

			$cat_ids = wp_get_post_categories($post_id);
	    }

	    if (isset($this->GzipCachePluginExtras)) {

			$links = $this->GzipCachePluginExtras->get_archive_links($links, $term_ids, $cat_ids);

		}

	    return $links;
	}

	protected function cugz_get_posts()
	{
		$links = [];

	    $args = [
			'post_type'   => $this->cugz_plugin_post_types,
			'post_status' => 'publish',
			'numberposts' => -1
		];

		$args = CUGZ_ENTERPRISE ? GzipCacheEnterprise::get_additional_post_args($args): $args;

	    foreach (get_posts($args) as $item)
	    {
	        $links[] = get_permalink($item);
	    }

	    return $links;
	}

	public function cugz_create_folder_structure_from_url($url)
	{
		if (isset($this->GzipCachePluginExtras) && $this->GzipCachePluginExtras->cugz_never_cache($url)) {

			return false;

		}

	    $url_parts = wp_parse_url($url);

	    $path = isset($url_parts['path']) ? $url_parts['path'] : '';

	    $path = trim($path, '/');

	    $path_parts = explode('/', $path);

	    $current_directory = $this->cache_dir;

	    foreach ($path_parts as $part)
	    {
	        $part = preg_replace('/[^a-zA-Z0-9-_]/', '', $part);

	        $current_directory .= '/' . $part;

	        if (!file_exists($current_directory)) {

	        	wp_mkdir_p($current_directory);

	        }
	    }

	    return $current_directory;
	}

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

	protected function cugz_is_local_script($src)
	{
		return false !== strpos($src, $this->host);
	}

	protected function cugz_parse_html($html)
	{
		$pattern_inline_css = '/<style\b[^>]*>(.*?)<\/style>/s';

	    $pattern_css_link = '/<link[^>]+rel\s*=\s*[\'"]?stylesheet[\'"]?[^>]+href\s*=\s*[\'"]?([^\'" >]+)/i';

	    $pattern_script_link = '/<script[^>]*\s+src\s*=\s*[\'"]?([^\'" >]+)[^>]*>/i';

	    preg_match_all($pattern_inline_css, $html, $matches_inline_css);

	    preg_match_all($pattern_css_link, $html, $matches_css_links);

	    preg_match_all($pattern_script_link, $html, $matches_script_links);

	    foreach ($matches_inline_css[0] as $inline_css)
	    {
	        $html = str_replace($inline_css, $this->cugz_minify_css($inline_css), $html);
	    }
	    
	    foreach ($matches_css_links[1] as $css_link)
	    {
	    	if($this->cugz_is_local_script($css_link)) {

	        	$css_content = wp_remote_retrieve_body(wp_remote_get($css_link));

	        	if ($css_content === false) {

		            error_log('Error: Unable to retrieve css content from ' . $css_link);
		            
		        }
	       
	        	$html = preg_replace('/<link[^>]+rel\s*=\s*[\'"]?stylesheet[\'"]?[^>]+href\s*=\s*[\'"]?' . preg_quote($css_link, '/') . '[\'"]?[^>]*>/i', '<style>' . $this->cugz_minify_css($css_content) . '</style>', $html);
	        
	        }
	    }

	    $html = preg_replace_callback($pattern_script_link, function($matches) {

	    	if($this->cugz_is_local_script($matches[1])) {

	    		$script_content = wp_remote_retrieve_body(wp_remote_get($matches[1]));
		        
		        if ($script_content === false) {

		            error_log('Error: Unable to retrieve script content from ' . $matches[1]);
		            
		        }

		        return '<script>' . $script_content;

	    	} else {
	    		
	    		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
	    		return '<script src="'.$matches[1].'">';
		        
	    	}

	    }, $html);
	    
	    return $html;
	}

	public function cugz_cache_page($url, $dir = "")
	{
		global $wp_filesystem;

		$dir = $dir ?: $this->cache_dir;

		$url = $url . "?t=" . time();

		$args = [];

		if(function_exists('getenv_docker')) {

			$site_url_parsed = wp_parse_url($this->site_url);

			$host = $site_url_parsed['host'] ?? "localhost";

			$url = str_replace($host, "host.docker.internal", $url);

			$args = ['timeout' => 15];
		}

		$response = wp_remote_get($url, $args);

	    $html = wp_remote_retrieve_body($response);

		if("1" === $this->cugz_inline_js_css) {
        
			$html = $this->cugz_parse_html($html);

		}

        $wp_filesystem->put_contents($dir . $this->cugz_get_filename(), $html);

        return $wp_filesystem->put_contents($dir . $this->cugz_get_filename("_gz"), gzencode($html, 9)) ? true: false;
	}

	protected function cugz_delete_cache_dir($dir)
	{
	    if (!is_dir($dir)) {

	        return false;

	    }

	    global $wp_filesystem;

	    $files = glob($dir . '/*');

	    foreach ($files as $file)
	    {
	        if (is_dir($file)) {

	            $this->cugz_delete_cache_dir($file);

	        } else {

	            wp_delete_file($file);

	        }
	    }

	    return $wp_filesystem->rmdir($dir);
	}

	protected function cugz_clean_dir($dir = "")
	{
		if("" === $dir) {

			$dir = $this->cache_dir;

		}

		global $wp_filesystem;

		if(file_exists($dir)) {
			
			$ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
			
			foreach ($ri as $file)
			{
				$file->isDir() ? $wp_filesystem->rmdir($file) : wp_delete_file($file);
			}

		} else {

			wp_mkdir_p($dir);

		}
	}

	protected function cugz_cache_blog_page()
	{
		$url = get_post_type_archive_link('post');

		if($dir = $this->cugz_create_folder_structure_from_url($url)) {
						
			$this->cugz_cache_page($url, $dir);

    	}
	}

	public function cugz_callback()
	{
		if (!isset( $_POST['nonce'] ) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ajax-nonce')) {

			wp_die("Security check failed");

		}

		$do = sanitize_text_field(wp_unslash($_POST['do'])) ?: '';

		switch ($do)
		{
			case 'check_status':

				echo esc_js($this->cugz_status);

				break;

			case 'empty':

				$this->cugz_clean_dir();

				self::update_option('cugz_status', 'empty');

				break;
			
			case 'regen':

				$this->cugz_clean_dir();

				self::update_option('cugz_status', 'processing');

				$this->cugz_cache_blog_page();

			    foreach ($this->cugz_get_links() as $url)
			    {
			    	if($dir = $this->cugz_create_folder_structure_from_url($url)) {
						
						$this->cugz_cache_page($url, $dir);

			    	}
			    }

			    self::update_option('cugz_status', 'preloaded');

				break;

			case 'single':

				$post_id = absint($_POST['post_id']);

				$post = get_post($post_id);

				$url = get_permalink($post);

				if($dir = $this->cugz_create_folder_structure_from_url($url)) {
						
					$this->cugz_cache_page($url, $dir);

					$this->cugz_refresh_archives($post);

		    	}

		    	break;

			default:

				// do nothing

				break;
		}

		die;
	}

	public function cugz_plugin_row_meta($links, $file)
	{    
		if(plugin_basename(CUGZ_PLUGIN_PATH) !== $file) {

			return $links;

		}

		$upgrade = [
			'docs' => '<a href="' . esc_url(self::$learn_more) . '" target="_blank"><span class="dashicons dashicons-star-filled" style="font-size: 14px; line-height: 1.5"></span>Upgrade</a>'
		];

		$bugs = [
			'bugs' => '<a href="https://github.com/marknokes/cache-using-gzip/issues/new?assignees=marknokes&labels=bug&template=bug_report.md" target="_blank">Submit a bug</a>'
		];

	    return !CUGZ_PLUGIN_EXTRAS ? array_merge($links, $upgrade, $bugs): array_merge($links, $bugs);
	}

	public function cugz_settings_link($links)
	{   
		$settings_link = ["<a href='" . esc_url($this->settings_url) . "'>Settings</a>"];

        return array_merge($settings_link, $links);
	}

	public function cugz_register_settings()
	{
		foreach (self::$options as $option => $array)
		{
			if('skip_settings_field' === $array['type'] || self::cugz_skip_option($array)) continue;
		
			register_setting(self::$options_group, $option);
		}
	}

	public function cugz_register_options_page()
	{
		add_management_page('Settings', 'Cache Using Gzip', 'manage_options', 'cugz_gzip_cache', [$this,'cugz_options_page'] );
	}

	public function cugz_options_page()
	{
		include dirname(CUGZ_PLUGIN_PATH) . '/templates/options-page.php';
	}

	protected static function cugz_get_server_type()
	{
		$server_software = sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) ?? '';

	    if (strpos($server_software, 'Apache') !== false) {

	        return 'Apache';

	    } elseif (strpos($server_software, 'nginx') !== false) {

	        return 'Nginx';

	    } else {

	        return 'Unknown';

	    }
	}

	public static function cugz_get_config_template_link()
	{
		$link = "";

		switch(self::cugz_get_server_type()) {

        	case 'Nginx':

        		$link = plugin_dir_url(CUGZ_PLUGIN_PATH) . "templates/nginx.conf.sample";

        		break;

        	case 'Apache':
        	case 'Unknown':

        		$link = plugin_dir_url(CUGZ_PLUGIN_PATH) . "templates/htaccess.sample";

        		break;
        }

        return $link;
	}

    public function cugz_print_comment()
    {
		printf("\n\t<!-- %s -->\n", esc_html(sprintf(
			'Performance optimized by Cache Using Gzip. Learn more: %s',
			esc_url(self::$learn_more)
		)));

		return;
    }
}
