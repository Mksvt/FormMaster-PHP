<?php
include_once 'connecta.php';

$sql = "SELECT EmployeeID, LastName, FirstName, Position, Phone, Email, PhotoFile
        FROM EMPLOYEES
        ORDER BY EmployeeID DESC";
$result = mysqli_query($dbc, $sql);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Перегляд співробітників</title>
</head>
<body>
<?php include 'menu.php'; ?>

<h3>Перегляд усіх співробітників</h3>

<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Прізвище</th>
        <th>Ім’я</th>
        <th>Посада</th>
        <th>Телефон</th>
        <th>Email</th>
        <th>Фото</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo (int)$row['EmployeeID']; ?></td>
            <td><?php echo htmlspecialchars($row['LastName']); ?></td>
            <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
            <td><?php echo htmlspecialchars($row['Position']); ?></td>
            <td><?php echo htmlspecialchars($row['Phone']); ?></td>
            <td><?php echo htmlspecialchars($row['Email']); ?></td>
            <td><?php echo htmlspecialchars($row['PhotoFile']); ?></td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
