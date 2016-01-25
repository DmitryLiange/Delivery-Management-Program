<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Программа управления доставкой</title>
</head>
<body>
<?php


$login = "kit@sadovod_elki";
$password = "815320222";

libxml_use_internal_errors(true);
libxml_clear_errors();

$numberOfOrders = 0; // Number of eligible orders + warehouses
$ordersForThisDateCounter = 0;
$ordersValidForThisDateCounter = 0;
$ordersWithValidContragentForThisDateCounter = 0;
$ordersYandexFailedCounter = 0;

$goodArray = array();
$serviceArray = array();
$orderArray = array();
$courierArray = array();
$warehouseArray = array();
$newOrderArray = array();


include_once "moysklad_classes.php";

include_once "computational_geometry.php";

include_once "moysklad_functions.php";

include_once "xml_parsing_functions.php";

include_once "yandex_functions.php";

include_once "comparison_functions.php";

include_once "html_printing_functions.php";


// Функция для получения и преобразования в нужный вид всех данных с Мойсклад
function processDataFromMoysklad($userDate) {

    $orderUrl = "CustomerOrder";
    $goodUrl = "Good";
    $serviceUrl = "Service";
    $courierUrl = "Employee";
    $warehouseUrl = "Warehouse";

    // Getting data from Moysklad
    $courierData = getXmlFromMoysklad($courierUrl, "");
    $goodData = getXmlFromMoysklad($goodUrl, "");
    $serviceData = getXmlFromMoysklad($serviceUrl, "");
    $warehouseData = getXmlFromMoysklad($warehouseUrl, "");


    $date = new DateTime($userDate, new DateTimeZone("Europe/Moscow"));
    $filterTimeTomorrow = (int)$date->format("Ymd");

    $date = new DateTime($userDate."+1 day", new DateTimeZone("Europe/Moscow"));
    $filterTimeAfterTomorrow = (int)$date->format("Ymd");

    $filterOrderString = urlencode("deliveryPlannedMoment>".$filterTimeTomorrow."040000;".
        "deliveryPlannedMoment<".$filterTimeAfterTomorrow."040000");
    $orderData = getXmlFromMoysklad($orderUrl, "?filter=".$filterOrderString);

    // Parsing XML
    try {

        $goodXml = new SimpleXmlElement($goodData);
    } catch (Exception $e) {

        echo "Возникла ошибка при загрузке данных о товарах с Moysklad. Пожалуйста, попробуйте еще раз.";
        return;
    }
    $goodArray = parseXml($goodXml, "good");
    $GLOBALS["goodArray"] = $goodArray;

    try {

        $serviceXml = new SimpleXmlElement($serviceData);
    } catch (Exception $e) {

        echo "Возникла ошибка при загрузке данных об услугах с Moysklad. Пожалуйста, попробуйте еще раз.";
        return;
    }
    $serviceArray = parseXml($serviceXml, "service");
    $GLOBALS["serviceArray"] = $serviceArray;

    try {

        $orderXml = new SimpleXmlElement($orderData);
    } catch (Exception $e) {

        echo "Возникла ошибка при загрузке данных о заказах покупателей с Moysklad. Пожалуйста, попробуйте еще раз.";
        return;
    }
    $orderArray = parseXml($orderXml, "customerOrder");
    $GLOBALS["orderArray"] = $orderArray;

    try {

        $warehouseXml = new SimpleXMLElement($warehouseData);
    } catch (Exception $e) {

        echo "Возникла ошибка при загрузке данных о складах с Moysklad. Пожалуйста, попробуйте еще раз.";
        return;
    }
    $warehouseArray =  parseXml($warehouseXml, "warehouse");
    $GLOBALS["warehouseArray"] = $warehouseArray;

    try {

        $courierXml = new SimpleXmlElement($courierData);
    } catch (Exception $e) {

        echo "Возникла ошибка при загрузке данных по курьерам с Moysklad. Пожалуйста, попробуйте еще раз.";
        return;
    }
    $courierArray = parseXml($courierXml, "courier");
    $GLOBALS["courierArray"] = $courierArray;
}


