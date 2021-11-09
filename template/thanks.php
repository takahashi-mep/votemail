<!DOCTYPE HTML>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <meta name="format-detection" content="telephone=no">
  <title>完了画面</title>
</head>
<body>
  <div align="center">
    <?php
    if($empty_flag == 1){ ?>
      <h4>送信が込み合っています。「戻る」ボタンより前画面に戻り再度送信をお試し下さい。</h4>
      <div style="color:red"><?php echo $errm; ?></div>
      <br /><br /><input type="button" value=" 前画面に戻る " onClick="history.back()">
  <?php
  }
  else { ?>
    送信ありがとうございました。<br />
    送信は正常に完了しました。<br /><br />
    <a href="<?php echo $site_top ;?>">トップページへ戻る&raquo;</a>
    <?php copyright(); ?>
    <?php
  } ?>
</div>
<?php
if($empty_flag != 1 && !empty($conversion)) {
  echo $conversion; // ヒアドキュメントで定義
} ?>
</body>
</html>
