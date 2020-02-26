<?php
if (!defined('ABSPATH')) exit;

class OFS_Province_Field extends OFS_Field {

	protected $provinces = array();

	protected $type = 'province';

	protected $name = '';

	public function __construct() {
		$this->name = _x('Province', 'condition field name', 'ofs');
		$this->load_provinces();
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
					if(isset($this->provinces[$borrower_data['data']])) {
						echo esc_html($this->provinces[$borrower_data['data']]['name_with_type']);
					}
					?>
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
		$data = isset($_POST[$field['field_id']]) ? sanitize_text_field($_POST[$field['field_id']]) : '';
		if(!isset($this->provinces[$data])) {
			$data = '';
		}
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
				<div class="ofsfc-<?=esc_attr($this->type)?>">
					<select name="<?php echo esc_attr($field['field_id']); ?>">
					<?php
					foreach ($this->provinces as $province_id => $province) {
						?>
						<option value="<?=esc_attr($province_id)?>" <?php selected($province_id, $filtered, true); ?>><?=esc_html($province['name_with_type'])?></option>
						<?php
					}
					?>
					</select>
				</div>
				<script type="text/javascript">
				jQuery(function($){
					$('select[name=<?php echo esc_attr($field['field_id']); ?>]').select2({
						width:'100%'
					});
				});
				</script>
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
		$accept_items = isset($required['accept_items']) ? (array)$required['accept_items'] : [];
		?>
		<tr>
			<td><?php _e('Accept items', 'credit-tool'); ?> </td>
			<td>
			<select name="required_accept_items[]" multiple="multiple">
			<?php
			foreach ($this->provinces as $province_id => $province) {
				?>
				<option value="<?=esc_attr($province_id)?>" <?php selected(in_array($province_id, $accept_items), true, true); ?>><?=esc_html($province['name_with_type'])?></option>
				<?php
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

	private function load_provinces() {
		$provinces = json_decode(file_get_contents(dirname(__FILE__).'/hanhchinhvn/dist/tinh_tp.json'), true);
		ksort($provinces);
		$this->provinces = $provinces;
	}

	public function get_provinces() {
		return $this->provinces;
	}

	public static function admin_enqueue_scripts() {
		wp_enqueue_style('select2');
		wp_enqueue_script('select2');
	}

	public static function enqueue_scripts() {
		wp_enqueue_style('select2');
		wp_enqueue_script('ofs-province-field', OFS_URL.'/fields/province/static/province.js', array('select2'), '', true);
	}
}
