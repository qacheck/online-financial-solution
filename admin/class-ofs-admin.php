<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin {

	private static $instance = null;

	private function __construct() {
		
		$this->includes();

		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

	private function includes() {
		require_once OFS_PATH.'/admin/class-ofs-uploader.php';
		require_once OFS_PATH.'/admin/class-ofs-admin-condition.php';
		require_once OFS_PATH.'/admin/class-ofs-admin-require.php';
		require_once OFS_PATH.'/admin/class-ofs-admin-connection.php';
		require_once OFS_PATH.'/admin/class-ofs-admin-dashboard.php';
		require_once OFS_PATH.'/admin/class-ofs-admin-user-profile.php';
		require_once OFS_PATH.'/admin/class-ofs-admin-users.php';
		require_once OFS_PATH.'/admin/class-ofs-admin-borrower.php';
		require_once OFS_PATH.'/admin/class-ofs-admin-coin.php';

	}

	public function enqueue_scripts($hook) {
		global $post_type;

		OFS_Field::admin_register_scripts();

		$ofs_fields = OFS_Field_Factory::get_fields();
		foreach ($ofs_fields as $type => $ofs_field) {
			if(class_exists($ofs_field)) {
				$ofs_field::admin_enqueue_scripts();
			}
		}

		wp_enqueue_style( 'ofs-admin', OFS_URL.'/assets/css/ofs-admin.css' );

		wp_enqueue_script( 'ofs-admin', OFS_URL.'/assets/js/ofs-admin.js', array('jquery-ui-sortable','jquery', 'jquery-input-number'), '3.0', true );
		$data = array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ofs-admin-ajax-nonce')
		);
		wp_localize_script('ofs-admin', 'ofs_admin', $data);

	}

	public function __clone() {}

	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}
OFS_Admin::get_instance();