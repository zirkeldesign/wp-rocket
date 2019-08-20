<?php
/**
 * Download log form template.
 *
 * @since 3.3.4
 *
 * @param array $data {
 *     Download Log form data.
 *
 *     @type string $nonce_name  Nonce name for download log form.
 *     @type string $action      WordPress action associated with the form.
 *     @type string $submit_text Content for the submit button.
 * }
 */

defined('ABSPATH') || die('Cheatin&#8217; uh?');

use WP_Rocket\Logger\Logger;

// Debug mode.
$log_description = '';
$log_delete_link = '';
$form_section = '';

if (rocket_direct_filesystem()->exists(Logger::get_log_file_path())) {
    $sections = Logger::get_log_files_sections();

    $stats = Logger::get_log_file_stats();

    foreach ($sections as $section) {
        $section_stats = Logger::get_log_file_stats($section);

		if (! is_wp_error($section_stats)) {
			$stats = array_map(
				function () {
					return array_sum(func_get_args());
				},
				$stats,
				$section_stats
			);
		}
	}

    if (! is_wp_error($stats)) {
		$decimals = $stats[1] > pow( 1024, 3 ) ? 1 : 0;
		$bytes    = @size_format( $stats[1], $decimals );
		$entries  = $stats[0];

        // translators: %1$s = formatted file size, %2$s = formatted number of entries (don't use %2$d).
        $log_description .= sprintf(__('Files size: %1$s. Number of entries: %2$s.', 'rocket'), '<strong>' . esc_html($bytes) . '</strong>', '<strong>' . esc_html($entries) . '</strong>');

        $select_section = '<select  name="section">
								<option value="">' . __('Default', 'rocket') . '</option>';
        foreach ($sections as $section) {
            $select_section .= '<option value="' . $section . '">' . $section . '</option>';
        }
        $select_section .= '</select>';

		$form_section = '<form action="' .  esc_url(admin_url('admin-post.php')) .'" method="POST" class="wpr-logs">
							<div class="wpr-logs-col">' .
								wp_nonce_field($data['nonce_name']) .
								$select_section .
								'<input type="hidden" name="action" value="' .  esc_attr($data['action']) . '" />
								<button type="submit" class="wpr-button wpr-button--icon wpr-button--small wpr-button--purple wpr-icon-chevron-down" value="' . esc_attr($data['submit_text']) .'">' . esc_attr($data['submit_text']) . '</button>
							</div>
						</form>';
        // translators: %1$s = opening <a> tag, %2$s = closing </a> tag.
        $log_delete_link = sprintf(__('%1$sDelete all log files%2$s', 'rocket'), '<a class="wpr-button wpr-button--icon wpr-button--small wpr-button--red wpr-icon-trash" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=rocket_delete_debug_file'), 'delete_debug_file')) . '">', '</a>');
    }
}
?>


<!-- Temporary hide the option. The logger can still be activated by adding the following to the wp-config.php file: define( 'WP_ROCKET_DEBUG', true ); -->
<div class="wpr-tools">
	<div class="wpr-tools-col wpr-radio">
		<div class="wpr-title3 wpr-tools-label">
			<input id="debug_enabled" name="wp_rocket_settings[debug_enabled]" value="1"<?php checked(true, Logger::debug_enabled()); ?> type="checkbox">
			<label for="debug_enabled">
				<span data-l10n-active="On" data-l10n-inactive="Off" class="wpr-radio-ui"></span>
				<?php esc_html_e('Debug mode', 'rocket'); ?>
			</label>
		</div>

		<div class="wpr-field-description">
			<?php esc_html_e('Create a debug log file.', 'rocket'); ?>
		</div>
	</div>
	<div class="wpr-tools-col">
		<?php echo $log_description; ?>

		<?php echo $form_section; ?>

        <?php echo $log_delete_link; ?>
	</div>
</div>