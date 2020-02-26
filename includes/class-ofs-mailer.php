<?php
if (!defined('ABSPATH')) exit;

class OFS_Mailer {

	public $to = '';

	public $headers = array('Content-Type: text/html; charset=UTF-8');

	public $subject = '';

	public $body = '';

	public $cc = array();

	public $bcc = array();

	public $sent = false;

	public function __construct($to='', $subject='', $body='', $headers=array()) {
		$this->to = ($to)?$to:get_bloginfo('admin_email');
		
		if(!empty($headers)) {
			$this->headers = $headers;
		}

		if(!empty($subject)) {
			$this->subject = $subject;
		}

		if(!empty($body)) {
			$this->body = $body;
		}
		
	}

	public function send() {
		$this->body .= '<br>------------------------------------';
		$this->body .= '<div>Email được gửi từ website '.home_url('/').'</div>';
		if(!empty($this->cc)) {
			foreach ($this->cc as $key => $value) {
				$this->headers[] = 'Cc: '.$value;
			}
		}
		if(!empty($this->bcc)) {
			foreach ($this->bcc as $key => $value) {
				$this->headers[] = 'Bcc: '.$value;
			}
		}
		$this->sent = wp_mail($this->to, $this->subject, $this->body, $this->headers);
		return $this->sent;
	}

	public function set_to($to) {
		$this->to = $to;
	}

	public function add_to($to) {
		if($this->to!='') {
			$this->to = array_unique( array_merge( (array) $this->to, (array) $to) );
		} else {
			$this->to = $to;
		}
		
	}

	public function set_cc($cc) {
		$cc = array_unique(array_values((array) $cc));
		$cc = array_map('sanitize_email', $cc);
		$cc = array_filter($cc);
		$this->cc = $cc;
	}

	public function add_cc($cc) {
		$cc = array_values((array) $cc);
		$cc = array_map('sanitize_email', $cc);
		$cc = array_unique(array_merge($this->cc, $cc));
		$cc = array_filter($cc);
		$this->cc = $cc;
	}

	public function set_bcc($bcc) {
		$bcc = array_unique(array_values((array) $bcc));
		$bcc = array_map('sanitize_email', $bcc);
		$bcc = array_filter($bcc);
		$this->bcc = $bcc;
	}

	public function add_bcc($bcc) {
		$bcc = array_values((array) $bcc);
		$bcc = array_map('sanitize_email', $bcc);
		$bcc = array_unique(array_merge($this->bcc, $bcc));
		$bcc = array_filter($bcc);
		$this->bcc = $bcc;
	}

	public function set_headers($headers) {
		$this->headers = (array)$headers;
	}

	public function add_headers($headers) {
		$this->headers = array_merge($this->headers, (array)$headers);
	}

	public function set_subject($string) {
		$this->subject = (string)$string;
	}

	public function append_subject($string) {
		$this->subject .= (string)$string;
	}

	public function prepend_subject($string) {
		$this->subject = (string)$string . $this->subject;
	}

	public function set_body($string) {
		$this->body = (string)$string;
	}

	public function append_body($string) {
		$this->body .= (string)$string;
	}

	public function prepend_body($string) {
		$this->body = (string)$string . $this->body;
	}
}