<?php

function getXmlFromMoysklad($requiredTypeOfData, $body) {

    usleep(500000);
    $data = "";

    $sock = fsockopen("ssl://online.moysklad.ru", 443, $errno, $errstr, 30);

    if (!$sock) {
        die("$errstr ($errno)\n");
    }

    fputs($sock, "GET /exchange/rest/ms/xml/".$requiredTypeOfData."/list".$body." HTTP/1.1\r\n");
    fputs($sock, "Host: online.moysklad.ru\r\n");
    fputs($sock, "Authorization: Basic ".base64_encode($GLOBALS["login"].":".$GLOBALS["password"])."\r\n");
    fputs($sock, "Content-Type: application/xml \r\n");
    fputs($sock, "Accept: */*\r\n");
    fputs($sock, "Content-Length: ".strlen($data)."\r\n");
    fputs($sock, "Connection: close\r\n\r\n");
    fputs($sock, "$data");

    while ($str = trim(fgets($sock, 65536)));

    $data = "";

    while (!feof($sock)) {
        $la = fgets($sock, 65536);
        if(strlen($la) > 10) {
            $data.= $la;
        }
    }

    $data = str_replace("\n", "", $data);
    $data = str_replace(array("\r\n", "\r", "\n"), "", $data);

    fclose($sock);

    return $data;
}

// Функция для скачивания информации об одном заказе с Моего Склада
function getOrderXmlFromMoysklad($orderUuid) {

    usleep(1000000);
    $data = "";

    $sock = fsockopen("ssl://online.moysklad.ru", 443, $errno, $errstr, 30);

    if (!$sock) {
        die("$errstr ($errno)\n");
    }

    fputs($sock, "GET /exchange/rest/ms/xml/CustomerOrder/".$orderUuid." HTTP/1.1\r\n");
    fputs($sock, "Host: online.moysklad.ru\r\n");
    fputs($sock, "Authorization: Basic ".base64_encode($GLOBALS["login"].":".$GLOBALS["password"])."\r\n");
    fputs($sock, "Content-Type: application/xml \r\n");
    fputs($sock, "Accept: */*\r\n");
    fputs($sock, "Content-Length: ".strlen($data)."\r\n");
    fputs($sock, "Connection: close\r\n\r\n");
    fputs($sock, "$data");

    while ($str = trim(fgets($sock, 65536)));

    $data = "";

    while (!feof($sock)) {
        $la = fgets($sock, 65536);
        if(strlen($la) > 10) {
            $data.= $la;
        }
    }

    $data = str_replace("\n", "", $data);
    $data = str_replace(array("\r\n", "\r", "\n"), "", $data);

    fclose($sock);

    return $data;
}

// Функция для отправления информации об одном заказе на Мой Склад
function putOrderXmlToMoysklad($xml) {

    usleep(1000000);
    $data = $xml;

    $sock = fsockopen("ssl://online.moysklad.ru", 443, $errno, $errstr, 30);

    if (!$sock) {
        die("$errstr ($errno)\n");
    }

    fputs($sock, "PUT /exchange/rest/ms/xml/CustomerOrder HTTP/1.1\r\n");
    fputs($sock, "Host: online.moysklad.ru\r\n");
    fputs($sock, "Authorization: Basic ".base64_encode($GLOBALS["login"].":".$GLOBALS["password"])."\r\n");
    fputs($sock, "Content-Type: application/xml \r\n");
    fputs($sock, "Accept: */*\r\n");
    fputs($sock, "Content-Length: ".strlen($data)."\r\n");
    fputs($sock, "Connection: close\r\n\r\n");
    fputs($sock, "$data");

    while ($str = trim(fgets($sock, 4096)));

    $data = "";

    while (!feof($sock)) {
        $data.= fgets($sock, 4096);
    }

    fclose($sock);

    return $data;
}

// Общая функция для обновления информации об одном заказе на Моем Складе
function uploadChanges($orderUuid, $courierUuid, $itineraryListNumber) {

    $itineraryListNumberUuid = "83b401b2-7643-11e4-90a2-8eca001235b3";
    $employeeValueUuid = "83b40086-7643-11e4-90a2-8eca001235b2";

    $oneOrderData = getOrderXmlFromMoysklad($orderUuid);

    try {

        $oneOrderXml = new SimpleXmlElement($oneOrderData);
    } catch (Exception $e) {

        echo "Возникла ошибка при загрузке данных о заказах покупателей с Moysklad. Пожалуйста, попробуйте еще раз.";
        return;
    }

    $attributeExistsListNumber = false;
    $attributeExistsEmployee = false;
    $date = new DateTime("now", new DateTimeZone("Europe/Moscow"));
    $date = $date->format('Y-m-d\TH:i:s.uP');

    foreach ($oneOrderXml->attribute as $attribute) {

        if ($attribute["metadataUuid"] == $itineraryListNumberUuid) {

            $attribute["longValue"] = $itineraryListNumber;
            $attributeExistsListNumber = true;
        }

        if ($attribute["metadataUuid"] == $employeeValueUuid) {

            $attribute["employeeValueUuid"] = $courierUuid;
            $attributeExistsEmployee = true;
        }
    }

    if (!$attributeExistsListNumber) {

        $attribute = $oneOrderXml->addChild("attribute");
        $attribute->addAttribute("operationUuid", $orderUuid);
        $attribute->addAttribute("metadataUuid", $itineraryListNumberUuid);
        $attribute->addAttribute("longValue", $itineraryListNumber);
        $attribute->addAttribute("updated", $date);
        $attribute->addAttribute("updatedBy", $GLOBALS["login"]);
        $attribute->addAttribute("readMode", 'SELF');
        $attribute->addAttribute("changeMode", 'SELF');
    }

    if (!$attributeExistsEmployee) {

        $attribute = $oneOrderXml->addChild("attribute");
        $attribute->addAttribute("operationUuid", $orderUuid);
        $attribute->addAttribute("metadataUuid", $employeeValueUuid);
        $attribute->addAttribute("employeeValueUuid", $courierUuid);
        $attribute->addAttribute("updated", $date);
        $attribute->addAttribute("updatedBy", $GLOBALS["login"]);
        $attribute->addAttribute("readMode", 'SELF');
        $attribute->addAttribute("changeMode", 'SELF');
    }

    $result = $oneOrderXml->asXML();

    putOrderXmlToMoysklad($result);
}