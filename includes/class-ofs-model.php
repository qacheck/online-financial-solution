<?php
if (!defined('ABSPATH')) exit;
/*
 Truy van truong du lieu serialize bang required
 - đối với field kiểu lựa chọn như select, mselect, province, radio, checkbox
 	SELECT * FROM `wp_ofs_required` WHERE `required` REGEXP 'a:1:{s:12:"accept_items";a:\\d+:{.*;s:\\d+:"[your keyword]".*}}'
 - đối với field kiểu number
	truy van con de tạo cột min,max
	SELECT *,SUBSTRING_INDEX(`required`,'|',1)+0 as min,SUBSTRING_INDEX(`required`,'|',-1)+0 as max FROM `wp_ofs_required`
 */

class OFS_Model {

	private static $instance = null;

	private $tables = array();

	private function __construct() {
		$this->tables = OFS_Install::get_tables();
	}

	public function total_lender_conditions($lender_id) {
		global $wpdb;
		$lender_id = absint($lender_id);
		$sql = "SELECT condition_id, COUNT(condition_id) AS num_fields FROM {$wpdb->posts} INNER JOIN {$this->tables['required']} ON (ID=condition_id) WHERE post_type = 'condition' AND post_status='publish' AND lender_id={$lender_id} GROUP BY condition_id";
		//ofs_log($sql);
		return $wpdb->get_col($sql);
	}

	public function total_conditions() {
		global $wpdb;
		$sql = "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'condition' AND post_status='publish'";
		return absint($wpdb->get_var($sql));
	}

	public function get_pending_connections() {
		global $wpdb;

		$sql = "SELECT * FROM {$this->tables['connection']} WHERE status='pending' ORDER BY conn_date ASC, borrower_id ASC";
		$data = $wpdb->get_results( $sql, ARRAY_A );
		return $data;
	}

	public function get_all_connections() {
		global $wpdb;

		$sql = "SELECT * FROM {$this->tables['connection']} WHERE 1=1 ORDER BY conn_date DESC, borrower_id DESC";
		$data = $wpdb->get_results( $sql, ARRAY_A );
		return $data;
	}

	public function get_connections_by_lender($lender_id) {
		global $wpdb;

		$sql = "SELECT * FROM {$this->tables['connection']} WHERE lender_id = %d ORDER BY conn_date DESC";
		$data = $wpdb->get_results( $wpdb->prepare( $sql, array($lender_id) ), ARRAY_A );
		return $data;
	}

	public function get_connection($borrower_id, $lender_id) {
		global $wpdb;

		$sql = "SELECT * FROM {$this->tables['connection']} WHERE borrower_id = %d AND lender_id = %d";
		
		$data = $wpdb->get_row( $wpdb->prepare( $sql, array($borrower_id, $lender_id) ), ARRAY_A );
		
		return $data;
	}

