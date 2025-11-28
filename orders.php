<?php
include_once 'connecta.php';

$message = '';
$error = '';

// Обробка додавання/редагування
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderDate = trim($_POST['OrderDate'] ?? '');
    $orderAmount = trim($_POST['OrderAmount'] ?? '');
    $orderStatus = trim($_POST['OrderStatus'] ?? '');
    $clientID = $_POST['ClientID'] ?? null;
    $objID = $_POST['ObjID'] ?? null;
    $orderID = $_POST['OrderID'] ?? null;

    // Конвертуємо порожні рядки в NULL
    $orderStatus = ($orderStatus === '') ? null : $orderStatus;
    $clientID = (empty($clientID) || $clientID === '') ? null : $clientID;
    $objID = (empty($objID) || $objID === '') ? null : $objID;

    // Валідація
    if (empty($orderDate) || empty($orderAmount)) {
        $error = "Дата та сума замовлення обов'язкові!";
    } elseif (!is_numeric($orderAmount) || $orderAmount <= 0) {
        $error = "Сума замовлення має бути додатним числом!";
    } elseif (!empty($clientID) && !is_numeric($clientID)) {
        $error = "Невірний ідентифікатор клієнта";
    } elseif (!empty($objID) && !is_numeric($objID)) {
        $error = "Невірний ідентифікатор об'єкта";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $orderDate)) {
        $error = "Невірний формат дати!";
    } else {
        if ($orderID && is_numeric($orderID)) {
            // Редагування з прихованим полем
            $orderID = (int)$orderID;
            $clientID = $clientID ? (int)$clientID : null;
            $objID = $objID ? (int)$objID : null;
            
            $sql = "UPDATE orders SET OrderDate=?, OrderAmount=?, OrderStatus=?, ClientID=?, ObjID=? WHERE OrderID=? LIMIT 1";
            $stmt = mysqli_prepare($dbc, $sql);
            mysqli_stmt_bind_param($stmt, 'sdsiii', $orderDate, $orderAmount, $orderStatus, $clientID, $objID, $orderID);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Замовлення успішно оновлено!";
            } else {
                $error = "Помилка оновлення: " . mysqli_error($dbc);
            }
            mysqli_stmt_close($stmt);
        } else {
            // Додавання
            $clientID = $clientID ? (int)$clientID : null;
            $objID = $objID ? (int)$objID : null;
            
            $sql = "INSERT INTO orders (OrderDate, OrderAmount, OrderStatus, ClientID, ObjID) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($dbc, $sql);
            mysqli_stmt_bind_param($stmt, 'sdsii', $orderDate, $orderAmount, $orderStatus, $clientID, $objID);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Замовлення успішно додано!";
            } else {
                $error = "Помилка додавання: " . mysqli_error($dbc);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Отримання даних для редагування
$editOrder = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $orderID = (int)$_GET['edit'];
    $sql = "SELECT * FROM orders WHERE OrderID = ?";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $orderID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editOrder = mysqli_fetch_assoc($result);
}

// Пошук
$searchTerm = trim($_GET['search'] ?? '');
$sql = "SELECT o.*, c.ClientName, obj.ObjectName 
        FROM orders o 
        LEFT JOIN clients c ON o.ClientID = c.ClientID 
        LEFT JOIN objects obj ON o.ObjID = obj.ObjID";

if ($searchTerm !== '') {
    $like = '%' . $searchTerm . '%';
    $sql .= " WHERE o.OrderStatus LIKE ? OR c.ClientName LIKE ? OR obj.ObjectName LIKE ? OR CAST(o.OrderAmount AS CHAR) LIKE ?";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql .= " ORDER BY o.OrderDate DESC";
    $result = mysqli_query($dbc, $sql);
}

