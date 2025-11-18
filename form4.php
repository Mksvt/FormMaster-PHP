<?php
include_once 'connecta.php';

$errors = [];
$res = null;
$lastnameFilter = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastnameFilter = trim($_POST['lastname'] ?? '');
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
        $res = mysqli_stmt_get_result($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Вибір співробітника для редагування</title>
</head>
<body>
<?php include 'menu.php'; ?>

<h3>Результати пошуку для редагування</h3>

<?php if ($errors): ?>
    <div style="color:red;">
        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
    </div>
<?php endif; ?>

<?php if ($res): ?>
    <?php if (mysqli_num_rows($res) > 0): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr><th>Прізвище</th><th>Ім’я</th><th>Посада</th><th>Редагувати</th></tr>
            <?php while ($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['LastName']); ?></td>
                    <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                    <td><?php echo htmlspecialchars($row['Position']); ?></td>
                    <td>
                        <a href="form4_valid.php?eid=<?php echo (int)$row['EmployeeID']; ?>">Edit</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Співробітників не знайдено.</p>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
