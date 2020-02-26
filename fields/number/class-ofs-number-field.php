<?php
if (!defined('ABSPATH')) exit;

class OFS_Number_Field extends OFS_Field {

	protected $type = 'number';

	protected $name = '';

	public function __construct() {
		$this->name = _x('Number', 'condition field name', 'ofs');
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
					<span class="number"><?php //echo self::sanitize_input_number($borrower_data['data']); ?>
						<input type="text" name="number-<?=esc_attr($borrower_data['field_id'])?>" value="<?=esc_attr($borrower_data['data'])?>" readonly="readonly">
					</span>
					<?php
					if($field_config['unit']!='') {
						echo '<span class="unit">'.esc_html($field_config['unit']).'</span>';
					}
					?>
				</div>
				<script type="text/javascript">
					jQuery(function($){
						$('input[name="number-<?=esc_attr($borrower_data['field_id'])?>"]').inputNumber({integer: false, negative:true}).trigger('keyup');
					});
				</script>
			</td>
		</tr>
		<?php
	}

	public function filter_sql($field_id, $filtered) {
		$sql = "(field_id='{$field_id}' AND cmin<={$filtered} AND cmax>={$filtered})";
		return $sql;
	}

	public function sanitize_filtering($field) {
		$data = isset($_POST[$field['field_id']]) ? self::sanitize_input_number($_POST[$field['field_id']]) : '';
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
				<div class="ofsfc-<?=esc_attr($this->type)?><?php echo ($field['config']['unit']!='')?' has-unit':''; ?>">
					<span class="input"><input type="text" name="<?php echo esc_attr($field['field_id']); ?>" value="<?=esc_attr($filtered)?>"></span>
					<?php
					if($field['config']['unit']!='') {
						echo '<span>'.esc_html($field['config']['unit']).'</span>';
					}
					?>
				</div>
				<script type="text/javascript">
					jQuery(function($){
						$('input[name=<?php echo esc_attr($field['field_id']); ?>]').inputNumber({integer: false, negative:true}).trigger('keyup');
					});
				</script>
			</td>
		</tr>
		<?php
	}

	public function get_default_filtered() {
		return 0;
	}

	public function admin_get_require_config() {
		$config = array();

		$min = (isset($_POST['required_min'])&&$_POST['required_min']!='') ? self::sanitize_input_number($_POST['required_min']) : PHP_INT_MIN;
		$max = (isset($_POST['required_max'])&&$_POST['required_max']!='') ? self::sanitize_input_number($_POST['required_max']) : PHP_INT_MAX;
		// $enum = (isset($_POST['required_enum'])&&$_POST['required_enum']!='') ? explode("|",$_POST['required_enum']) : [];
		// $enum = array_map(__CLASS__.'::sanitize_input_number', $enum);
		// if(!empty($enum)) {
		// 	$enum = array_values(array_unique($enum));
		// }

		if(is_int($min) && is_int($max) && $min>$max) {
			$temp = $min;
			$min = $max;
			$max = $temp;
		}

	
		$config = implode('|',array($min, $max));
		//$config = array('min'=>$min, 'max'=>$max, 'enum'=>$enum);

		return $config;
	}

	public function admin_require_config_template($config, $required = array()) {
		//print_r($config);
		$required = explode('|', $required);
		$min = (isset($required[0])) ? $required[0] : '';
		$max = (isset($required[1])) ? $required[1] : '';
		// $enum = (isset($required['enum'])) ? (array)$required['enum'] : [];
		// $enum_string = [];
		
		?>
		<tr>
			<td><?php _e('Min', 'ofs'); ?> </td>
			<td><div class="has-unit-control"><div class="input"><input type="text" name="required_min" value="<?=esc_attr($min)?>"></div><span class="unit"><?=esc_html($config['unit'])?></span></div></td>
		</tr>
		<tr>
			<td><?php _e('Max', 'ofs'); ?> </td>
			<td><div class="has-unit-control"><div class="input"><input type="text" name="required_max" value="<?=esc_attr($max)?>"></div><span class="unit"><?=esc_html($config['unit'])?></span></div></td>
		</tr>
		<!-- <tr>
			<td></td>
			<td><?php //_e('Or', 'ofs'); ?></td>
		</tr>
		<tr>
			<td><?php //_e('Enum', 'ofs'); ?> </td>
			<td><div class="has-unit-control"><div class="input"><textarea name="required_enum"><?php //echo esc_textarea(implode("|", $enum_string)); ?></textarea></div><span class="unit"><?php //echo esc_html($config['unit']); ?></span></div>
				<i>Mỗi số cách nhau bởi ký tự |</i>
			</td>
		</tr> -->
		<script type="text/javascript">
			jQuery(function($){
				$('input[name=required_min],input[name=required_max]').inputNumber({integer: false, negative:true}).trigger('keyup');
			});
		</script>
		<?php
	}

	public static function sanitize_input_number($number) {
		$number = preg_replace("/[^0-9+\-\.]/", '', $number);
		if(preg_match('/\./', $number)) {
			$number = floatval($number);
		} else {
			$number = intval($number);
		}
		return $number;
	}

	public function admin_get_config() {

		$field_config = array(
			//'step' => 1,
			'unit' => ''
		);
	
		//$field_step = isset($_POST['field_step']) ? abs(self::sanitize_input_number($_POST['field_step'])) : 1;
		$field_unit = isset($_POST['field_unit']) ? sanitize_text_field($_POST['field_unit']) : '';

		//$field_config['step'] = ($field_step)?$field_step:1;
		$field_config['unit'] = $field_unit;

		return $field_config;
	}

	public function admin_config_template($config = array()) {
		//$step = (isset($config['config']['step'])) ? abs(self::sanitize_input_number($config['config']['step'])) : 1;
		$unit = (isset($config['config']['unit'])) ? $config['config']['unit'] : '';
		?>
		<!-- <tr>
			<td><?php //_e('Step', 'ofs'); ?> </td>
			<td><input type="text" name="field_step" value="<?php //echo $step; ?>"></td>
		</tr> -->
		<tr>
			<td><?php _e('Unit', 'ofs'); ?> </td>
			<td><input type="text" name="field_unit" value="<?=esc_attr($unit)?>"></td>
		</tr>
		<?php
		//$this->admin_config_script();
	}

	private function admin_config_script() {
		?>
		<script type="text/javascript">
		jQuery(function($){
			$('input[name=field_step]').inputNumber({integer: false, negative:false}).trigger('keyup');
		});
		</script>
		<?php
	}

	public static function admin_enqueue_scripts() {
		//wp_enqueue_style('ofs-number-field', OFS_URL.'/fields/number/static/style.css');
		wp_enqueue_script('jquery-input-number');
	}

	public static function enqueue_scripts() {
		wp_enqueue_script('jquery-input-number');
	}
}