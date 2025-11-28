<?php
include_once 'connecta.php';

$searchTerm = trim($_GET['q'] ?? '');
$searchIn = $_GET['in'] ?? 'all'; // all, clients, objects, orders

$results = [
    'clients' => [],
    'objects' => [],
    'orders' => []
];

if ($searchTerm !== '') {
    $like = '%' . $searchTerm . '%';
    
    // Пошук в клієнтах
    if ($searchIn === 'all' || $searchIn === 'clients') {
        $sql = "SELECT ClientID, ClientName, Email, TaxCode FROM clients 
                WHERE ClientName LIKE ? OR Email LIKE ? OR TaxCode LIKE ? LIMIT 20";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $results['clients'][] = $row;
        }
    }
    
    // Пошук в об'єктах
    if ($searchIn === 'all' || $searchIn === 'objects') {
        $sql = "SELECT o.ObjID, o.ObjectName, o.Address, o.Status, o.Photo, c.ClientName 
                FROM objects o 
                LEFT JOIN clients c ON o.ClientID = c.ClientID
                WHERE o.ObjectName LIKE ? OR o.Address LIKE ? OR o.Status LIKE ? LIMIT 20";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $results['objects'][] = $row;
        }
    }
    
    // Пошук в замовленнях
    if ($searchIn === 'all' || $searchIn === 'orders') {
        $sql = "SELECT o.OrderID, o.OrderDate, o.OrderAmount, o.OrderStatus, 
                       c.ClientName, obj.ObjectName
                FROM orders o 
                LEFT JOIN clients c ON o.ClientID = c.ClientID 
                LEFT JOIN objects obj ON o.ObjID = obj.ObjID
                WHERE o.OrderStatus LIKE ? OR c.ClientName LIKE ? OR obj.ObjectName LIKE ? 
                      OR CAST(o.OrderAmount AS CHAR) LIKE ? LIMIT 20";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $results['orders'][] = $row;
        }
    }
}

