<?php
include_once 'connecta.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: objects.php');
    exit;
}

$objID = (int)$_GET['id'];

// Отримання даних об'єкта з клієнтом
$sql = "SELECT o.*, c.ClientName FROM objects o LEFT JOIN clients c ON o.ClientID = c.ClientID WHERE o.ObjID = ?";
$stmt = mysqli_prepare($dbc, $sql);
mysqli_stmt_bind_param($stmt, 'i', $objID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$object = mysqli_fetch_assoc($result);

if (!$object) {
    header('Location: objects.php');
    exit;
}

// Підрахунок пов'язаних даних
$sql = "SELECT COUNT(*) as count FROM employees WHERE ObjID = ?";
$stmt = mysqli_prepare($dbc, $sql);
mysqli_stmt_bind_param($stmt, 'i', $objID);
mysqli_stmt_execute($stmt);
$employeesCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

$sql = "SELECT COUNT(*) as count FROM orders WHERE ObjID = ?";
$stmt = mysqli_prepare($dbc, $sql);
mysqli_stmt_bind_param($stmt, 'i', $objID);
mysqli_stmt_execute($stmt);
$ordersCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

$sql = "SELECT COUNT(*) as count FROM object_materials WHERE ObjID = ?";
$stmt = mysqli_prepare($dbc, $sql);
mysqli_stmt_bind_param($stmt, 'i', $objID);
mysqli_stmt_execute($stmt);
$materialsCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];

// Обробка підтвердження видалення
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Видалити фото, якщо є
    if (!empty($object['Photo']) && file_exists($object['Photo'])) {
        unlink($object['Photo']);
    }
    
    $sql = "DELETE FROM objects WHERE ObjID = ? LIMIT 1";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $objID);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header('Location: objects.php?deleted=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Підтвердження видалення об'єкта</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 700px; margin: 50px auto; padding: 30px; border: 2px solid #000; }
        h2 { border-bottom: 1px solid #000; padding-bottom: 10px; }
        .warning { border: 1px solid #000; padding: 15px; margin: 20px 0; font-weight: bold; }
        .info-block { border: 1px solid #000; padding: 15px; margin: 15px 0; }
        .info-row { margin: 8px 0; }
        .info-label { font-weight: bold; display: inline-block; width: 180px; }
        .info-value { }
        .photo-preview { max-width: 200px; max-height: 200px; margin: 10px 0; border: 1px solid #000; }
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
        <strong>Увага!</strong> Ви дійсно хочете видалити цей об'єкт?
    </div>

    <div class="info-block">
        <h3>Інформація про об'єкт:</h3>
        <div class="info-row">
            <span class="info-label">ID:</span>
            <span class="info-value"><?php echo htmlspecialchars($object['ObjID']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Назва:</span>
            <span class="info-value"><?php echo htmlspecialchars($object['ObjectName']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Адреса:</span>
            <span class="info-value"><?php echo htmlspecialchars($object['Address'] ?? '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Дата початку:</span>
            <span class="info-value"><?php echo htmlspecialchars($object['StartDate'] ?? '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Дата завершення:</span>
            <span class="info-value"><?php echo htmlspecialchars($object['EndDate'] ?? '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Статус:</span>
            <span class="info-value"><?php echo htmlspecialchars($object['Status'] ?? '—'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Клієнт:</span>
            <span class="info-value"><?php echo htmlspecialchars($object['ClientName'] ?? '—'); ?></span>
        </div>
        <?php if (!empty($object['Photo']) && file_exists($object['Photo'])): ?>
            <div class="info-row">
                <span class="info-label">Фото:</span><br>
                <img src="<?php echo htmlspecialchars($object['Photo']); ?>" class="photo-preview" alt="Фото об'єкта">
            </div>
        <?php endif; ?>
    </div>

    <div class="info-block">
        <h3>Пов'язані дані:</h3>
        <div class="info-row">
            <span class="info-label">Співробітників:</span>
            <span class="info-value <?php echo $employeesCount > 0 ? 'danger-text' : ''; ?>">
                <?php echo $employeesCount; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Замовлень:</span>
            <span class="info-value <?php echo $ordersCount > 0 ? 'danger-text' : ''; ?>">
                <?php echo $ordersCount; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Матеріалів:</span>
            <span class="info-value <?php echo $materialsCount > 0 ? 'danger-text' : ''; ?>">
                <?php echo $materialsCount; ?>
            </span>
        </div>
    </div>

    <?php if ($employeesCount > 0 || $ordersCount > 0): ?>
        <div class="warning">
            <p class="danger-text">Увага! Видалення цього об'єкта вплине на пов'язані записи в інших таблицях.</p>
            <p>Записи матеріалів (<?php echo $materialsCount; ?>) будуть видалені CASCADE.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <button type="submit" name="confirm_delete" class="btn-danger">
            Так, видалити об'єкт
        </button>
        <a href="objects.php"><button type="button" class="btn-secondary">Скасувати</button></a>
    </form>
</div>

</body>
</html>
