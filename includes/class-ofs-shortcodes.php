<?php
if (!defined('ABSPATH')) exit;

class OFS_Shortcodes {
	private static $instance = null;

	private $conditions = array();

	private function __construct() {
		$this->conditions = ofs_get_conditions();

		add_shortcode('online_financial_solution', array($this, 'online_financial_solution'));
		add_shortcode('ofs', array($this, 'online_financial_solution'));

		add_action('wp_ajax_ofs_borrower_login', array($this, 'ofs_borrower_login'));
		add_action('wp_ajax_nopriv_ofs_borrower_login', array($this, 'ofs_borrower_login'));

		add_action('wp_ajax_ofs_borrower_login_verify', array($this, 'ofs_borrower_login_verify'));
		add_action('wp_ajax_nopriv_ofs_borrower_login_verify', array($this, 'ofs_borrower_login_verify'));

		add_action('wp_ajax_ofs_borrower_logout', array($this, 'ofs_borrower_logout'));
		add_action('wp_ajax_nopriv_ofs_borrower_logout', array($this, 'ofs_borrower_logout'));

		add_action('wp_ajax_ofs_get_public_borrowers', array($this, 'ofs_get_public_borrowers'));
		add_action('wp_ajax_nopriv_ofs_get_public_borrowers', array($this, 'ofs_get_public_borrowers'));

		add_shortcode('condition', array($this, 'ofs_single_condition_shortcode'));

		add_action('the_content', array($this, 'ofs_single_condition'));

		add_action('wp_ajax_ofs_condition_filter', array($this, 'ofs_condition_filter'));
		add_action('wp_ajax_nopriv_ofs_condition_filter', array($this, 'ofs_condition_filter'));

		add_action('wp_footer', array($this, 'ofs_popup_template'), 99);

		add_action('wp_ajax_borrower_register', array($this, 'borrower_register'));
		add_action('wp_ajax_nopriv_borrower_register', array($this, 'borrower_register'));

	}

	public function borrower_register() {
		$return = array(
			'status' => false,
			'message' => ''
		);
		if(check_ajax_referer( 'ofs-ajax', 'nonce', false )) {
			$lender_id = isset($_POST['lender_id']) ? absint($_POST['lender_id']) : 0;
			$condition_id = isset($_POST['condition_id']) ? absint($_POST['condition_id']) : 0;
			$lender = ofs_get_lender($lender_id);
			$borrower = ofs_get_current_borrower();
			if($lender->get_id() && $borrower->get_id()) {
				$status = ofs_model()->connect_status($borrower->get_id(), $lender->get_id());

				if($status!='pending') {
					if( ofs_model()->connect($borrower->get_id(), $lender->get_id(), $condition_id) ) {
					
						$mailer = new OFS_Mailer($lender->user_email);
						
						$mailer->set_subject( esc_html($borrower->get_display_name()).' ('.$borrower->get_hidden_name().') đăng ký vay' );
						
						$mailer->set_body( '<p>'.esc_html($borrower->get_display_name()).'('.$borrower->get_hidden_name().') đăng ký vay</p>' );
						$mailer->append_body( '<p>Truy cập <a href="'.esc_url(admin_url('admin.php?page=edit-connection')).'">'.esc_url(admin_url('admin.php?page=edit-connection')).'</a> để xét duyệt.</p>' );
						$mailer->set_bcc(get_bloginfo('admin_email'));
						$mailer->add_bcc(OFS_ADDITION_ADMIN_EMAIL);

						if( $mailer->send() ) {
							$return['status'] = true;
							$return['message'] = 'Đã đăng ký';
						} else {
							$return['message'] = 'Gửi email đăng ký lỗi';
						}

					} else {
						$return['message'] = 'Có lỗi xảy ra. Xin thử lại hoặc liên hệ với ban quản trị vì sự cố này.';
					}
					
				}

			} else {
				$return['message'] = 'Thông tin không hợp lệ!';
			}
		} else {
			$return['message'] = 'Quá hạn xử lý, vui lòng tải lại trang rồi thử lại.';
		}
		wp_send_json($return);
		die;
	}

