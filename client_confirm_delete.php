<?php
include_once 'connecta.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: clients.php');
    exit;
}

$clientID = (int)$_GET['id'];

// Отримання даних клієнта
$sql = "SELECT * FROM clients WHERE ClientID = ?";
$stmt = mysqli_prepare($dbc, $sql);
mysqli_stmt_bind_param($stmt, 'i', $clientID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($result);

if (!$client) {
    header('Location: clients.php');
    exit;
}

// Підрахунок пов'язаних даних
$sql = "SELECT COUNT(*) as count FROM objects WHERE ClientID = ?";
$stmt = mysqli_prepare($dbc, $sql);
mysqli_stmt_bind_param($stmt, 'i', $clientID);
mysqli_stmt_execute($stmt);
$objectsCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

$sql = "SELECT COUNT(*) as count FROM orders WHERE ClientID = ?";
$stmt = mysqli_prepare($dbc, $sql);
mysqli_stmt_bind_param($stmt, 'i', $clientID);
mysqli_stmt_execute($stmt);
$ordersCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

// Обробка підтвердження видалення
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $sql = "DELETE FROM clients WHERE ClientID = ? LIMIT 1";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $clientID);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header('Location: clients.php?deleted=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Підтвердження видалення клієнта</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 50px auto; padding: 30px; border: 2px solid #000; }
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
        <strong>Увага!</strong> Ви дійсно хочете видалити цього клієнта?
    </div>

    <div class="info-block">
        <h3>Інформація про клієнта:</h3>
        <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value"><?php echo htmlspecialchars($client['ClientID']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Назва:</span>
            <span class="info-value"><?php echo htmlspecialchars($client['ClientName']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($client['Email'] ?? '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Податковий код:</span>
            <span class="info-value"><?php echo htmlspecialchars($client['TaxCode'] ?? '—'); ?></span>
        </div>
    </div>

    <div class="info-block">
        <h3>Пов'язані дані:</h3>
        <div class="info-row">
            <span class="info-label">Об'єктів:</span>
            <span class="info-value <?php echo $objectsCount > 0 ? 'danger-text' : ''; ?>">
                <?php echo $objectsCount; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Замовлень:</span>
            <span class="info-value <?php echo $ordersCount > 0 ? 'danger-text' : ''; ?>">
                <?php echo $ordersCount; ?>
            </span>
        </div>
    </div>

    <?php if ($objectsCount > 0 || $ordersCount > 0): ?>
        <div class="warning">
            <p class="danger-text">Увага! Видалення цього клієнта вплине на пов'язані записи в інших таблицях (встановиться NULL).</p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <button type="submit" name="confirm_delete" class="btn-danger">
            Так, видалити клієнта
        </button>
        <a href="clients.php"><button type="button" class="btn-secondary">Скасувати</button></a>
    </form>
</div>

</body>
</html>
