<?php
session_start();

function fetchBookById($id, $dbConnection)
{
    $query = "SELECT * FROM books WHERE id = :id";
    $stmt = $dbConnection->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateBookStock($id, $dbConnection, $newStock)
{
    $query = "UPDATE books SET stock = :stock WHERE id = :id";
    $stmt = $dbConnection->prepare($query);
    $stmt->bindParam(':stock', $newStock, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
}

if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
    $_SESSION['error2'] = "ログインしてください";
    header("Location: login.php");
    exit();
}

try {
    $dbConnection = new PDO('mysql:host=localhost;dbname=phpbooks;charset=utf8', 'root', '');
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('データベース接続失敗: ' . htmlspecialchars($e->getMessage()));
}

if (!isset($_POST['books'], $_POST['stock']) || !is_array($_POST['books']) || !is_array($_POST['stock'])) {
    $_SESSION['error'] = "不正なデータが送信されました";
    include "nyuka.php";
    exit();
}

foreach ($_POST['books'] as $index => $bookId) {
    if (!is_numeric($bookId) || !isset($_POST['stock'][$index]) || !is_numeric($_POST['stock'][$index])) {
        $_SESSION['error'] = "不正なデータが含まれています";
        include "nyuka.php";
        exit();
    }

    $book = fetchBookById($bookId, $dbConnection);
    if (!$book) {
        $_SESSION['error'] = "書籍情報が見つかりません (ID: $bookId)";
        include "nyuka.php";
        exit();
    }

    $newStock = $book['stock'] + $_POST['stock'][$index];
    if ($newStock > 100) {
        $_SESSION['error'] = "最大在庫数を超える数は入力できません (ID: $bookId)";
        include "nyuka.php";
        exit();
    }
}

if (isset($_POST['add']) && $_POST['add'] === 'ok') {
    foreach ($_POST['books'] as $index => $bookId) {
        $book = fetchBookById($bookId, $dbConnection);
        $newStock = $book['stock'] + $_POST['stock'][$index];
        updateBookStock($bookId, $dbConnection, $newStock);
    }

    $_SESSION['success'] = "入荷が完了しました";
    header("Location: zaiko_ichiran.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>入荷確認</title>
    <link rel="stylesheet" href="css/ichiran.css" type="text/css" />
</head>
<body>
	<div id="header">
		<h1>入荷確認</h1>
	</div>
	<form action="nyuka_kakunin.php" method="post" id="test">
		<div id="pagebody">
			<div id="center">
				<table>
					<thead>
						<tr>
							<th id="book_name">書籍名</th>
							<th id="stock">在庫数</th>
							<th id="stock">入荷数</th>
						</tr>
					</thead>
					<tbody>
						<?php
						//㉜書籍数をカウントするための変数を宣言し、値を0で初期化する。
						$book_count = 1;
						//㉝POSTの「books」から値を取得し、変数に設定する。
						foreach ($_POST['books'] as $booksId/* ㉝の処理を書く */) {
							//㉞「getByid」関数を呼び出し、変数に戻り値を入れる。その際引数に㉜の処理で取得した値と⑧のDBの接続情報を渡す。
							$book = getByid($book_count, $dbh);
						?>
							<tr>
								<td><?php echo htmlspecialchars($book['title'])/* ㉟ ㉞で取得した書籍情報からtitleを表示する。 */; ?></td>
								<td><?php echo isset($book['stock'])? htmlspecialchars($book['stock']) : '0'/* ㊱ ㉞で取得した書籍情報からstockを表示する。 */; ?></td>
								<td><?php echo htmlspecialchars($_POST['stock'][$book_count])/* ㊱ POSTの「stock」に設定されている値を㉜の変数を使用して呼び出す。 */; ?></td>
							</tr>
							<input type="hidden" name="books[]" value="<?php echo htmlspecialchars($booksId/* ㊲ ㉝で取得した値を設定する */); ?>">
							<input type="hidden" name="stock[]" value='<?php echo htmlspecialchars($_POST['stock'][$book_count]/* ㊳POSTの「stock」に設定されている値を㉜の変数を使用して設定する。 */); ?>'>
						<?php
							//㊴ ㉜で宣言した変数をインクリメントで値を1増やす。
							$book_count++;
						}
						?>
					</tbody>
				</table>
				<div id="kakunin">
					<p>
						上記の書籍を入荷します。<br>
						よろしいですか？
					</p>
					<button type="submit" id="message" formmethod="POST" name="add" value="ok">はい</button>
					<button type="submit" id="message" formaction="nyuka.php">いいえ</button>
				</div>
			</div>
		</div>
	</form>
	<div id="footer">
		<footer>株式会社アクロイト</footer>
	</div>
</body>
</html>