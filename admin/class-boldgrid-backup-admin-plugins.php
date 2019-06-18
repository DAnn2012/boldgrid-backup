<?php
/**
 * File: class-boldgrid-backup-admin-plugins.php
 *
 * @link  https://www.boldgrid.com
 * @since xxx
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Plugins
 *
 * This is a generic class designed to help manage how BoldGrid Backup behaves within the scope of
 * "WordPress Dashboard > Plugins > *".
 *
 * @since xxx
 */
class Boldgrid_Backup_Admin_Plugins {
	/**
	 * Filter the links under "BoldGrid Backup" within WP Dashboard > Plugins > Installed Plugins.
	 *
	 * @since xxx
	 *
	 * @param string $actions     An array of plugin action links. By default this can include 'activate',
	 *                            'deactivate', and 'delete'. With Multisite active this can also include
	 *                            'network_active' and 'network_only' items.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                            'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 */
	public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		$core = apply_filters( 'boldgrid_backup_get_core', null );

		$row_actions = [
			// @todo "Start here" is a good idea, but where does it link?
			// 'start_here' => '<a href="">Start Here</a>',
			'settings' => '<a href="' . esc_url( $core->settings->get_settings_url() ) . '">' . esc_html__( 'Settings', 'boldgrid-backup' ) . '</a>',
		];

		if ( ! $core->config->get_is_premium() ) {
			$row_actions[] = '<a href="' . esc_url( $core->go_pro->get_premium_url( 'bgbkup-plugin-actions' ) ) . '" target="_blank">' . esc_html__( 'Get Premium', 'boldgrid-backup' ) . '</a>';
		}

		$actions = array_merge( $row_actions, $actions );

		return $actions;
	}
}