	public function build_mysql_datetime( $datetime, $default_to_max = false ) {
		if ( ! is_array( $datetime ) ) {

			/*
			 * Try to parse some common date formats, so we can detect
			 * the level of precision and support the 'inclusive' parameter.
			 */
			if ( preg_match( '/^(\d{4})$/', $datetime, $matches ) ) {
				// Y
				$datetime = array(
					'year' => intval( $matches[1] ),
				);

			} elseif ( preg_match( '/^(\d{4})\-(\d{2})$/', $datetime, $matches ) ) {
				// Y-m
				$datetime = array(
					'year'  => intval( $matches[1] ),
					'month' => intval( $matches[2] ),
				);

			} elseif ( preg_match( '/^(\d{4})\-(\d{2})\-(\d{2})$/', $datetime, $matches ) ) {
				// Y-m-d
				$datetime = array(
					'year'  => intval( $matches[1] ),
					'month' => intval( $matches[2] ),
					'day'   => intval( $matches[3] ),
				);

			} elseif ( preg_match( '/^(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2})$/', $datetime, $matches ) ) {
				// Y-m-d H:i
				$datetime = array(
					'year'   => intval( $matches[1] ),
					'month'  => intval( $matches[2] ),
					'day'    => intval( $matches[3] ),
					'hour'   => intval( $matches[4] ),
					'minute' => intval( $matches[5] ),
				);
			}

			// If no match is found, we don't support default_to_max.
			if ( ! is_array( $datetime ) ) {
				$wp_timezone = wp_timezone();

				// Assume local timezone if not provided.
				$dt = date_create( $datetime, $wp_timezone );

				if ( false === $dt ) {
					return gmdate( 'Y-m-d H:i:s', false );
				}

				return $dt->setTimezone( $wp_timezone )->format( 'Y-m-d H:i:s' );
			}
		}

		$datetime = array_map( 'absint', $datetime );

		if ( ! isset( $datetime['year'] ) ) {
			$datetime['year'] = current_time( 'Y' );
		}

		if ( ! isset( $datetime['month'] ) ) {
			$datetime['month'] = ( $default_to_max ) ? 12 : 1;
		}

		if ( ! isset( $datetime['day'] ) ) {
			$datetime['day'] = ( $default_to_max ) ? (int) gmdate( 't', mktime( 0, 0, 0, $datetime['month'], 1, $datetime['year'] ) ) : 1;
		}

		if ( ! isset( $datetime['hour'] ) ) {
			$datetime['hour'] = ( $default_to_max ) ? 23 : 0;
		}

		if ( ! isset( $datetime['minute'] ) ) {
			$datetime['minute'] = ( $default_to_max ) ? 59 : 0;
		}

		if ( ! isset( $datetime['second'] ) ) {
			$datetime['second'] = ( $default_to_max ) ? 59 : 0;
		}

		return sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $datetime['year'], $datetime['month'], $datetime['day'], $datetime['hour'], $datetime['minute'], $datetime['second'] );
	}

	public function update_connect_status($borrower_id, $lender_id, $status) {
		global $wpdb;
		return $wpdb->update($this->tables['connection'], ['status'=>$status], ['borrower_id'=>$borrower_id,'lender_id'=>$lender_id], ['%s'], ['%d','%d']);
	}

	public function connect_status($borrower_id, $lender_id) {
		global $wpdb;

		$sql = "SELECT * FROM {$this->tables['connection']} WHERE borrower_id = %d AND lender_id = %d";
		
		$data = $wpdb->get_row( $wpdb->prepare( $sql, array($borrower_id, $lender_id) ), ARRAY_A );

		$status = '';
		//ofs_log($data);
		if($data) {
			$current_time = strtotime(current_time( 'mysql' ));
			$conn_date = strtotime($data['conn_date']);
			$time = $current_time-$conn_date;

			$status = $data['status'];

			if($status=='pending' && $time>OFS_CONNECT_TIMEOUT*HOUR_IN_SECONDS) {
				$status = 'expired';
				$this->update_connect_status($borrower_id, $lender_id, $status);
			}
			
		}
	
		return $status;
	}

	public function connect($borrower_id, $lender_id, $condition_id=0) {
		global $wpdb;

		$current_date = current_time( 'mysql' );
		$current_date_gmt = get_gmt_from_date($current_date);
		//$current_date_gmt = current_time( 'mysql', 1 );
		$return = false;
		if($this->has_connect($borrower_id, $lender_id)) {
			$return = $wpdb->update($this->tables['connection'], ['conn_date'=>$current_date,'conn_date_gmt'=>$current_date_gmt,'status'=>'pending','condition_id'=>$condition_id], ['borrower_id'=>$borrower_id,'lender_id'=>$lender_id], ['%s','%s','%s','%d'], ['%d','%d']);
		} else {
			$return = $wpdb->insert($this->tables['connection'], ['borrower_id'=>$borrower_id, 'lender_id'=>$lender_id, 'conn_date'=>$current_date,'conn_date_gmt'=>$current_date_gmt,'status'=>'pending','condition_id'=>$condition_id], ['%d', '%d', '%s', '%s', '%s', '%d']);
		}
		
		return $return;
	}

	public function has_connect($borrower_id, $lender_id) {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$this->tables['connection']} WHERE borrower_id = %d AND lender_id = %d";
		
		$connect = $wpdb->get_var( $wpdb->prepare( $sql, array($borrower_id, $lender_id) ) );
	
		return absint($connect);
	}

	public function lenders_filtering($condition, $filter_data) {
		global $wpdb;
		$sub_query = "SELECT *,SUBSTRING_INDEX(required,'|',1)+0 as cmin,SUBSTRING_INDEX(required,'|',-1)+0 as cmax FROM {$this->tables['required']}";
		$sql = "SELECT lender_id FROM ({$sub_query}) AS tbl_required WHERE condition_id={$condition->get_id()} AND (";
		//$prepare = array();
		$field_query = array();
		foreach ($condition->get_fields() as $field_id => $field_config) {
			$field = OFS_Field_Factory::get_field($field_config['field_type']);
			$field_query[] = $field->filter_sql($field_id, $filter_data[$field_id]);
		}

		//print_r($field_query);

		$sql .= implode(' OR ', $field_query);

		$sql .= ')';

		$sql .= " GROUP BY lender_id HAVING count(*)=".count($condition->get_fields());

		//echo $sql;

		//SELECT lender_id FROM (SELECT *,SUBSTRING_INDEX(required,'|',1)+0 as min,SUBSTRING_INDEX(required,'|',-1)+0 as max FROM wp_ofs_required) AS tbl_required WHERE condition_id=10 AND ((field_id='ofsf_ajatd8rwpero' AND required REGEXP 'a:1:{s:12:"accept_items";a:\\d+:{.*;s:\\d+:"17".*}}') OR (field_id='ofsf_ozirygzj9zzv' AND min<=7000000 AND max>=7000000) OR (field_id='ofsf_m3ufjwttfsi6' AND required REGEXP 'a:1:{s:12:"accept_items";a:\\d+:{.*;s:\\d+:"Hợp đồng lao động không thời hạn".*}}') OR (field_id='ofsf_n0ebffff0jum' AND min<=100 AND max>=100) OR (field_id='ofsf_mss9oacbe5kf' AND required REGEXP 'a:1:{s:12:"accept_items";a:\\d+:{.*;s:\\d+:"Chưa từng vay và làm thẻ tín dụng bao giờ".*}}') OR (field_id='ofsf_qexr0ephxraa' AND min<=0 AND max>=0) OR (field_id='ofsf_0lt5utjgtntu' AND min<=1 AND max>=1)) GROUP BY lender_id HAVING count(*)=7

		$ids = $wpdb->get_col($sql,0);

		$results = array();
		$lenders = array();

		if(!empty($ids)) {
			$results = get_users( array( 'include'=>$ids ) );

			if($results) {
				foreach ($results as $key => $value) {
					$lender = ofs_get_lender($value);
					if($lender->get_id()) {
						$lenders[] = $lender;
					}
				}
			}
		}

		//return $sql;
		return $lenders;
	}

	public function get_borrower_data($borrower_id, $condition_id=0, $field_id='') {
		global $wpdb;

		$sql = "SELECT * FROM {$this->tables['borrower_data']} WHERE borrower_id = %d";

		$placeholder = array($borrower_id);

		if($condition_id) {
			$sql .= "AND condition_id = %d";
			$placeholder[] = $condition_id;
		}
		if($field_id!='') {
			$sql .= " AND field_id = %s";
			$placeholder[] = $field_id;
		}
		
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $placeholder ), ARRAY_A );
		$data = array();
		//print_r($results);
		if($results && is_array($results)) {
			foreach ($results as $key => $value) {
				$value['data'] = maybe_unserialize($value['data']);
				$data[$value['condition_id']][$value['field_id']] = $value;
			}
		}

		return $data;
	}

	public function update_borrower_data($borrower_id, $condition_id, $field_id, $data='') {
		global $wpdb;
		$return = 0;
		$cdata_id = $this->is_borrower_data_exist($borrower_id, $condition_id, $field_id);
		//$data = wp_unslash($data);
		if($cdata_id) {
			$wpdb->update($this->tables['borrower_data'], ['data'=>maybe_serialize($data)], ['cdata_id'=>$cdata_id], ['%s'], ['%d','%d','%s']);
			$return = $cdata_id;
		} else {
			$return = $wpdb->insert($this->tables['borrower_data'], ['borrower_id'=>$borrower_id, 'condition_id'=>$condition_id, 'field_id'=>$field_id, 'data'=>maybe_serialize($data)], ['%d', '%d', '%s', '%s']);
		}
		return $return;
	}

	public function is_borrower_data_exist($borrower_id, $condition_id, $field_id) {
		global $wpdb;

		$sql = "SELECT cdata_id FROM {$this->tables['borrower_data']} WHERE borrower_id = %d AND condition_id = %d AND field_id = %s";
		
		$cdata_id = $wpdb->get_var( $wpdb->prepare( $sql, array($borrower_id, $condition_id, $field_id) ) );
	
		return absint($cdata_id);
	}

	public function remove_condition($condition_id) {
		global $wpdb;
	
		$wpdb->delete($this->tables['fields'], ['condition_id'=>$condition_id], ['%d']);
		$wpdb->delete($this->tables['required'], ['condition_id'=>$condition_id], ['%d']);
		$wpdb->delete($this->tables['borrower_data'], ['condition_id'=>$condition_id], ['%d']);

		return true;
	}

	public function remove_borrower_data($borrower_id) {
		global $wpdb;
		
		return $wpdb->delete($this->tables['borrower_data'], ['borrower_id'=>$borrower_id], ['%d']);
	}

	public function remove_required($lender_id) {
		global $wpdb;
	
		return $wpdb->delete($this->tables['required'], ['lender_id'=>$lender_id], ['%d']);
	}

	public function remove_connection_by_lender($lender_id) {
		global $wpdb;
	
		return $wpdb->delete($this->tables['connection'], ['lender_id'=>$lender_id], ['%d']);
	}

	public function remove_connection_by_borrower($borrower_id) {
		global $wpdb;
	
		return $wpdb->delete($this->tables['connection'], ['borrower_id'=>$borrower_id], ['%d']);
	}

	public function save_required($data) {
		global $wpdb;

		$check_required = $this->check_required($data['lender_id'], $data['condition_id'], $data['field_id']);
		$return = false;
		
		$data = wp_unslash($data);
		
		//error_log(print_r($data, true));
		
		if(!empty($check_required)) {
			$return = $wpdb->update($this->tables['required'], ['required'=>maybe_serialize($data['required'])], ['lender_id'=>$data['lender_id'],'condition_id'=>$data['condition_id'],'field_id'=>$data['field_id']], ['%s'], ['%d','%d','%s']);
		} else {
			$return = $wpdb->insert($this->tables['required'], ['lender_id'=>$data['lender_id'], 'condition_id'=>$data['condition_id'], 'field_id'=>$data['field_id'], 'required'=>maybe_serialize($data['required'])], ['%d', '%d', '%s', '%s']);
		}

		return $return;
	}

	public function check_required($lender_id, $condition_id, $field_id) {
		global $wpdb;

		$sql = "SELECT * FROM {$this->tables['required']} WHERE lender_id = %d AND condition_id = %d AND field_id = %s";
		
		$required = $wpdb->get_row( $wpdb->prepare( $sql, array($lender_id, $condition_id, $field_id) ), ARRAY_A );
		if(!empty($required)) {
			$nrq = explode('|', $required['required']);
			if(count($nrq)==2) {
				$required['required'] = array('min'=>$nrq[0],'max'=>$nrq[1]);
			} else {
				$required['required'] = array('min'=>'','max'=>'');
			}
		}
		return $required;
	}

	public function get_required($args=array()) {
		global $wpdb;

		$defaults = array(
			'lender_id' => '',
			'condition_id' => '',
			'field_id' => '',
		);
		$args = wp_parse_args($args, $defaults);
		$prepare_values = array();

		$sql = "SELECT * FROM {$this->tables['required']} WHERE 1=1";
		if($args['lender_id'] !='') {
			$sql .= " AND lender_id = %d";
			$prepare_values[] = $args['lender_id'];
		}
		if($args['condition_id'] !='') {
			$sql .= " AND condition_id = %d";
			$prepare_values[] = $args['condition_id'];
		}
		if($args['lender_id'] !='') {
			$sql .= " AND lender_id = %s";
			$prepare_values[] = $args['lender_id'];
		}

		$required = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_values ), ARRAY_A );
		$return = [];
		if(!empty($required)) {
			foreach ($required as $key => $rq) {
				// $nrq = explode('|', $rq['required']);
				// if(count($nrq)==2) {
				// 	$rq['required'] = array('min'=>$nrq[0],'max'=>$nrq[1]);
				// } else {
				// 	$rq['required'] = array('min'=>'','max'=>'');
				// }
				$rq['required'] = maybe_unserialize($rq['required']);
				$return[$rq['lender_id']][$rq['condition_id']][$rq['field_id']] = $rq;
			}
		}
		return $return;

	}

	public function update_field_order($sort) {
		global $wpdb;
		if(!empty($sort)) {
			foreach ($sort as $order => $field_id) {
				$wpdb->update($this->tables['fields'], array('field_order'=>$order), array('field_id'=>$field_id), ['%d'], ['%s']);
			}
		}
	}

	public function get_condition_fields($condition_id) {
		global $wpdb;
		$sql = "SELECT * FROM {$this->tables['fields']} WHERE condition_id=%d ORDER BY field_order";
		$fields = $wpdb->get_results( $wpdb->prepare( $sql, [$condition_id] ), ARRAY_A );
		$return = [];
		if(!empty($fields)) {
			foreach ($fields as $key => $field) {
				$field['config'] = maybe_unserialize($field['config']);
				$return[$field['field_id']] = $field;
			}
		}
		return $return;
	}

	public function remove_field_constraints($field_id) {
		global $wpdb;
		$wpdb->delete($this->tables['required'], ['field_id'=>$field_id], ['%s']);
		$wpdb->delete($this->tables['borrower_data'], ['field_id'=>$field_id], ['%s']);
	}

	public function remove_field($field_id) {
		global $wpdb;
		$delete = false;
		if($this->check_field($field_id)) {
			$delete = $wpdb->delete($this->tables['fields'], ['field_id'=>$field_id], ['%s']);
			if($delete) {
				$this->remove_field_constraints($field_id);
			}
		}
		return $delete;
	}

	public function save_field($data) {
		global $wpdb;
		$return = false;

		$data = wp_unslash($data);

		//error_log(print_r($data,true));

		if($this->check_field($data['field_id'])) {
			$return = $wpdb->update($this->tables['fields'], ['config'=>maybe_serialize($data['config'])], ['field_id'=>$data['field_id']], ['%s'], ['%s']);
		} else {
			$return = $wpdb->insert($this->tables['fields'], ['field_id'=>$data['field_id'], 'condition_id'=>$data['condition_id'], 'field_type'=>$data['field_type'], 'config'=>maybe_serialize($data['config'])], ['%s', '%d', '%s', '%s']);
		}
		return $return;
	}

	public function get_field($field_id) {
		global $wpdb;
		$sql = "SELECT * FROM {$this->tables['fields']} WHERE field_id=%s";
		$field = $wpdb->get_row( $wpdb->prepare( $sql, [$field_id] ), ARRAY_A );
		if(!empty($field)) {
			$field['config'] = maybe_unserialize($field['config']);
		}
		return $field;
	}

	public function check_field($field_id) {
		global $wpdb;
		$sql = "SELECT field_id FROM {$this->tables['fields']} WHERE field_id=%s";
		$field = $wpdb->get_var( $wpdb->prepare( $sql, [$field_id] ) );

		return !empty($field);
	}

	public function unslash($data) {
		if(is_string($data)) {
			$data = wp_unslash($data);
		} else if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->unslash($value);
			}
		}

		return $data;
	}

	public static function get_instance() {
		if(self::$instance==null)
			self::$instance = new self();
		return self::$instance;
	}
}

