<?php
	header('Content-Type: text/html; charset=UTF-8');
	
//MySQLへの接続
	$dsn='データベース名';
	$user='ユーザ名';
	$password='パスワード';
	$pdo=new PDO($dsn,$user,$password);
//テーブル作成
	$make_sql = "CREATE TABLE IF NOT EXISTS bboard "
				."("
				."id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,"
				."name VARCHAR(32),"
				."comment TEXT,"
				."time DATETIME,"
				."isDeleted BOOLEAN NOT NULL DEFAULT 0,"
				."password VARCHAR(255),"
				."salt VARCHAR(255)"
				.");";
	$stmt=$pdo->query($make_sql);

//パスワードの判定
	function judge_password($num,$password,$pdo){
		$input_edit_sql="SELECT * FROM bboard WHERE id=$num";
		$sever_password=$pdo->query($input_edit_sql)->fetchColumn(5);
		$sever_salt=$pdo->query($input_edit_sql)->fetchColumn(6);
	//入力されたパスワードをハッシュ
		$input_password = crypt($password, $sever_salt);
		if($sever_password===$input_password){
			echo "正しいパスワードが入力されました<br>";
			return true;
		}else{
			return false;
		}
	}
	
//送信POSTで受け取る値
	$name=htmlspecialchars($_POST["name"]);
	$comment=str_replace(array("\r\n","\n","\r"),'<br>',$_POST["comment"]);
	$password=htmlspecialchars($_POST["password"]);
	$edit_Num=$_POST["edit_number"];
	$edit=$_POST["mode"];

//送信機能
	if((isset($comment))&&(isset($name))&&(!($comment===''))&&(!($name===''))&&(isset($password))&&(!($password===''))){
			if(preg_match('/^[a-zA-Z0-9]{1,8}+$/',$password)){
				//ハッシュする
				// $strから乱数で文字列を取得して、$saltにcryptのsaltを生成する
				 $str  = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'),array(".","/"));
				  // ランダムな文字列を22文字取得
				 $max  = 22;
				 // Blowfishをハッシュ形式に指定、ストレッチ用のコストパラメータを指定
				 $salt = "$2y$04$";
				 for ($i = 1; $i <= $max; $i++) {
				 	$salt .= $str[rand(0, count($str)-1)];
				 }
				$hashedPassword = crypt($password, $salt);
				//hashのsaltとパスワードを送信する
				$time=date('Y-m-d H:i:s');
				if($edit=="false"){
					$send_sql=$pdo->prepare("INSERT INTO bboard (id,name,comment,time,password,salt) VALUES ('',:name,:comment,:time,:password,:salt)");
					$send_sql->bindValue(':name',$name);
					$send_sql->bindValue(':comment',$comment);
					$send_sql->bindValue(':time',$time);
					$send_sql->bindValue(':password',$hashedPassword);
					$send_sql->bindValue(':salt',$salt);
					$send_sql->execute();
				}else{
					$edit_sql="UPDATE bboard SET name='$name',comment='$comment',time='$time',password='$hashedPassword',salt='$salt' WHERE id=$edit_Num";
					$edit_result=$pdo->query($edit_sql);
				}
		}else{
			echo "<font color='red'>"."パスワードは半角英数字8文字でお願いします"."</font><br>";
		}
	}else{
			echo " 文字を入力して下さい<br>";
	}

//削除機能
	$delete_number=$_POST["delete"];
	$delete_password=htmlspecialchars($_POST["delete_password"]);
	if((isset($delete_number))&&(is_numeric($delete_number))&&(isset($delete_password))&&(!($delete_password===''))){
	//番号のデータが存在するかどうか
		$empty_check_sql="SELECT MAX(id) FROM bboard";
		$max=$pdo->query($empty_check_sql)->fetchColumn();
		if(($delete_number<1)||($max<$delete_number)){
			echo "<font color='red'>".$delete_number."は存在しません</font><br>";
		}else{
		//削除済みかどうかの判定
			$delete_check_sql="SELECT isDeleted FROM bboard WHERE id=$delete_number";
			$delete_check_result=$pdo->query($delete_check_sql);
			$isDeleted = $delete_check_result->fetchAll(PDO::FETCH_ASSOC);
			$check= $isDeleted[0]['isDeleted'];
			
			if($check==1){//削除済みなら
				echo "<font color='red'>".$delete_number."は削除済みです</font><br>";
			}else{
				//パスワードの判定
				$judge_bool=judge_password($delete_number,$delete_password,$pdo);
				if($judge_bool){
					$delete_sql="UPDATE bboard SET isDeleted=1 WHERE id=$delete_number";
					$delete_result=$pdo->query($delete_sql);
					echo "<font color='red'>".$delete_number."を削除しました</font><br>";
				}else{
					echo "<font color='red'>不正なパスワードです</font><br>";
				}
			}
		}
	}

