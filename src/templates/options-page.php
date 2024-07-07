<?php

if (!defined('ABSPATH')) exit;

use CUGZ\GzipCachePluginExtras;

?>

<div class="flex-container">

	<div class="cugz-wrap">

		<h2>Cache Using Gzip Settings</h2>

		<form method="post" action="options.php">
			<?php settings_fields(self::$options_group); ?>
			<table>
				<?php
				foreach (self::$options as $option => $array)
				{
					if(isset($array['is_premium'])) {

						$is_premium = $array['is_premium'];

						$feature = "Premium";

						$disabled = $is_premium && !CUGZ_PLUGIN_EXTRAS ? 'disabled': '';

					} else if(isset($array['is_enterprise'])) {

						$is_enterprise = $array['is_enterprise'];

						$feature = "Enterprise";

						$disabled = $is_enterprise && !CUGZ_ENTERPRISE ? 'disabled': '';

					} else {

						$is_premium = $is_enterprise = false;

						$feature = $disabled = "";
					}
			
					if('skip_settings_field' === $array['type']) continue;

					$value = self::cugz_get_option($option);
					?>
					<tr>
						<th>
							<label for="<?php echo esc_attr($option); ?>"><?php echo esc_attr($array['name']); ?></label>
						</th>
						<td>
							<?php

							$name = !$disabled ? $option: "";
							
							switch ($array['type'])
							{
								case 'datepicker':
									?>
									<input type="text" id="datepicker" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" <?php echo esc_attr($disabled); ?> />
									<?php
									break;
								case 'checkbox':
									$checked = checked(1, $value, false);
									?>
									<input type='checkbox' name='<?php echo esc_attr($name); ?>' value='1' <?php echo esc_attr($checked); ?> <?php echo esc_attr($disabled); ?> />
									<?php
									break;
								case 'textarea':
									?>
									<textarea rows="10" cols="100" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($option); ?>" <?php echo esc_attr($disabled); ?>><?php echo esc_textarea($value); ?></textarea>
									<?php
									break;
								case 'plugin_post_types':
									?>
									<select name='<?php echo esc_attr($name); ?>[]' multiple='multiple'>
										<?php
										$options = [
											'option' => [
												'value'    => [],
												'selected' => []
											]
										];
										if(!CUGZ_PLUGIN_EXTRAS) {
											echo wp_kses(self::cugz_get_post_type_select_options($value), $options);
										} else {
											echo wp_kses(GzipCachePluginExtras::cugz_get_post_type_select_options($value), $options);
										}
										?>
									</select>
									<?php
									break;
								default:
									?>
									<input size="30" type="text" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($option); ?>" value="<?php echo esc_attr($value); ?>" <?php echo esc_attr($disabled); ?> />
									<?php
									break;
							} ?>
							<p class="description"><span class="pro-name"><?php echo $disabled ? esc_html($feature) . " feature: ": ""; ?></span><?php echo esc_html($array['description']); ?></p>
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
					</td>
				</tr>
				<tr>
					<td>
						
					</td>
				</tr>
			</table>
			<a class="button" href="<?php echo esc_url(self::cugz_get_config_template_link()); ?>" target="_blank">Download config</a>
			<a class="button" id="empty" href="#">Empty cache</a>
			<a class="button" id="regen" href="#">Preload cache</a>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php if(!CUGZ_PLUGIN_EXTRAS) { ?>
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
			</ul>
			<a class="button button-primary" target="_blank" rel="noopener" href="<?php echo esc_url(self::$learn_more); ?>">Compare Plans</a>
		</div>
	<div>
	<?php } ?>
</div>
