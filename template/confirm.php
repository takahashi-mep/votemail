<!DOCTYPE HTML>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <meta name="format-detection" content="telephone=no">
  <title>確認画面</title>
  <style type="text/css">
  /* 自由に編集下さい */
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

<!-- ▲ Headerやその他コンテンツなど　※自由に編集可 ▲-->

<!-- ▼************ 送信内容表示部　※編集は自己責任で ************ ▼-->
<div id="formWrap">
  <?php if($empty_flag == 1){ ?>
    <div align="center">
      <h4>入力にエラーがあります。下記をご確認の上「戻る」ボタンにて修正をお願い致します。</h4>
      <?php echo $errm; ?><br /><br /><input type="button" value=" 前画面に戻る " onClick="history.back()">
    </div>
  <?php }else{ ?>
    <h3>確認画面</h3>
    <p align="center">以下の内容で間違いがなければ、「送信する」ボタンを押してください。</p>
    <form action="<?php echo h($_SERVER['SCRIPT_NAME']); ?>" method="POST">
      <table class="formTable">
        <?php echo confirmOutput($_POST);//入力内容を表示?>
      </table>
      <p align="center">
        <input type="hidden" name="mail_set" value="confirm_submit">
        <input type="hidden" name="httpReferer" value="<?php echo h($_SERVER['HTTP_REFERER']);?>">
        <input type="button" value="前画面に戻る" onClick="history.back()">
        <input type="submit" value="　応募する　">
      </p>
    </form>
  <?php } ?>
  </div><!-- /formWrap -->
  <!-- ▲ *********** 送信内容確認部　※編集は自己責任で ************ ▲-->

  <!-- ▼ Footerその他コンテンツなど　※編集可 ▼-->
</body>
</html>