$totalResults = count($results['clients']) + count($results['objects']) + count($results['orders']);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Універсальний пошук</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        h2 { border-bottom: 1px solid #000; padding-bottom: 10px; margin-top: 20px; }
        h3 { margin-top: 30px; border-left: 2px solid #000; padding-left: 10px; }
        .search-box { 
            border: 1px solid #000; padding: 20px; margin-bottom: 30px; 
        }
        .search-box input[type="text"] { 
            width: 60%; padding: 10px; border: 1px solid #000; font-size: 16px;
        }
        .search-box button { 
            padding: 10px 20px; background: #fff; color: #000; border: 1px solid #000; 
            cursor: pointer; font-size: 16px;
        }
        .search-box button:hover { background: #eee; }
        .search-box select {
            padding: 10px; border: 1px solid #000; margin-left: 10px;
            background: white; font-size: 16px;
        }
        .result-item { 
            border: 1px solid #000; padding: 15px; margin: 10px 0;
        }
        .result-title { font-weight: bold; font-size: 16px; margin-bottom: 8px; }
        .result-meta { font-size: 14px; margin: 5px 0; }
        .result-actions { margin-top: 10px; }
        .result-actions a { 
            padding: 6px 12px; margin-right: 8px; text-decoration: underline; 
            display: inline-block; font-size: 14px; color: #000;
        }
        .no-results { 
            text-align: center; padding: 40px; border: 1px solid #000; margin: 20px 0;
        }
        .photo-thumb { 
            max-width: 60px; max-height: 60px; border: 1px solid #000; 
            vertical-align: middle; margin-right: 10px;
        }
        .search-hint { margin-top: 10px; font-size: 14px; }
        .result-count { 
            border: 1px solid #000; padding: 10px 15px; 
            display: inline-block; margin-bottom: 20px;
        }
    </style>
</head>
<body>

<?php include 'menu.php'; ?>

<div class="container">
    <h2>Універсальний пошук</h2>

    <div class="search-box">
        <form method="get" action="search.php">
            <input type="text" name="q" placeholder="Введіть текст для пошуку..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>" required>
            <button type="submit">Шукати</button>
            <select name="in">
                <option value="all" <?php echo $searchIn === 'all' ? 'selected' : ''; ?>>Усі таблиці</option>
                <option value="clients" <?php echo $searchIn === 'clients' ? 'selected' : ''; ?>>Тільки клієнти</option>
                <option value="objects" <?php echo $searchIn === 'objects' ? 'selected' : ''; ?>>Тільки об'єкти</option>
                <option value="orders" <?php echo $searchIn === 'orders' ? 'selected' : ''; ?>>Тільки замовлення</option>
            </select>
        </form>
        <?php if (!$searchTerm): ?>
            <p class="search-hint">Введіть назву, адресу, email, статус або будь-який інший текст для пошуку в базі даних</p>
        <?php endif; ?>
    </div>

    <?php if ($searchTerm): ?>
        <div class="result-count">
            Знайдено результатів: <strong><?php echo $totalResults; ?></strong>
        </div>

        <?php if ($totalResults === 0): ?>
            <div class="no-results">
                <h3>Нічого не знайдено</h3>
                <p>Спробуйте змінити пошуковий запит або оберіть іншу категорію</p>
            </div>
        <?php endif; ?>

        <!-- Результати: Клієнти -->
        <?php if (count($results['clients']) > 0): ?>
            <h3>Клієнти (<?php echo count($results['clients']); ?>)</h3>
            <?php foreach ($results['clients'] as $client): ?>
                <div class="result-item">
                    <div class="result-title">
                        <?php echo htmlspecialchars($client['ClientName']); ?>
                    </div>
                    <div class="result-meta">
                        Email: <?php echo htmlspecialchars($client['Email'] ?: '—'); ?> | 
                        Код: <?php echo htmlspecialchars($client['TaxCode'] ?: '—'); ?>
                    </div>
                    <div class="result-actions">
                        <a href="clients.php?edit=<?php echo $client['ClientID']; ?>" class="btn-edit">Редагувати</a>
                        <a href="client_confirm_delete.php?id=<?php echo $client['ClientID']; ?>" class="btn-view">Детальніше</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Результати: Об'єкти -->
        <?php if (count($results['objects']) > 0): ?>
            <h3>Об'єкти (<?php echo count($results['objects']); ?>)</h3>
            <?php foreach ($results['objects'] as $object): ?>
                <div class="result-item">
                    <?php if (!empty($object['Photo']) && file_exists($object['Photo'])): ?>
                        <img src="<?php echo htmlspecialchars($object['Photo']); ?>" class="photo-thumb" alt="Фото">
                    <?php endif; ?>
                    <div class="result-title">
                        <?php echo htmlspecialchars($object['ObjectName']); ?>
                    </div>
                    <div class="result-meta">
                        Адреса: <?php echo htmlspecialchars($object['Address'] ?: '—'); ?> | 
                        Статус: <?php echo htmlspecialchars($object['Status'] ?: '—'); ?>
                        <?php if ($object['ClientName']): ?>
                            | Клієнт: <?php echo htmlspecialchars($object['ClientName']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="result-actions">
                        <a href="objects.php?edit=<?php echo $object['ObjID']; ?>" class="btn-edit">Редагувати</a>
                        <a href="object_confirm_delete.php?id=<?php echo $object['ObjID']; ?>" class="btn-view">Детальніше</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Результати: Замовлення -->
        <?php if (count($results['orders']) > 0): ?>
            <h3>Замовлення (<?php echo count($results['orders']); ?>)</h3>
            <?php foreach ($results['orders'] as $order): ?>
                <div class="result-item">
                    <div class="result-title">
                        Замовлення #<?php echo htmlspecialchars($order['OrderID']); ?> 
                        — <?php echo number_format($order['OrderAmount'], 2, '.', ' '); ?> грн
                    </div>
                    <div class="result-meta">
                        Дата: <?php echo htmlspecialchars($order['OrderDate']); ?> | 
                        Статус: <?php echo htmlspecialchars($order['OrderStatus'] ?: '—'); ?>
                        <?php if ($order['ClientName']): ?>
                            | Клієнт: <?php echo htmlspecialchars($order['ClientName']); ?>
                        <?php endif; ?>
                        <?php if ($order['ObjectName']): ?>
                            | Об'єкт: <?php echo htmlspecialchars($order['ObjectName']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="result-actions">
                        <a href="orders.php?edit=<?php echo $order['OrderID']; ?>" class="btn-edit">Редагувати</a>
                        <a href="order_confirm_delete.php?id=<?php echo $order['OrderID']; ?>" class="btn-view">Детальніше</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>
</div>

</body>
</html>
