<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_Dashboard {

	private static $instance = null;

	private function __construct() {
		add_action('wp_dashboard_setup', array($this, 'admin_dashboard_widgets'), 999 );
	}

	public function admin_dashboard_widgets() {
		global $wp_meta_boxes;
		//ofs_debug($wp_meta_boxes);
		if(isset($wp_meta_boxes['dashboard']['normal'])) {
			unset($wp_meta_boxes['dashboard']['normal']);
		}
		if(isset($wp_meta_boxes['dashboard']['side'])) {
			unset($wp_meta_boxes['dashboard']['side']);
		}
		$user = wp_get_current_user();
		if( in_array('lender', (array) $user->roles) ) {
			wp_add_dashboard_widget(
				'statistics_lender_dashboard_widget',
				'Statistics',
				array($this, 'statistics_lender_dashboard_widget_render')
			); 
		}
	}

	public function statistics_lender_dashboard_widget_render() {
		$number_conditions = wp_count_posts('condition', 'readable');
		?>
		<div class="ofs-statistic-condition">
			Tổng khẩu vị:	
		</div>
		<?php
	}

	public function __clone() {}

	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}
OFS_Admin_Dashboard::get_instance();