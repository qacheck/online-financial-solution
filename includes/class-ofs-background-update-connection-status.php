<?php
if (!defined('ABSPATH')) exit;

class OFS_Background_Update_Connection_Status extends WP_Background_Process {
	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'ofs_update_connection_status';

		parent::__construct();
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $data ) {
		//sleep(10);
		//ofs_log($data);
		$current_time = strtotime(current_time( 'mysql' ));
		$conn_date = strtotime($data['conn_date']);
		$time = $current_time-$conn_date;

		$status = $data['status'];

		if($status=='pending' && $time>OFS_CONNECT_TIMEOUT*HOUR_IN_SECONDS) {
			$status = 'expired';
			ofs_model()->update_connect_status($data['borrower_id'], $data['lender_id'], $status);
		}

		return false;
	}

	protected function complete() {
		if (! wp_next_scheduled( 'update_connection_status' )) {
			wp_schedule_single_event( time(), 'update_connection_status' );
		}
		parent::complete();
	}

	public function get_current_batch() {
		return $this->get_batch();
	}
}