<?php
if (!defined('ABSPATH')) exit;

abstract class OFS_Field {

	/*
	 * lấy nhãn của trường
	 * hàm dùng chung
	 */
	public function get_name() {
		$name = empty($this->name) ? ucfirst($this->type) : $this->name;
		return $this->name;
	}

	/*
	 * lấy kiểu của trường
	 * hàm dùng chung
	 */
	public function get_type() {
		return $this->type;
	}

	public function borrower_data_display($field_config, $borrower_data) {
		// print_r($field_config);
		// print_r($borrower_data);
	}

	public function filter_sql($field_id, $filtered) {
		$sql = " AND {$field_id}='{$filtered}'";
	}

	public function sanitize_filtering($field) {
		$data = isset($_POST[$field['field_id']]) ? $_POST[$field['field_id']] : '';
		if(is_array($data)) {
			$data = array_map('sanitize_text_field', $data);
		} else {
			$data = sanitize_text_field($data);
		}
		return $data;
	}

	public function filter_template($field, $filtered='') {
		return;
	}

	public function get_default_filtered() {
		return '';
	}

	public function admin_get_require_config() {
		return array();
	}

	/*
	 * html form controls cấu hình khẩu vị riêng của trường
	 * hàm này được viết lại theo yêu cầu của mỗi loại trường cụ thể
	 */
	public function admin_require_config_template($config, $required = array()) {
		//echo 'Hãy viết lại phương thức '.__FUNCTION__.' này cho một kiểu field cụ thể.';
	}

	/*
	 * lấy các giá trị được post lên của form control cấu hình của trường
	 * các giá trị được đưa vào mảng và lưu vào csdl
	 * hàm này được viết lại theo yêu cầu của mỗi loại trường cụ thể
	 */
	public function admin_get_config() {
		return array();
	}

	/*
	 * html form controls cấu hình riêng của trường
	 * hàm này được viết lại theo yêu cầu của mỗi loại trường cụ thể
	 */
	public function admin_config_template($config = array()) {
		//echo 'Hãy viết lại phương thức '.__FUNCTION__.' này cho một kiểu field cụ thể.';
	}

	public static function admin_enqueue_scripts() {}

	/*
	 Các admin script dùng chung cho các field. Chúng sẽ được enqueue ở field khi cần.
	 */
	public static function admin_register_scripts() {
		wp_register_style('select2', OFS_URL.'/assets/select2/css/select2.min.css', null, '4.0.7');
		wp_register_script('select2', OFS_URL.'/assets/select2/js/select2.min.js', array('jquery'), '4.0.7', true);
		wp_register_script('jquery-input-number', OFS_URL.'/assets/jquery-input-number/jquery-input-number.js', array('jquery'), '1.0', true);
	}

	public static function enqueue_scripts() {}

	/*
	 Các script dùng chung cho các field. Chúng sẽ được enqueue ở field khi cần.
	 */
	public static function register_scripts() {
		wp_register_style('select2', OFS_URL.'/assets/select2/css/select2.min.css', null, '4.0.7');
		wp_register_script('select2', OFS_URL.'/assets/select2/js/select2.min.js', array('jquery'), '4.0.7', true);
		wp_register_script('jquery-input-number', OFS_URL.'/assets/jquery-input-number/jquery-input-number.js', array('jquery'), '1.0', true);
	}

}