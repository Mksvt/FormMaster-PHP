<?php
include_once 'connecta.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderID = (int)$_GET['id'];

// Отримання даних замовлення з клієнтом та об'єктом
$sql = "SELECT o.*, c.ClientName, obj.ObjectName 
        FROM orders o 
        LEFT JOIN clients c ON o.ClientID = c.ClientID 
        LEFT JOIN objects obj ON o.ObjID = obj.ObjID 
        WHERE o.OrderID = ?";
$stmt = mysqli_prepare($dbc, $sql);
mysqli_stmt_bind_param($stmt, 'i', $orderID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Обробка підтвердження видалення
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $sql = "DELETE FROM orders WHERE OrderID = ? LIMIT 1";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $orderID);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header('Location: orders.php?deleted=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Підтвердження видалення замовлення</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 650px; margin: 50px auto; padding: 30px; border: 2px solid #000; }
        h2 { border-bottom: 1px solid #000; padding-bottom: 10px; }
        .warning { border: 1px solid #000; padding: 15px; margin: 20px 0; font-weight: bold; }
        .info-block { border: 1px solid #000; padding: 15px; margin: 15px 0; }
        .info-row { margin: 8px 0; }
        .info-label { font-weight: bold; display: inline-block; width: 150px; }
        .info-value { }
        button { 
            padding: 12px 24px; margin: 10px 10px 10px 0; border: 1px solid #000; 
            cursor: pointer; font-size: 16px; background: #fff;
        }
        button:hover { background: #eee; }
    </style>
</head>
<body>

<div class="container">
    <h2>Підтвердження видалення</h2>

    <div class="warning">
        <strong>Увага!</strong> Ви дійсно хочете видалити це замовлення?
    </div>

    <div class="info-block">
        <h3>Інформація про замовлення:</h3>
        <div class="info-row">
            <span class="info-label">ID замовлення:</span>
            <span class="info-value"><?php echo htmlspecialchars($order['OrderID']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Дата:</span>
            <span class="info-value"><?php echo htmlspecialchars($order['OrderDate']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Сума:</span>
            <span class="info-value amount-highlight"><?php echo number_format($order['OrderAmount'], 2, '.', ' '); ?> грн</span>
        </div>
        <div class="info-row">
            <span class="info-label">Статус:</span>
            <span class="info-value"><?php echo htmlspecialchars($order['OrderStatus'] ?? '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Клієнт:</span>
            <span class="info-value"><?php echo htmlspecialchars($order['ClientName'] ?? '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Об'єкт:</span>
            <span class="info-value"><?php echo htmlspecialchars($order['ObjectName'] ?? '—'); ?></span>
        </div>
    </div>

    <div class="warning">
        <p><strong>Це незворотня дія!</strong> Після видалення відновити замовлення буде неможливо.</p>
    </div>

    <form method="post" action="">
        <button type="submit" name="confirm_delete" class="btn-danger">
            Так, видалити замовлення
        </button>
        <a href="orders.php"><button type="button" class="btn-secondary">Скасувати</button></a>
    </form>
</div>

</body>
</html>
