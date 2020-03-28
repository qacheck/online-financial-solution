<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_Dashboard {

	private static $instance = null;

	private $user;

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
		$this->user = wp_get_current_user();

		if( in_array('lender', (array) $this->user->roles) ) {
			wp_add_dashboard_widget(
				'lender_profile_notify_dashboard_widget',
				__('Profile notify', 'ofs'),
				array($this, 'lender_profile_notify_dashboard_widget_render')
			);
			wp_add_dashboard_widget(
				'lender_condition_statistic_dashboard_widget',
				__('Condition Statistic', 'ofs'),
				array($this, 'lender_condition_statistic_dashboard_widget_render')
			); 
		}
	}

	public function lender_condition_statistic_dashboard_widget_render() {
		global $wpdb;
		$tables = OFS_Install::get_tables();

		$sql = "SELECT ID, COUNT(condition_id) AS num_fields FROM {$wpdb->posts} LEFT JOIN {$tables['fields']} ON (ID=condition_id) WHERE post_type = 'condition' AND post_status='publish' GROUP BY condition_id ORDER BY ID ASC";

		$total_condition = $wpdb->get_results($sql, ARRAY_A);
		
		$sql = "SELECT condition_id, COUNT(condition_id) AS num_requireds FROM {$wpdb->posts} INNER JOIN {$tables['required']} ON (ID=condition_id) WHERE post_type = 'condition' AND post_status='publish' AND lender_id={$this->user->ID} GROUP BY condition_id";
		//ofs_log($sql);
		$lender_requireds = $wpdb->get_results($sql, ARRAY_A);
		foreach ($lender_requireds as $key => $value) {
			$sql = "SELECT COUNT(condition_id) AS num_fields FROM {$tables['fields']} WHERE condition_id = {$value['condition_id']}";
			$lender_requireds[$key]['num_fields'] = $wpdb->get_var($sql);
		}

		

		//print_r($lender_requireds);
		?>
		<div class="ofs-statistic-condition">
			<table>
				<tr>
					<td>Lớp khẩu vị:</td>
					<td><?php echo number_format_i18n(count($lender_requireds)); ?></td>
				</tr>
				<tr>
					<td>Khẩu vị:</td>
					<td></td>
				</tr>
			</table>
		</div>
		<?php
	}

	public function lender_profile_notify_dashboard_widget_render() {

		?>
		<div class="ofs-lender-profile-notify">
			<table>
				<tr>
					<td>Avatar:</td>
					<td><?php

					?></td>
					<td><i class="dashicons dashicons-yes-alt" style="color:green;"></i></td>
				</tr>
				<tr>
					<td>Họ tên:</td>
					<td><?php echo esc_html($this->user->display_name); ?></td>
					<td><i class="dashicons dashicons-yes-alt" style="color:green;"></i></td>
				</tr>
				<tr>
					<td>Tiểu sử:</td>
					<td><?php echo ofs_format_content($this->user->description); ?></td>
					<td><i class="dashicons dashicons-dismiss" style="color:red;"></i></td>
				</tr>
			</table>
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