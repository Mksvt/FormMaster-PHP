<?php
// form3.php – пошук + підтвердження видалення співробітника
include_once 'connecta.php';

$usertable = "employees";
$errors = [];
$mode   = '';   // search | confirm | done
$row    = null;

// 1) Якщо прийшов GET pid → показуємо форму підтвердження
if (isset($_GET['pid']) && trim($_GET['pid']) !== '' && ctype_digit($_GET['pid'])) {
    $idv = (int)$_GET['pid'];

    $sql = "SELECT EmployeeID, LastName, FirstName, Phone, PhotoFile
            FROM $usertable
            WHERE EmployeeID = ?";
    $stmt = mysqli_prepare($dbc, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idv);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($row) {
        $mode = 'confirm';
    } else {
        $errors[] = "Запис не знайдено.";
    }
}
// 2) Якщо прийшов POST із eid → реально видаляємо
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eid'])) {
    if (!ctype_digit($_POST['eid'])) {
        $errors[] = "Некоректний ідентифікатор.";
    } else {
        $idv = (int)$_POST['eid'];
        $sql = "DELETE FROM $usertable WHERE EmployeeID = ? LIMIT 1";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, "i", $idv);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $mode = 'done';
    }
}
// 3) Якщо прийшов POST з прізвищем → шукаємо
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lastname'])) {
    $lastname = trim($_POST['lastname']);
    if ($lastname === '' || mb_strlen($lastname) < 2) {
        $errors[] = "Введіть прізвище (мінімум 2 символи).";
    } else {
        $like = '%' . $lastname . '%';
        $sql = "SELECT EmployeeID, LastName, FirstName, Phone
                FROM $usertable
                WHERE LastName LIKE ?";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, "s", $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
        mysqli_stmt_close($stmt);
        $mode = 'search';
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Вилучення даних</title>
</head>
<body>
<?php include 'menu.php'; ?>

<h3>Вилучення співробітника</h3>

<?php if ($errors): ?>
    <div style="color:red;">
        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
    </div>
<?php endif; ?>

<!-- форма пошуку за прізвищем -->
<form action="form3.php" method="post">
    <b>Введіть прізвище співробітника для вилучення:</b><br>
    <input type="text" name="lastname" size="30" required minlength="2">
    <input type="submit" value="Шукати">
</form>

<hr>

<?php if ($mode === 'search'): ?>
    <?php if (empty($rows)): ?>
        <p>Немає такого запису.</p>
    <?php else: ?>
        <p>Знайдено записів: <?php echo count($rows); ?></p>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr><th>Прізвище</th><th>Ім’я</th><th>Телефон</th><th>Дія</th></tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['LastName']); ?></td>
                    <td><?php echo htmlspecialchars($r['FirstName']); ?></td>
                    <td><?php echo htmlspecialchars($r['Phone']); ?></td>
                    <td>
                        <a href="form3.php?pid=<?php echo (int)$r['EmployeeID']; ?>">Вилучити</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
<?php endif; ?>

<?php if ($mode === 'confirm' && $row): ?>
    <h4>Підтвердження вилучення</h4>
    <p>Ви дійсно хочете вилучити співробітника:
        <strong><?php echo htmlspecialchars($row['LastName'] . ' ' . $row['FirstName']); ?></strong>?</p>

    <form method="post" action="form3.php"
          onsubmit="return confirm('Підтвердити вилучення запису?');">
        <input type="hidden" name="eid" value="<?php echo (int)$row['EmployeeID']; ?>">
        <input type="submit" value="Вилучити">
    </form>
<?php endif; ?>

<?php if ($mode === 'done'): ?>
    <p style="color:green;">Запис вилучено (якщо він існував).</p>
<?php endif; ?>

</body>
</html>
