<?php

function printYandexResults() {

    $orderArray = $GLOBALS["orderArray"];

    if (count($orderArray) > 0) {

        echo "<br><table border='1' align='center'>
   <caption>Таблица сравнений адресов</caption>
   <tr>
    <th>Номер заказа</th>
    <th>Имя контрагента</th>
    <th>Адрес в заказе</th>
    <th>Адрес, найденный Яндексом</th>
   </tr>";

        foreach ($orderArray as $orderUuid => $order) {

            echo "<tr><td align='center'>".$order->getName().
                "</td><td align='center'>".$order->getContragentName().
                "</td><td align='center'>". $order->getAddress().
                "</td><td align='center'>".$order->getYandexAddress().
                "</td></tr>";
        }

        echo "</table><br>";
    }
}

function printHeader() {

    echo '
<p align="center">Программа управления доставкой</p>'; //TODO
}

function printFirstFooter() {

    echo '
<form name="secondForm" method="post" action="orders_sort.php">
    <p align="center"><input type="submit" name="restart_button" value="Вернуться к выбору даты"/></p>
</form>';
}

function printSecondFooter($userDate) {

    echo '
<form name="thirdForm" method="post" action="main_script.php">
    <input type="hidden" name="userDate" value="'.$userDate.'"/>
    <p align="center"><input type="submit" name="check_button" value="Перезапустить скрипт"/></p>
</form>';

    echo '
<form name="fourthForm" method="post" action="orders_sort.php">
    <p align="center"><input type="submit" name="restart_button" value="Вернуться к выбору даты"/></p>
</form>';
}

class TableRow implements JsonSerializable {

    private
        $itineraryListNumber,
        $name,
        $address,
        $time,
        $goods;


    public function TableRow($itineraryListNumber, $name, $address, $time, $goods) {

        $this->itineraryListNumber = $itineraryListNumber;
        $this->name = $name;
        $this->address = $address;
        $this->time = $time;
        $this->goods = $goods;
    }


    public function jsonSerialize() {

        return array(
            "i" => $this->itineraryListNumber,
            "n" => $this->name,
            "a" => $this->address,
            "t" => $this->time,
            "g" => $this->goods
        );
    }
}