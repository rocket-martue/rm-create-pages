<?php
/**
 * Plugin Name: RM Create Pages
 * Plugin URI: https://github.com/rocket-martue/rm-create-pages
 * Description: 固定ページを一括で作成するためのプラグインだよ！管理画面からページタイトルとスラッグを入力して、サクッとページを作っちゃうの！マジ便利だから使ってみてね！😉
 * Version: 1.0.0
 * Author: Rocket Martue
 * Author URI: https://profiles.wordpress.org/rocketmartue/
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rm-create-pages
 * Domain Path: /languages
 *
 * @package RM_Create_Pages
 */

// セキュリティ対策！直接アクセスされた場合は処理を中断するよ！これ大事！👍
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'functions/admin-menu.php';
