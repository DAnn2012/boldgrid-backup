<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @author BoldGrid.com <wpb@boldgrid.com>
 */
class Boldgrid_Backup_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0
	 * @access private
	 * @var string $version
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0
	 *
	 * @global WP_Filesystem $wp_filesystem The WordPress Filesystem API global object.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Connect to the WordPress Filesystem API.
		global $wp_filesystem;

		// Ensure the WP_Filesystem was initialized.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->config = new Boldgrid_Backup_Admin_Config( null );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0
	 */
	public function enqueue_styles() {
		/*
		 * An instance of this class should be passed to the run() function
		 * defined in Boldgrid_Backup_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Boldgrid_Backup_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( $this->plugin_name,
		plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin.css', array(), $this->version );

		// Enqueue JS.
		wp_register_script( 'boldgrid-backup-admin',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);

		$translation = array(
			'is_premium' => ( true === $this->config->get_is_premium() ? 'true' : 'false' ),
			'max_dow' => $this->config->get_max_dow(),
			'lang' => $this->config->lang,
		);

		wp_localize_script( 'boldgrid-backup-admin', 'BoldGridBackupAdmin', $translation );

		wp_enqueue_script( 'boldgrid-backup-admin' );

		// Enqueue CSS for the home page.
		if ( isset( $_REQUEST['page'] ) && 'boldgrid-backup' === $_REQUEST['page'] ) {
			wp_enqueue_style( 'boldgrid-backup-admin-home',
				plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-home.css', array(),
				BOLDGRID_BACKUP_VERSION
			);
		}
	}
}
