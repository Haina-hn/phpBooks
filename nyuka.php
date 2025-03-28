<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login']) || $_SESSION['login'] == false) {
    $_SESSION['error2'] = 'ログインしてください'; // 修正: == を = に変更
    header("Location: login.php");
    exit();
}

$dsn = 'mysql:host=localhost;dbname=phpbooks;charset=utf8'; // 修正: charset=utf8 を追加
$user = 'root';
$password = '';
try {
    $dbh = new PDO($dsn, $user, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // 修正: エラーモードを設定
} catch (PDOException $e) {
    die('データベースに接続できません！' . htmlspecialchars($e->getMessage()));
}

if (empty($_POST['books'])) {
    $_SESSION['success'] = '入荷する商品が選択されていません';
    header('Location: zaiko_ichiran.php');
    exit();
}

function getId($id, $dbh) {
    $sql = "SELECT * FROM books WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>入荷</title>
    <link rel="stylesheet" href="css/ichiran.css" type="text/css" />
</head>
<body>
    <div id="header">
        <h1>入荷</h1>
    </div>
    <div id="menu">
        <nav>
            <ul>
                <li><a href="zaiko_ichiran.php?page=1">書籍一覧</a></li>
            </ul>
        </nav>
    </div>
    <form action="nyuka_kakunin.php" method="post">
        <div id="pagebody">
            <div id="error">
                <?php
                if (isset($_SESSION['error']) && !empty($_SESSION['error'])) {
                    echo htmlspecialchars($_SESSION['error']); // 修正: htmlspecialchars を追加
                    unset($_SESSION['error']);
                }
                ?>
            </div>
            <div id="center">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>書籍名</th>
                            <th>著者名</th>
                            <th>発売日</th>
                            <th>金額</th>
                            <th>在庫数</th>
                            <th>入荷数</th>
                        </tr>
                    </thead>
                    <?php 
                    foreach ($_POST['books'] as $bookId) {
                        $book = getId($bookId, $dbh);
                        if (!$book) {
                            echo '<tr><td colspan="7">書籍情報が見つかりません ' . htmlspecialchars($bookId) . ')</td></tr>';
                            continue;
                        }
                    ?>
                    <input type="hidden" value="<?= htmlspecialchars($bookId) ?>" name="books[]">
                    <tr>
                        <td><?= htmlspecialchars($book['id']) ?></td>
                        <td><?= htmlspecialchars($book['title']) ?></td>
                        <td><?= htmlspecialchars($book['author']) ?></td>
                        <td><?= htmlspecialchars($book['salesDate']) ?></td>
                        <td><?= htmlspecialchars($book['price']) ?></td>
                        <td><?= htmlspecialchars($book['stock']) ?></td>
                        <td><input type="text" name="stock[]" size="5" maxlength="11" required></td>
                    </tr>
                    <?php 
                    }
                    ?>
                </table>
                <button type="submit" id="kakutei" formmethod="POST" name="decision" value="1">確定</button>
            </div>
        </div>
    </form>
    <div id="footer">
        <footer>株式会社アクロイト</footer>
    </div>
</body>
</html>
