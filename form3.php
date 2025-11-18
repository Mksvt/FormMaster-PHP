<?php
include_once 'connecta.php';

$errors = [];
$success = '';
$result = null;
$lastnameFilter = '';

// Якщо прийшов POST з eid — видаляємо
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eid'])) {
    $eid = $_POST['eid'];
    if (!ctype_digit($eid)) {
        $errors[] = "Некоректний ідентифікатор.";
    } else {
        $idv = (int)$eid;
        $sql = "DELETE FROM EMPLOYEES WHERE EmployeeID = ? LIMIT 1";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $idv);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = "Запис видалено (якщо існував).";
    }
}

// Якщо POST з lastname — шукаємо
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['lastname']) &&
    $_POST['lastname'] !== '' &&
    !isset($_POST['eid'])) {

    $lastnameFilter = trim($_POST['lastname']);
    if ($lastnameFilter === '' || mb_strlen($lastnameFilter) < 2) {
        $errors[] = "Введіть коректне прізвище для пошуку.";
    } else {
        $like = '%' . $lastnameFilter . '%';
        $sql = "SELECT EmployeeID, LastName, FirstName, Position
                FROM EMPLOYEES
                WHERE LastName LIKE ?";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 's', $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Видалити співробітника</title>
</head>
<body>
<?php include 'menu.php'; ?>

<h3>Видалення співробітника</h3>

<form method="post" action="form3.php">
    <b>Введіть прізвище співробітника для пошуку:</b><br>
    <input type="text" name="lastname" size="30" required minlength="2"
           value="<?php echo htmlspecialchars($lastnameFilter); ?>">
    <input type="submit" value="Знайти">
</form>

<?php if ($errors): ?>
    <div style="color:red;">
        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;"><?php echo $success; ?></p>
<?php endif; ?>

<?php if ($result): ?>
    <h4>Результати пошуку:</h4>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr><th>Прізвище</th><th>Ім’я</th><th>Посада</th><th>Дія</th></tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['LastName']); ?></td>
                <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                <td><?php echo htmlspecialchars($row['Position']); ?></td>
                <td>
                    <form method="post" action="form3.php"
                          onsubmit="return confirm('Ви дійсно хочете видалити цього співробітника?');">
                        <input type="hidden" name="eid"
                               value="<?php echo (int)$row['EmployeeID']; ?>">
                        <input type="submit" value="Видалити">
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

</body>
</html>
