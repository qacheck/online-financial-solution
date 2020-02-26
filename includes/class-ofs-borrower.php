<?php
if (!defined('ABSPATH')) exit;

class OFS_Borrower {

	private $ID = 0;

	private $post = null;

	private $data = null;

	public function __construct($post) {
		if($post instanceof self) {
			$post = $post->get_id();
		}

		$borrower_post = get_post($post);

		if($borrower_post && $borrower_post->post_type=='borrower') {
			$this->post = $borrower_post;
			$this->ID = absint($borrower_post->ID);

			$this->load_data();

		} else {
			$this->ID = 0;
		}
	}

	public function is_connected($lender_id) {
		$conn_status = ofs_model()->connect_status($this->ID, absint($lender_id));
		return ($conn_status=='connected');
	}

	public function get_data() {
		return $this->data;
	}

	public function get_date() {
		if($this->ID) {
			return get_the_time('H:i | d/m/Y',$this->post);
		}
		return '';
	}

	public function get_id() {
		return $this->ID;
	}

	public function get_display_name() {
		if($this->ID) {
			return $this->post->post_excerpt;
		}
		return '(Deleted)';
	}

	public function get_name() {
		if($this->ID) {
			return $this->post->post_title;
		}
		return '(Deleted)';
	}

	public function get_hidden_name() {
		$name = $this->get_name();
		if($name!='') {
			$logname_begin = substr($this->get_name(), 0, 2);
			$logname_end = substr($this->get_name(), -2);
			return $logname_begin.'*******'.$logname_end;
		}
		return '(Deleted)';
	}

	public function get_cost() {
		return OFS_CONNECT_COST;
	}

	public function get_actions() {
		$lender = ofs_get_lender(null);

		ob_start();
		?>
		
		<?php
		return ob_get_clean();
	}

	private function load_data() {
		$this->data = ofs_model()->get_borrower_data($this->get_id());
	}


}