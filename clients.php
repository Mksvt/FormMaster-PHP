<?php
include_once 'connecta.php';

$message = '';
$error = '';

// Обробка додавання/редагування
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = trim($_POST['ClientName'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $taxCode = trim($_POST['TaxCode'] ?? '');
    $clientID = $_POST['ClientID'] ?? null;

    // Валідація
    if (empty($clientName) || strlen($clientName) < 2) {
        $error = "Назва клієнта обов'язкова (мінімум 2 символи)!";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Невірний формат email!";
    } elseif (!empty($taxCode) && !preg_match('/^[0-9]{8,12}$/', $taxCode)) {
        $error = "Податковий код має містити 8-12 цифр!";
    } else {
        if ($clientID && is_numeric($clientID)) {
            // Редагування з прихованим полем
            $clientID = (int)$clientID;
            $sql = "UPDATE clients SET ClientName=?, Email=?, TaxCode=? WHERE ClientID=? LIMIT 1";
            $stmt = mysqli_prepare($dbc, $sql);
            mysqli_stmt_bind_param($stmt, 'sssi', $clientName, $email, $taxCode, $clientID);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Клієнта успішно оновлено!";
            } else {
                $error = "Помилка оновлення: " . mysqli_error($dbc);
            }
            mysqli_stmt_close($stmt);
        } else {
            // Додавання
            $sql = "INSERT INTO clients (ClientName, Email, TaxCode) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($dbc, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $clientName, $email, $taxCode);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Клієнта успішно додано!";
            } else {
                $error = "Помилка додавання: " . mysqli_error($dbc);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Отримання даних для редагування
$editClient = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $clientID = (int)$_GET['edit'];
    $sql = "SELECT * FROM clients WHERE ClientID = ?";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $clientID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editClient = mysqli_fetch_assoc($result);
}

// Пошук
$searchTerm = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM clients";
if ($searchTerm !== '') {
    $like = '%' . $searchTerm . '%';
    $sql .= " WHERE ClientName LIKE ? OR Email LIKE ? OR TaxCode LIKE ?";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($dbc, $sql);
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Управління клієнтами</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        h2 { border-bottom: 1px solid #000; padding-bottom: 10px; margin-top: 20px; }
        .message { padding: 10px; margin: 10px 0; border: 1px solid #000; }
        .success { background: #fff; }
        .error { background: #fff; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="email"] { 
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
    </style>
</head>
<body>

<?php include 'menu.php'; ?>

<div class="container">
    <h2><?php echo $editClient ? 'Редагування клієнта' : 'Додати нового клієнта'; ?></h2>

    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="clients.php">
        <?php if ($editClient): ?>
            <input type="hidden" name="ClientID" value="<?php echo $editClient['ClientID']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Назва клієнта *</label>
            <input type="text" name="ClientName" required minlength="2" maxlength="255"
                   pattern="[A-Za-zА-Яа-яІіЇїЄєҐґ0-9\s\.,'\-]+"
                   title="Мінімум 2 символи, тільки літери, цифри та базові символи"
                   value="<?php echo htmlspecialchars($editClient['ClientName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="Email" maxlength="255"
                   title="Введіть коректний email"
                   value="<?php echo htmlspecialchars($editClient['Email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
            <label>Податковий код (ІПН/ЄДРПОУ)</label>
            <input type="text" name="TaxCode" pattern="[0-9]{8,12}" maxlength="12"
                   title="Тільки цифри, 8-12 символів"
                   value="<?php echo htmlspecialchars($editClient['TaxCode'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <button type="submit"><?php echo $editClient ? 'Зберегти зміни' : 'Додати клієнта'; ?></button>
        <?php if ($editClient): ?>
            <a href="clients.php"><button type="button" class="secondary">Скасувати</button></a>
        <?php endif; ?>
    </form>

    <h2>Список клієнтів</h2>

    <div class="search-box">
        <form method="get" action="clients.php">
            <input type="text" name="search" placeholder="Пошук по назві, email або коду..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Шукати</button>
            <?php if ($searchTerm): ?>
                <a href="clients.php"><button type="button" class="secondary">Скинути</button></a>
            <?php endif; ?>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Назва клієнта</th>
                <th>Email</th>
                <th>Податковий код</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['ClientID']); ?></td>
                        <td><?php echo htmlspecialchars($row['ClientName']); ?></td>
                        <td><?php echo htmlspecialchars($row['Email'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['TaxCode'] ?? '—'); ?></td>
                        <td class="actions">
                            <a href="clients.php?edit=<?php echo $row['ClientID']; ?>" class="edit">Редагувати</a>
                            <a href="client_confirm_delete.php?id=<?php echo $row['ClientID']; ?>" class="delete">Видалити</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;">Клієнтів не знайдено</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
