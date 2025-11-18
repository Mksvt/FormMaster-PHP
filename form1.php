<?php
include_once 'connecta.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastname  = trim($_POST['lastname']  ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $position  = trim($_POST['position']  ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $email     = trim($_POST['email']     ?? '');
    $photo     = trim($_POST['photo']     ?? '');

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
        $sql = "INSERT INTO EMPLOYEES
                (LastName, FirstName, Position, Phone, Email, is_active, PhotoFile)
                VALUES (?,?,?,?,?,1,?)";
        $stmt = mysqli_prepare($dbc, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssss',
            $lastname, $firstname, $position, $phone, $email, $photo
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = "Співробітника додано.";
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Результат додавання</title>
</head>
<body>
<?php include 'menu.php'; ?>

<h3>Результат додавання</h3>

<?php if ($errors): ?>
    <div style="color:red;">
        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;"><?php echo $success; ?></p>
<?php endif; ?>

<p><a href="form1.html">Повернутися до форми</a></p>

</body>
</html>
