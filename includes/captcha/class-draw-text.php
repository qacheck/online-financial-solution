<?php
class Draw_Text {

	private $img_width      =   120;
	private $img_height     =   30;	
        
	private $font_path      =   './fonts'; // đường dẫn đên thư mục file text
	private $fonts          =   array();
	private $font_size      =   14;

	private $text 			= '';
	private $char_length    =   6;
	
	private $char_color     =   "#880000,#008800,#000088,#888800,#880088,#008888,#000000";
	private $char_colors    =   array();
	
	private $line_count     =   10;
	private $line_color     =   "#DD6666,#66DD66,#6666DD,#DDDD66,#DD66DD,#66DDDD,#666666";
	private $line_colors    =   array();
        
	private $bg_color       =   '#FFFFFF';

	public function __construct($text='') {
		$this->text = (string)$text;
		$this->char_length = ($this->text!=='') ? strlen($text) : 6;
		$this->font_path = dirname(__FILE__).'/fonts';
		// Lấy danh sách fonts trong folder được định nghĩa trong biến font_path
		$this->fonts = $this->collect_files( $this->font_path, "ttf");

	}

	public function set_config($override = array()) {
		if( is_array( $override) ) {
			foreach ( $override as  $key => $value) {
				if( isset( $this->$key ))
					$this->$key = $value;
			}			
		}
	}

	
	// vẽ
	public function draw( $override = array() ) {
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
		$y = ($this->img_height / 2) + ( $this->font_size / 2);
		
		for ($i = 0; $i < $this->char_length ; $i++) {
			$color = $this->gd_color( $this->char_colors[rand(0, count($this->char_colors) - 1)] );
			$angle = rand(-30, 30);

			$char = ($this->text!=='') ? substr( $this->text, $i, 1) : ' ';

			$sel_font = $this->fonts[rand(0, count($this->fonts) - 1)];	

			$font = $this->font_path . "/" . $sel_font;

			$x = (intval(( $this->img_width / $this->char_length) * $i) + ( $this->font_size / 2));

			imagettftext($img, $this->font_size, $angle, $x, $y, $color, $font, $char);
		}

		// Hiển thị ảnh
		header('content-type: image/jpg');

		ImageJPeg( $img);

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

}