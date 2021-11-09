<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php error_reporting(E_ALL | E_STRICT);
##-----------------------------------------------------------------------------------------------------------------##
#
#  PHPメールプログラム　フリー版 ver2.0.1 最終更新日2021/10/26
#　改造や改変は自己責任で行ってください。
#
#  HP: http://www.php-factory.net/
#
#  重要！！サイトでチェックボックスを使用する場合のみですが。。。
#  チェックボックスを使用する場合はinputタグに記述するname属性の値を必ず配列の形にしてください。
#  例　name="当サイトをしったきっかけ[]"  として下さい。
#  nameの値の最後に[と]を付ける。じゃないと複数の値を取得できません！
#
##-----------------------------------------------------------------------------------------------------------------##
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {//PHP5.1.0以上の場合のみタイムゾーンを定義
	date_default_timezone_set('Asia/Tokyo');//タイムゾーンの設定（日本以外の場合には適宜設定ください）
}
/*-------------------------------------------------------------------------------------------------------------------
* ★以下設定時の注意点　
* ・値（=の後）は数字以外の文字列（一部を除く）はダブルクオーテーション「"」、または「'」で囲んでいます。
* ・これをを外したり削除したりしないでください。後ろのセミコロン「;」も削除しないください。
* ・また先頭に「$」が付いた文字列は変更しないでください。数字の1または0で設定しているものは必ず半角数字で設定下さい。
* ・メールアドレスのname属性の値が「Email」ではない場合、以下必須設定箇所の「$Email」の値も変更下さい。
* ・name属性の値に半角スペースは使用できません。
*以上のことを間違えてしまうとプログラムが動作しなくなりますので注意下さい。
-------------------------------------------------------------------------------------------------------------------*/

require 'init.php';

//------------------------------- 任意設定ここまで ---------------------------------------------


// 以下の変更は知識のある方のみ自己責任でお願いします。

//----------------------------------------------------------------------
//  関数実行、変数初期化
//----------------------------------------------------------------------
//トークンチェック用のセッションスタート
if($useToken == 1 && $confirmDsp == 1){
	session_name('PHPMAILFORMSYSTEM');
	session_start();
}

$encode = "UTF-8";//このファイルの文字コード定義（変更不可）
if(isset($_GET)) { $_GET = sanitize($_GET); } //NULLバイト除去//
if(isset($_POST)) { $_POST = sanitize($_POST); } //NULLバイト除去//
if(isset($_COOKIE)) { $_COOKIE = sanitize($_COOKIE); } //NULLバイト除去//
if($encode == 'SJIS') { $_POST = sjisReplace($_POST,$encode); }//Shift-JISの場合に誤変換文字の置換実行
$funcRefererCheck = refererCheck($Referer_check,$Referer_check_domain);//リファラチェック実行

//変数初期化
$sendmail   = 0;
$empty_flag = 0;
$post_mail  = '';
$errm       = '';
$header     = '';

if($requireCheck == 1) {
	$requireResArray = requireCheck($require); //必須チェック実行し返り値を受け取る
	$errm            = $requireResArray['errm'];
	$empty_flag      = $requireResArray['empty_flag'];
}
//メールアドレスチェック
if(empty($errm)){
	foreach($_POST as $key=>$val) {
		if($val == "confirm_submit") { $sendmail = 1; } // 確認画面からのsubmit判定
		if($key == $Email) { $post_mail = h($val); }
		if($key == $Email && $mail_check == 1 && !empty($val)){
			if(!checkMail($val)){
				$errm .= "<p class=\"error_messe\">メールアドレスの形式が正しくありません。</p>\n";
				$empty_flag = 1;
			}
		}
	}
}
if($empty_flag == 0) {
	if($doubleCheck == 1) {
		if(!empty($_POST)) {
		// 重複応募チェック
			$empty_flag = doubleCheck($_POST);
		}
		if($empty_flag == 1) {
			$errm .= "<p class=\"error_messe\">既にご応募いただいております。</p>\n";
		}
		else if($empty_flag == 2) {
			$errm .= "<p class=\"error_messe\">重複チェック不備</p>\n";
		}
	}
}

