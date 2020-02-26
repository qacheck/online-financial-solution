<?php
if (!defined('ABSPATH')) exit;

class OFS_MSelect_Field extends OFS_Field {

	protected $type = 'mselect';

	protected $name = '';

	public function __construct() {
		$this->name = _x('Multi Select', 'condition field name', 'ofs');
	}

	public function borrower_data_display($field_config, $borrower_data) {
		?>
		<tr class="ofs-field-type-<?=esc_attr($this->type)?>">
			<td>
				<?php if($field_config['label']!='') {
					?>
					<div class="field-label"><?php echo esc_html($field_config['label']); ?></div>
					<?php
				} ?>
				<?php if($field_config['desc']!='') {
					?>
					<div class="field-desc"><?php echo esc_html($field_config['desc']); ?></div>
					<?php
				} ?>
				
			</td>
			<td>
				<div class="ofs-field-borrower-data">
					<?php
					foreach($borrower_data['data'] as $data) {
						echo '<div class="selected">'.esc_html($data).'</div>';
					}
					?>
				</div>
			</td>
		</tr>
		<?php
	}

	public function filter_sql($field_id, $filtered) {
		global $wpdb;
		$sql = "(field_id='{$field_id}'";
		if(!empty($filtered)) {
			//print_r($field_id);
			foreach ($filtered as $key => $value) {
				//$sql .= " AND required REGEXP 'a:1:{s:12:\"accept_items\";a:\\\d+:{.*;s:\\\d+:\"".esc_sql($value)."\".*}}'";
				$sql .= " AND required LIKE '%".$wpdb->esc_like($value)."%'";
			}
		}
		
		$sql .= ")";
		return $sql;
	}


	public function sanitize_filtering($field) {
		$data = isset($_POST[$field['field_id']]) ? (array)$_POST[$field['field_id']] : array();
		$data = array_map('sanitize_text_field', $data);
		$data = array_map('wp_unslash', $data);
		return $data;
	}


	public function filter_template($field, $filtered=array()) {
		?>
		<tr class="ofsft-<?=esc_attr($this->type)?>">
			<td>
				<?php if($field['config']['label']!='') {
					?>
					<div class="field-label"><?php echo esc_html($field['config']['label']); ?></div>
					<?php
				} ?>
				<?php if($field['config']['desc']!='') {
					?>
					<div class="field-desc"><?php echo esc_html($field['config']['desc']); ?></div>
					<?php
				} ?>
				
			</td>
			<td>
				<div class="ofsfc-<?=esc_attr($this->type)?>">
					<select name="<?php echo esc_attr($field['field_id']); ?>[]" multiple="multiple">
					<?php
					if(!empty($field['config']['items'])) {
						foreach ($field['config']['items'] as $item) {
							?>
							<option value="<?=esc_attr($item)?>" <?php selected(in_array($item, $filtered), true, true); ?>><?=esc_html($item)?></option>
							<?php
						}
					}
					?>
					</select>
				</div>
				<script type="text/javascript">
				jQuery(function($){
					$('select[name^=<?php echo esc_attr($field['field_id']); ?>][multiple]').select2({
						width:'100%'
					});
				});
				</script>
			</td>
		</tr>
		<?php
	}

	public function get_default_filtered() {
		return array();
	}

	public function admin_get_require_config() {
		$config = array();

		$accept_items = isset($_POST['required_accept_items']) ? (array)$_POST['required_accept_items'] : [];
		$accept_items = array_map('sanitize_text_field', $accept_items);
		if(!empty($accept_items)) {
			$accept_items = array_values(array_unique($accept_items));
		}
		$config['accept_items'] = $accept_items;

		return $config;
	}

	public function admin_require_config_template($config, $required = array()) {
		$items = isset($config['items']) ? (array)$config['items'] : array();
		$accept_items = isset($required['accept_items']) ? (array)$required['accept_items'] : [];
		?>
		<tr>
			<td><?php _e('Accept items', 'credit-tool'); ?> </td>
			<td>
			<select name="required_accept_items[]" multiple="multiple">
			<?php
			if(!empty($items)) {
				foreach ($items as $value) {
					?>
					<option value="<?=esc_attr($value)?>" <?php selected(in_array($value, $accept_items), true, true); ?>><?=esc_html($value)?></option>
					<?php
				}
			}
			?>
			</select>
			</td>
		</tr>
		<script type="text/javascript">
		jQuery(function($){
			$('select[name^=required_accept_item][multiple]').select2({
				width:'100%',
				dropdownParent: $('.ofs-modal')
			});
		});
		</script>
		<?php
	}

	public function admin_get_config() {
		$field_config = array(
			'items' => array(),
		);

		$field_items = isset($_POST['field_items']) ? (array)$_POST['field_items'] : [];

		if(!empty($field_items)) {
			$field_items = array_map('sanitize_text_field', $field_items);
			$field_items = array_values(array_unique($field_items));
		}
		$field_config['items'] = $field_items;

		return $field_config;
	}

	public function admin_config_template($config = array()) {
		$items = (isset($config['config']['items'])) ? (array)($config['config']['items']) : [];
		?>
		<tr>
			<td><?php _e('List items', 'ofs'); ?> </td>
			
			<td><select name="field_items[]" multiple>
			<?php
			if(!empty($items)) {
				foreach ($items as $item) {
					?>
					<option selected><?=esc_html($item)?></option>
					<?php
				}
			}
			?>
			</select></td>
		</tr>
		<?php
		$this->admin_config_script();
	}

	public function admin_config_script() {
		?>
		<script type="text/javascript">
		jQuery(function($){
			jQuery("select[name^=field_items][multiple]").select2({
				width:'100%',
				tags: true,
				dropdownParent: $('.ofs-modal')
			})
		});
		</script>
		<?php
	}

	public static function admin_enqueue_scripts() {
		wp_enqueue_style('select2');
		wp_enqueue_script('select2');
	}

	public static function enqueue_scripts() {
		wp_enqueue_style('select2');
		
	}
}
