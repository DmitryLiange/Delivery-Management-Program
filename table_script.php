<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Скрипт для Moysklad</title>
</head>
<body>

<?php

if (isset($_POST["jsonTableRowArray"]) && isset($_POST["jsonCourierName"])) {

    $tableRowArray = json_decode($_POST["jsonTableRowArray"]);
    $courierName = json_decode($_POST["jsonCourierName"]);

    echo "<table border='1' align='center'>
               <caption>Таблица распределенных заказов для ".$courierName."</caption>
               <tr>
                <th>Номер заказа в листе курьера</th>
                <th>Номер заказа</th>
                <th>Адрес заказа</th>
                <th>Время доставки</th>
                <th>Товары</th>
               </tr>";

    foreach ($tableRowArray as $courierName => $jsonTableRow) {

        $tableRow = json_decode($jsonTableRow, true);

        echo "<tr><td align='center'>".$tableRow["i"].
            "</td><td align='center'>".$tableRow["n"].
            "</td><td align='center'>".$tableRow["a"].
            "</td><td align='center'>".$tableRow["t"].
            "</td><td align='center'>".$tableRow["g"].
            "</td></tr>";
    }
    echo "</table>";
}

?>
</body>
</html>