<?php
/*
Plugin Name: OFS
Plugin URI: https://sps.vn
Description: Online Financial Solution
Author: qqngoc
Author URI: https://sps.vn
Version: 3.0.3
Text Domain: ofs
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
if (!defined('ABSPATH')) exit;

define('OFS_PLUGIN_FILE', __FILE__);
define('OFS_URL', untrailingslashit(plugins_url( '', OFS_PLUGIN_FILE)));
define('OFS_PATH', dirname(OFS_PLUGIN_FILE));
define('OFS_BASE', plugin_basename(OFS_PLUGIN_FILE));

define('OFS_CONNECT_TIMEOUT', 72); // đơn vị là giờ
define('OFS_COIN_RATE', 1); // tỷ lệ chuyển đổi giữ tiền thật và tiền ảo
define('OFS_CONNECT_COST', 20000);
define('OFS_CURRENCY_UNIT', 'VNĐ');
define('OFS_CURRENCY_UNIT_SHORT', 'đ');
define('OFS_DONATE', '100000');
define('OFS_ADDITION_ADMIN_EMAIL', 'qqngoc2988@gmail.com');
define('OFS_IUCS', MINUTE_IN_SECONDS);

require_once OFS_PATH.'/includes/class-online-financial-solution.php';

function OFS() {
	return Online_Financial_Solution::get_instance();
}
OFS();