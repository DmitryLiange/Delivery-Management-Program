<?php

// Getting coordinates for orders and warehouses
function getCoordinatesFromYandex() {

    usleep(1000000);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $failed_orders = array();

    $orderArray = $GLOBALS["orderArray"];
    foreach ($orderArray as $order) {

        $request = "https://geocode-maps.yandex.ru/1.x/?&format=xml&results=1&geocode=".$order->getAddress();
        curl_setopt($ch, CURLOPT_URL, $request);

        $response = curl_exec($ch);

        try {

            $xml = new SimpleXMLElement($response);
        } catch (Exception $e) {

            //echo "Возникла ошибка при загрузке данных с Яндекса. Пожалуйста, попробуйте еще раз";
        }

        $isAnswered = false;
        if (isset($xml)) {

            foreach ($xml->GeoObjectCollection->children() as $child) {

                if ($child->getName() == "featureMember") {

                    $isAnswered = true;
                }
            }
        }

        if ($isAnswered) {

            $point = (string)$xml->GeoObjectCollection->featureMember->GeoObject->Point->pos;
            $values = explode(" ", $point);
            $order->setAddressLongitudeLatitude((float)$values[0], (float)$values[1]);
            $yandexAddress = (string)$xml->GeoObjectCollection->featureMember->GeoObject->metaDataProperty->GeocoderMetaData->text;
            $order->setYandexAddress($yandexAddress);
        } else {

            array_push($failed_orders, $order);
            unset($orderArray[$order->getUuid()]);
        }

        usleep(100000);
    }

    $GLOBALS["ordersYandexFailedCounter"] = count($failed_orders);
    if (count($failed_orders) > 0) {

        echo "<table border='1' align='center'>
   <caption>Яндекс не смог сопоставить следующие адреса</caption>
   <tr>
    <th>Номер заказа</th>
    <th>Имя контрагента</th>
    <th>Адрес в заказе</th>
   </tr>";

        foreach ($failed_orders as $failed_order) {

            echo "<tr><td align='center'>".$failed_order->getName().
                "</td><td align='center'>".$failed_order->getContragentName().
                "</td><td align='center'>". $failed_order->getAddress().
                "</td></tr>";
        }
        echo "</table><br>";
    }



    $GLOBALS["orderArray"] = $orderArray;

    $warehouseArray = $GLOBALS["warehouseArray"];
    foreach ($warehouseArray as $warehouse) {

        $request = "http://geocode-maps.yandex.ru/1.x/?&format=xml&results=1&geocode=".$warehouse->getAddress();
        curl_setopt($ch, CURLOPT_URL, $request);

        $response = curl_exec($ch);

        try {

            $xml = new SimpleXMLElement($response);

            $point = (string)$xml->GeoObjectCollection->featureMember->GeoObject->Point->pos;
            $values = explode(" ", $point);
            $warehouse->setAddressLongitudeLatitude((float)$values[0], (float)$values[1]);
        } catch (Exception $e) {

            //echo "Возникла ошибка при загрузке данных с Яндекса. Пожалуйста, попробуйте еще раз.";
        }
    }

    curl_close($ch);
}