//編集機能
	$edit_name ="";
	$edit_comment="";
	$input_edit_number=$_POST["edit"];
	$edit_mode=false;
	$edit_password=htmlspecialchars($_POST["edit_password"]);
	
	if((isset($input_edit_number))&&(is_numeric($input_edit_number))&&(isset($edit_password))&&(!($edit_password===''))){//番号欄とパスワード欄が空じゃないなら
		$check_sql="SELECT COUNT(*) FROM bboard WHERE id=$input_edit_number";
		$result=$pdo->query($check_sql)->fetchColumn();
		
		if($result==1){//idの番号の投稿があるとき
			$delete_check_sql="SELECT isDeleted FROM bboard WHERE id=$input_edit_number";
			$delete_check_result=$pdo->query($delete_check_sql);
			$isDeleted = $delete_check_result->fetchAll(PDO::FETCH_ASSOC);
			$check= $isDeleted[0]['isDeleted'];
			
			if($check==0){//編集する番号は削除済みかどうか
				
				$judge_bool=judge_password($input_edit_number,$edit_password,$pdo);
				if($judge_bool){
					$input_edit_sql="SELECT * FROM bboard WHERE id=$input_edit_number";
					$edit_name=$pdo->query($input_edit_sql)->fetchColumn(1);
					$edit_comment=$pdo->query($input_edit_sql)->fetchColumn(2);
					
					echo "<font color='blue'>".$input_edit_number."を編集します</font><br>";
					echo "<font color='blue'>"."パスワードは再入力してください</font><br>";
					$edit_mode=true;
				}else{
					echo "<font color='red'>不正なパスワードです</font><br>";
				}
				
			}else{
				echo "<font color='red'>".$input_edit_number."は削除されています</font><br>";
			}
		}else{
			 echo "<font color='red'>".$input_edit_number."は存在しません</font><br>";
		}
	}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta http-equiv="content-type" charset="utf-8">
	<link rel="stylesheet" type="text/css" href="mission_4_css.php">
</head>
<body>
	<form action="mission_4.php" method="POST">
			<div>
				<span>
					名前<br>
				</span>
				<input type="text" name="name" maxlength="16" value="<?php echo $edit_name; ?>" >
			</div>
			<div>
				<span>
					コメント<br>
				</span>
				<textarea name="comment" style="width:200px;height:50px;"  ><?php echo $edit_comment; ?></textarea>
			</div>
			<div>
				<span>
					パスワード<br>
				</span>
				<input type="password" name="password" value="" >
			</div>
			<input type="hidden" name="mode" value="<?php echo var_export($edit_mode); ?>" >
			<input type="hidden" name="edit_number" value="<?php echo $input_edit_number; ?>" >
			<input type="submit" value="送信">
	</form>
	<form action="mission_4.php" method="POST" style="margin:5px;">
				<input type="text" name="delete" placeholder="削除したい番号" value="">
				<input type="password" name="delete_password" placeholder="パスワード" value="">
				<input type="submit" value="削除">
	</form>
	<form action="mission_4.php" method="POST" style="margin:5px;">
				<input type="text" name="edit" placeholder="編集したい番号" value="">
				<input type="password" name="edit_password" placeholder="パスワード" value="">
				<input type="submit" value="編集">
	</form>
<?php
//テーブル一覧を表示
	//順番に並べ替えて$sort_resultに代入
	$sort="SELECT * FROM bboard WHERE isDeleted=0 ORDER by id ASC";
	$sort_results=$pdo->query($sort);
	
	foreach($sort_results as $row){
		echo '<div class="chat_block">';
		echo $row['id'].':';
		echo $row['name'];
		echo '['.$row['time'].']<br>';
		echo str_replace('&lt;br&gt;','<br>',htmlspecialchars($row['comment'])).'<br>';
		echo '</div>';
	}
?>
</body>