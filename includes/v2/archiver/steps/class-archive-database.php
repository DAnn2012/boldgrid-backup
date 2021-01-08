<?php
/**
 * Archive Database class.
 *
 * @link       https://www.boldgrid.com
 * @since      SINCEVERSION
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Archive
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\V2\Archiver\Steps;

use Ifsnop\Mysqldump as IMysqldump;

/**
 * Class: Archive_Database
 *
 * @since SINCEVERSION
 */
class Archive_Database extends \Boldgrid\Backup\V2\Step\Step {
	/**
	 * The path to our dump file.
	 *
	 * @since SINCEVERSION
	 * @access private
	 * @var string
	 */
	private $dump_filepath;

	/**
	 *
	 */
	private function add_to_filelist() {
		$sql_filepath = $this->get_path_to( 'filelist-sql.json' );

		$filelist = array(
			array(
				$this->dump_filepath,
				basename( $this->dump_filepath ),
				$this->get_core()->wp_filesystem->size( $this->dump_filepath ),
			),
		);

		$this->get_core()->wp_filesystem->put_contents( $sql_filepath, wp_json_encode( $filelist ) );
	}

	/**
	 * Dump the database.
	 *
	 * @since SINCEVERSION
	 *
	 * @return true on success, array on failure.
	 */
	private function dump() {
		global $wpdb;

		$discovery  = new \Boldgrid\Backup\V2\Archiver\Steps\Discovery( 'discovery', $this->id, $this->get_dir() );
		$table_list = $discovery->get_data_type( 'step' )->get_key( 'tables' );

		\Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'status', __( 'Backing up database...', 'boldgrid-backup' ) );
		\Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'tables', $table_list['tables'] );
		\Boldgrid_Backup_Admin_In_Progress_Data::set_arg( 'step', 1 );

		$settings = array(
			'include-tables' => $table_list['tables'],
			'include-views'  => $table_list['views'],
			'add-drop-table' => true,
			'no-autocommit'  => false,
		);

		/*
		 * Set default character set.
		 *
		 * By default, IMysqldump\Mysqldump uses utf8.
		 *
		 * By default, WordPress sets CHARSET to utf8 in wp-config but will default to utf8mb4
		 * if it's available.
		 *
		 * @see wpdb::determine_charset
		 */
		if ( ! empty( $wpdb->charset ) ) {
			$settings['default-character-set'] = $wpdb->charset;
		}

		if ( ! empty( $table_list['views'] ) ) {
			$db_import           = new \Boldgrid_Backup_Admin_Db_Import();
			$user_has_privileges = $db_import->has_db_privileges( array( 'SHOW VIEW' ) );
			if ( false === $user_has_privileges ) {
				return array(
					'error' => esc_html__(
						'The database contains VIEWS, but the database user does not have the permissions needed to create a backup.',
						'boldgrid-backup'
					),
				);
			}
		}

		try {
			$dump = new IMysqldump\Mysqldump(
				$this->get_core()->db_dump->get_connection_string(),
				DB_USER,
				DB_PASSWORD,
				$settings
			);
			$dump->start( $this->dump_filepath );
		} catch ( \Exception $e ) {
			return array( 'error' => $e->getMessage() );
		}

		return true;
	}

	/**
	 * Run the database archiver.
	 *
	 * @since SINCEVERSION
	 */
	public function run() {
		$this->add_attempt();

		$this->dump_filepath = $this->get_path_to( DB_NAME . '.' . date( 'Ymd-His' ) . '.sql' );
		$this->info->set_key( 'db_filename', basename( $this->dump_filepath ) );

		$this->pre();

		$success = $this->dump();

		$this->info->set_key( 'db_time_stop', microtime( true ) );

		$this->post();

		if ( true === $success ) {
			$this->complete();
		}

		return $success;
	}

	/**
	 * Steps to take after a database has been dumped.
	 *
	 * @since SINCEVERSION
	 */
	private function post() {
		$this->add_to_filelist();

		/**
		 * Take action after a database is dumped.
		 *
		 * @since 1.6.0
		 */
		do_action( 'boldgrid_backup_post_dump', $this->dump_filepath );
	}

	/**
	 * Steps to take before a database has been dumped.
	 *
	 * @since SINCEVERSION
	 */
	private function pre() {
		/**
		 * Take action before a database is dumped.
		 *
		 * @since 1.6.0
		 */
		do_action( 'boldgrid_backup_pre_dump', $this->dump_filepath );
	}
}
