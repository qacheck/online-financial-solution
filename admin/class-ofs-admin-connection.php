<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_Connection {

	private static $instance = null;

	private $lender = null;

	private $is_admin = false;

	public static $admin_connection_page_slug = 'edit-connection';

	private function __construct() {

		add_action('init', array($this, 'init'), 0);

		add_action('admin_menu', array($this, 'admin_settings_menu'), 50);

		add_action('wp_ajax_ofs_buy_borrower', array($this, 'ofs_buy_borrower'));
		add_action('wp_ajax_ofs_view_borrower', array($this, 'ofs_view_borrower'));
	}

	public function init() {
		$current_user = wp_get_current_user();
		$this->lender = ofs_get_lender($current_user);
		if(in_array('administrator', (array)$current_user->roles)) {
			$this->is_admin = true;
		}
	}

	public function ofs_view_borrower() {
		if(!current_user_can('edit_connection')) die;
		check_ajax_referer('ofs-admin-ajax-nonce', 'nonce');

		$borrower_id = isset($_POST['borrower_id'])?absint($_POST['borrower_id']):0;
		$lender_id = isset($_POST['lender_id'])?absint($_POST['lender_id']):0;
		//$status = ofs_model()->connect_status($borrower_id, $lender_id);
		$connected = ofs_model()->get_connection($borrower_id, $lender_id);
		//print_r($connected);
		$response = array(
			'title' => '',
			'body' => ''
		);

		if($connected['status']=='connected' || $this->is_admin) {
			$borrower = ofs_get_borrower($borrower_id);
			
			$response['title'] = esc_html($borrower->get_name().' - '.$borrower->get_display_name());

			if(!empty($borrower->get_data())) {
			ob_start();
			?>
			<div class="borrower-data-filter">
				<select id="select-condition">
				<?php
				//$i=0;
				foreach ($borrower->get_data() as $condition_id => $data) {
					$condition = ofs_get_condition($condition_id);
					?>
					<option value="<?=$condition_id?>" <?php selected( $condition_id, $connected['condition_id'], true ); ?>><?=esc_html($condition->get_title())?></option>
					<?php
					//$i++;
				}
				?>
				</select>
			</div>
			<div class="borrower-data">
			<?php
			//$i=0;
			foreach ($borrower->get_data() as $condition_id => $borrower_data) {
				$condition = ofs_get_condition($condition_id);
				?>
				<div id="condition-<?=$condition_id?>" class="condition-borrower-data<?php echo ($condition_id==$connected['condition_id'])?' active':''; ?>">
					<table>
					<?php
					foreach ($condition->get_fields() as $field_id => $field_config) {
						$field = OFS_Field_Factory::get_field($field_config['field_type']);
						$field->borrower_data_display($field_config['config'], $borrower_data[$field_id]);
					}
					?>
					</table>
				</div>
				<?php
				//$i++;
			}
			?>
			</div>
			<?php
			}
			$response['body'] = ob_get_clean();
		}

		wp_send_json( $response );
		die;
	}

	public function ofs_buy_borrower() {
		if(!current_user_can('edit_connection')) die;
		check_ajax_referer('ofs-admin-ajax-nonce', 'nonce');

		$borrower_id = isset($_POST['borrower_id'])?absint($_POST['borrower_id']):0;
		$lender_id = isset($_POST['lender_id'])?absint($_POST['lender_id']):0;

		$status = ofs_model()->connect_status($borrower_id, $lender_id);

		$response = array(
			'status' => 0,
			'html' => '',
			'coin' => 0,
			'message' => ''
		);

		if($status=='pending') {
			$coin = absint(get_user_meta( $lender_id, '_coin', true ));
			if(OFS_CONNECT_COST<=$coin) {
				ofs_model()->update_connect_status($borrower_id, $lender_id, 'connected');
				$connection = ofs_model()->get_connection($borrower_id, $lender_id);
				if($connection && $connection['status']=='connected') {
					$borrower = ofs_get_borrower($borrower_id);
					$conn_date = mysql2date('H:i:s d/m/Y', $connection['conn_date']);
					$condition = ofs_get_condition(absint($connection['condition_id']));
					$_coin = $coin-OFS_CONNECT_COST;
					error_log('Buy - lender:'.$lender_id.' | borrower:'.$borrower_id);
					update_user_meta( $lender_id, '_coin', $_coin );
					ob_start();
					?>
					<td>
						<!-- <p><?=esc_html($borrower->get_display_name())?></p> -->
						<?=esc_html($borrower->get_name())?>
					</td>
					<td><?=esc_html($conn_date)?></td>
					<td><?=esc_html($condition->get_title())?></td>
					<td><?=esc_html($connection['status'])?></td>
					<td><button class="button ofs-view-borrower" data-borrower-id="<?=$borrower_id?>" data-lender-id="<?=$lender_id?>" data-condition-id="<?=$condition->get_id()?>">View info</button></td>
					<?php
					$response['html'] = ob_get_clean();
					$response['status'] = 1;
					$response['coin'] = number_format($_coin,0,',','.');
					$response['message'] = 'Thành công!';
				} else {
					$response['status'] = 2;
					$response['message'] = 'Lỗi!';
				}
			} else {
				$response['status'] = 3;
				$response['message'] = "Không đủ tiền.\nVui lòng thực hiện nạp tiền theo quy định để sử dụng chức năng này!";
			}
		} else {
			$response['status'] = 4;
			$response['message'] = 'Đã mua hoặc đã hết hạn!';
		}

		wp_send_json($response);
		die;
	}

	public function admin_page() {
		if(!current_user_can('edit_connection')) return;

		global $wpdb;

		$tables = OFS_Install::get_tables();
		$limit = 15;
		$qs = 'qs';
		$current_page = isset($_GET[$qs]) ? absint($_GET[$qs]) : 1;
		$total_records = 0;

		?>
		<div class="wrap">
			<h2><?php _e('Connections'); ?></h2>
			<div class="postbox">
				<div class="inside">
				<?php
				$connections = array();
				if($this->lender->get_id()) {
					$total_records = $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['connection']} WHERE lender_id={$this->lender->get_id()} ORDER BY conn_date DESC " );
				} else if($this->is_admin) {
					$total_records = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['connection']} WHERE 1=1 ORDER BY conn_date DESC, borrower_id DESC");
				}

				$total_page = ceil($total_records / $limit);

				//echo $total_records;

				// Giới hạn current_page trong khoảng 1 đến total_page
				if ($current_page > $total_page){
				    $current_page = $total_page;
				} else if ($current_page < 1){
				    $current_page = 1;
				}
				 
				// Tìm Start
				$start = ($current_page - 1) * $limit;

				if($this->lender->get_id()) {
					//$connections = ofs_model()->get_connections_by_lender($this->lender->get_id());
					$sql = "SELECT * FROM {$tables['connection']} WHERE lender_id = {$this->lender->get_id()} ORDER BY conn_date DESC LIMIT %d, %d";
				} else {
					//$connections = ofs_model()->get_all_connections();
					
					$sql = "SELECT * FROM {$tables['connection']} WHERE 1=1 ORDER BY conn_date DESC, borrower_id DESC LIMIT %d, %d";
				}

				$connections = $wpdb->get_results( $wpdb->prepare( $sql, array($start, $limit) ), ARRAY_A );

				$pagination_args = array(
					'base' => $this->get_admin_url().'%_%',
					'format' => '&'.$qs.'=%#%',
					'total' => $total_page,
					'current' => $current_page,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				);

				//print_r($connections);
				if(!empty($connections)) {
					echo '<table class="ofs-table ofs-list-connection">';
					echo '<tr><th>Borrower</th>';
					if($this->is_admin) {
						echo '<th>Lender</th>';
					}
					if(!wp_is_mobile()) {
						echo '<th>Register date</th><th>Product</th><th>Status</th>';
					}

					echo '<th>Action</th></tr>';
						foreach ($connections as $key => $value) {
							$borrower = ofs_get_borrower($value['borrower_id']);
							if($borrower->get_id()) {
								$conn_date = mysql2date('H:i:s d/m/Y', $value['conn_date']);
								$condition = ofs_get_condition(absint($value['condition_id']));
								?>
								<tr id="conn-<?=$value['borrower_id']?>-<?=$value['lender_id']?>">
									<td>
										<strong>
										<?php
										if ( $value['status']=='connected' || $this->is_admin ) {
											echo esc_html($borrower->get_name());
										} else {
											echo esc_html($borrower->get_hidden_name());
										}
										?></strong>
										<div style="font-size: 12px;text-transform: capitalize;">(<?=esc_html(mb_strtolower($borrower->get_display_name()))?>)</div>
									</td>
									<?php
									if($this->is_admin) {
										//$lender = ofs_get_lender($value['lender_id']);
										$user = get_userdata( $value['lender_id'] );
										?>
										<td><?=esc_html($user->user_login)?></td>
										<?php
									}
									if(!wp_is_mobile()) {
									?>
										<td><?=esc_html($conn_date)?></td>
										<td><?=esc_html($condition->get_title())?></td>
										<td><?=esc_html($value['status'])?></td>
									<?php } ?>
									<td><?php
										if(!$this->is_admin) {
											if( $value['status']=='pending' ) {
												?>
												<button class="button ofs-buy-borrower" data-borrower-id="<?=$value['borrower_id']?>" data-lender-id="<?=$value['lender_id']?>"><?php _e('Buy', 'ofs'); ?> (<?=OFS_CONNECT_COST?> <span><?=OFS_CURRENCY_UNIT?></span>)</button>
												<?php
											} else if ( $value['status']=='connected' ) {
												?>
												<button class="button ofs-view-borrower" data-borrower-id="<?=$value['borrower_id']?>" data-lender-id="<?=$value['lender_id']?>" data-condition-id="<?=$condition->get_id()?>"><?php _e('View info', 'ofs'); ?></button>
												<?php
											} else if ( $value['status']=='expired' ) {
												echo 'Đã quá hạn phê duyệt';
											}
										} else {
											?>
											<button class="button ofs-view-borrower" data-borrower-id="<?=$value['borrower_id']?>" data-lender-id="<?=$value['lender_id']?>" data-condition-id="<?=$condition->get_id()?>"><?php _e('View info', 'ofs'); ?></button>
											<?php
										}
									?></td>
								</tr>
								<?php
							}
						}
					echo '</table>';

					$paginate_links = paginate_links($pagination_args);

					if($paginate_links!='') {
						$paginate_links = str_replace('page-numbers', 'button button-secondary',$paginate_links);
						$paginate_links = str_replace('button-secondary current', 'button-primary',$paginate_links);
						?>
						<div class="ofs-pagination">
							<?php echo $paginate_links; ?>
						</div>
						<?php	
					}

				}
				?>
				</div>
			</div>
		</div>
		<?php
	}

	public function get_admin_url() {
		return admin_url('admin.php?page='.self::$admin_connection_page_slug);
	}

	public function admin_settings_menu() {
		add_menu_page('Quản lý đăng ký', 'Các đăng ký', 'edit_connection', self::$admin_connection_page_slug, array($this, 'admin_page'), 'dashicons-share-alt', 63);
	}

	public function __clone() {}

	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}

OFS_Admin_Connection::get_instance();