	public function ofs_popup_template() {
		?>
		<div id="ofs-modal">
			<div class="ofs-modal-dialog">
				<div class="ofs-modal-content">
					<div class="ofs-modal-header">
						<div class="ofs-modal-title"></div>
						<button type="button" class="ofs-modal-close">x</button>
					</div>
					<div class="ofs-modal-body">
						
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function ofs_condition_filter() {
		$condition_id = isset($_POST['condition_id']) ? absint($_POST['condition_id']) : 0;
		$condition = $this->get_condition_object($condition_id);
		$borrower = ofs_get_current_borrower();
		
		if(check_ajax_referer( 'condition-filter-'.$condition->get_id().$borrower->get_id(), 'nonce', false )) {
			//print_r($condition->get_fields());
			if(!empty($condition->get_fields())) {
				$filter_data = array();
				$lenders = array();

				foreach ($condition->get_fields() as $field_id => $field_data) {
					
					$field = OFS_Field_Factory::get_field($field_data['field_type']);
					$data = $field->sanitize_filtering($field_data);
					ofs_model()->update_borrower_data($borrower->get_id(), $condition->get_id(), $field_id, $data);
					$filter_data[$field_id] = $data;
				}

				$lenders = ofs_model()->lenders_filtering($condition, $filter_data);
				//print_r($lenders);
				if(!empty($lenders)) {
					foreach ($lenders as $lender) {
						$lender->display_filter_profile($borrower->get_id(),$condition_id);
					}
				} else {
					echo '<p style="padding:15px;color:#f00;"><strong>Rất tiếc, không có chuyên viên nào phù hợp điều kiện của bạn.</strong></p>';
				}
			}
		} else {
			echo '<p style="padding:15px;color:#f00;"><strong>Hết hạn phiên làm việc. Vui lòng tải lại trang và tiếp tục.</strong></p>';
		}
		die;
	}

	public function ofs_single_condition_shortcode($atts) {
		$condition_id = isset($atts['id']) ? absint($atts['id']) : 0;
		$condition = $this->get_condition_object($condition_id);

		ob_start();
		$this->ofs_page_header();
		?>
		<div class="condition-filter">
		<?php
		if(ofs_is_borrower_logged_in()) {
			$borrower = ofs_get_current_borrower();
			if(!empty($condition->get_fields()) && $borrower->get_id()) {
				//print_r($borrower->get_data());
			?>
			<form id="ofs-condition-filter-form" class="ofs-condition-filter-form" action="" method="post">
				<input type="hidden" name="action" value="ofs_condition_filter">
				<input type="hidden" name="condition_id" value="<?=$condition->get_id()?>">
				<input type="hidden" name="locale" value="<?=esc_attr(get_locale())?>">
				<?php wp_nonce_field( 'condition-filter-'.$condition->get_id().$borrower->get_id(), 'nonce', true, true ); ?>
				<table class="ofs-condition-filter-table">
					<tr><th colspan="2">Bạn vui lòng nhập các thông tin cá nhân đầy đủ và chính xác để có kết quả phê duyệt tự động đúng.</th></tr>
					<?php
					foreach ($condition->get_fields() as $field_id => $field_config) {
						try {
							$field = OFS_Field_Factory::get_field($field_config['field_type']);
							$data = isset($borrower->get_data()[$condition->get_id()][$field_id]) ? $borrower->get_data()[$condition->get_id()][$field_id]['data']: $field->get_default_filtered();
							$field->filter_template($field_config, $data);
						} catch (Exception $e) {
							echo $e->getMessage();
						}
					}
					?>
					<tr class="ofs-filter-form-submit"><td colspan="2">
						<button type="submit" class="ofs-button">Phê duyệt</button>
					</td></tr>
				</table>
			</form>
			<div id="ofs-condition-filter-results">
				
			</div>
			<?php
			}
		} else {
			?>
			<p style="text-align:center;color:#f00;">Vui lòng đăng nhập phê duyệt trước khi sử dụng chức năng này bằng cách click nút <strong>Tôi cần vay</strong> ở trên.</p>
			<?php
		}
		?>
		</div>
		<?php
		$this->ofs_get_products();

		$this->ofs_page_footer();

		return ob_get_clean();
	}

	public function get_condition_object($condition_id) {
		$condition = new OFS_Condition(-1);
		if(isset($this->conditions[$condition_id])) {
			$condition = $this->conditions[$condition_id];
		}
		return $condition;
	}

	public function ofs_single_condition($content) {
		if(is_singular('condition')) {
			$content .= do_shortcode('[condition id="'.get_the_ID().'"]');
		}

		return $content;
	}

	public function ofs_borrower_logout() {
		$redirect = isset($_POST['_wp_http_referer'])?$_POST['_wp_http_referer']:'';
		
		ofs_borrower_logout();

		wp_redirect($redirect);
		die;
	}

	public function ofs_borrower_login_verify() {
		//$redirect = parse_url($_POST['_wp_http_referer'], PHP_URL_PATH);
		$redirect = $_POST['_wp_http_referer'];
		if(check_ajax_referer('ofs-borrower-check-login-verify', 'ofs_nonce', false)) {
			$verify = isset($_POST['verify']) ? sanitize_key($_POST['verify']) : '';
			if($verify!=='') {
				$temp_borrower = ofs_get_session('temp_borrower_logged_'.$verify);
				if($temp_borrower instanceof OFS_Borrower && $temp_borrower->get_id() && Captcha::verify('verify_code')) {
					ofs_set_session('ofs_borrower', $temp_borrower);
					ofs_delete_session('temp_borrower_logged_'.$verify);
					$redirect = parse_url($redirect, PHP_URL_PATH);
				} else {
					$redirect = add_query_arg('ofs_error', 'failure', $redirect);
				}
			} else {
				$redirect = add_query_arg('ofs_error', 'failure', $redirect);
			}
		} else {
			$redirect = add_query_arg('ofs_error', 'expired', $redirect);
		}

		wp_safe_redirect( $redirect );
		die;
	}

	public function ofs_borrower_login() {
		$redirect = parse_url($_POST['_wp_http_referer'], PHP_URL_PATH);
		if(check_ajax_referer('ofs-borrower-check-login', 'ofs_nonce', false)) {
			$logname = isset($_POST['logname']) ? preg_replace('/\D/','',$_POST['logname']) : '';
			$display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
			$borrower = ofs_authenticate(null, $logname, $display_name);

			if($borrower instanceof OFS_Borrower && $borrower->get_id()) {
				$token = md5(wp_generate_password( 24 ));
				ofs_set_session('temp_borrower_logged_'.$token, $borrower);
				$redirect .= '?verify='.$token;
			}

		}

		wp_safe_redirect( $redirect );
		
		die;
	}

	private function borrower_login_verify_form($verify) {
		$submit_url = admin_url('admin-ajax.php');
		$error = isset($_GET['ofs_error']) ? sanitize_key($_GET['ofs_error']) : '';
		$msg = '';
		$msg_code = '';
		switch ($error) {
			case 'failure':
				$msg = 'Sai mã xác nhận hoặc thông tin đăng nhập.';
				$msg_code = 'danger';
				break;
			case 'expired':
				$msg = 'Quá hạn xử lý. Vui lòng thử lại.';
				$msg_code = 'warning';
				break;
		}
		?>
		<form id="ofs-borrower-login" class="ofs-borrower-login" action="<?=esc_url($submit_url)?>" method="post">
			<input type="hidden" name="locale" value="<?=get_locale()?>">
			<input type="hidden" name="action" value="ofs_borrower_login_verify">
			<input type="hidden" name="verify" value="<?=esc_attr($verify)?>">
			<?php wp_nonce_field('ofs-borrower-check-login-verify', 'ofs_nonce'); ?>
			<?php ofs_message($msg, $msg_code); ?>
			<p><label for="verify_code"><?php _e('Verify code', 'ofs'); ?></label></p>
			<div class="ofs-verify-controls">
				<p><input type="text" id="verify_code" name="verify_code" required></p>
				<p><img src="<?php echo Captcha::src(); ?>" alt="Captcha" onclick="captcha_refresh(this)"></p>
			</div>
			<p class="ofs-submit"><button class="ofs-button" type="submit"><?php _e('Login', 'ofs'); ?></button></p>
		</form>
		<?php
		
	}

	private function borrower_login_form() {
		$submit_url = admin_url('admin-ajax.php');
		?>
		<form id="ofs-borrower-login" class="ofs-borrower-login" action="<?=esc_url($submit_url)?>" method="post">
			<input type="hidden" name="locale" value="<?=get_locale()?>">
			<input type="hidden" name="action" value="ofs_borrower_login">
			<?php wp_nonce_field('ofs-borrower-check-login', 'ofs_nonce'); ?>
			<p class="ofs-error"></p>
			<p>
				<label for="logname"><?php _e('Phone number', 'ofs'); ?></label>
				<input type="text" id="logname" name="logname" required pattern="[0-9]{10}" oninvalid="checkPhoneNumber(this);" oninput="checkPhoneNumber(this);">
			</p>
			<p>
				<label for="display_name"><?php  _e('Display name', 'ofs'); ?></label>
				<input type="text" id="display_name" name="display_name" pattern="^[^\u0021-\u0026\u0028-\u002C\u002E-\u0040\u005B-\u0060\u007B-\u007F\u0080-\u009F\u00A1-\u00BF\u00D7\u00F7\u2014-\u2018\u2020-\u206F]+$" oninvalid="checkFullName(this);" oninput="checkFullName(this);">
			</p>
			<p class="ofs-submit"><button class="ofs-button" type="submit"><?php _e('Login', 'ofs'); ?></button></p>
		</form>
		<?php
		
	}

	public function ofs_page_header() {
		if(empty($this->conditions)) return;
		?>
		<div class="ofs-user-info-bar">
			<?php
			if(ofs_is_borrower_logged_in()) {
				$borrower = ofs_get_current_borrower();
				?>
				<div class="ofs-button ofs-button-borrowing">
					<span class="ofs-button-label"><a href="javascript:;"><?=esc_html($borrower->get_name())?></a></span>
					<form class="ofs-button-addon" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
						<?php wp_nonce_field('ofs-borrower-logout', 'ofs_nonce') ?>
						<input type="hidden" name="action" value="ofs_borrower_logout">
						<button type="submit"></button>
					</form>
				</div>
				<?php
			} else {
				?>
				<div class="ofs-button ofs-button-borrower-login">Tôi cần vay</div>
				<?php
			}
			
			if(is_user_logged_in()) {
				
				$current_user = wp_get_current_user();
				//print_r($current_user);
				?>
				
				<div class="ofs-button ofs-button-lendering">
					<span class="ofs-button-label"><a class="button-lender" href="<?php echo admin_url(); ?>"><?=esc_html($current_user->display_name)?></a></span>
					<span class="ofs-button-addon"><a href="<?php echo wp_logout_url(ofs_current_url()); ?>"></a></span>
				</div>
				<?php
			} else {
				?>
				<div class="ofs-button ofs-button-lender">
					<a href="<?php echo wp_login_url(admin_url()); ?>">Tôi muốn cho vay</a>
				</div>
				<?php
			}
		?>
		</div><!-- /.ofs-user-info-bar -->
		<?php
		if(!ofs_is_borrower_logged_in()) {
			$verify = isset($_GET['verify']) ? sanitize_key($_GET['verify']) : '';
			if($verify!=='') {
				$this->borrower_login_verify_form($verify);
			} else {
				$this->borrower_login_form();
			}
		}
		?>
		
		<?php
	}

	public function ofs_get_products() {
		$current = (is_singular('condition')) ? get_the_ID() : 0;
		?>
		<div class="ofs-products-list">
		<?php
		foreach ($this->conditions as $key => $value) {
			echo '<div class="ofs-pi';
			if($value->get_id()==$current) {
				echo ' current';
			}
			echo '">';
			echo '<a href="'.esc_url($value->get_filter_url()).'">';
			echo '<span class="icon">'.$value->get_thumbnail_image('full').'</span>';
			echo esc_html($value->get_title());
			echo '</a>';
			echo '</div>';
		}
		?>
		</div>
		<?php
	}

	public function ofs_page_footer() {
		?>
		
		<?php
	}

	public function online_financial_solution($atts) {
		ob_start();
		$this->ofs_page_header();

		?>
		<h3 class="ofs-products-heading">Gói sản phẩm vay</h3>
		<?php
		$this->ofs_get_products();
		
		// $this->public_borrowers();

		$this->ofs_page_footer();
		return ob_get_clean();
	}

	public function public_borrowers() {
		// $cid = isset($_GET['cid']) ? absint($_GET['cid']) : 0;
		// $odb = isset($_GET['odb']) ? sanitize_key($_GET['odb']) : '';
		// $od = isset($_GET['od']) ? sanitize_key($_GET['od']) : '';

		if(!empty($this->conditions)) {
		?>
		<div class="ofs-public-borrowers-label">Đơn vay của chúng tôi</div>
		<div id="ofs-borrowers-list-filter">
			<div class="condition-filter">
				<select name="cid">
					<option value="0">--Sản phẩm--</option>
				<?php
				foreach ($this->conditions as $key => $value) {
					?>
					<option value="<?=$key?>"><?=esc_html($value->get_title())?></option>
					<?php
				}
				?>
				</select>
			</div>
		</div>
		<div id="ofs-borrowers-list">
			<input type="hidden" name="odb" value="">
			<input type="hidden" name="od" value="">
			<input type="hidden" name="page" value="1">
			<input type="hidden" name="maxpage" value="1">
		</div>
		<?php
		}
	}

	public function ofs_get_public_borrowers() {
		$page = isset($_GET['page']) ? absint($_GET['page']) : 1;
		$cid = isset($_GET['cid']) ? absint($_GET['cid']) : 0;
		$odb = isset($_GET['odb']) ? sanitize_key($_GET['odb']) : '';
		$od = isset($_GET['od']) ? sanitize_key($_GET['od']) : 'ASC';
		
		$args = array(
			'post_type' => 'borrower',
			'posts_per_page' => 5,
			'post_status' => 'publish'
		);

		if($page) {
			$args['paged'] = $page;
		}

		$query = new WP_Query($args);

		?>
		<div id="ofs-borrowers-list-table">
			<div id="ofs-borrowers-list-heading">
				<div>STT</div>
				<div>Khách hàng</div>
				<div>Thời gian tạo</div>
				<div>Giá bán<br><i>(Coin)</i></div>
				<div></div>
			</div>
			<div id="ofs-borrowers-list-body">
			<?php
			if($query->have_posts()) {
				$i=0;
				while ($query->have_posts()) {
					$query->the_post();
					global $post;

					$borrower = ofs_get_borrower($post);
					?>
					<div class="crow">
						<div class="col-stt"><?=++$i?></div>
						<div class="col-name">
							<div><?=esc_html($borrower->get_display_name())?></div>
							<div><?=esc_html($borrower->get_hidden_name())?></div>
						</div>
						<div><?=esc_html($borrower->get_date())?></div>
						<div style="text-align:right;"><?=OFS_CONNECT_COST?></div>
						<div style="text-align:center;"><button type="button">Mua</button></div>
					</div>
					<?php
					
				}
			}
			?>
			</div>
		</div>
		<input type="hidden" name="odb" value="<?=esc_attr($odb)?>">
		<input type="hidden" name="od" value="<?=esc_attr($od)?>">
		<input type="hidden" name="page" value="<?=esc_attr($page)?>">
		<input type="hidden" name="maxpage" value="<?=esc_attr($query->max_num_pages)?>">
		<div id="ofs-borrowers-list-page">
			<a class="prev" href="#">&laquo</a><span><?=esc_html($page)?> of <?=esc_html($query->max_num_pages)?></span><a class="next" href="#">&raquo</a>
		</div>
		<?php
		die;
	}

	/**
	 * Cloning is forbidden.
	 * @since 1.0
	 */
	public function __clone() {}

	/**
	 * Unserializing instances of this class is forbidden.
	 * @since 1.0
	 */
	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
OFS_Shortcodes::get_instance();