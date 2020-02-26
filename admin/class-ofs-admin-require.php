<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_Require {

	private static $instance = null;

	private $conditions = array();

	private $users = array();

	public static $admin_required_page_slug = 'edit-requirement';

	private function __construct() {
		$conditions = get_posts(array(
			'post_type' => 'condition',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		));

		if(!empty($conditions)) {
			foreach ($conditions as $condition) {
				$this->conditions[$condition->ID] = ofs_get_condition($condition);
			}
		}

		$this->users = get_users(array(
			'role__in'    => array('lender'),
			'orderby' => 'user_nicename',
			'order'   => 'ASC'
		));

		add_action('admin_menu', array($this, 'admin_settings_menu'), 50);

		add_action('admin_footer-toplevel_page_edit-requirement', array($this, 'ofs_modal'));

		add_action('wp_ajax_ofs_edit_condition_field_require', array($this, 'ofs_edit_condition_field_require'));

		add_action('wp_ajax_ofs_save_condition_field_required', array($this, 'ofs_save_condition_field_required'));
	}

	public function ofs_save_condition_field_required() {
		$response = [
			'code' => 0,
			'msg' => ''
		];

		if(current_user_can('edit_requirement')) {
			if(check_ajax_referer('ofs-save-condition-field-required', 'ofs_save_condition_field_required_nonce', false)) {

				$uid = isset($_POST['uid']) ? absint($_POST['uid']) : 0;
				$cid = isset($_POST['cid']) ? absint($_POST['cid']) : 0;
				$field_id = isset($_POST['field_id']) ? sanitize_key($_POST['field_id']) : '';
				$field_type = isset($_POST['field_type']) ? sanitize_key($_POST['field_type']) : '';

				$current_user = wp_get_current_user();

				$condition = isset($this->conditions[$cid])?$this->conditions[$cid]:ofs_get_condition(-1);

				if( (current_user_can('edit_requirements') || (int)$current_user->ID===$uid) && $condition->get_id() && $field_id!='' && $field_type!='' ) {
					try {

						$field = OFS_Field_Factory::get_field($field_type);
						$condition_fields = $this->conditions[$cid]->get_fields();
			
						if(isset($condition_fields[$field_id])) {
							$data = array(
								'lender_id' => $uid,
								'condition_id' => $cid,
								'field_id' => $field_id,
								'required' => ''
							);
							$data['required'] = $field->admin_get_require_config();
							$response['code'] = (ofs_get_model()->save_required($data))?1:-1;
						}
						
					} catch (Exception $e) {

					}
					
				}

			}
		}

		wp_send_json($response);
		die;
	}

	public function ofs_edit_condition_field_require() {
		if(!current_user_can('edit_requirement')) die;
		
		check_ajax_referer('ofs-admin-ajax-nonce', 'nonce', true);

		$uid = isset($_POST['uid']) ? absint($_POST['uid']) : 0;
		$current_user = wp_get_current_user();

		if(!current_user_can('edit_requirements') && (int)$current_user->ID!==$uid) {
			die;
		}

		$cid = isset($_POST['cid']) ? absint($_POST['cid']) : 0;
		if(isset($this->conditions[$cid])) {
			$field_id = isset($_POST['field_id']) ? $_POST['field_id'] : '';
			$condition_fields = $this->conditions[$cid]->get_fields();
			
			if(isset($condition_fields[$field_id])) {
				$required = ofs_get_model()->get_required(array(
								'lender_id' => $uid,
								'condition_id' => $cid,
								'field_id' => $field_id
							));
				//print_r($required);
				$required = (!empty($required)) ? $required[$uid][$cid][$field_id]['required'] : array();

			?>
			<form id="ofs-condition-field-require-config-form" class="ofs-config-form" action="<?php echo admin_url('admin-ajax.php?action=ofs_save_condition_field_required'); ?>" method="post">
				<?php wp_nonce_field( 'ofs-save-condition-field-required', 'ofs_save_condition_field_required_nonce' ); ?>
				<input type="hidden" name="uid" value="<?=$uid?>">
				<input type="hidden" name="cid" value="<?=$cid?>">
				<input type="hidden" name="field_id" value="<?=esc_attr($field_id)?>">
				<input type="hidden" name="field_type" value="<?=esc_attr($condition_fields[$field_id]['field_type'])?>">
				<div><?=esc_html($condition_fields[$field_id]['config']['label'])?></div>
				<i><?=esc_html($condition_fields[$field_id]['config']['desc'])?></i>
				<p></p>
				<table>
				<?php
				try {
					$field = OFS_Field_Factory::get_field($condition_fields[$field_id]['field_type']);
					$field->admin_require_config_template($condition_fields[$field_id]['config'], $required);
				} catch (Exception $e) {
					echo $e->getMessage();
				}
				
				?>
				</table>
				<p class="ofs-frm-submit"><button type="submit" class="button button-primary"><?php _e('Save required', 'ofs'); ?></button></p>
			</form>
			<?php
			}
		}
		die;
	}

	public function ofs_modal() {
		?>
		<div id="ofs-condition-field-require-config" class="ofs-modal">
			<div class="modal-dialog">
				<div class="modal-header">
					<h3 class="modal-title"><?php _e('Require config', 'ofs'); ?></h3>
					<span class="close">&times;</span>
				</div>
				<div class="modal-body"></div>
			</div>
		</div>
		<?php
	}

	public function admin_page() {
		if(!current_user_can('edit_requirement')) return;

		$current_user = wp_get_current_user();
		$user = $current_user->ID;
		$editing_user_name = $current_user->display_name;

		?>
		<div class="wrap">
			<h2><?php _e('Requirement management'); ?></h2>
			<?php
			if(!empty($this->conditions)) {
				$conditions = array_values($this->conditions);
				
				$editing_condition = absint(get_user_meta($current_user->ID, '_editing_condition', true ));
				if($editing_condition==0) {
					$editing_condition = $conditions[0]->get_id();
				}

				$condition_id = isset($_REQUEST['cid']) ? absint($_REQUEST['cid']) : $editing_condition;

				$condition = isset($this->conditions[$condition_id]) ? $this->conditions[$condition_id] : $conditions[0];

				$editing_condition_title = $condition->get_title();

				update_user_meta($current_user->ID, '_editing_condition', $condition->get_id());
				?>
				<p><?php _e('Requirement management for a condition','ofs'); ?></p>
				<div id="ofs-requirement-management" class="postbox">
				<div class="inside">
					<form name="ofs-select-condition" action="<?php echo admin_url('admin.php'); ?>" method="get">
						<input type="hidden" name="page" value="<?=self::$admin_required_page_slug?>">
						<div class="condition-requirement-editing">
						<?php
							if(current_user_can( 'edit_requirements' )) {
								$editing_user = absint(get_user_meta($current_user->ID, '_editing_user', true ));
								if($editing_user==0) {
									$editing_user = $current_user->ID;
								}
								$user = isset($_REQUEST['uid']) ? absint($_REQUEST['uid']) : $editing_user;
								if($user!==$current_user->ID) {
									$editing_user = get_user_by( 'id', $user );
									if($editing_user) {
										$editing_user_name = $editing_user->display_name;
										update_user_meta($current_user->ID, '_editing_user', $user);
									} else {
										update_user_meta($current_user->ID, '_editing_user', $current_user->ID);
									}
								}
								?>
								<select name="uid">
								<?php
								foreach($this->users as $get_user) {
								?>
									<option value="<?php echo $get_user->ID; ?>" <?php selected($user, $get_user->ID, true, true); ?>><?php echo esc_html($get_user->display_name); ?></option>
								<?php
								}
								?>
								</select>
							<?php } ?>
							<?php if( current_user_can('edit_requirement') ) { ?>
								<span>Select condition: 
									<select name="cid">
									<?php
										foreach ($this->conditions as $key => $value) {
											?>
											<option value="<?=$key?>" <?php selected($condition->get_id(), $value->get_id(), true); ?>><?=esc_html($value->get_title())?></option>
											<?php
										}
									?>
									</select>
								</span>
								<button type="submit" class="button"><?php _e('Select', 'ofs'); ?></button>
								<?php
							}
						?>
						</div>
					</form>
					<div id="ofs-edit-condition-requirement">
						<p class="manage-for"><?php _e('Requirement management for','ofs');
						if(current_user_can('edit_requirements')) { ?> <strong><?php echo esc_html($editing_user_name); ?></strong> <?php _e('and','ofs'); ?> <?php } ?>
						<strong><?=esc_html($editing_condition_title)?></strong>
						</p>

						<input type="hidden" name="uid" value="<?=esc_attr($user)?>">
						<input type="hidden" name="cid" value="<?=$condition->get_id()?>">
						<div class="ofs-management-requirement-condition-fields">
						<?php
						if(!empty($condition->get_fields())) {
							foreach ($condition->get_fields() as $key => $field_data) {
								?>
								<a href="javascript:void(0);" class="ofs-manage-condition-field-require" data-field="<?=esc_attr($field_data['field_id'])?>" data-type="<?=esc_attr($field_data['field_type'])?>">
									<span>[<?php echo ucfirst(esc_html($field_data['field_type'])); ?>]</span>
									<span title="<?php echo esc_attr($field_data['config']['label']); ?>"><?php echo esc_html(mb_substr($field_data['config']['label'], 0, 60)); ?></span>
								</a>
								<?php
							}
						}
						?>
						</div>
					</div>
				</div>
				<?php
			} else {
				?><p><?php _e('No conditions yet', 'ofs'); ?></p><?php
			}
			?>
		</div>
		<?php
	}

	public function admin_settings_menu() {
		add_menu_page('Quản lý khẩu vị', 'Chỉnh sửa khẩu vị', 'edit_requirement', self::$admin_required_page_slug, array($this, 'admin_page'), 'dashicons-sticky', 61);
	}

	public function __clone() {}

	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}

OFS_Admin_Require::get_instance();
