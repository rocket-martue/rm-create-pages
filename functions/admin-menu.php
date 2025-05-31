<?php
/**
 * Admin Menu Functions
 * 管理画面にメニューを追加する
 *
 * @package RM_Create_Pages
 */

/**
 * 管理画面にメニューを追加する関数
 */
function rm_create_pages_admin_menu() {
	add_menu_page(
		'固定ページ一括作成', // ページのタイトル
		'固定ページ一括作成', // メニューのタイトル
		'manage_options',    // このメニューを操作できる権限
		'rm-create-pages',   // メニューのスラッグ (URLになるやつ)
		'rm_create_pages_page_content', // メニューがクリックされた時に表示するコンテンツを生成する関数
		'dashicons-admin-page', // メニューアイコン (Dashiconsから選べるよ！)
		99 // メニューの表示位置 (数字が小さいほど上)
	);
}
add_action( 'admin_menu', 'rm_create_pages_admin_menu' );

/**
 * プラグインのメインページコンテンツを表示する関数
 * ここにテキストエリアと実行ボタンを設置する
 */
function rm_create_pages_page_content() {
	// フォームが送信された後のメッセージ表示用
	if ( isset( $_GET['message'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rm_create_pages_message_nonce' ) ) {
		$message = sanitize_text_field( wp_unslash( $_GET['message'] ) );
		// URLから作成数を取得するよ！なかったら -1 とかで区別しとく
		$created_count_in_url       = isset( $_GET['created_count'] ) ? intval( $_GET['created_count'] ) : -1;
		$display_skipped_separately = true; // スキップリストを別途表示するかのフラグ

		if ( 'success' === $message ) {
			if ( 0 === $created_count_in_url && isset( $_GET['skipped_titles'] ) ) {
				// 作成0件で、スキップされたページがある場合の特別メッセージ！
				$skipped_titles_json = sanitize_text_field( wp_unslash( $_GET['skipped_titles'] ) );
				$skipped_titles_arr  = json_decode( $skipped_titles_json, true );
				$skipped_count       = ( is_array( $skipped_titles_arr ) ) ? count( $skipped_titles_arr ) : 0;

				if ( ! empty( $skipped_titles_arr ) && is_array( $skipped_titles_arr ) ) {
					echo '<div id="message" class="notice notice-info is-dismissible">'; // 青っぽいメッセージにしてみた！
					echo '<p>固定ページは新しく作られなかったけど、' . esc_html( $skipped_count ) . '件のページは既にあったからスキップしたよん😉:</p><ul>';
					foreach ( $skipped_titles_arr as $skipped_title ) {
						echo '<li>' . esc_html( $skipped_title ) . '</li>';
					}
					echo '</ul></div>';
					$display_skipped_separately = false; // もう表示したから、下では表示しないよん
				} else {
					// URLにskipped_titlesはあるけど中身が空っぽだった場合 (普通はないはずだけど一応ね)
					echo '<div id="message" class="notice notice-warning is-dismissible"><p>固定ページは新しく作られなかったみたい。スキップ情報がなんか変かも？🤔</p></div>';
					$display_skipped_separately = false;
				}
			} else {
				// 通常の成功メッセージ (1件以上作成された場合)
				echo '<div id="message" class="updated notice is-dismissible"><p>固定ページの作成が完了したよ！やったね！🎉</p></div>';
			}
		} elseif ( 'error' === $message ) {
			echo '<div id="message" class="error notice is-dismissible"><p>ありゃ、固定ページの作成中にエラーが出ちゃったみたい…😢</p></div>';
		} elseif ( 'no_data' === $message ) {
			echo '<div id="message" class="error notice is-dismissible"><p>えっと、入力データがないみたいだよ？何か入力してね！🥺</p></div>';
		}

		// 警告メッセージがあったら表示！
		if ( isset( $_GET['warnings'] ) ) {
			$warnings_json = sanitize_text_field( wp_unslash( $_GET['warnings'] ) );
			$warnings_arr  = json_decode( $warnings_json, true );

			if ( ! empty( $warnings_arr ) && is_array( $warnings_arr ) ) {
				echo '<div id="warnings-info" class="notice notice-warning is-dismissible"><p>⚠️ いくつか気になることがあったよ:</p><ul>';
				foreach ( $warnings_arr as $warning ) {
					echo '<li>' . esc_html( $warning ) . '</li>';
				}
				echo '</ul></div>';
			}
		}

		// スキップされたページの情報があったら表示するよ！
		// (successメッセージで既にスキップリストを表示してない場合だけね！)
		if ( $display_skipped_separately && isset( $_GET['skipped_titles'] ) ) {
			$skipped_titles_json = sanitize_text_field( wp_unslash( $_GET['skipped_titles'] ) );
			$skipped_titles_arr  = json_decode( $skipped_titles_json, true );

			if ( ! empty( $skipped_titles_arr ) && is_array( $skipped_titles_arr ) ) {
				$prefix_message = ( 'success' === $message && $created_count_in_url > 0 ) ? 'あと、' : ''; // 作成成功してて追加でスキップがある場合
				echo '<div id="skipped-pages-info" class="notice notice-warning is-dismissible"><p>' . esc_html( $prefix_message ) . '以下のページは既に存在していたため、スキップしたよん😉:</p><ul>';
				foreach ( $skipped_titles_arr as $skipped_title ) {
					echo '<li>' . esc_html( $skipped_title ) . '</li>';
				}
				echo '</ul></div>';
			}
		}
	}
	?>
	<div class="wrap">
		<h1>固定ページ一括作成</h1>
		<p>下のテキストエリアに、1行に1ページの情報を「ページタイトル,ページスラッグ」の形式で入力してね！<br>
		例：<br>
		会社概要,company<br>
		お問い合わせ,contact<br>
		サービス,service<br>
		メインサービス,service/main (※スラッシュで階層も作れるよ！ただし親ページが先に存在してないとダメ！)</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="rm_create_pages_submit">
			<?php wp_nonce_field( 'rm_create_pages_action', 'rm_create_pages_nonce' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="rm_pages_data">ページ情報:</label>
					</th>
					<td>
						<textarea name="rm_pages_data" id="rm_pages_data" rows="10" cols="50" class="large-text" placeholder="会社概要,company"></textarea>
						<p class="description">1行に1ページ。「ページタイトル,ページスラッグ」の形式で入力してね。</p>
					</td>
				</tr>
			</table>
			<?php submit_button( '固定ページを一括作成する！' ); ?>
		</form>
	</div>
	<?php
}

/**
 * フォーム送信時の処理を行う関数だよ！
 */
function rm_create_pages_handle_form_submission() {
	// nonceチェック！これがないとセキュリティ的にヤバいからね！
	if ( ! isset( $_POST['rm_create_pages_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rm_create_pages_nonce'] ) ), 'rm_create_pages_action' ) ) {
		wp_die( '不正なアクセスだよ！もうっ！😠' );
	}

	// 権限チェック！管理者じゃないとダメだよ！
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '権限が足りないみたい…管理者でログインしてる？🤔' );
	}

	// 入力データを取得
	$redirect_url_base = admin_url( 'admin.php?page=rm-create-pages' );
	$nonce_url         = '&_wpnonce=' . wp_create_nonce( 'rm_create_pages_message_nonce' ); // メッセージ表示用のnonceも作る！

	if ( ! isset( $_POST['rm_pages_data'] ) || empty( trim( sanitize_textarea_field( wp_unslash( $_POST['rm_pages_data'] ) ) ) ) ) {
		// データが空っぽだったらエラーメッセージ出してリダイレクト
		wp_safe_redirect( $redirect_url_base . '&message=no_data' . $nonce_url );
		exit;
	}

	$pages_data_raw = sanitize_textarea_field( wp_unslash( $_POST['rm_pages_data'] ) );
	$pages_lines    = explode( "\n", $pages_data_raw ); // 改行で分割して配列にするよ

	$created_count       = 0;
	$error_count         = 0;
	$skipped_page_titles = array(); // スキップしたページのタイトルを入れとく配列！
	$warning_messages    = array(); // 警告メッセージを入れとく配列！

	foreach ( $pages_lines as $line ) {
		$line = trim( $line );
		if ( empty( $line ) ) {
			continue; // 空行はスルー！
		}

		$parts = str_getcsv( $line ); // CSV形式でパースするよ (カンマ区切り)

		if ( count( $parts ) >= 2 ) {
			$page_title = sanitize_text_field( $parts[0] );

			// スラッグが階層構造を持つ場合の特別処理を追加するよ！
			if ( strpos( $parts[1], '/' ) !== false ) {
				// スラッシュが含まれてる = 階層構造にしたい
				$slug_parts = explode( '/', $parts[1] );
				// 最後の部分だけを sanitize_title で整形
				$last_part = array_pop( $slug_parts );
				$last_part = sanitize_title( $last_part );
				// 親階層はそのまま保持（必要に応じて sanitize）
				$parent_path = '';
				foreach ( $slug_parts as $parent_part ) {
					$parent_path .= sanitize_title( $parent_part ) . '/';
				}
				$page_slug = $parent_path . $last_part;

				// 親ページが存在するかチェック
				$parent_slug = rtrim( $parent_path, '/' );
				$parent_page = get_page_by_path( $parent_slug, OBJECT, 'page' );
				if ( ! $parent_page ) {
					// 警告メッセージを配列に追加！
					$warning_messages[] = '「' . $page_title . '」の親ページ「' . $parent_slug . '」が見つからないよ！階層にならずに「' . str_replace( '/', '-', $parts[1] ) . '」みたいなスラッグになっちゃうかも💦';
				}
			} else {
				// 通常のスラッグ処理
				$page_slug = sanitize_title( $parts[1] );
			}

			// 同じスラッグのページが既に存在するかチェック 既に存在する場合の処理 (スキップする)
			$existing_page = get_page_by_path( $page_slug, OBJECT, 'page' );
			if ( $existing_page ) {
				// 既に存在する場合はスキップ
				$skipped_page_titles[] = $page_title; // スキップリストにタイトルを追加！
				continue;
			}

			$new_page = array(
				'post_title'   => $page_title,
				'post_name'    => $page_slug,
				'post_content' => '', // とりあえず内容は空っぽで！
				'post_status'  => 'publish', // 'draft' (下書き) とか 'private' (非公開) も選べるよ
				'post_author'  => get_current_user_id(),
				'post_type'    => 'page',
			);

			$result = wp_insert_post( $new_page, true ); // 第2引数をtrueにするとエラー時にWP_Errorオブジェクトが返るよ

			if ( is_wp_error( $result ) ) {
				// エラーメッセージも配列に追加！
				$warning_messages[] = '「' . $page_title . '」の作成でエラー発生: ' . $result->get_error_message();
				++$error_count;
			} else {
				++$created_count;
			}
		} else {
			// フォーマットが違う行はエラー扱い
			$warning_messages[] = '「' . $line . '」の形式が正しくないよ！「タイトル,スラッグ」で入力してね😅';
			++$error_count;
		}
	}

	// リダイレクトURLにパラメータを追加していくよ！
	$redirect_params                  = array();
	$redirect_params['created_count'] = $created_count; // 作成件数をパラメータに追加！これ大事！

	if ( ! empty( $skipped_page_titles ) ) {
		// スキップしたページがあったら、JSONにしてURLエンコードしてパラメータに追加！
		$redirect_params['skipped_titles'] = rawurlencode( wp_json_encode( $skipped_page_titles ) );
	}

	if ( ! empty( $warning_messages ) ) {
		// 警告メッセージがあったら、それも追加！
		$redirect_params['warnings'] = rawurlencode( wp_json_encode( $warning_messages ) );
	}

	// 処理が終わったらメッセージ付きでリダイレクト
	if ( $error_count > 0 && 0 === $created_count ) { // エラーがあって、かつ1件も作れなかった場合
		$redirect_params['message'] = 'error';
	} elseif ( $created_count > 0 ) { // 1件でも作成成功した場合 (スキップがあってもこっち優先)
		$redirect_params['message'] = 'success';
	} elseif ( 0 === $created_count && ! empty( $skipped_page_titles ) && 0 === $error_count ) { // 作成0件、スキップあり、エラーなしの場合
		$redirect_params['message'] = 'success'; // これも 'success' にして、表示側で created_count を見て判断させる！
	} else {
		// 上記以外 (入力が全部無効だったとか、空行だけだったとか)
		// 関数冒頭で入力データ全体の空チェックはしてるから、ここは実質「有効な行が1行もなかった」場合だね
		$redirect_params['message'] = 'no_data';
	}

	// $redirect_url_base と $nonce_url は上で定義済み
	wp_safe_redirect( add_query_arg( $redirect_params, $redirect_url_base . $nonce_url ) );
	exit;
}
// admin_post_{action} フックに登録！これでフォーム送信時に上の関数が呼ばれるようになるよ！
add_action( 'admin_post_rm_create_pages_submit', 'rm_create_pages_handle_form_submission' );
