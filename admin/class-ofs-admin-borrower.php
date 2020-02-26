<?php
if (!defined('ABSPATH')) exit;

class OFS_Admin_Borrower {

	private static $instance = null;

	private function __construct() {
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action('after_delete_post', array($this, 'remove_connection_data'));

		add_filter('the_title', array($this, 'borrower_title_in_list_table'), 10, 2 );

		add_filter( 'manage_borrower_posts_columns', array( $this, 'borrower_columns_header' ), 10, 1 );
		add_action( 'manage_borrower_posts_custom_column', array( $this, 'borrower_columns_value' ), 10, 2 );
	}

	public function borrower_columns_value( $column_name, $post_id ) {
		if($column_name=='fullname') {
			$current_post = get_post($post_id);
			echo esc_html($current_post->post_excerpt);
		}
	}

	public function borrower_columns_header($columns) {
		$date = $columns['date'];
		unset($columns['date']);
		$columns['fullname'] = __('Fullname', 'ofs');
		$columns['date'] = $date;
		return $columns;
	}

	public function borrower_title_in_list_table($title, $id) {
		$screen = get_current_screen();
		//print_r($screen);
		if( $screen->id == 'edit-borrower' && !current_user_can('publish_borrowers') ) {
			$borrower = ofs_get_borrower($id);
			$user = wp_get_current_user();
			if($borrower->is_connected($user->ID)) {
				$title = $borrower->get_name();
			} else {
				$title = $borrower->get_hidden_name();
			}
		}
		return $title;
	}

	public function remove_connection_data($post_id) {
		ofs_model()->remove_connection_by_borrower($post_id);
		ofs_model()->remove_borrower_data($post_id);
	}

	public function add_meta_boxes() {
		add_meta_box(
            'borrower-filter-data',
            'Filter data',
            array($this, 'borrower_filter_data'),
            'borrower'
        );
	}

	public function borrower_filter_data( $post ) {
		$borrower = ofs_get_borrower($post);
		if(!empty($borrower->get_data())) {
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
	}

	public function __clone() {}

	public function __wakeup() {}

	public static function get_instance() {
		if(empty(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}
OFS_Admin_Borrower::get_instance();