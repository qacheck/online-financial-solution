<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_Connection {

	private static $instance = null;

	private $lender = null;

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
	}

	public function ofs_view_borrower() {
		if(!current_user_can('edit_connection')) die;
		check_ajax_referer('ofs-admin-ajax-nonce', 'nonce');

		$borrower_id = isset($_POST['borrower_id'])?absint($_POST['borrower_id']):0;
		$lender_id = isset($_POST['lender_id'])?absint($_POST['lender_id']):0;
		$status = ofs_model()->connect_status($borrower_id, $lender_id);

		$response = array(
			'title' => '',
			'body' => ''
		);

		if($status=='connected') {
			$borrower = ofs_get_borrower($borrower_id);
			
			$response['title'] = esc_html($borrower->get_name().' - '.$borrower->get_display_name());

			if(!empty($borrower->get_data())) {
			ob_start();
			?>
			<div class="borrower-data-filter">
				<select id="select-condition">
				<?php
				$i=0;
				foreach ($borrower->get_data() as $condition_id => $data) {
					$condition = ofs_get_condition($condition_id);
					?>
					<option value="<?=$condition_id?>" <?php selected( $i, 0, true ); ?>><?=esc_html($condition->get_title())?></option>
					<?php
					$i++;
				}
				?>
				</select>
			</div>
			<div class="borrower-data">
			<?php
			foreach ($borrower->get_data() as $condition_id => $borrower_data) {
				$condition = ofs_get_condition($condition_id);
				$i=0;
				?>
				<div id="condition-<?=$condition_id?>" class="condition-borrower-data<?php echo ($i==0)?' active':''; ?>">
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
				$i++;
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

		?>
		<div class="wrap">
			<h2><?php _e('Connections'); ?></h2>
			<div class="postbox">
				<div class="inside">
				<?php
				$connections = ofs_model()->get_connections_by_lender($this->lender->get_id());
				//print_r($connections);
				if(!empty($connections)) {
				echo '<table class="ofs-table">';
				echo '<tr><th>Borrower</th><th>Register date</th><th>Product</th><th>Status</th><th>Action</th></tr>';
					foreach ($connections as $key => $value) {
						$borrower = ofs_get_borrower($value['borrower_id']);
						if($borrower->get_id()) {
							$conn_date = mysql2date('H:i:s d/m/Y', $value['conn_date']);
							$condition = ofs_get_condition(absint($value['condition_id']));
							?>
							<tr id="conn-<?=$value['borrower_id']?>-<?=$value['lender_id']?>">
								<td>
									<!-- <p><?=esc_html($borrower->get_display_name())?></p> -->
									<?php
									if ( $value['status']=='connected' ) {
										echo esc_html($borrower->get_name());
									} else {
										echo esc_html($borrower->get_hidden_name());
									}

									?>
								</td>
								<td><?=esc_html($conn_date)?></td>
								<td><?=esc_html($condition->get_title())?></td>
								<td><?=esc_html($value['status'])?></td>
								<td><?php
									if( $value['status']=='pending' ) {
										?>
										<button class="button ofs-buy-borrower" data-borrower-id="<?=$value['borrower_id']?>" data-lender-id="<?=$value['lender_id']?>">Buy (<?=OFS_CONNECT_COST?> <span><?=OFS_CURRENCY_UNIT?></span>)</button>
										<?php
									} else if ( $value['status']=='connected' ) {
										?>
										<button class="button ofs-view-borrower" data-borrower-id="<?=$value['borrower_id']?>" data-lender-id="<?=$value['lender_id']?>" data-condition-id="<?=$condition->get_id()?>">View info</button>
										<?php
									} else if ( $value['status']=='expired' ) {
										
									}
								?></td>
							</tr>
							<?php
						}
					}
				echo '</table>';
				}
				?>
				</div>
			</div>
		</div>
		<?php
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