// $confirmDsp 0:確認画面を表示しない 1:確認画面を表示する
// $sendmail   1:確認画面からの戻りを表す
// $empty_flag 1:エラーあら(未入力、未選択)

if(($confirmDsp == 0 || $sendmail == 1) && $empty_flag != 1){
	//トークンチェック（CSRF対策）※確認画面がONの場合のみ実施
	if($useToken == 1 && $confirmDsp == 1){
		if(empty($_SESSION['mailform_token']) || ($_SESSION['mailform_token'] !== $_POST['mailform_token'])){
			exit('ページ遷移が不正です');
		}
		if(isset($_SESSION['mailform_token'])) unset($_SESSION['mailform_token']);//トークン破棄
		if(isset($_POST['mailform_token'])) unset($_POST['mailform_token']);//トークン破棄
	}

	//差出人に届くメールをセット
	if($remail == 1) {
		$userBody = mailToUser($_POST,$dsp_name,$remail_text,$mailFooterDsp,$mailSignature,$encode);
		$reheader = userHeader($refrom_name,$from,$encode);
		$re_subject = "=?iso-2022-jp?B?".base64_encode(mb_convert_encoding($re_subject,"JIS",$encode))."?=";
	}
	//管理者宛に届くメールをセット
	$adminBody = mailToAdmin($_POST,$subject,$mailFooterDsp,$mailSignature,$encode,$confirmDsp);
	$header = adminHeader($userMail,$post_mail,$BccMail,$to);
	$subject = "=?iso-2022-jp?B?".base64_encode(mb_convert_encoding($subject,"JIS",$encode))."?=";

	//-fオプションによるエンベロープFrom（Return-Path）の設定(safe_modeがOFFの場合かつ上記設定がONの場合のみ実施)
	if($use_envelope == 0){
		mail($to,$subject,$adminBody,$header);
		if($remail == 1 && !empty($post_mail)) mail($post_mail,$re_subject,$userBody,$reheader);
	}else{
		mail($to,$subject,$adminBody,$header,'-f'.$from);
		if($remail == 1 && !empty($post_mail)) mail($post_mail,$re_subject,$userBody,$reheader,'-f'.$from);
	}
	// CSVファイル格納
	if($csvPool      == 1) {
		$empty_flag    =  csvPool($_POST);
	}
}
else if($confirmDsp == 1){
	if($empty_flag == 1) {
		require $TempFolder . '/error.php';
	}
	else {
		require $TempFolder . '/confirm.php';
	}
}

if(($jumpPage == 0 && $sendmail == 1) || ($jumpPage == 0 && ($confirmDsp == 0 && $sendmail == 0))) {
?>
	<?php // 完了画面の呼び出し
	require $TempFolder . '/thanks.php';
	?>
<?php
}
//確認画面無しの場合の表示、指定のページに移動する設定の場合、エラーチェックで問題が無ければ指定ページヘリダイレクト
else if(($jumpPage == 1	 && $sendmail == 1) || $confirmDsp == 0) {
	if($empty_flag == 1) { ?>
		<?php // エラー画面の呼び出し
		require $TempFolder . '/error.php';
		?>
	<?php
	}
	else {
		header("Location: ".$thanksPage);
	}
}

// 以下の変更は知識のある方のみ自己責任でお願いします。

