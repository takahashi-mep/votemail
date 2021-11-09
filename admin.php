<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php error_reporting(E_ALL | E_STRICT);

if (version_compare(PHP_VERSION, '5.1.0', '>=')) {//PHP5.1.0以上の場合のみタイムゾーンを定義
	date_default_timezone_set('Asia/Tokyo');//タイムゾーンの設定（日本以外の場合には適宜設定ください）
}

require 'init.php';

$session   =  'off';
$login     =  'off';
$download  =  'off';
if(!empty($_POST)) {
	// psw チェック
	if(!empty($_POST['psw'])) {
		$psw = htmlspecialchars($_POST['psw'], ENT_QUOTES,'utf-8');
		if($adminPsw == $psw) {
			$session   =  'on';
			$login     =  'on';
		}
		else {
			$session   =  'on';
			$login     =  'off';
		}
	}
	else {
		// logout時はpsw入力画面を表示
		if(!empty($_POST['logout'])) {
			unset($_POST['psw']);
			unset($_POST['utf-8']);
			unset($_POST['utf-8']);
			$session     =  'off';
			$login       =  'off';
			$download    =  'off';
		}
		// 続けてダウンロード時
		else if(!empty($_POST['next'])) {
			unset($_POST['sjis']);
			unset($_POST['utf-8']);
			$session     =  'on';
			$login       =  'on';
		}
		// SJISでダウンロード
		else if(!empty($_POST['sjis'])) {
			$session     =  'on';
			$login       =  'on';
			$download    =  'sjis';
		}
		// UTF-8でダウンロード
		else if(!empty($_POST['utf-8'])) {
			$session     =  'on';
			$login       =  'on';
			$download    =  'utf-8';
		}
	}
}
// ダウンロード処理
if($download == 'sjis') { download('sjis-win'); }
if($download == 'utf-8') { download('utf-8'); }
function download($ccode) {
	global $csvFile;
	$lf       = $csvFile . '/log.csv';
	$lp       = fopen($lf,'r');
	// 一時ファイル準備
	$temppath = $csvFile . '/' . date("YmdHis", time()) . '-' . $ccode . 'temp.csv';
	$tp       = fopen($temppath,'w');
	// 一時ファイルに出力
	if($lp !== false) {
		while($logfl = fgets($lp)) {
			if($ccode !== 'utf-8') {
				$line    = mb_convert_encoding($logfl, $ccode , "utf-8");
			}
			else {
				$line    = $logfl;
			}
			fwrite($tp,$line);
		}
	}
	fclose($lp);
	fclose($tp);

	// HTTPヘッダを設定
	header('Content-Type: application/octet-stream');
	header('Content-Length: '.filesize($temppath));
	header('Content-Disposition: attachment; filename=download.csv');

	// ファイル出力
	readfile($temppath);
	exit;
}
?>


<!DOCTYPE HTML>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <meta name="format-detection" content="telephone=no">
	<!-- インデックス・キャッシュ回避 -->
	<meta name="robots" content="noindex">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Cache-Control" content="no-cache">
	<!-- インデックス・キャッシュ回避 -->
  <title>ダウンロード</title>
  <style type="text/css">
  /* 自由に編集下さい */
	.tit {
		background-color: #ffd4f6;
		display: block;
		padding: 1rem;
		text-align: center;
		font-weight: bold;
		color: #717171;
	}
  #formWrap {
    width:700px;
    margin:0 auto;
    color:#555;
    line-height:120%;
    font-size:90%;
  }
  table.formTable{
    width:100%;
    margin:0 auto;
    border-collapse:collapse;
  }
  table.formTable td,table.formTable th{
    border:1px solid #ccc;
    padding:10px;
  }
  table.formTable th{
    width:30%;
    font-weight:normal;
    background:#efefef;
    text-align:left;
  }
  p.error_messe{
    margin:5px 0;
    color:red;
  }
  /*　簡易版レスポンシブ用CSS（必要最低限のみとしています。ブレークポイントも含め自由に設定下さい）　*/
  @media screen and (max-width:572px) {
    #formWrap {
      width:95%;
      margin:0 auto;
    }
    table.formTable th, table.formTable td {
      width:auto;
      display:block;
    }
    table.formTable th {
      margin-top:5px;
      border-bottom:0;
    }
    input[type="submit"], input[type="reset"], input[type="button"] {
      display:block;
      width:100%;
      height:40px;
    }
  }
</style>
</head>
<body>
	<h1 class="tit">ダウンロード</h1>
	<div id="formWrap">
	  <?php
		if($session == 'off') { ?>
			<div align="center">
				パスワードをご入力ください。
				<br />
				<br />
				<form class="" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post">
					<input type="text" name="psw" value="" placeholder="パスワード">
					<br />
					<br />
					<input type="reset" name="reset" value="RESET">
					<input type="submit" name="login" value="LOGIN">
				</form>
			</div>
		<?php
		}
		else if($login == 'off') { ?>
	    <div align="center">
	      パスワードをご確認ください。
				<br />
				<br />
				<form class="" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="post">
					<input type="text" name="psw" value="" placeholder="パスワード">
					<br />
					<br />
					<input type="reset" name="reset" value="RESET">
					<input type="submit" name="login" value="LOGIN">
				</form>
	    </div>
	  <?php
		}
		else {
			if($download == 'off') { ?>
				<p align="center">該当する「文字コード」ボタンを押してください。</p>
				<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="POST">
					<p align="center">CSV文字コードを選択</p>
					<p align="center">
						<input type="submit" name="sjis" value="SJIS">
						<input type="submit" name="utf-8" value="UTF-8">
					</p>
					<br />
					<p align="center">
						<input type="submit" name="logout" value="LOGOUT">
					</p>
				</form>
			<?php
			}
			else { ?>
				<p align="center">ご指定の文字コードでのダウンロードが行われました。</p>
				<p><?php echo $download; ?></p>
				<br />
				<br />
				<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" method="POST">
					<input type="submit" name="next" value="続けてダウンロード">
					<input type="submit" name="logout" value="LOGOUT">
				</form>
			<?php
			}
		} ?>
  </div><!-- /formWrap -->
</body>
</html>
