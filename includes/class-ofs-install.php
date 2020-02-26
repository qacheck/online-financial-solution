<?php
if (!defined('ABSPATH')) exit;

class OFS_Install {

	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		self::create_tables();
		self::create_roles();

		update_option( 'ofs_queue_flush_rewrite_rules', 'yes' );
	}

	public static function uninstall() {
		flush_rewrite_rules();
		self::remove_roles();
	}

	public static function remove_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		$capabilities = OFS_Post_Types::get_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->remove_cap( 'administrator', $cap );
			}
		}

		$wp_roles->remove_role('lender');

		$wp_roles->remove_cap( 'administrator', 'edit_requirement' );
		$wp_roles->remove_cap( 'administrator', 'edit_requirements' );
		$wp_roles->remove_cap( 'administrator', 'edit_connection' );
		$wp_roles->remove_cap( 'administrator', 'edit_connections' );
	}

	public static function update_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		$wp_roles->remove_cap( 'lender', 'edit_borrowers' );
		$wp_roles->remove_cap( 'lender', 'delete_borrowers' );
	}

	/**
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		$capabilities = OFS_Post_Types::get_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}

		// $wp_roles->remove_role('lender');
		// Customer role.
		add_role(
			'lender',
			_x( 'Lender', 'role', 'ofs' ),
			array(
				'edit_requirement' => true,
				//'edit_requirements' => false,
				'edit_connection' => true,
				//'edit_connections' => false,
				//'read_borrower' => true,
				//'edit_borrower' => true,
				// 'edit_borrowers' => true,
				// 'delete_borrowers' => true,
				//'edit_others_borrowers' => true,
				//'edit_published_borrowers' => true,
				//'edit_connections' => false,
				'read' => true,
				'level_0' => true,
			)
		);

		$wp_roles->add_cap( 'administrator', 'edit_requirement' );
		$wp_roles->add_cap( 'administrator', 'edit_requirements' );
		$wp_roles->add_cap( 'administrator', 'edit_connection' );
		$wp_roles->add_cap( 'administrator', 'edit_connections' );

	}

	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
		CREATE TABLE {$wpdb->prefix}ofs_fields (
		  field_id varchar(32) NOT NULL,
		  condition_id bigint UNSIGNED NOT NULL,
		  field_type varchar(32) NOT NULL,
		  field_order int NOT NULL DEFAULT 0,
		  config longtext NOT NULL,
		  PRIMARY KEY (field_id),
		  KEY condition_id (condition_id)
		) $collate;

		CREATE TABLE {$wpdb->prefix}ofs_required (
		  lender_id bigint UNSIGNED NOT NULL,
		  condition_id bigint UNSIGNED NOT NULL,
		  field_id varchar(32) NOT NULL,
		  required longtext NOT NULL,
		  PRIMARY KEY (lender_id, condition_id, field_id),
		  KEY condition_id (condition_id),
		  KEY field_id (field_id)
		) $collate;
		
		CREATE TABLE {$wpdb->prefix}ofs_connection (
		  borrower_id bigint UNSIGNED NOT NULL,
		  lender_id bigint UNSIGNED NOT NULL,
		  conn_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  conn_date_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  status varchar(20) NOT NULL DEFAULT 'pending',
		  condition_id bigint UNSIGNED NOT NULL DEFAULT 0,
		  PRIMARY KEY (borrower_id, lender_id),
		  KEY borrower_id (borrower_id),
		  KEY lender_id (lender_id)
		  
		) $collate;

		CREATE TABLE {$wpdb->prefix}ofs_borrower_data (
		  cdata_id bigint UNSIGNED NOT NULL auto_increment,
		  borrower_id bigint UNSIGNED NOT NULL,
		  condition_id bigint UNSIGNED NOT NULL,
		  field_id varchar(32) NOT NULL,
		  data longtext NOT NULL,
		  PRIMARY KEY (cdata_id),
		  KEY borrower_id (borrower_id),
		  KEY condition_id (condition_id),
		  KEY field_id (field_id)
		) $collate;";

		return $tables;
	}

	public static function get_tables() {
		global $wpdb;

		$tables = array(
			'fields' => "{$wpdb->prefix}ofs_fields",
			'required' => "{$wpdb->prefix}ofs_required",
			'borrower_data' => "{$wpdb->prefix}ofs_borrower_data",
			'connection' => "{$wpdb->prefix}ofs_connection",
		);

		return $tables;
	}

	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}

	public static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		//error_log(self::get_schema());
		dbDelta( self::get_schema() );
	}
}