<?php

function parseXml($xml, $dataType) {

    $contragentUrl = "Company";
    $array = array();

    switch ($dataType) {

        case "customerOrder":

            $ordersForThisDateCounter = 0;
            $ordersValidForThisDateCounter = 0;
            $ordersWithValidContragentForThisDateCounter = 0;

            foreach ($xml->customerOrder as $customerOrder) {

                $isDeleted = false;
                foreach ($customerOrder->children() as $child) {

                    if ($child->getName() == "deleted") {

                        $isDeleted = true;
                    }
                }

                if (!$isDeleted) {

                    $ordersForThisDateCounter ++;
                    $newOrder = new CustomerOrder($customerOrder);

                    if ($newOrder->areAllFieldsValid()) {

                        $ordersValidForThisDateCounter ++;
                        $filterCompanyString = urlencode("uuid=".$newOrder->getSourceAgentUuid());
                        $contragentData = getXmlFromMoysklad($contragentUrl, "?filter=".$filterCompanyString);
                        try {

                            $contragentXml = new SimpleXmlElement($contragentData);
                            $address = (string)$contragentXml->company->requisite["actualAddress"];
                            $contragentName = (string)$contragentXml->company["name"];

                            if (!empty($address)) {

                                $ordersWithValidContragentForThisDateCounter ++;
                                $newOrder->setAddress($address);
                                $newOrder->setContragentName($contragentName);

                                $array[$newOrder->getUuid()] = $newOrder;
                                $GLOBALS["numberOfOrders"] ++;

                            }
                        } catch (Exception $e) {
                            //echo "Выброшено исключение: ?filter=".$filterCompanyString."<br>";
                        }
                    }
                }
            }

            $GLOBALS["ordersForThisDateCounter"] = $ordersForThisDateCounter;
            $GLOBALS["ordersValidForThisDateCounter"] = $ordersValidForThisDateCounter;
            $GLOBALS["ordersWithValidContragentForThisDateCounter"] = $ordersWithValidContragentForThisDateCounter;

            break;

        case "good":
            foreach ($xml->good as $good) {

                $newGood = new Good($good);

                $array[$newGood->getUuid()] = $newGood;
            }
            break;

        case "service":
            foreach ($xml->service as $service) {

                $newServiceUuid = (string)$service->uuid;
                $newServiceName = (string)$service["name"];

                $array[$newServiceUuid] = $newServiceName;
            }
            break;

        case "courier":
            foreach ($xml->employee as $courier) {

                $newCourier = new Courier($courier);

                if ($newCourier->areAllFieldsValid() && $newCourier->getIsCourier() && $newCourier->getIsActive()) {

                    $array[$newCourier->getUuid()] = $newCourier;
                }
            }
            break;

        case "warehouse":
            foreach ($xml->warehouse as $warehouse) {

                $newWarehouse = new Warehouse($warehouse);

                $array[$newWarehouse->getUuid()] = $newWarehouse;
            }
            break;
    }

    return $array;
}

function parseOrderXml($xml) {

    $orderArray = &$GLOBALS["orderArray"];

    foreach ($xml->customerOrder as $customerOrder) {

        $newOrder = new CustomerOrder($customerOrder);

        if ($newOrder->isEligibleDate()) {

            $orderArray[$newOrder->getUuid()] = $newOrder;
            $GLOBALS["numberOfOrders"] ++;
        }
    }
}