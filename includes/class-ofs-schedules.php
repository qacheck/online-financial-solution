<?php
if (!defined('ABSPATH')) exit;
/**
 * Thực hiện các lịch trình đã đề ra
 */
class OFS_Schedules {

	private $process;

	public function __construct() {
		$this->process = new OFS_Background_Update_Connection_Status();

		add_filter('cron_schedules', array($this, 'cron_schedules'));

		if (! wp_next_scheduled( 'update_connection_status' )) {
			wp_schedule_single_event( time(), 'update_connection_status' );
		}

		add_action('update_connection_status', array($this, 'update_connection_status'));
	}

	public function update_connection_status() {
		$pending_connections = ofs_model()->get_pending_connections();
		if(!empty($pending_connections)) {
			foreach ($pending_connections as $key => $value) {
				$this->process->push_to_queue($value);
			}
			$this->process->save()->dispatch();
		}
	}

	public function cron_schedules($schedules) {
		$schedules['interval_update_connection_status'] = array(
			'interval' => OFS_IUCS,
			'display'  => esc_html__( 'Interval update connection status', 'ofs' ),
		);

		return $schedules;
	}
}
new OFS_Schedules();