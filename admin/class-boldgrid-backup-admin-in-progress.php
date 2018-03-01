<?php
/**
 * In Progress class.
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
 * BoldGrid Backup Admin In Progress Class.
 *
 * @since 1.5.4
 */
class Boldgrid_Backup_Admin_In_Progress {

	/**
	 * The core class object.
	 *
	 * @since  1.5.4
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * A unix timestamp indicating when a backup was started.
	 *
	 * Currently this property is only used before and after a database is dumped,
	 * see $this->pre_dump() and $this->post_dump().
	 *
	 * @since  1.6.0
	 * @access protected
	 * @var    int
	 */
	protected $in_progress;

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
	 * Add a notice telling the user there's a backup in progress.
	 *
	 * @since 1.5.4
	 *
	 * @param  array $notices
	 * @return array
	 */
	public function add_notice( $notices ) {
		$in_progress = $this->get();

		if( empty( $in_progress ) ) {
			return $notices;
		}

		$elapsed = time() - $in_progress;
		$limit = 15 * MINUTE_IN_SECONDS;

		$notice= array(
			'class' => 'notice notice-warning',
			'message' => sprintf( __( 'BoldGrid Backup began archiving your website %1$s ago.', 'boldgrid-backup' ), human_time_diff( $in_progress, time() ) ),
			'heading' => __( 'BoldGrid Backup - Backup in progress', 'boldgrid-backup' )
		);

		/*
		 * @todo If the backup takes longer than 15 minutes, the user needs more
		 * help with troubleshooting.
		 */
		if( $elapsed > $limit ) {
			$notice['message'] .= __( ' Most backups usually finish before this amount of time, so we will stop displaying this notice.', 'boldgrid-backup' );
			$this->end();
		}

		$notices[] = $notice;

		return $notices;
	}

	/**
	 * Stop.
	 *
	 * Specify that we are no longer backing up a website.
	 *
	 * @since 1.5.4
	 */
	public function end() {
		$settings = $this->core->settings->get_settings( true );

		if( ! empty( $settings['in_progress'] ) ) {
			unset( $settings['in_progress'] );
		}

		$this->core->settings->save( $settings );
	}

	/**
	 * Get the in progress value.
	 *
	 * The value is the time we started the backup.
	 *
	 * @since  1.5.4
	 *
	 * @return int
	 */
	public function get() {
		$settings = $this->core->settings->get_settings( true );

		$in_progress = ! empty( $settings['in_progress'] ) ? $settings['in_progress'] : null;

		return $in_progress;
	}

	/**
	 * Action to take before a database is dumped.
	 *
	 * @since 1.6.0
	 */
	public function post_dump() {

		/*
		 * After the database has been dumped, restore the flag stating a backup
		 * is still in progress.
		 *
		 * @see documentation in $this->pre_dump().
		 */
		if( ! empty( $this->in_progress ) ) {
			$this->set( $this->in_progress );
		}
	}

	/**
	 * Action to take after a database is dumped.
	 *
	 * @since 1.6.0
	 */
	public function pre_dump() {

		/*
		 * Cancel any "Backup in progress" statuses.
		 *
		 * Avoid this issue:
		 * Before we begin creating a backup, we set a flag stating there is a
		 * "Backup in progress". So, when we create a backup of the database,
		 * that flag is in the backup. When we restore a backup, that flag will
		 * be restored, even if we're not in the middle of making a backup, thus
		 * giving us a false positive.
		 */
		$this->in_progress = $this->get();
		$this->end();
	}

	/**
	 * Set that we are in progress of backing up a website.
	 *
	 * @since 1.5.4
	 *
	 * @param int $time A unix timestamp indicating the time a backup started.
	 */
	public function set( $time = null ) {
		$settings = $this->core->settings->get_settings( true );

		$settings['in_progress'] = ! empty( $time ) ? $time : time();

		$this->core->settings->save( $settings );
	}
}
