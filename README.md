# TECH-BASE_internship
## 概要
TECH-BASEのインターンでの制作物です。
PHPとMySQLを使った掲示板です。
## 機能
1.送信機能
2.削除機能
3.編集機能

編集、削除はどちらも送信時に設定したパスワードを入力することで実行できます。

##　その他
途中ハッシュについて<https://qiita.com/h1y0r1n/items/a719d308503c28712287>から
```php:sample
   // $strから乱数で文字列を取得して、$saltにcryptのsaltを生成する
    $str  = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'),array(".","/"));
    // ランダムな文字列を22文字取得
    $max  = 22;

    // Blowfishをハッシュ形式に指定、ストレッチ用のコストパラメータを指定
    $salt = "$2y$04$";

    for ($i = 1; $i <= $max; $i++) {
        $salt .= $str[rand(0, count($str)-1)];
    }
   // ハッシュ化
    $hashedPassword = crypt($password, $salt);
```
引用させてもらいました。

