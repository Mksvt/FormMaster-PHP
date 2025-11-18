<?php
include_once 'connecta.php';

$errors = [];
$result = null;
$lastnameFilter = '';
$count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastnameFilter = trim($_POST['lastname'] ?? '');

    if ($lastnameFilter === '' || mb_strlen($lastnameFilter) < 2) {
        $errors[] = "Рядок пошуку повинен містити щонайменше 2 символи.";
    } else {
        $like = '%' . $lastnameFilter . '%';
        $sql = "SELECT EmployeeID, LastName, FirstName, Position, Phone, Email, PhotoFile
                FROM EMPLOYEES
                WHERE LastName LIKE ?";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 's', $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_num_rows($result);
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Результат пошуку співробітника</title>
</head>
<body>
<?php include 'menu.php'; ?>

<h3>Результати пошуку</h3>

<?php if ($errors): ?>
    <div style="color:red;">
        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
    </div>
<?php endif; ?>

<?php if ($result): ?>
    <p>Знайдено записів: <?php echo $count; ?></p>
    <?php if ($count > 0): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Прізвище</th>
                <th>Ім’я</th>
                <th>Посада</th>
                <th>Телефон</th>
                <th>Email</th>
                <th>Фото</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <!-- EmployeeID не показуємо користувачу (IDOR) -->
                    <td><?php echo htmlspecialchars($row['LastName']); ?></td>
                    <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                    <td><?php echo htmlspecialchars($row['Position']); ?></td>
                    <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                    <td><?php echo htmlspecialchars($row['PhotoFile']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Співробітників не знайдено.</p>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
