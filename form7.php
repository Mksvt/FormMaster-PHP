<?php
include_once 'connecta.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid      = $_POST['eid']      ?? '';
    $lastname = trim($_POST['lastname']  ?? '');
    $firstname= trim($_POST['firstname'] ?? '');
    $position = trim($_POST['position']  ?? '');
    $phone    = trim($_POST['phone']     ?? '');
    $email    = trim($_POST['email']     ?? '');
    $photo    = trim($_POST['photo']     ?? '');

    if (!ctype_digit($eid)) {
        $errors[] = "Некоректний ідентифікатор.";
    }
    if ($lastname === '' || $firstname === '' || $position === '' ||
        $phone === '' || $email === '' || $photo === '') {
        $errors[] = "Усі поля повинні бути заповнені.";
    }
    if (!preg_match('/^[0-9\+\-\s]{5,20}$/', $phone)) {
        $errors[] = "Телефон повинен містити лише цифри, +, -, пробіли (5–20 символів).";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некоректний email.";
    }

    if (!$errors) {
        $idv = (int)$eid;
        $sql = "UPDATE EMPLOYEES
                SET LastName=?, FirstName=?, Position=?, Phone=?, Email=?, PhotoFile=?
                WHERE EmployeeID=?";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssssi',
            $lastname, $firstname, $position, $phone, $email, $photo, $idv
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = "Запис оновлено.";
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Оновлення співробітника</title>
</head>
<body>
<?php include 'menu.php'; ?>

<h3>Оновлення співробітника</h3>

<?php if ($errors): ?>
    <div style="color:red;">
        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;"><?php echo $success; ?></p>
<?php endif; ?>

<p><a href="form4.html">Повернутися до редагування</a></p>

</body>
</html>
