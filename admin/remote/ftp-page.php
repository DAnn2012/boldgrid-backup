<?php
/**
 * FTP Page class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.4
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * FTP Page class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_Ftp_Page {

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.4
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.5.4
	 */
	public function enqueue_scripts() {
		$handle = 'boldgrid-backup-admin-ftp-settings';
		wp_register_script( $handle,
			plugin_dir_url( dirname( __FILE__ ) ) . 'js/' . $handle . '.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);
		$translation = array(
			'default_port' => $this->core->ftp->default_port,
		);
		wp_localize_script( $handle, 'BoldGridBackupAdminFtpSettings', $translation );
		wp_enqueue_script( $handle );

		wp_enqueue_style(
			$handle,
			plugin_dir_url( dirname( __FILE__ ) ) . 'css/' . $handle . '.css',
			array(),
			BOLDGRID_BACKUP_VERSION
		);
	}

	/**
	 * Generate the submenu page for our FTP Settings page.
	 *
	 * @since 1.5.4
	 */
	public function settings() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		$this->enqueue_scripts();
		wp_enqueue_style( 'boldgrid-backup-admin-hide-all' );

		// Blank data, used when deleting settings.
		$type = $this->core->ftp->default_type;
		$blank_data = array(
			'type' => $type,
			'host' => null,
			'port' => $this->core->ftp->default_port[$type],
			'user' => null,
			'pass' => null,
			'retention_count' => $this->core->ftp->retention_count,
			'nickname' => '',
		);

		// Post data, used by default or when updating settings.
		$post_data = $this->core->ftp->get_from_post();

		$action = ! empty( $_POST['action'] ) ? $_POST['action'] : null;
		switch( $action ) {
			case 'save':
				echo $this->core->elements['long_checking_creds'];
				ob_flush();
				flush();

				$this->settings_save();
				$data = $post_data;
				break;
			case 'delete':
				$this->settings_delete();
				$data = $blank_data;
				break;
			default:
				$data = $post_data;
		}

		include BOLDGRID_BACKUP_PATH . '/admin/partials/remote/ftp.php';
	}

	/**
	 * Process the user's request to update their FTP settings.
	 *
	 * @since 1.5.4
	 */
	public function settings_delete() {
		$ftp = $this->core->ftp;

		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		$settings = $this->core->settings->get_settings();
		if( ! isset( $settings['remote'][$ftp->key] ) || ! is_array( $settings['remote'][$ftp->key] ) ) {
			$settings['remote'][$ftp->key] = array();
		}

		$settings['remote'][$ftp->key] = array();
		update_site_option( 'boldgrid_backup_settings', $settings );

		$ftp->reset();
		$ftp->disconnect();

		do_action( 'boldgrid_backup_notice', __( 'Settings deleted.', 'boldgrid-backup' ), 'notice updated is-dismissible' );
	}

	/**
	 * Process the user's request to update their FTP settings.
	 *
	 * @since 1.5.4
	 */
	public function settings_save() {

		// Readability.
		$ftp = $this->core->ftp;

		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		if( empty( $_POST ) ) {
			return false;
		}

		$settings = $this->core->settings->get_settings();
		if( ! isset( $settings['remote'][$ftp->key] ) || ! is_array( $settings['remote'][$ftp->key] ) ) {
			$settings['remote'][$ftp->key] = array();
		}

		$data = $ftp->get_from_post();

		$valid_credentials = $ftp->is_valid_credentials( $data['host'], $data['user'], $data['pass'], $data['port'], $data['type'] );

		if( $valid_credentials ) {
			$settings['remote'][$ftp->key]['host'] = $data['host'];
			$settings['remote'][$ftp->key]['user'] = $data['user'];
			$settings['remote'][$ftp->key]['pass'] = $data['pass'];
			$settings['remote'][$ftp->key]['port'] = $data['port'];
			$settings['remote'][$ftp->key]['type'] = $data['type'];
		}

		$settings['remote'][$ftp->key]['retention_count'] = $data['retention_count'];
		$settings['remote'][$ftp->key]['nickname'] = $data['nickname'];

		if( ! empty( $ftp->errors ) ) {
			do_action( 'boldgrid_backup_notice', implode( '<br /><br />', $ftp->errors ) );
		} else {
			update_site_option( 'boldgrid_backup_settings', $settings );
			do_action( 'boldgrid_backup_notice', __( 'Settings saved.', 'boldgrid-backup' ), 'notice updated is-dismissible' );
		}
	}
}