function scriptFirstPart($userDate) {

    processDataFromMoysklad($userDate);

    getCoordinatesFromYandex();

    printYandexResults();

    scriptSecondPart($userDate);
}

function scriptSecondPart($userDate) {

    $segmentArray = array();

    $numberOfOrders = $GLOBALS["numberOfOrders"];
    $numberOfWarehouses = 0; // NUmber of warehouses

    $orderArray = $GLOBALS["orderArray"];
    $courierArray = $GLOBALS["courierArray"];
    $warehouseArray = $GLOBALS["warehouseArray"];


    // Adding warehouses to orderArray for distance calculation
    foreach ($warehouseArray as $warehouseUuid => $warehouse) {

        $orderArray[$warehouseUuid] = $warehouse;
        $numberOfOrders ++;
        $numberOfWarehouses ++;
    }

    // Если массив подходящих курьеров или заказов пуст, то заканчиваем
    if ((count($courierArray) == 0) || ($numberOfOrders - $numberOfWarehouses == 0)) {

        if (count($courierArray) != 0) {

            echo "Нет подходящих заказов! Заказ должен иметь заполненным поля 'Склад', 'План. дата отгрузки'. Контрагент должен иметь заполненными поле 'Фактический адрес'.";
        } else if ($numberOfOrders - $numberOfWarehouses != 0) {

            echo "Нет подходящих курьеров! Курьер должен иметь отметку в полях 'Курьер', 'Активен', все кастомные поля должны быть заполненными.";
        } else {

            echo "Нет подходящих заказов и курьеров! Курьер должен иметь отметку в полях 'Курьер', 'Активен', все кастомные поля должны быть заполненными.
        Заказ должен иметь заполненным поля 'Склад', 'План. дата отгрузки'. Контрагент должен иметь заполненными поле 'Фактический адрес'.";
        }

        printFirstFooter();

        return;
    }

    // Считаем попарные расстояния между заказами и складами
    foreach ($orderArray as $uuidFrom => $orderFrom) {

        foreach ($orderArray as $uuidTo => $orderTo) {

            if ($uuidFrom != $uuidTo) {

                if ($orderFrom->getAddressCoordinates() != $orderTo->getAddressCoordinates()) {

                    $segmentArray[$uuidFrom][$uuidTo] = calculateDistance($orderFrom->getAddressLatitude(),
                        $orderFrom->getAddressLongitude(), $orderTo->getAddressLatitude(), $orderTo->getAddressLongitude());
                } else {

                    $segmentArray[$uuidFrom][$uuidTo] = 0;
                }
            }
        }
    }

    $amountOfAllOrders = 0;


    // Удаляем склады из массива заказов
    foreach ($warehouseArray as $warehouseUuid => $warehouse) {

        unset($orderArray[$warehouseUuid]);
    }

    if (count($orderArray) == 0) {
        echo "Не оказалось заказов с подходящими Яндексу адресами для распределения";
        printFirstFooter();

        return;
    }

    // Проверяем массив заказов на расположение относительно ТТК
    foreach ($orderArray as $orderUuid => $order) {

        $newOrderArray[$orderUuid] = new Order($order);
        $amountOfAllOrders ++;

        $latitude = $newOrderArray[$orderUuid]->getLatitude();
        $longitude = $newOrderArray[$orderUuid]->getLongitude();
        $orderPoint = new Point($latitude, $longitude);
        $isInsideTTR = isInsideTTR($orderPoint);
        $newOrderArray[$orderUuid]->setIsInsideTTR($isInsideTTR);
    }

    // Сортируем заказы по времени доставки
    uasort($newOrderArray, "cmp");

    // Сортируем курьеров по времени прибытия
    uasort($courierArray, "cmp1");


    foreach ($warehouseArray as $warehouseUuid => $warehouse) {

        $currentWarehouseOrderArray = $newOrderArray;
        $currentCourierArray = $courierArray;

        // Удаляем товары не с текущего склада
        foreach ($currentWarehouseOrderArray as $orderUuid => $order) {

            if ($warehouseUuid != $order->getSourceStoreUuid()) {

                unset($currentWarehouseOrderArray[$orderUuid]);
            }
        }

        // Удаляем курьеров не с текущего склада
        foreach ($currentCourierArray as $courierUuid => $courier) {

            if ($warehouseUuid != $courier->getSourceStoreUuid()) {

                unset($currentCourierArray[$courierUuid]);
            }
        }

        // По очереди берем курьеров, которые работают и могут развозить/развозят заказы с этого склада
        foreach ($currentCourierArray as $courierUuid => $courier) {

            //Временные промежутки: каждый час, с 4 до 4(28)
            $isDayNotOver = true;
            $timeFrom = 4;
            $timeTo = 5;
            while ($isDayNotOver) {

                // Выделяем заказы, заканчивающиеся в этот час
                $currentTimePeriodOrderArray = array();
                foreach ($currentWarehouseOrderArray as $orderUuid => $order) {

                    if ($order->getDeliveryToHour() >= $timeTo && $order->getDeliveryFromHour() <= $timeFrom) {
                        //if ($order->getDeliveryFromHour() <= $timeFrom) {

                        $currentTimePeriodOrderArray[$orderUuid] = $order;
                    }
                }

                // Считаем число заказов, кончающихся в этот час и проверяем, есть ли они вообще
                $amountOfOrdersToBeAssigned = count($currentTimePeriodOrderArray);

                if ($amountOfOrdersToBeAssigned > 0) {

                    while (($courier->getCurrentAmountOfOrders() < $courier->getAmountOfOrders()) &&
                        ($courier->getCurrentTime() < $timeTo) &&
                        ($amountOfOrdersToBeAssigned > 0)) {

                        // Сортируем массив расстояний от точки нахождения по возрастанию
                        $currentPosition = $courier->getCurrentPosition();
                        $currentTimeFromCourierPositionArray = $segmentArray[$currentPosition];
                        asort($currentTimeFromCourierPositionArray);

                        // Проверка на то, что кто-то из курьеров может взять этот товар
                        $courierCannotTake = true;

                        // Берем первый подходящий ( по длине, макс длине, весу, макс весу, общему времени доставки) заказ
                        foreach ($currentTimeFromCourierPositionArray as $orderUuid => $time) {

                            // Проверяем, существует ли такой заказ в списке заказов на данный временной промежуток
                            if (array_key_exists($orderUuid, $currentTimePeriodOrderArray)) {

                                $order = $currentTimePeriodOrderArray[$orderUuid];

                                $hours = (double)round($time/50000, 1).PHP_EOL;

                                //echo $time." ".$hours;

                                if ((!$order->getIsTaken()) &&
                                    ($courier->getMaxWeight() >= $order->getMaxWeight()) &&
                                    ($courier->getMaxLength() >= $order->getMaxLength()) &&
                                    ($courier->getCurrentLength() + $order->getWholeOrderLength() <= $courier->getLimitLength()) &&
                                    ($courier->getCurrentWeight() + $order->getWholeOrderWeight() <= $courier->getLimitWeight()) &&
                                    //($courier->getCurrentTime() + $hours <= $order->getDeliveryToHour()) &&
                                    ($courier->getCanWorkInsideTTR() ||
                                        (!$order->getIsInsideTTR() ||
                                            (($timeFrom <= 6) ||
                                                ($timeFrom >= 22))))
                                ) {

                                    $courier->increaseCurrentAmountOfOrders();
                                    $courier->increaseCurrentLengthBy($order->getWholeOrderLength());
                                    $courier->increaseCurrentWeightBy($order->getWholeOrderWeight());
                                    $courier->setCurrentPosition($order->getUuid());
                                    $courier->increaseCurrentTimeBy(1);

                                    $order->setIsTaken();
                                    $currentWarehouseOrderArray[$orderUuid]->setIsTaken();
                                    $newOrderArray[$orderUuid]->setIsTaken();

                                    $newOrderArray[$orderUuid]->setItineraryListNumber($courier->getCurrentAmountOfOrders());
                                    $newOrderArray[$orderUuid]->setCourier($courierUuid);

                                    $amountOfOrdersToBeAssigned--;
                                    $courierCannotTake = false;

                                    break;
                                }
                            }
                        }

                        if ($courierCannotTake) {

                            break;
                        }
                    }
                }


                if ($timeTo == 28) {

                    $isDayNotOver = false;
                } else {

                    $timeFrom = $timeTo;
                    $timeTo += 1;
                }
            }
        }
    }

    $counterOfNotTakenOrders = 0;


    foreach ($newOrderArray as $orderUuid => $order) {

        if (!$order->getIsTaken()) {

            $counterOfNotTakenOrders ++;
        } else {

            //uploadChanges($orderUuid, $order->getCourier(), $order->getItineraryListNumber());
        }
    }

    $ordersForThisDateCounter = $GLOBALS['ordersForThisDateCounter'];
    $ordersValidForThisDateCounter = $GLOBALS['ordersValidForThisDateCounter'];
    $ordersWithValidContragentForThisDateCounter = $GLOBALS['ordersWithValidContragentForThisDateCounter'];
    $ordersYandexFailedCounter = $GLOBALS['ordersYandexFailedCounter'];

    $ordersNotValidForThisDateCounter = $ordersForThisDateCounter - $ordersValidForThisDateCounter;
    $ordersWithNotValidContragentForThisDateCounter = $ordersValidForThisDateCounter - $ordersWithValidContragentForThisDateCounter;
    $ordersCounter = count($newOrderArray) - $counterOfNotTakenOrders;

    $date = new DateTime($userDate, new DateTimeZone("Europe/Moscow"));
    $timeTomorrow = (string)$date->format("d.m.Y");

    $date = new DateTime($userDate."+1 day", new DateTimeZone("Europe/Moscow"));
    $timeAfterTomorrow = (string)$date->format("d.m.Y");

    $summaryString = "<br><br><br><p align='center'>
    Скрипт завершил работу!<br>
    Из МойСклад было загружено $ordersForThisDateCounter заказов на период с $timeTomorrow 04:00 до $timeAfterTomorrow 04:00.<br>
    У $ordersNotValidForThisDateCounter заказов были не заполнены поля \"Склад\" и/или \"План. дата отгрузки\".<br>
    У $ordersWithNotValidContragentForThisDateCounter контрагентов было не заполнено поле \"Фактический адрес\".<br>
    Для $ordersYandexFailedCounter заказов Яндекс не смог сопоставить адреса.<br>";

    if ($counterOfNotTakenOrders != 0) {

        $summaryString .= "$counterOfNotTakenOrders заказов не удалось распределить между существующими курьерами.<br>";
    }

    $summaryString .= "$ordersCounter заказов были распределены между курьерами и информация о них была обновлена в МойСклад.<br><br></p>";

    echo $summaryString;

    echo "<table border='1' align='center'>
   <caption>Общая таблица распределенных заказов</caption>
   <tr>
    <th>Номер заказа</th>
    <th>Адрес заказа</th>
    <th>Время доставки</th>
    <th>ФИО курьера</th>
    <th>Номер заказа в листе курьера</th>
    <th>Статус обработки</th>
   </tr>";


    foreach ($newOrderArray as $orderUuid => $order) {

        $orderName = $order->getName();
        $orderAddress = $order->getAddress();
        $orderDeliveryToTime = $order->getDeliveryToTime();
        $orderCourierName = "";
        $orderItineraryListNumber = "";

        if ($order->getIsTaken()) {

            $courierArray[$order->getCourier()]->setOrdersIdArray($order->getUuid());

            $orderCourierName = $courierArray[$order->getCourier()]->getName();
            $orderItineraryListNumber = $order->getItineraryListNumber();
            $isOrderTaken = "Распределен";
        } else {

            $isOrderTaken = "Не распределен";
        }

        echo "<tr><td align='center'>".$orderName.
            "</td><td align='center'>".$orderAddress.
            "</td><td align='center'>".$orderDeliveryToTime.
            "</td><td align='center'>".$orderCourierName.
            "</td><td align='center'>".$orderItineraryListNumber.
            "</td><td align='center'>".$isOrderTaken.
            "</td></tr>";
    }

    echo "</table><br><br>";

    $GLOBALS["newOrderArray"] = $newOrderArray;

    foreach ($courierArray as $courierUuid => $courier) {

        $ordersIdArray = $courier->getOrdersIdArray();

        if (count($ordersIdArray) > 0) {

            uasort($ordersIdArray, "cmp2");

            $addressArray = array();
            $tableRowArray = array();

            $firstAddress = $warehouseArray[$courier->getSourceStoreUuid()]->getAddressCoordinates();
            array_push($addressArray, $firstAddress);

            echo "<table border='1' align='center'>
                   <caption>Таблица распределенных заказов для ".$courier->getName()."</caption>
                   <tr>
                    <th>Номер заказа в листе курьера</th>
                    <th>Номер заказа</th>
                    <th>Адрес заказа</th>
                    <th>Время доставки</th>
                    <th>Товары</th>
                   </tr>";

            foreach ($ordersIdArray as $orderId) {

                $orderById = $newOrderArray[$orderId];

                array_push($addressArray, $orderById->getAddressCoordinates());

                $goodsArray = $orderById->getGoodOrderArray();
                $goodsString = "";
                foreach ($goodsArray as $goodId => $goodOrder) {

                    if ($goodOrder->getName() != "") {

                        $goodsString .= $goodOrder->getAmount()." ".$goodOrder->getName()."<br>";
                    }
                }

                $itineraryListNumber = $orderById->getItineraryListNumber();
                $name = $orderById->getName();
                $address = $orderById->getAddress();
                $toTime = $orderById->getDeliveryToTime();

                echo "<tr><td align='center'>".$itineraryListNumber.
                    "</td><td align='center'>".$name.
                    "</td><td align='center'>".$address.
                    "</td><td align='center'>".$toTime.
                    "</td><td align='center'>".$goodsString.
                    "</td></tr>";

                $newTableRow = array();
                $newTableRow["i"] = $itineraryListNumber;
                $newTableRow["n"] = $name;
                $newTableRow["a"] = $address;
                $newTableRow["t"] = $toTime;
                $newTableRow["g"] = $goodsString;
                $jsonNewTableRow = json_encode($newTableRow);

                array_push($tableRowArray, $jsonNewTableRow);
            }

            echo "</table>";

            $jsonAddressArray = json_encode($addressArray);
            $jsonTableRowArray = json_encode($tableRowArray);
            $jsonCourierName = json_encode($courier->getName());

            echo "
            <form name='tableForm' method='post' action='table_script.php' target='_blank'>
                <input type='hidden' name='jsonCourierName' value='".$jsonCourierName."'>
                <input type='hidden' name='jsonTableRowArray' value='".$jsonTableRowArray."'>
                <p align='center'><input type='submit' name='table_button' value='Вывести для печати'/></p>
            </form>
            <form name='yandexForm' method='post' action='yandex_map_script.php' target='_blank'>
                <input type='hidden' name='jsonAddressArray' value='".$jsonAddressArray."'>
                <p align='center'><input type='submit' name='yandex_button' value='Показать на карте'/></p>
            </form><br><br>";
        } else {

            echo "
            <table border='1' align='center'>
                <caption>Таблица распределенных заказов для ".$courier->getName()."</caption>
                <tr>
                 <th>Номер заказа в листе курьера</th>
                 <th>Номер заказа</th>
                 <th>Адрес заказа</th>
                 <th>Время доставки</th>
                 <th>Товары</th>
                </tr>
            </table><br><br>";
        }
    }

    printSecondFooter($userDate);
}


if (count($_POST) > 0) {

    printHeader();

    if (isset($_POST["userDate"])) {

        scriptFirstPart($_POST["userDate"]);
    } else if (isset($_POST["script_button"])) {

        printFirstFooter();
    }
} else {

    printHeader();
    printFirstFooter();
}
?>
</body>
</html>