<?php
session_start();
global $captcha;
$_SESSION['captcha_security'] = $captcha->get_and_show_image();

