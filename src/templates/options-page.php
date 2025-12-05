<?php

if (!defined('ABSPATH')) {
    exit;
}

use CUGZ\GzipCacheEnterprise;
use CUGZ\GzipCachePluginExtras;

?>

<div class="wrap">

	<div class="flex-container">

		<div class="cugz-wrap">

			<h2>Cache Using Gzip Settings</h2>

			<form method="post" action="options.php">
				<?php settings_fields(self::$cugz_options_group); ?>
				<table>
					<?php
                    foreach (self::$cugz_options as $cugz_option => $cugz_array) {
                        if (isset($cugz_array['is_premium'])) {
                            $cugz_is_premium = $cugz_array['is_premium'];

                            $cugz_feature = 'Premium';

                            $cugz_disabled = $cugz_is_premium && (!CUGZ_PLUGIN_EXTRAS || !GzipCachePluginExtras::cugz_onboarding_complete()) ? 'disabled' : '';
                        } elseif (isset($cugz_array['is_enterprise'])) {
                            $cugz_is_enterprise = $cugz_array['is_enterprise'];

                            $cugz_feature = 'Enterprise';

                            $cugz_disabled = $cugz_is_enterprise && (!CUGZ_ENTERPRISE || !GzipCacheEnterprise::cugz_onboarding_complete()) ? 'disabled' : '';
                        } else {
                            $cugz_is_premium = $cugz_is_enterprise = false;

                            $cugz_feature = $cugz_disabled = '';
                        }

                        if ('skip_settings_field' === $cugz_array['type']) {
                            continue;
                        }

                        $cugz_value = self::cugz_get_option($cugz_option);
                        ?>
						<tr>
							<th>
								<label for="<?php echo esc_attr($cugz_option); ?>"><?php echo esc_attr($cugz_array['name']); ?></label>
							</th>
							<td>
								<?php

                                $cugz_name = !$cugz_disabled ? $cugz_option : '';

                        switch ($cugz_array['type']) {
                            case 'datepicker':
                                ?>
										<input type="text" id="datepicker" name="<?php echo esc_attr($cugz_name); ?>" value="<?php echo esc_attr($cugz_value); ?>" <?php echo esc_attr($cugz_disabled); ?> />
										<?php
                                break;

                            case 'checkbox':
                                $cugz_checked = checked(1, $cugz_value, false);
                                ?>
										<input type='checkbox' name='<?php echo esc_attr($cugz_name); ?>' value='1' <?php echo esc_attr($cugz_checked); ?> <?php echo esc_attr($cugz_disabled); ?> />
										<?php
                                break;

                            case 'textarea':
                                ?>
										<textarea rows="10" cols="100" name="<?php echo esc_attr($cugz_name); ?>" id="<?php echo esc_attr($cugz_option); ?>" <?php echo esc_attr($cugz_disabled); ?>><?php echo esc_textarea($cugz_value); ?></textarea>
										<?php
                                break;

                            case 'select':
                                ?>
										<select name='<?php echo esc_attr($cugz_name); ?>'>
										<?php
                                $cugz_options = '';

                                $cugz_value = $cugz_value ?: [];

                                foreach ($cugz_array['options'] as $cugz_option) {
                                    $cugz_selected = selected($cugz_option, $cugz_value, false);

                                    $cugz_options .= "<option value='{$cugz_option}' {$cugz_selected}>{$cugz_option}</option>";
                                }

                                echo wp_kses($cugz_options, [
                                    'option' => [
                                        'value' => [],
                                        'selected' => [],
                                    ],
                                ]);
                                ?>
										</select>
										<?php
                                break;

                            case 'plugin_post_types':
                                ?>
										<select name='<?php echo esc_attr($cugz_name); ?>[]' multiple='multiple' <?php echo esc_attr($cugz_disabled); ?>>
										<?php
                                $cugz_options = [
                                    'option' => [
                                        'value' => [],
                                        'selected' => [],
                                    ],
                                ];

                                if (!CUGZ_PLUGIN_EXTRAS || !GzipCachePluginExtras::cugz_onboarding_complete()) {
                                    echo wp_kses(self::cugz_get_post_type_select_options($cugz_value), $cugz_options);
                                } else {
                                    echo wp_kses(GzipCachePluginExtras::cugz_get_post_type_select_options($cugz_value), $cugz_options);
                                }
                                ?>
										</select>
										<?php
                                break;

                            default:
                                ?>
										<input size="30" type="text" name="<?php echo esc_attr($cugz_name); ?>" id="<?php echo esc_attr($cugz_option); ?>" value="<?php echo esc_attr($cugz_value); ?>" <?php echo esc_attr($cugz_disabled); ?> />
										<?php
                                break;
                        } ?>
								<p class="description"><span class="pro-name"><?php echo $cugz_disabled ? esc_html($cugz_feature).' feature: ' : ''; ?></span><?php echo esc_html($cugz_array['description']); ?></p>
							</td>
						</tr>
					<?php } ?>
					<tr>
						<th>
							Preload status: 
						</th>
						<td>
							<p id="processing" class="cache-status"><span class="spinner is-active"></span> Processing...</p>
							<p id="complete" class="cache-status">&#10004; Preloaded</p>
							<p id="clean" class="cache-status">&#45; Not preloaded</p>
							<p><?php do_action('cugz_options_page_next_auto_preload'); ?></p>
						</td>
					</tr>
					<tr>
						<td>
							
						</td>
					</tr>
				</table>
				<a class="button" id="empty" href="#">Empty cache</a>
				<a class="button" id="regen" href="#">Preload cache</a>
				<?php do_action('cugz_post_options_page'); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php if (!CUGZ_PLUGIN_EXTRAS || !GzipCachePluginExtras::cugz_onboarding_complete()) { ?>
		<div class="cugz-wrap">
			<div class="go-pro">
				<h2>Upgrading to <span class="pro-name">Cache Using Gzip Premium</span> gives you these features:</h2>
				<ul>
					<li>Support for custom post types</li>
					<li>A cache link on posts and pages let you cache individual items on the fly</li>
					<li>Cache WooCommerce products and product category/tag archives</li>
					<li>Exclude a list of page slugs from ever being cached</li>
					<li>Use the bulk edit menu for pages, posts, etc. to cache a selection</li>
					<li><span class="pro-name">Enterprise feature</span>: Specify a date before which items will not be cached</li>
					<li><span class="pro-name">Enterprise feature</span>: Enterprise priority support</li>
					<li><span class="pro-name">Enterprise feature</span>: Select individual post types for preloading</li>
				</ul>
				<a class="button button-primary" target="_blank" rel="noopener" href="<?php echo esc_url(self::$learn_more); ?>">Compare Plans</a>
			</div>
		<div>
		<?php } ?>
	</div>

</div>
