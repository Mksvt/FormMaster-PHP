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
            border-bottom: 2px solid #000;
        }
        li {
            float: left;
        }
        li a {
            display: block;
            color: #000;
            text-align: center;
            padding: 14px 16px;
            text-decoration: none;
            border-right: 1px solid #000;
        }
        li a:hover {
            background-color: #eee;
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
    <li><a href="search.php">Пошук</a></li>
    <li><a href="clients.php">Клієнти</a></li>
    <li><a href="objects.php">Об'єкти</a></li>
    <li><a href="orders.php">Замовлення</a></li>
</ul>
</body>
</html>
