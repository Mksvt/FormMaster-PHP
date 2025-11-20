<?php
// form2prepare.php – пошук співробітника з використанням збереженого (параметризованого) запиту
include_once 'connecta.php';

$usertable = "employees";
$error = '';
$result = null;
$lastname = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastname = trim($_POST['lastname'] ?? '');

    // проста валідація
    if ($lastname === '' || mb_strlen($lastname) < 2) {
        $error = "Введіть не менше 2 символів для пошуку.";
    } else {
        // pattern для LIKE
        $pattern = '%' . $lastname . '%';

        $sql = "SELECT EmployeeID, LastName, FirstName, Position, Phone, Email, PhotoFile
                FROM $usertable
                WHERE LastName LIKE ?";

        // створюємо збережений (параметризований) запит
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, "s", $pattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Пошук (параметризований запит)</title>
</head>
<body>
<?php include 'menu.php'; ?>

<h3>Пошук співробітника (збережений / параметризований запит)</h3>

<form method="post" action="form2prepare.php">
    <b>Введіть прізвище або його частину:</b><br>
    <input type="text" name="lastname" size="30"
           required minlength="2"
           value="<?php echo htmlspecialchars($lastname, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="submit" value="Шукати">
</form>

<?php if ($error): ?>
    <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($result): ?>
    <?php if (mysqli_num_rows($result) > 0): ?>
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
                    <td><?php echo htmlspecialchars($row['LastName']); ?></td>
                    <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                    <td><?php echo htmlspecialchars($row['Position']); ?></td>
                    <td><?php echo htmlspecialchars($row['Phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                    <td>
                        <?php if (!empty($row['PhotoFile'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($row['PhotoFile']); ?>" height="60">
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Записів не знайдено.</p>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