//----------------------------------------------------------------------
//  関数定義(START)
//----------------------------------------------------------------------
function checkMail($str){
	$mailaddress_array = explode('@',$str);
	if(preg_match("/^[\.!#%&\-_0-9a-zA-Z\?\/\+]+\@[!#%&\-_0-9a-zA-Z]+(\.[!#%&\-_0-9a-zA-Z]+)+$/", "$str") && count($mailaddress_array) ==2){
		return true;
	}else{
		return false;
	}
}
function h($string) {
	global $encode;
	return htmlspecialchars($string, ENT_QUOTES,$encode);
}
function sanitize($arr){
	if(is_array($arr)){
		return array_map('sanitize',$arr);
	}
	return str_replace("\0","",$arr);
}
//Shift-JISの場合に誤変換文字の置換関数
function sjisReplace($arr,$encode){
	foreach($arr as $key => $val){
		$key = str_replace('＼','ー',$key);
		$resArray[$key] = $val;
	}
	return $resArray;
}
//送信メールにPOSTデータをセットする関数
function postToMail($arr){
	global $hankaku,$hankaku_array;
	$resArray = '';
	foreach($arr as $key => $val) {
		$out = '';
		if(is_array($val)){
			foreach($val as $key02 => $item){
				//連結項目の処理
				if(is_array($item)){
					$out .= connect2val($item);
				}else{
					$out .= $item . ', ';
				}
			}
			$out = rtrim($out,', ');

		}else{ $out = $val; }//チェックボックス（配列）追記ここまで

		if (version_compare(PHP_VERSION, '5.1.0', '<=')) {//PHP5.1.0以下の場合のみ実行（7.4でget_magic_quotes_gpcが非推奨になったため）
			if(get_magic_quotes_gpc()) { $out = stripslashes($out); }
		}

		//全角→半角変換
		if($hankaku == 1){
			$out = zenkaku2hankaku($key,$out,$hankaku_array);
		}

		//名称取得
		global $Namearr;
		$itemname = $key;
		if(array_key_exists($key,$Namearr)) {
			$itemname = $Namearr[$key];
		}

		if($out != "confirm_submit" && $key != "httpReferer") {
			$resArray .= "【 ".h($itemname)." 】 ".h($out)."\n";
		}
	}
	return $resArray;
}
//確認画面の入力内容出力用関数
function confirmOutput($arr){
	global $hankaku,$hankaku_array,$useToken,$confirmDsp,$replaceStr;
	$html = '';
	foreach($arr as $key => $val) {
		$out = '';
		if(is_array($val)){
			foreach($val as $key02 => $item){
				//連結項目の処理
				if(is_array($item)){
					$out .= connect2val($item);
				}else{
					$out .= $item . ', ';
				}
			}
			$out = rtrim($out,', ');

		}else{ $out = $val; }//チェックボックス（配列）追記ここまで

		if (version_compare(PHP_VERSION, '5.1.0', '<=')) {//PHP5.1.0以下の場合のみ実行（7.4でget_magic_quotes_gpcが非推奨になったため）
			if(get_magic_quotes_gpc()) { $out = stripslashes($out); }
		}

		$out = nl2br(h($out));//※追記 改行コードを<br>タグに変換
		$key = h($key);
		$out = str_replace($replaceStr['before'], $replaceStr['after'], $out);//機種依存文字の置換処理

		//全角→半角変換
		if($hankaku == 1){
			$out = zenkaku2hankaku($key,$out,$hankaku_array);
		}

		//名称取得
		global $Namearr;
		$itemname = $key;
		if(array_key_exists($key,$Namearr)) {
			$itemname = $Namearr[$key];
		}

		$html .= "<tr><th>".$itemname."</th><td>".$out;
		$html .= '<input type="hidden" name="'.$key.'" value="'.str_replace(array("<br />","<br>"),"",$out).'" />';
		$html .= "</td></tr>\n";
	}
	//トークンをセット
	if($useToken == 1 && $confirmDsp == 1){
		$token = sha1(uniqid(mt_rand(), true));
		$_SESSION['mailform_token'] = $token;
		$html .= '<input type="hidden" name="mailform_token" value="'.$token.'" />';
	}

	return $html;
}

//全角→半角変換
function zenkaku2hankaku($key,$out,$hankaku_array){
	global $encode;
	if(is_array($hankaku_array) && function_exists('mb_convert_kana')){
		foreach($hankaku_array as $hankaku_array_val){
			if($key == $hankaku_array_val){
				$out = h(mb_convert_kana($out,'a',$encode));
			}
		}
	}
	return $out;
}
//配列連結の処理
function connect2val($arr){
	$out = '';
	foreach($arr as $key => $val){
		if($key === 0 || $val == ''){//配列が未記入（0）、または内容が空のの場合には連結文字を付加しない（型まで調べる必要あり）
			$key = '';
		}elseif(strpos($key,"円") !== false && $val != '' && preg_match("/^[0-9]+$/",$val)){
			$val = number_format($val);//金額の場合には3桁ごとにカンマを追加
		}
		$out .= $val . $key;
	}
	return $out;
}

