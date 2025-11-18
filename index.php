<?php
include_once 'connecta.php';

// ----- ЛІЧИЛЬНИК -----
$filename = "counter.txt";
if (!file_exists($filename)) {
    file_put_contents($filename, "0");
}
$counter = 0;
$fp = fopen($filename, "r");
if ($fp) {
    $counter = (int)fgets($fp, 10);
    fclose($fp);
}
$counter++;
$fp = fopen($filename, "w");
if ($fp) {
    fputs($fp, (string)$counter);
    fclose($fp);
}

// ----- ПОШУК ПО V_OrdersDashboard -----
$clientFilter  = '';
$searchError   = '';
$result        = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['client']) &&
    trim($_GET['client']) !== '') {

    $clientFilter = trim($_GET['client']);

    // Валідація на сервері
    if (mb_strlen($clientFilter) < 2) {
        $searchError = "Рядок пошуку має бути довший за 1 символ.";
    } else {
        $like = '%' . $clientFilter . '%';
        $sql = "SELECT OrderID, OrderDate, OrderAmount, OrderStatus, Client, ObjectName
                FROM V_OrdersDashboard
                WHERE Client LIKE ?";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 's', $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
} else {
    // просто перші 20 записів з view
    $sql = "SELECT OrderID, OrderDate, OrderAmount, OrderStatus, Client, ObjectName
            FROM V_OrdersDashboard
            ORDER BY OrderDate DESC
            LIMIT 20";
    $result = mysqli_query($dbc, $sql);
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>ЛР4 – Головна</title>
    <style>
        body { font-family: Arial, sans-serif; margin:20px; }
        .nav { list-style:none; margin:0; padding:0; overflow:hidden; background:#333; }
        .nav li { float:left; }
        .nav li a { display:block; padding:10px 16px; color:#fff; text-decoration:none; }
        .nav li a:hover { background:#555; }
        table { border-collapse:collapse; margin-top:15px; }
        th, td { border:1px solid #ccc; padding:6px 10px; }
        th { background:#eee; }
        .error { color:red; }
    </style>
</head>
<body>

<?php include 'menu.php'; ?>

<h3>Лабораторна робота №4 – головна сторінка</h3>
<p>Число відвідувань: <strong><?php echo $counter; ?></strong></p>

<h4>Дані з подання V_OrdersDashboard (ЛР3)</h4>

<form method="get" action="index.php">
    <label>Пошук замовлень по клієнту (частина назви):</label><br>
    <input type="text" name="client"
           required minlength="2" maxlength="50"
           pattern="[A-Za-zА-Яа-яІіЇїЄє0-9\s\.]+"
           value="<?php echo htmlspecialchars($clientFilter, ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit">Шукати</button>
</form>

<?php if ($searchError): ?>
    <p class="error"><?php echo $searchError; ?></p>
<?php endif; ?>

<table>
    <tr>
        <th>Дата</th>
        <th>Сума</th>
        <th>Статус</th>
        <th>Клієнт</th>
        <th>Об’єкт</th>
    </tr>
    <?php if ($result): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['OrderDate'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['OrderAmount'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['OrderStatus'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['Client'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['ObjectName'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>

</body>
</html>
