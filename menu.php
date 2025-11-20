<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <style>
        ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #333;
        }
        li {
            float: left;
        }
        li a {
            display: block;
            color: white;
            text-align: center;
            padding: 14px 16px;
            text-decoration: none;
        }
        li a:hover {
            background-color: #111;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
<ul class="clearfix">
    <li><a href="index.php">Головна</a></li>
    <li><a href="form1.html">Додати співробітника</a></li>
    <li><a href="form2.html">Пошук співробітника</a></li>
    <li><a href="form2prepare.php">Пошук (prepared)</a></li>
    <li><a href="form3.php">Видалити співробітника</a></li>
    <li><a href="form4.html">Редагувати співробітника</a></li>
    <li><a href="form5.php">Перегляд співробітників</a></li>
</ul>
</body>
</html>