//管理者宛送信メールヘッダ
function adminHeader($userMail,$post_mail,$BccMail,$to){
	global $from;
	$header = '';
	if($userMail == 1 && !empty($post_mail)) {
		$header="From: $from\n";
		if($BccMail != '') {
		  $header.="Bcc: $BccMail\n";
		}
		$header.="Reply-To: ".$post_mail."\n";
	}else {
		if($BccMail != '') {
		  $header="Bcc: $BccMail\n";
		}
		$header.="Reply-To: ".$to."\n";
	}
		$header.="Content-Type:text/plain;charset=iso-2022-jp\nX-Mailer: PHP/".phpversion();
		return $header;
}
//管理者宛送信メールボディ
function mailToAdmin($arr,$subject,$mailFooterDsp,$mailSignature,$encode,$confirmDsp){
	$adminBody="「".$subject."」からメールが届きました\n\n";
	$adminBody .="＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n\n";
	$adminBody.= postToMail($arr);//POSTデータを関数からセット
	$adminBody.="\n＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n";
	$adminBody.="送信された日時：".date( "Y/m/d (D) H:i:s", time() )."\n";
	$adminBody.="送信者のIPアドレス：".@$_SERVER["REMOTE_ADDR"]."\n";
	$adminBody.="送信者のホスト名：".getHostByAddr(getenv('REMOTE_ADDR'))."\n";
	if($confirmDsp != 1){
		$adminBody.="問い合わせのページURL：".@$_SERVER['HTTP_REFERER']."\n";
	}else{
		$adminBody.="問い合わせのページURL：".@$arr['httpReferer']."\n";
	}
	if($mailFooterDsp == 1) $adminBody.= $mailSignature;
	return mb_convert_encoding($adminBody,"JIS",$encode);
}

//ユーザ宛送信メールヘッダ
function userHeader($refrom_name,$to,$encode){
	$reheader = "From: ";
	if(!empty($refrom_name)){
		$default_internal_encode = mb_internal_encoding();
		if($default_internal_encode != $encode){
			mb_internal_encoding($encode);
		}
		$reheader .= mb_encode_mimeheader($refrom_name)." <".$to.">\nReply-To: ".$to;
	}else{
		$reheader .= "$to\nReply-To: ".$to;
	}
	$reheader .= "\nContent-Type: text/plain;charset=iso-2022-jp\nX-Mailer: PHP/".phpversion();
	return $reheader;
}
//ユーザ宛送信メールボディ
function mailToUser($arr,$dsp_name,$remail_text,$mailFooterDsp,$mailSignature,$encode){
	$userBody = '';
	if(isset($arr[$dsp_name])) $userBody = h($arr[$dsp_name]). " 様\n";
	$userBody.= $remail_text;
	$userBody.="\n＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n\n";
	$userBody.= postToMail($arr);//POSTデータを関数からセット
	$userBody.="\n＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝\n\n";
	$userBody.="送信日時：".date( "Y/m/d (D) H:i:s", time() )."\n";
	if($mailFooterDsp == 1) $userBody.= $mailSignature;
	return mb_convert_encoding($userBody,"JIS",$encode);
}
//必須チェック関数
function requireCheck($require){
	global $Namearr;
	$res['errm'] = '';
	$res['empty_flag'] = 0;
	foreach($require as $requireVal){
		$existsFalg = '';
		foreach($_POST as $key => $val) {
			if($key == $requireVal) {
				//名称取得
				$itemname   = $key;
				if(array_key_exists($key,$Namearr)) {
					$itemname = $Namearr[$key];
				}
				//連結指定の項目（配列）のための必須チェック
				if(is_array($val)){
					$connectEmpty = 0;
					foreach($val as $kk => $vv){
						if(is_array($vv)){
							foreach($vv as $kk02 => $vv02){
								if($vv02 == ''){
									$connectEmpty++;
								}
							}
						}

					}
					if($connectEmpty > 0){
						$res['errm'] .= "<p class=\"error_messe\">【".h($itemname)."】は必須項目です。</p>\n";
						$res['empty_flag'] = 1;
					}
				}
				//デフォルト必須チェック
				elseif($val == ''){
					$res['errm'] .= "<p class=\"error_messe\">【".h($itemname)."】は必須項目です。</p>\n";
					$res['empty_flag'] = 1;
				}

				$existsFalg = 1;
				break;
			}
		}
		// $_POSTに存在しない場合は、checkbox もしくは radio 未選択状態と判定
		if($existsFalg != 1){
			//名称取得
			$itemname   = $requireVal;
			if(array_key_exists($requireVal,$Namearr)) {
				$itemname = $Namearr[$requireVal];
			}
				$res['errm'] .= "<p class=\"error_messe\">【".$itemname."】が未選択です。</p>\n";
				$res['empty_flag'] = 1;
		}
	}

	return $res;
}
//リファラチェック
function refererCheck($Referer_check,$Referer_check_domain){
	if($Referer_check == 1 && !empty($Referer_check_domain)){
		if(strpos($_SERVER['HTTP_REFERER'],$Referer_check_domain) === false){
			return exit('<p align="center">リファラチェックエラー。フォームページのドメインとこのファイルのドメインが一致しません</p>');
		}
	}
}
function copyright(){
	// echo '<a style="display:block;text-align:center;margin:15px 0;font-size:11px;color:#aaa;text-decoration:none" href="http://www.php-factory.net/" target="_blank">- PHP工房 -</a>';
}
//----------------------------------------------------------------------
//  関数定義(END)
//----------------------------------------------------------------------


