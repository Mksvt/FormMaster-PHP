<?php
include_once 'connecta.php';

$errors = [];
$row = null;

if (isset($_GET['eid'])) {
    $eid = $_GET['eid'];
    if (!ctype_digit($eid)) {
        $errors[] = "Некоректний ідентифікатор.";
    } else {
        $idv = (int)$eid;
        $sql = "SELECT EmployeeID, LastName, FirstName, Position, Phone, Email, PhotoFile
                FROM EMPLOYEES
                WHERE EmployeeID = ?";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $idv);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        if (!$row) {
            $errors[] = "Запис не знайдено.";
        }
    }
} else {
    $errors[] = "Не передано ідентифікатор.";
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Редагування співробітника</title>
</head>
<body>
<?php include 'menu.php'; ?>

<h3>Редагування співробітника</h3>

<?php if ($errors): ?>
    <div style="color:red;">
        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
    </div>
<?php endif; ?>

<?php if ($row): ?>
<form method="post" action="form7.php">
    <b>Прізвище:</b><br>
    <input name="lastname" size="30" required minlength="2"
           value="<?php echo htmlspecialchars($row['LastName']); ?>"><br><br>

    <b>Ім’я:</b><br>
    <input name="firstname" size="30" required minlength="2"
           value="<?php echo htmlspecialchars($row['FirstName']); ?>"><br><br>

    <b>Посада:</b><br>
    <input name="position" size="30" required minlength="2"
           value="<?php echo htmlspecialchars($row['Position']); ?>"><br><br>

    <b>Телефон:</b><br>
    <input name="phone" size="20" required
           pattern="[0-9\+\-\s]{5,20}"
           value="<?php echo htmlspecialchars($row['Phone']); ?>"><br><br>

    <b>Email:</b><br>
    <input name="email" type="email" size="30" required
           value="<?php echo htmlspecialchars($row['Email']); ?>"><br><br>

    <b>Ім’я файлу фото:</b><br>
    <input name="photo" size="30" required
           value="<?php echo htmlspecialchars($row['PhotoFile']); ?>"><br><br>

    <!-- прихований ключ, ID користувачу не показуємо (IDOR) -->
    <input type="hidden" name="eid" value="<?php echo (int)$row['EmployeeID']; ?>">

    <input type="submit" value="Зберегти зміни">
</form>
<?php endif; ?>

</body>
</html>
