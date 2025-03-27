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
    <form action="nyuka_kakunin.php" method="post">
        <div id="pagebody">
            <div id="center">
                <table>
                    <thead>
                        <tr>
                            <th>書籍名</th>
                            <th>在庫数</th>
                            <th>入荷数</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_POST['books'] as $index => $bookId): ?>
                            <?php 
                                $book = fetchBookById($bookId, $dbConnection);
                                if (!$book) {
                                    echo '<tr><td colspan="3">データが見つかりません (ID: ' . htmlspecialchars($bookId) . ')</td></tr>';
                                    continue;
                                }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['stock']) ?></td>
                                <td><?= htmlspecialchars($_POST['stock'][$index]) ?></td>
                            </tr>
                            <input type="hidden" name="books[]" value="<?= htmlspecialchars($bookId) ?>">
                            <input type="hidden" name="stock[]" value="<?= htmlspecialchars($_POST['stock'][$index]) ?>">
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="kakunin">
                    <p>上記の書籍を入荷します。<br>よろしいですか？</p>
                    <button type="submit" name="add" value="ok">はい</button>
                    <button type="submit" formaction="nyuka.php">いいえ</button>
                </div>
            </div>
        </div>
    </form>
    <div id="footer">
        <footer>株式会社アクロイト</footer>
    </div>
</body>
</html>