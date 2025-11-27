<?php
include_once 'connecta.php';

$message = '';
$error = '';

// Обробка додавання/редагування
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $objectName = trim($_POST['ObjectName'] ?? '');
    $address = trim($_POST['Address'] ?? '');
    $startDate = $_POST['StartDate'] ?? null;
    $endDate = $_POST['EndDate'] ?? null;
    $status = $_POST['Status'] ?? '';
    $clientID = $_POST['ClientID'] ?? null;
    $objID = $_POST['ObjID'] ?? null;

    // Валідація дат
    $dateError = false;
    if (!empty($startDate) && !empty($endDate)) {
        if (strtotime($endDate) < strtotime($startDate)) {
            $error = "Дата завершення не може бути раніше дати початку!";
            $dateError = true;
        }
    }
    
    // Обробка фото з валідацією
    $photoPath = null;
    if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        $fileType = mime_content_type($_FILES['Photo']['tmp_name']);
        $extension = strtolower(pathinfo($_FILES['Photo']['name'], PATHINFO_EXTENSION));
        $fileSize = $_FILES['Photo']['size'];
        
        if (!in_array($fileType, $allowedTypes) || !in_array($extension, $allowedExtensions)) {
            $error = "Дозволені тільки формати зображень: JPG, PNG, GIF, WEBP";
        } elseif ($fileSize > $maxFileSize) {
            $error = "Розмір файлу не повинен перевищувати 5MB";
        } else {
            $uploadDir = 'uploads/objects/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = 'obj_' . time() . '_' . uniqid() . '.' . $extension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['Photo']['tmp_name'], $targetPath)) {
                $photoPath = $targetPath;
            } else {
                $error = "Помилка завантаження фото";
            }
        }
    }

    if (empty($objectName) || strlen($objectName) < 3) {
        $error = "Назва об'єкта обов'язкова (мінімум 3 символи)!";
    } elseif ($dateError) {
        // Помилка вже встановлена
    } else {
        if ($objID && is_numeric($objID)) {
            // Редагування з прихованим полем
            $objID = (int)$objID;
            
            // Перевірка ClientID
            if (!empty($clientID) && !is_numeric($clientID)) {
                $error = "Невірний ідентифікатор клієнта";
            } else {
                $clientID = $clientID ? (int)$clientID : null;
                
                if ($photoPath) {
                    // Видалити стару фотку
                    $sql = "SELECT Photo FROM objects WHERE ObjID = ? LIMIT 1";
                    $stmt = mysqli_prepare($dbc, $sql);
                    mysqli_stmt_bind_param($stmt, 'i', $objID);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $oldData = mysqli_fetch_assoc($result);
                    if ($oldData && $oldData['Photo'] && file_exists($oldData['Photo'])) {
                        unlink($oldData['Photo']);
                    }
                    mysqli_stmt_close($stmt);
                    
                    $sql = "UPDATE objects SET ObjectName=?, Address=?, StartDate=?, EndDate=?, Status=?, ClientID=?, Photo=? WHERE ObjID=? LIMIT 1";
                    $stmt = mysqli_prepare($dbc, $sql);
                    mysqli_stmt_bind_param($stmt, 'sssssiis', $objectName, $address, $startDate, $endDate, $status, $clientID, $photoPath, $objID);
                } else {
                    $sql = "UPDATE objects SET ObjectName=?, Address=?, StartDate=?, EndDate=?, Status=?, ClientID=? WHERE ObjID=? LIMIT 1";
                    $stmt = mysqli_prepare($dbc, $sql);
                    mysqli_stmt_bind_param($stmt, 'sssssii', $objectName, $address, $startDate, $endDate, $status, $clientID, $objID);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Об'єкт успішно оновлено!";
                } else {
                    $error = "Помилка оновлення: " . mysqli_error($dbc);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            // Додавання
            $clientID = (!empty($clientID) && is_numeric($clientID)) ? (int)$clientID : null;
            
            $sql = "INSERT INTO objects (ObjectName, Address, StartDate, EndDate, Status, ClientID, Photo) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($dbc, $sql);
            mysqli_stmt_bind_param($stmt, 'ssssssi', $objectName, $address, $startDate, $endDate, $status, $clientID, $photoPath);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Об'єкт успішно додано!";
            } else {
                $error = "Помилка додавання: " . mysqli_error($dbc);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Отримання даних для редагування
$editObject = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $objID = (int)$_GET['edit'];
    $sql = "SELECT * FROM objects WHERE ObjID = ?";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $objID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editObject = mysqli_fetch_assoc($result);
}

// Пошук
$searchTerm = trim($_GET['search'] ?? '');
$sql = "SELECT o.*, c.ClientName FROM objects o LEFT JOIN clients c ON o.ClientID = c.ClientID";
if ($searchTerm !== '') {
    $like = '%' . $searchTerm . '%';
    $sql .= " WHERE o.ObjectName LIKE ? OR o.Address LIKE ? OR o.Status LIKE ? OR c.ClientName LIKE ?";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($dbc, $sql);
}

// Отримати список клієнтів для dropdown
$clientsResult = mysqli_query($dbc, "SELECT ClientID, ClientName FROM clients ORDER BY ClientName");
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Управління об'єктами</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        h2 { border-bottom: 1px solid #000; padding-bottom: 10px; margin-top: 20px; }
        .message { padding: 10px; margin: 10px 0; border: 1px solid #000; }
        .success { background: #fff; }
        .error { background: #fff; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="date"], select { 
            width: 100%; padding: 8px; border: 1px solid #000; box-sizing: border-box;
        }
        input[type="file"] { padding: 5px; border: 1px solid #000; }
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
        .photo-preview { max-width: 100px; max-height: 100px; border: 1px solid #000; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    </style>
</head>
<body>

<?php include 'menu.php'; ?>

<div class="container">
    <h2><?php echo $editObject ? 'Редагування об\'єкта' : 'Додати новий об\'єкт'; ?></h2>

    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="objects.php" enctype="multipart/form-data">
        <?php if ($editObject): ?>
            <input type="hidden" name="ObjID" value="<?php echo $editObject['ObjID']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>Назва об'єкта *</label>
                <input type="text" name="ObjectName" required minlength="3" maxlength="255"
                       pattern="[A-Za-zА-Яа-яІіЇїЄєҐґ0-9\s\.\-,'\"()]+"
                       title="Мінімум 3 символи, літери, цифри та базові символи"
                       value="<?php echo htmlspecialchars($editObject['ObjectName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label>Адреса</label>
                <input type="text" name="Address" maxlength="255"
                       title="Максимум 255 символів"
                       value="<?php echo htmlspecialchars($editObject['Address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Дата початку</label>
                <input type="date" name="StartDate" id="startDate"
                       value="<?php echo htmlspecialchars($editObject['StartDate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label>Дата завершення</label>
                <input type="date" name="EndDate" id="endDate"
                       value="<?php echo htmlspecialchars($editObject['EndDate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Статус</label>
                <select name="Status">
                    <option value="">-- Оберіть статус --</option>
                    <option value="planned" <?php echo ($editObject['Status'] ?? '') === 'planned' ? 'selected' : ''; ?>>Запланований</option>
                    <option value="in progress" <?php echo ($editObject['Status'] ?? '') === 'in progress' ? 'selected' : ''; ?>>У процесі</option>
                    <option value="completed" <?php echo ($editObject['Status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Завершений</option>
                </select>
            </div>

            <div class="form-group">
                <label>Клієнт</label>
                <select name="ClientID">
                    <option value="">-- Оберіть клієнта --</option>
                    <?php mysqli_data_seek($clientsResult, 0); ?>
                    <?php while ($client = mysqli_fetch_assoc($clientsResult)): ?>
                        <option value="<?php echo $client['ClientID']; ?>"
                                <?php echo ($editObject['ClientID'] ?? '') == $client['ClientID'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['ClientName']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Фото об'єкта (JPG, PNG, GIF, WEBP, макс. 5MB)</label>
            <?php if ($editObject && !empty($editObject['Photo']) && file_exists($editObject['Photo'])): ?>
                <div style="margin-bottom: 10px;">
                    <img src="<?php echo htmlspecialchars($editObject['Photo'], ENT_QUOTES, 'UTF-8'); ?>" class="photo-preview" alt="Поточне фото">
                    <p style="font-size: 12px; color: #666;">Поточне фото (завантажте нове, щоб замінити)</p>
                </div>
            <?php endif; ?>
            <input type="file" name="Photo" id="photoFile" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        </div>

        <button type="submit"><?php echo $editObject ? 'Зберегти зміни' : 'Додати об\'єкт'; ?></button>
        <?php if ($editObject): ?>
            <a href="objects.php"><button type="button" class="secondary">Скасувати</button></a>
        <?php endif; ?>
    </form>

    <h2>Список об'єктів</h2>

    <div class="search-box">
        <form method="get" action="objects.php">
            <input type="text" name="search" placeholder="Пошук по назві, адресі, статусу або клієнту..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Шукати</button>
            <?php if ($searchTerm): ?>
                <a href="objects.php"><button type="button" class="secondary">Скинути</button></a>
            <?php endif; ?>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Фото</th>
                <th>Назва об'єкта</th>
                <th>Адреса</th>
                <th>Дата початку</th>
                <th>Дата завершення</th>
                <th>Статус</th>
                <th>Клієнт</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['ObjID']); ?></td>
                        <td>
                            <?php if (!empty($row['Photo']) && file_exists($row['Photo'])): ?>
                                <img src="<?php echo htmlspecialchars($row['Photo']); ?>" class="photo-preview" alt="Фото">
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['ObjectName']); ?></td>
                        <td><?php echo htmlspecialchars($row['Address'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['StartDate'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['EndDate'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['Status'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['ClientName'] ?? '—'); ?></td>
                        <td class="actions">
                            <a href="objects.php?edit=<?php echo $row['ObjID']; ?>" class="edit">Редагувати</a>
                            <a href="object_confirm_delete.php?id=<?php echo $row['ObjID']; ?>" class="delete">Видалити</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align:center;">Об'єктів не знайдено</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
