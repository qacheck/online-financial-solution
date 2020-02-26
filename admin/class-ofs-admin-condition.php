<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_Condition {

	private static $instance = null;

	private function __construct() {

		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action('after_delete_post', array($this, 'remove_condition_data'));

		add_action('admin_footer-post-new.php', array($this, 'field_config_modal'));
		add_action('admin_footer-post.php', array($this, 'field_config_modal'));

		add_action('wp_ajax_ofs_condition_add_field', array($this, 'ajax_condition_add_field'));
		add_action('wp_ajax_ofs_condition_save_field_config', array($this, 'ajax_condition_save_field_config'));
		add_action('wp_ajax_ofs_condition_remove_field', array($this, 'ajax_condition_remove_field'));
		add_action('wp_ajax_ofs_condition_sort_fields', array($this, 'ajax_condition_sort_fields'));
	
	}

	public function ajax_condition_sort_fields() {
		check_ajax_referer('ofs-admin-ajax-nonce', 'nonce');
		$condition_id = isset($_POST['condition_id']) ? absint($_POST['condition_id']) : 0;

		$post_type = get_post_type($condition_id);

		if($post_type!='condition' || !current_user_can('edit_condition', $condition_id)) die;

		$sort = isset($_POST['sort']) ? (array)$_POST['sort'] : array();

		ofs_get_model()->update_field_order($sort);

		die;
	}

	public function ajax_condition_remove_field() {
		check_ajax_referer('ofs-admin-ajax-nonce', 'nonce');

		$condition_id = isset($_POST['condition_id']) ? absint($_POST['condition_id']) : 0;

		$post_type = get_post_type($condition_id);

		if($post_type!='condition' || !current_user_can('delete_condition', $condition_id)) die;

		$field_id = isset($_POST['field_id']) ? sanitize_key($_POST['field_id']) : '';

		$remove = false;

		if(ofs_get_model()->remove_field($field_id)) {
			$remove = true;
		}

		wp_send_json($remove);
		die;
	}

	/**
	 * html field đang có của condition
	 * @param  array $field_config cấu hình của field
	 * html này hiển thị ở danh sách các field của trang quản lý 1 condition
	 * click Sửa sẽ gọi lại popup form cấu hình cho field
	 * click Xóa sẽ loại bỏ field khỏi danh sách field của condition đang chỉnh sửa
	 */
	private function condition_field_admin_get_render($field_config) {
		ob_start();
		?>
		<li id="<?=esc_attr($field_config['field_id'])?>">
			<div class="ofs-field-config-title">
				<span><?php echo ucfirst(esc_html($field_config['field_type'])); ?></span>
				<span title="<?php echo esc_attr($field_config['config']['label']); ?>"><?php echo esc_html(mb_substr($field_config['config']['label'], 0, 60)); ?></span>
			</div>
			<div class="ofs-field-config-actions">
				<a href="javascript:void(0);" class="ofs-edit-field" data-field="<?=esc_attr($field_config['field_id'])?>" data-type="<?=esc_attr($field_config['field_type'])?>">Sửa</a>
				<a href="javascript:void(0);" class="ofs-remove-field" data-field="<?=esc_attr($field_config['field_id'])?>">Xóa</a>
			</div>
		</li>
		<?php
		return ob_get_clean();
	}

	/**
	 * lưu cấu hình của một field, hành động ajax submit form cấu hình field ở popup cấu hình field
	 * @return json trả về kết quả dạng json cho jquery ajax submit form cấu hình field xử lý
	 */
	public function ajax_condition_save_field_config() {
		check_ajax_referer('ofs-condition-save-field-config', 'ofs_condition_save_field_config_nonce');

		$condition_id = isset($_POST['condition_id']) ? absint($_POST['condition_id']) : 0;

		$post_type = get_post_type($condition_id);

		if($post_type!='condition' || !current_user_can('edit_condition', $condition_id)) die;

		$field_type = isset($_POST['field_type']) ? $_POST['field_type'] : '';
		$field_id = isset($_POST['field_id']) ? sanitize_key($_POST['field_id']) : '';
		$act = isset($_POST['act']) ? sanitize_key($_POST['act']) : '';

		$response = array(
			'status' => 0,
			'act' => $act,
			'html' => ''
		);

		$fields = OFS_Field_Factory::get_fields();

		if(isset($fields[$field_type]) && $field_id!='') {
			try {
				$field = OFS_Field_Factory::get_field($field_type);
				
				$field_label = isset($_POST['field_label']) ? sanitize_text_field($_POST['field_label']) : '';
				$field_desc = isset($_POST['field_desc']) ? sanitize_textarea_field($_POST['field_desc']) : '';

				if($field_label!='') {
					
					$data = array(
						'field_id' => $field_id,
						'condition_id' => $condition_id,
						'field_type' => $field->get_type(),
						'config' => array(
							'label' => $field_label,
							'desc' => $field_desc
						)
					);

					$field_config = $field->admin_get_config();

					$data['config'] = array_merge($data['config'], $field_config);

					//error_log(print_r($data,true));

					$status = false;
				
					$status = ofs_get_model()->save_field($data);

					$response['status'] = $status;
					
					$field_config = ofs_get_model()->get_field($field_id);

					if(!empty($field_config)) {
						$response['html'] = $this->condition_field_admin_get_render($field_config);
					}
					
				}
			} catch (Exception $e) {

			}
		}
	
		wp_send_json($response);

		die;
	}

	/**
	 * trả về form cấu hình cho field.
	 * form này sẽ hiển thị trên popup cấu hình field thông qua mã jquery chèn vào modal-body
	 */
	public function ajax_condition_add_field() {
		check_ajax_referer('ofs-admin-ajax-nonce', 'nonce');

		$condition_id = isset($_POST['condition_id']) ? absint($_POST['condition_id']) : 0;

		$post_type = get_post_type($condition_id);

		if ( $post_type!='condition' || !current_user_can('edit_condition', $condition_id) ) die;

		$field_type = isset($_POST['field_type']) ? $_POST['field_type'] : '';

		$fields = OFS_Field_Factory::get_fields();

		if ( isset($fields[$field_type]) ) {
			$field_id = isset($_POST['field_id']) ? sanitize_key($_POST['field_id']) : '';
			$config = array();

			if ( ofs_get_model()->check_field($field_id) ) {
				$act = 'edit';
				$config = ofs_get_model()->get_field($field_id);
			} else {
				$field_id = 'ofsf_'.strtolower(wp_generate_password(12,false,false));
				//$field_id = uniqid($type);
				$act = 'add';
			}

			$label = (isset($config['config']['label'])) ? $config['config']['label'] : '';
			$desc = (isset($config['config']['desc'])) ? $config['config']['desc'] : '';
			?>
			<form id="ofs-condition-field-config-form" class="ofs-config-form" action="<?php echo admin_url('admin-ajax.php?action=ofs_condition_save_field_config'); ?>" method="post">
				<?php wp_nonce_field( 'ofs-condition-save-field-config', 'ofs_condition_save_field_config_nonce' ); ?>
				<input type="hidden" name="condition_id" value="<?=$condition_id?>">
				<input type="hidden" name="field_id" value="<?=esc_attr($field_id)?>">
				<input type="hidden" name="field_type" value="<?=esc_attr($field_type)?>">
				<input type="hidden" name="act" value="<?=esc_attr($act)?>">
				<table>
					<tr>
						<td><?php _e('Label', 'ofs'); ?> </td>
						<td><input type="text" name="field_label" value="<?=esc_attr($label)?>" required></td>
					</tr>
					<tr>
						<td><?php _e('Description', 'ofs'); ?> </td>
						<td><textarea name="field_desc"><?=esc_textarea($desc)?></textarea></td>
					</tr>
					<?php
					try {
						$field = OFS_Field_Factory::get_field($field_type);
						$field->admin_config_template($config);
					} catch (Exception $e) {
						echo $e->getMessage();
					}
					?>
				</table>
				<p class="ofs-frm-submit"><button type="submit" class="button button-primary"><?php _e('Save field', 'ofs'); ?></button></p>
			</form>
			<?php
		}

		die;
	}

	/*
	 Xoa cac du lieu neu xoa condition
	 */
	public function remove_condition_data($post_id) {
		ofs_model()->remove_condition($post_id);
	}

	public function add_meta_boxes() {
		add_meta_box(
            'condition-manage-fields',
            'Cấu hình trường thông tin',
            array($this, 'condition_manage_fields'),
            'condition'
        );
	}

	/**
	 * trang quản lý các field của một condition
	 * @param  int $post condition id
	 */
	public function condition_manage_fields($post) {
		$fields = OFS_Field_Factory::get_fields();
		//print_r($fields);
		$condition_fields = ofs_get_model()->get_condition_fields($post->ID);
		?>
		<div class="ofs-select-condition-field-wrap">
		<table>
			<tr>
				<td>
					<select id="ofs-select-condition-field">
					<?php
					foreach ($fields as $key => $value) {
						try {
							$field = OFS_Field_Factory::get_field($key);
							?>
								<option value="<?=$field->get_type()?>"><?=esc_html($field->get_name())?></option>
							<?php
						} catch (Exception $e) {
							echo $e->getMessage();
						}
					}
					?>
					</select>
				</td>
				<td><button type="button" id="ofs-add-condition-field" class="button"><?php _e('Add new'); ?></button></td>
			</tr>
		</table>
		</div>
		<ul id="ofs-condition-fields">
			<?php
			//ofs_debug($condition_fields);
			if(!empty($condition_fields)) {
				foreach ($condition_fields as $field_config) {
					echo $this->condition_field_admin_get_render($field_config);
				}
			}
			?>
		</ul>
		<?php
	}

	/**
	 * popup chứa form cấu hình của một field
	 * @return popup html

	 */
	public function field_config_modal() {
		global $post_type;
		if($post_type=='condition') {
			?>
			<div id="ofs-condition-field-config" class="ofs-modal">
				<div class="modal-dialog">
					<div class="modal-header">
						<h3 class="modal-title"><?php _e('Field config', 'ofs'); ?></h3>
						<span class="close">&times;</span>
					</div>
					<div class="modal-body"></div>
				</div>
			</div>
			<?php
		}
	}


	public function __clone() {}

	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}

OFS_Admin_Condition::get_instance();