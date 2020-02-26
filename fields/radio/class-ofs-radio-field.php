<?php
if (!defined('ABSPATH')) exit;

class OFS_Radio_Field extends OFS_Field {

	protected $type = 'radio';

	protected $name = '';

	public function __construct() {
		$this->name = _x('Radio', 'condition field name', 'ofs');
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
					<?php echo esc_html($borrower_data['data']); ?>
				</div>
			</td>
		</tr>
		<?php
	}

	public function filter_sql($field_id, $filtered) {
		global $wpdb;
		//$sql = "(field_id='{$field_id}' AND required REGEXP 'a:1:{s:12:\"accept_items\";a:\\\d+:{.*;s:\\\d+:\"".esc_sql($filtered)."\".*}}')";
		$sql = "(field_id='{$field_id}' AND required LIKE '%".$wpdb->esc_like($filtered)."%')";
		return $sql;
	}

	public function sanitize_filtering($field) {
		$data = isset($_POST[$field['field_id']]) ? wp_unslash(sanitize_text_field($_POST[$field['field_id']])) : '';
		return $data;
	}

	public function filter_template($field, $filtered='') {
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
				<?php
				if(!empty($field['config']['items'])) {
				echo '<div class="ofsfc-'.esc_attr($this->type).' layout-'.esc_attr($field['config']['layout']).'">';
					foreach ($field['config']['items'] as $item) {
						?>
						<label><input type="radio" name="<?php echo esc_attr($field['field_id']); ?>" value="<?=esc_attr($item)?>" <?php checked( $item, $filtered, true ); ?>> <?=esc_html($item)?></label>
						<?php
					}
				echo '</div>';
				}
				?>
			</td>
		</tr>
		<?php
	}

	public function get_default_filtered() {
		return '';
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
			'layout' => 'grid'
		);

		$field_items = isset($_POST['field_items']) ? (array)$_POST['field_items'] : [];
		$field_layout = isset($_POST['field_layout']) ? sanitize_key($_POST['field_layout']) : 'grid';

		if(!empty($field_items)) {
			$field_items = array_map('sanitize_text_field', $field_items);
			$field_items = array_values(array_unique($field_items));
		}
		$field_config['items'] = $field_items;
		$field_config['layout'] = $field_layout;

		return $field_config;
	}

	public function admin_config_template($config = array()) {
		$items = (isset($config['config']['items'])) ? (array)($config['config']['items']) : [];
		$layout = (isset($config['config']['layout'])) ? sanitize_key($config['config']['layout']) : 'grid';
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
		<tr>
			<td><?php _e('Layout', 'ofs'); ?> </td>
			
			<td>
			<select name="field_layout">
				<option value="grid" <?php selected('grid', $layout, true, true); ?>>Grid</option>
				<option value="list" <?php selected('list', $layout, true, true); ?>>List</option>
			</select>
			</td>
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
