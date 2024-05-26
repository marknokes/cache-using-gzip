<?php if (!defined('ABSPATH')) exit; ?>

<div class="flex-container">

	<div class="wrap">

		<h2>Cache Using Gzip Settings</h2>

		<form method="post" action="options.php">
			<?php settings_fields(self::$options_group); ?>
			<table>
				<?php
				foreach (self::$options as $option => $array)
				{
					$is_premium = $array['is_premium'] ?? false;

					$disabled = $is_premium && !CUGZ_PERMISSIONS ? 'disabled': '';
			
					if('skip_settings_field' === $array['type']) continue;

					$value = get_option($option);
					?>
					<tr>
						<th>
							<label for="<?php echo esc_attr($option); ?>"><?php echo esc_attr($array['name']); ?></label>
						</th>
						<td>
							<?php
							
							switch ($array['type'])
							{
								case 'datepicker':
									?>
									<input type="text" id="datepicker" name="<?php echo esc_attr($option); ?>" value="<?php echo esc_attr($value); ?>" <?php echo esc_attr($disabled); ?> />
									<?php
									break;
								case 'checkbox':
									$checked = checked(1, $value, false);
									?>
									<input type='checkbox' name='<?php echo esc_attr($option); ?>' value='1' <?php echo esc_attr($checked); ?> <?php echo esc_attr($disabled); ?> />
									<?php
									break;
								case 'textarea':
									?>
									<textarea rows="10" cols="100" id="<?php echo esc_attr($option); ?>" name="<?php echo esc_attr($option); ?>" <?php echo esc_attr($disabled); ?>><?php echo esc_textarea($value); ?></textarea>
									<?php
									break;
								case 'plugin_post_types':
									?>
									<select name='<?php echo esc_attr($option); ?>[post_types][]' multiple='multiple'>
										<?php
										$options = [
											'option' => [
												'value'    => [],
												'selected' => []
											]
										];
										if(!CUGZ_PERMISSIONS) {
											echo wp_kses(\CUGZ\GzipCache::cugz_get_post_type_select_options($value), $options);
										} else {
											echo wp_kses(\CUGZ\GzipCachePermissions::cugz_get_post_type_select_options($value), $options);
										}
										?>
									</select>
									<?php
									break;
								default:
									?>
									<input size="30" type="text" id="<?php echo esc_attr($option); ?>" name="<?php echo esc_attr($option); ?>" value="<?php echo esc_attr($value); ?>" <?php echo esc_attr($disabled); ?> />
									<?php
									break;
							} ?>
							<p class="description"><span class="pro-name"><?php echo $is_premium && !CUGZ_PERMISSIONS ? "Pro feature: ": ""; ?></span><?php echo esc_html($array['description']); ?></p>
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
			<a class="button" href="<?php echo esc_url(\CUGZ\GzipCache::cugz_get_config_template_link()); ?>" target="_blank">Download config</a>
			<a class="button" id="empty" href="#">Empty cache</a>
			<a class="button" id="regen" href="#">Preload cache</a>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php if(!CUGZ_PERMISSIONS) { ?>
	<div class="wrap">
		<div class="go-pro">
			<h2>Upgrading to <span class="pro-name">Cache Using Gzip Pro</span> gives you these added features:</h2>
			<ul>
				<li>Support for custom post types</li>
				<li>A cache link on posts and pages let you cache individual items on the fly</li>
				<li>Cache WooCommerce products and product category/tag archives</li>
				<li>Exclude a list of page slugs from ever being cached</li>
				<li>Use on as many websites as you like</li>
			</ul>
			<a class="button button-primary" target="_blank" rel="noopener" href="<?php echo esc_url(self::$learn_more); ?>">Learn more</a>
		</div>
	<div>
	<?php } ?>
</div>