// Отримати списки для dropdown
$clientsResult = mysqli_query($dbc, "SELECT ClientID, ClientName FROM clients ORDER BY ClientName");
$objectsResult = mysqli_query($dbc, "SELECT ObjID, ObjectName FROM objects ORDER BY ObjectName");
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Управління замовленнями</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        h2 { border-bottom: 1px solid #000; padding-bottom: 10px; margin-top: 20px; }
        .message { padding: 10px; margin: 10px 0; border: 1px solid #000; }
        .success { background: #fff; }
        .error { background: #fff; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="date"], input[type="number"], select { 
            width: 100%; padding: 8px; border: 1px solid #000; box-sizing: border-box;
        }
        button { 
            padding: 10px 20px; background: #fff; color: #000; border: 1px solid #000; 
            cursor: pointer; margin-right: 10px;
        }
        button:hover { background: #eee; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #000; }
        th, td { padding: 12px; text-align: left; border: 1px solid #000; }
        th { background: #fff; font-weight: bold; }
        .actions a { 
            padding: 6px 12px; margin-right: 5px; text-decoration: underline; 
            display: inline-block; color: #000;
        }
        .search-box { margin: 20px 0; }
        .search-box input { width: 300px; display: inline-block; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    </style>
</head>
<body>

<?php include 'menu.php'; ?>

<div class="container">
    <h2><?php echo $editOrder ? 'Редагування замовлення' : 'Додати нове замовлення'; ?></h2>

    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="orders.php">
        <?php if ($editOrder): ?>
            <input type="hidden" name="OrderID" value="<?php echo $editOrder['OrderID']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>Дата замовлення *</label>
                <input type="date" name="OrderDate" required 
                       max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>"
                       title="Оберіть дату замовлення"
                       value="<?php echo htmlspecialchars($editOrder['OrderDate'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label>Сума замовлення (грн) *</label>
                <input type="number" step="0.01" min="0.01" max="999999999.99" name="OrderAmount" required 
                       title="Введіть додатну суму"
                       value="<?php echo htmlspecialchars($editOrder['OrderAmount'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Статус замовлення</label>
                <select name="OrderStatus">
                    <option value="">-- Оберіть статус --</option>
                    <option value="new" <?php echo ($editOrder['OrderStatus'] ?? '') === 'new' ? 'selected' : ''; ?>>Новий</option>
                    <option value="in progress" <?php echo ($editOrder['OrderStatus'] ?? '') === 'in progress' ? 'selected' : ''; ?>>У процесі</option>
                    <option value="completed" <?php echo ($editOrder['OrderStatus'] ?? '') === 'completed' ? 'selected' : ''; ?>>Завершений</option>
                    <option value="cancelled" <?php echo ($editOrder['OrderStatus'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Скасований</option>
                </select>
            </div>

            <div class="form-group">
                <label>Клієнт</label>
                <select name="ClientID">
                    <option value="">-- Оберіть клієнта --</option>
                    <?php mysqli_data_seek($clientsResult, 0); ?>
                    <?php while ($client = mysqli_fetch_assoc($clientsResult)): ?>
                        <option value="<?php echo $client['ClientID']; ?>"
                                <?php echo ($editOrder['ClientID'] ?? '') == $client['ClientID'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['ClientName']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Об'єкт</label>
            <select name="ObjID">
                <option value="">-- Оберіть об'єкт --</option>
                <?php mysqli_data_seek($objectsResult, 0); ?>
                <?php while ($object = mysqli_fetch_assoc($objectsResult)): ?>
                    <option value="<?php echo $object['ObjID']; ?>"
                            <?php echo ($editOrder['ObjID'] ?? '') == $object['ObjID'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($object['ObjectName']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit"><?php echo $editOrder ? 'Зберегти зміни' : 'Додати замовлення'; ?></button>
        <?php if ($editOrder): ?>
            <a href="orders.php"><button type="button" class="secondary">Скасувати</button></a>
        <?php endif; ?>
    </form>

    <h2>Список замовлень</h2>

    <div class="search-box">
        <form method="get" action="orders.php">
            <input type="text" name="search" placeholder="Пошук по статусу, клієнту, об'єкту або сумі..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Шукати</button>
            <?php if ($searchTerm): ?>
                <a href="orders.php"><button type="button" class="secondary">Скинути</button></a>
            <?php endif; ?>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Дата</th>
                <th>Сума (грн)</th>
                <th>Статус</th>
                <th>Клієнт</th>
                <th>Об'єкт</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['OrderID']); ?></td>
                        <td><?php echo htmlspecialchars($row['OrderDate']); ?></td>
                        <td><?php echo number_format($row['OrderAmount'], 2, '.', ' '); ?></td>
                        <td>
                            <?php 
                            $status = $row['OrderStatus'] ?? '';
                            $statusClass = '';
                            if ($status === 'new') $statusClass = 'status-new';
                            elseif ($status === 'in progress') $statusClass = 'status-progress';
                            elseif ($status === 'completed') $statusClass = 'status-completed';
                            ?>
                            <span class="<?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($status ?: '—'); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['ClientName'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['ObjectName'] ?? '—'); ?></td>
                        <td class="actions">
                            <a href="orders.php?edit=<?php echo $row['OrderID']; ?>" class="edit">Редагувати</a>
                            <a href="order_confirm_delete.php?id=<?php echo $row['OrderID']; ?>" class="delete">Видалити</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;">Замовлень не знайдено</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
