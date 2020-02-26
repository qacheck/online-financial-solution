<?php 

class Captcha {

	private $img_width      =   120;
	private $img_height     =   30;	
        
	private $font_path      =   './fonts'; // đường dẫn đên thư mục file text
	private $fonts          =   array();
	private $font_size      =   14;
	
	private $char_set       =   "abcdefghjklmnpqrstuvwxyz2345689";
	private $char_length    =   6;
	
	private $char_color     =   "#880000,#008800,#000088,#888800,#880088,#008888,#000000";
	private $char_colors    =   array();
	
	private $line_count     =   10;
	private $line_color     =   "#DD6666,#66DD66,#6666DD,#DDDD66,#DD66DD,#66DDDD,#666666";
	private $line_colors    =   array();
        
	private $bg_color       =   '#FFFFFF';

	private static $instance = null;

	private function __construct() {
		$this->font_path = __DIR__.'/fonts';
		// Lấy danh sách fonts trong folder được định nghĩa trong biến font_path
		$this->fonts = $this->collect_files( $this->font_path, "ttf");

		add_action('wp_head', array($this, 'refresh_captcha_image_script'));

		add_action('wp_ajax_get_captcha_image', array($this, 'get_captcha_image'));
		add_action('wp_ajax_nopriv_get_captcha_image', array($this, 'get_captcha_image'));
	}

	public function refresh_captcha_image_script() {
		?>
		<script type="text/javascript">
			function captcha_refresh(el) {
				var src = el.src;
				//console.log(src);
				el.src = (src.match(/&r=(.*)/g))?src.replace(/&r=(.*)/, '&r='+(new Date().getTime())):src+'&r='+(new Date().getTime());
			}
		</script>
		<?php
	}

	public static function verify($request_name) {
		if(isset($_REQUEST[$request_name]) && self::hash($_REQUEST[$request_name]) === $_SESSION['captcha'] ) {
			return true;
		}
		return false;
	}

	public function set_config($override = array()) {
		if( is_array( $override) ) {
			foreach ( $override as  $key => $value) {
				if( isset( $this->$key ))
					$this->$key = $value;
			}			
		}
	}

	public function get_captcha_image() {
		$config = [];
		if(!empty($_GET)) {
			$excludes = ['font_path','fonts','char_set','char_color','char_colors','line_color','line_colors'];
			foreach ($_GET as $key => $value) {
				if(!in_array($key, $excludes)) {
					switch ($key) {
						case 'img_width':
						case 'img_height':
						case 'font_size':
						case 'char_length':
						case 'line_count':
							$config[$key] = absint($value);
							break;
						case 'bg_color':
							$config[$key] = '#'.sanitize_hex_color_no_hash($value);
							break;
						default:
							$config[$key] = sanitize_text_field($value);
							break;
					}
				}
			}
		}
		//debug($config);
		$_SESSION['captcha'] = $this->get_and_show_image($config);
		die;
	}

	public static function src() {
		return admin_url('admin-ajax.php?action=get_captcha_image');
	}

	public static function show_image($config = array()) {
		$src = admin_url('admin-ajax.php?action=get_captcha_image');
		$src = (!empty($config)) ? add_query_arg($config, $src) : $src;
		echo '<img class="img-captcha" src="'.esc_url($src).'" title="Click on refresh"><span class="img-captcha-refresh">Refresh</span>';
	}

	public static function hash($string) {
		return md5($string);
	}
	
	// Khởi tạo cấu hình, hàm này sẽ trả về mã code và hiển thị hình
	public function get_and_show_image( $override = array() ) {
		// Override lại thong số config
		if( is_array( $override) ) {
			foreach ( $override as  $key => $value) {
				if( isset( $this->$key ) ) {
					$this->$key = $value;
				}
			}			
		}

		// Tạo danh sách colors thành một mảng
		$this->line_colors = preg_split("/,\s*?/", $this->line_color );
		$this->char_colors = preg_split("/,\s*?/", $this->char_color );


		// Khởi tạo hình ảnh
		$img = imagecreatetruecolor( $this->img_width, $this->img_height);
		imagefilledrectangle($img, 0, 0, $this->img_width - 1, $this->img_height - 1, $this->gd_color( $this->bg_color ));


		// Vẽ hình lung tung cho đời nó tươi mát
		for ($i = 0; $i < $this->line_count; $i++) {
			imageline($img,
				rand(0, $this->img_width  - 1),
				rand(0, $this->img_height - 1),
				rand(0, $this->img_width  - 1),
				rand(0, $this->img_height - 1),
				$this->gd_color($this->line_colors[rand(0, count($this->line_colors) - 1)])
			);
		}

		// Vẽ code lên hình
		$code = "";
		$y = ($this->img_height / 2) + ( $this->font_size / 2);

		for ($i = 0; $i < $this->char_length ; $i++) {
			$color = $this->gd_color( $this->char_colors[rand(0, count($this->char_colors) - 1)] );
			$angle = rand(-30, 30);
			$char = substr( $this->char_set, rand(0, strlen($this->char_set) - 1), 1);

			$sel_font = $this->fonts[rand(0, count($this->fonts) - 1)];	

			$font = $this->font_path . "/" . $sel_font;

			$x = (intval(( $this->img_width / $this->char_length) * $i) + ( $this->font_size / 2));
			$code .= $char;

			imagettftext($img, $this->font_size, $angle, $x, $y, $color, $font, $char);
		}

		// Hiển thị ảnh
		header('content-type: image/jpg');

		ImageJPeg( $img);

		return self::hash($code);
	}

	// Chuyển color
	private function gd_color($html_color) {
		return preg_match('/^#?([\dA-F]{6})$/i', $html_color, $rgb) ? hexdec($rgb[1]) : false;
	}

	// Lấy danh sách file theo phần mở rộng (ext)
	private function collect_files($dir, $ext) {

		if(! is_dir($dir))
			return false;

		$files = array();

		if( $dh = opendir($dir)) {
			while( false !== ($file = readdir($dh))) {
				// Skip '.' and '..'
				if( $file == '.' || $file == '..') {
				    continue;
				}

				if (preg_match("/\.{$ext}$/i", $file)) {
					$files[] = $file;
				}
				
			}
			closedir($dh);

			return $files;
		}

		return false;
	}

	private function __wakeup() {}

	private function __clone() {}

	public static function get_instance() {
		if(empty(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
global $captcha;
$captcha = Captcha::get_instance();