function csvPool($formdata) {
	global $Namearr;
	global $csvFile;
	global $lockFile;
	$status  = 0;
	$outf    = $csvFile . '/log.csv';
	$lockf   = $csvFile . '/' . $lockFile;
	$lockfp  = fopen($lockf,'w');
	if(flock($lockfp,LOCK_EX)) {
		$fp    = fopen($outf,'a');
		$dataarr = $Namearr;
		foreach($formdata as $key=>$val) {
			if($key == 'mail_set' || $key == 'httpReferer') {
				continue;
			}
			else {
				$dataarr[$key] = $val;
			}
		}
		// 管理情報
		$dataarr["DATE_TIME"]   = date("Y/m/d (D) H:i:s", time());
		$dataarr["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];

		$line    = implode(',',$dataarr);
		// $linecnv = mb_convert_encoding($line,'sjis-win','UTF-8');
		fwrite($fp,$line."\n");
		fclose($fp);
		flock($lockfp,LOCK_UN);
		fclose($lockfp);
	}
	else {
		$status  = 1;
	}
	return $status;
}

function doubleCheck($formdata) {
	global   $Namearr;
	global   $double;
	global   $csvPool;
	global   $csvFile;
	global   $lockFile;
	$lockf   = $csvFile . '/' . $lockFile;
	$lockfp  = fopen($lockf,'w');

	// $Namearrのハッシュを配列化 → 配列番号を値とした$hashを作成
	// $Namearr = 'apple'=>'リンゴ','orange'=>'オレンジ','maron'=>'メロン'・・・
	// ↓↓↓↓↓↓↓↓↓↓↓↓
	// $hash='apple'=>0,'orange'=>1,'meron'=>2・・・・
	$hash  = array_flip(array_keys($Namearr));
	$dhash = array_flip($double);
	// チェック用
	$ps        = [];
	foreach($formdata as $key=>$val) {
		foreach($double as $d) {
			if($key == $d) {
				$ps[]  = $val;
				continue;
			}
		}
	}
	if(array_search('DATE_TIME',$double)) {
		$ps[]    = date("Y/m/d (D) H:i:s", time());
	}
	if(array_search('REMOTE_ADDR',$double)) {
		$ps[]    = $_SERVER["REMOTE_ADDR"];
	}
	$status  = 0;
	if(flock($lockfp,LOCK_EX)) {
		$inf   = $csvFile . '/log.csv';
		$fp    = fopen($inf,'r');
		while($arr = fgetcsv($fp,0,",")) {
			$ck     = [];
			foreach($double as $d) {
				$ck[] = $arr[$hash[$d]];
			}
			if($ps == $ck) {
				$status = 1;
				break;
			}
		}
		fclose($fp);
		flock($lockfp,LOCK_UN);
		fclose($lockfp);
	}
	else {
		$status =     2;
	}
	return $status;
}
?>
