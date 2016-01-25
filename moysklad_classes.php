<?php

class CustomerOrder implements JsonSerializable{

    private
        $uuid,
        $name,
        $address,
        $yandexAddress,
        $deliveryFromTime,
        $deliveryFromDay = 0, // нулевые значения на тот случай, если не введено время начала заказа
        $deliveryFromHour = 4,
        $deliveryToTime,
        $deliveryToYear,
        $deliveryToMonth,
        $deliveryToDay,
        $deliveryToHour,

        $goodOrderArray = array(),

        $maxWeight,
        $maxLength,
        $wholeOrderLength,
        $wholeOrderWeight,

        $contragentName,
        $addressLongitude,
        $addressLatitude,

        $sourceStoreUuid,
        $sourceAgentUuid;


    public function CustomerOrder($customerOrder) {

        $deliveryFromTimeUuid = "83b3fcfd-7643-11e4-90a2-8eca001235b0";

        $this->uuid = (string)$customerOrder->uuid;
        $this->sourceStoreUuid = "w-".$customerOrder["sourceStoreUuid"];
        $this->sourceAgentUuid = $customerOrder["sourceAgentUuid"];
        $this->name = (string)$customerOrder["name"];

        $this->deliveryToTime = (string)$customerOrder["deliveryPlannedMoment"];
        if (!empty($this->deliveryToTime)) {

            $deliveryTimeArray1 = explode("T", $this->deliveryToTime);      // Y-m-dTh:i:s.uP
            $deliveryTimeArray2 = explode("-", $deliveryTimeArray1[0]);     // Y-m-d
            $deliveryTimeArray3 = explode(":", $deliveryTimeArray1[1]);     // h:i:s.uP
            $this->deliveryToYear = (int)$deliveryTimeArray2[0];
            $this->deliveryToMonth = (int)$deliveryTimeArray2[1];
            $this->deliveryToDay = (int)$deliveryTimeArray2[2];
            $this->deliveryToHour = (int)$deliveryTimeArray3[0];
            unset($deliveryTimeArray1);
            unset($deliveryTimeArray2);
            unset($deliveryTimeArray3);
        }

        foreach ($customerOrder->attribute as $attribute) {

            switch((string)$attribute["metadataUuid"]) {

                case $deliveryFromTimeUuid:
                    $this->deliveryFromTime = (string)$attribute["timeValue"];

                    $deliveryTimeArray1 = explode("T", $this->deliveryFromTime);      // Y-m-dTh:i:s.uP
                    $deliveryTimeArray2 = explode("-", $deliveryTimeArray1[0]);       // Y-m-d
                    $deliveryTimeArray3 = explode(":", $deliveryTimeArray1[1]);       // h:i:s.uP

                    $this->deliveryFromDay = (int)$deliveryTimeArray2[2];
                    $this->deliveryFromHour = (int)$deliveryTimeArray3[0];
                    break;
            }
        }

        $this->maxWeight = 0;
        $this->maxLength = 0;
        $this->wholeOrderLength = 0;
        $this->wholeOrderWeight = 0;

        foreach ($customerOrder->customerOrderPosition as $customerOrderPosition) {

            $goodUuid = (string)$customerOrderPosition["goodUuid"];
            $goodOrder = new GoodOrder($customerOrderPosition);

            if ($this->maxLength <= $goodOrder->getLength()) {

                $this->maxLength = $goodOrder->getLength();
            }

            if ($this->maxWeight <= $goodOrder->getWeight()) {

                $this->maxWeight = $goodOrder->getWeight();
            }

            $this->wholeOrderWeight += $goodOrder->getWholeWeight();
            $this->wholeOrderLength += $goodOrder->getLength() * $goodOrder->getAmount();

            $this->goodOrderArray[$goodUuid] = $goodOrder;
        }

    }


    public function getUuid() {

        return $this->uuid;
    }

    public function getName() {

        return $this->name;
    }

    public function getAddress() {

        return $this->address;
    }

    public function getYandexAddress() {

        return $this->yandexAddress;
    }

    public function getAddressCoordinates() {

        $addressCoordinates = $this->addressLatitude.", ".$this->addressLongitude;
        return $addressCoordinates;
    }

    public function getAddressLatitude() {

        return $this->addressLatitude;
    }

    public function getAddressLongitude() {

        return $this->addressLongitude;
    }

    public function getDeliveryFromHour() {

        return $this->deliveryFromHour;
    }

    public function getDeliveryFromDay() {

        return $this->deliveryFromDay;
    }

    public function getDeliveryToTime() {

        return $this->deliveryToTime;
    }

    public function getDeliveryToHour() {

        return $this->deliveryToHour;
    }

    public function getDeliveryToDay() {

        return $this->deliveryToDay;
    }

    public function getSourceStoreUuid() {

        return $this->sourceStoreUuid;
    }

    public function getMaxWeight() {

        return $this->maxWeight;
    }

    public function getWholeOrderWeight() {

        return $this->wholeOrderWeight;
    }

    public function getMaxLength() {

        return $this->maxLength;
    }

    public function getWholeOrderLength() {

        return $this->wholeOrderLength;
    }

    public function getSourceAgentUuid() {

        return $this->sourceAgentUuid;
    }

    public function getContragentName() {

        return $this->contragentName;
    }

    public function getGoodOrderArray() {

        return $this->goodOrderArray;
    }


    public function setAddress($address) {

        $this->address = $address;
    }

    public function setYandexAddress($address) {

        $this->yandexAddress = $address;
    }

    public function setAddressLongitudeLatitude($longitude, $latitude) {

        $this->addressLongitude = round($longitude, 6);
        $this->addressLatitude = round($latitude, 6);
    }

    public function setContragentName($contragentName) {

        $this->contragentName = $contragentName;
    }

    public function areAllFieldsValid() {

        if ((!empty($this->deliveryToTime)) && ($this->sourceStoreUuid != "w-")) {

            return true;
        } else {

            return false;
        }
    }


    function jsonSerialize() {

        return array(
            "uuid" => $this->uuid,
            "dFD" => $this->deliveryFromDay,
            "dFH" => $this->deliveryFromHour,
            "dTD" => $this->deliveryToDay,
            "dTH" => $this->deliveryToHour,
            "mW" => $this->maxWeight,
            "mL" => $this->maxLength,
            "wOL" => $this->wholeOrderLength,
            "wOW" => $this->wholeOrderWeight,
            "sSU" => $this->sourceStoreUuid,
            "lat" => round($this->addressLatitude, 6),
            "lon" => round($this->addressLongitude, 6),
        );
    }
}

class GoodOrder implements JsonSerializable{

    private
        $name,
        $amount,
        $good,
        $weight,
        $length,
        $wholeWeight;


    public function GoodOrder($customerOrderPosition) {

        $this->amount = (float)$customerOrderPosition["quantity"];

        $goodUuid = (string)$customerOrderPosition["goodUuid"];
        $goodArray = $GLOBALS["goodArray"];
        $serviceArray = $GLOBALS["serviceArray"];

        if (array_key_exists($goodUuid, $goodArray)) {

            $this->good = $goodArray[$goodUuid];

            $this->name = $this->good->getName();
            $this->weight = $this->good->getWeight();
            $this->length = $this->good->getLength();
            $this->wholeWeight = $this->amount * $this->weight;
        } else {

            $this->name = "";
            $this->weight = 0;
            $this->length = 0;
            $this->wholeWeight = 0;

            if (array_key_exists($goodUuid, $serviceArray)) {

                $this->name = $serviceArray[$goodUuid];
            }
        }
    }


    public function getName() {

        return $this->name;
    }

    public function getAmount() {

        return $this->amount;
    }

    public function getWeight() {

        return $this->weight;
    }

    public function getWholeWeight() {

        return $this->wholeWeight;
    }

    public function getLength() {

        return $this->length;
    }


    function jsonSerialize() {

        return array(
            "amount" => $this->amount
        );
    }
}

class Good implements JsonSerializable {

    private
        $uuid,
        $name,
        $weight,
        $length = 0;


    public function Good($good) {

        $lengthUuid = "a8df89d5-763e-11e4-90a2-8eca0011224f";

        $this->uuid = (string)$good->uuid;
        $this->name = (string)$good["name"];
        $this->weight = (float)$good["weight"];

        foreach ($good->attribute as $attribute) {

            switch((string)$attribute["metadataUuid"]) {

                case $lengthUuid:
                    $this->length = (float)$attribute["doubleValue"];
                    break;
            }
        }
    }


    public function getUuid() {

        return $this->uuid;
    }

    public function getName() {

        return $this->name;
    }

    public function getWeight() {

        return $this->weight;
    }

    public function getLength() {

        return $this->length;
    }


    public function jsonSerialize() {

        return array(
            "uuid" => $this->uuid,
            "weight" => $this->weight,
            "length" => $this->length
        );
    }
}

class Courier implements JsonSerializable{

    private
        $uuid,
        $name,
        $amountOfOrders,
        $startWorkTime,
        $finishWorkTime,
        $maxLength,
        $maxWeight,
        $limitLength,
        $limitWeight,
        $canWorkInsideTTR,
        $isCourier,
        $isActive,
        $sourceStoreUuid,

        $currentAmountOfOrders,
        $currentTime,
        $currentLength,
        $currentWeight,

        $currentPosition,

        $ordersIdArray;


    public function Courier($courier) {

        $amountOfOrdersUuid = "ce359aba-7640-11e4-90a2-8eca0011a1be";
        $startWorkTimeUuid = "ce359bdf-7640-11e4-90a2-8eca0011a1bf";
        $finishWorkTimeUuid = "a4bd0b4b-7641-11e4-90a2-8eca0011cedf";
        $maxLengthUuid = "d777c9e8-7641-11e4-90a2-8eca0011d523";
        $maxWeightUuid = "d777cbe4-7641-11e4-90a2-8eca0011d524";
        $limitLengthUuid = "f1afa187-7641-11e4-90a2-8eca0011de5a";
        $limitWeightUuid = "f1afa39a-7641-11e4-90a2-8eca0011de5b";
        $canWorkInsideTTRUuid = "1995c124-7642-11e4-90a2-8eca0011e6db";
        $isCourierUuid = "ce359765-7640-11e4-90a2-8eca0011a1bc";
        $isActiveUuid = "ce359967-7640-11e4-90a2-8eca0011a1bd";
        $sourceStoreUuidUuid = "ce359d1f-7640-11e4-90a2-8eca0011a1c0";

        $this->uuid = (string)$courier->uuid;
        $this->name = (string)$courier["name"];

        $this->currentAmountOfOrders = 0;
        $this->currentLength = 0;
        $this->currentWeight = 0;

        $this->ordersIdArray = array();

        foreach ($courier->attribute as $attribute) {

            switch((string)$attribute["metadataUuid"]) {

                case $amountOfOrdersUuid:
                    $this->amountOfOrders = (int)$attribute["longValue"];
                    break;

                case $startWorkTimeUuid:
                    $this->startWorkTime = (int)$attribute["longValue"];
                    $this->currentTime = $this->startWorkTime;
                    break;

                case $finishWorkTimeUuid:
                    $this->finishWorkTime = (int)$attribute["longValue"];
                    break;

                case $maxLengthUuid:
                    $this->maxLength = (float)$attribute["doubleValue"];
                    break;

                case $maxWeightUuid:
                    $this->maxWeight = (float)$attribute["doubleValue"];
                    break;

                case $limitLengthUuid:
                    $this->limitLength = (float)$attribute["doubleValue"];
                    break;

                case $limitWeightUuid:
                    $this->limitWeight = (float)$attribute["doubleValue"];
                    break;

                case $canWorkInsideTTRUuid:
                    if ((string)$attribute["booleanValue"] == "true") {

                        $this->canWorkInsideTTR = true;
                    } else {

                        $this->canWorkInsideTTR = false;
                    }
                    break;

                case $isCourierUuid:
                    if ((string)$attribute["booleanValue"] == "true") {

                        $this->isCourier = true;
                    } else {

                        $this->isCourier = false;
                    }
                    break;

                case $isActiveUuid:
                    if ((string)$attribute["booleanValue"] == "true") {

                        $this->isActive = true;
                    } else {

                        $this->isActive = false;
                    }
                    break;

                case $sourceStoreUuidUuid:
                    $this->sourceStoreUuid = "w-".(string)$attribute["placeValueUuid"];
                    $this->currentPosition = $this->sourceStoreUuid;
                    break;
            }
        }
    }


    public function getUuid() {

        return $this->uuid;
    }

    public function getName() {

        return $this->name;
    }

    public function getIsCourier() {

        return $this->isCourier;
    }

    public function getIsActive() {

        return $this->isActive;
    }

    public function getAmountOfOrders() {

        return $this->amountOfOrders;
    }

    public function getStartWorkTime() {

        return $this->startWorkTime;
    }

    public function getFinishWorkTime() {

        return $this->finishWorkTime;
    }

    public function getMaxLength() {

        return $this->maxLength;
    }

    public function getMaxWeight() {

        return $this->maxWeight;
    }

    public function getLimitLength() {

        return $this->limitLength;
    }

    public function getLimitWeight() {

        return $this->limitWeight;
    }

    public function getCanWorkInsideTTR() {

        return $this->canWorkInsideTTR;
    }

    public function getSourceStoreUuid() {

        return $this->sourceStoreUuid;
    }

    public function getCurrentAmountOfOrders() {

        return $this->currentAmountOfOrders;
    }

    public function getCurrentTime() {

        return $this->currentTime;
    }

    public function getCurrentLength() {

        return $this->currentLength;
    }

    public function getCurrentWeight() {

        return $this->currentWeight;
    }

    public function getCurrentPosition() {

        return $this->currentPosition;
    }

    public function getOrdersIdArray() {

        return $this->ordersIdArray;
    }


    public function setCurrentPosition($position) {

        $this->currentPosition = $position;
    }

    public function setOrdersIdArray($id) {

        array_push($this->ordersIdArray, $id);
    }

    public function increaseCurrentTimeBy($time) {

        $this->currentTime += $time;
    }

    public function increaseCurrentAmountOfOrders() {

        $this->currentAmountOfOrders ++;
    }

    public function increaseCurrentLengthBy($length) {

        $this->currentLength += $length;
    }

    public function increaseCurrentWeightBy($weight) {

        $this->currentWeight += $weight;
    }


    public function areAllFieldsValid() {

        if (!empty($this->amountOfOrders)  && (!empty($this->startWorkTime)) && (!empty($this->sourceStoreUuid))
            && (!empty($this->maxLength)) && (!empty($this->maxWeight)) && (!empty($this->limitLength))
            && (!empty($this->limitWeight))  && !empty($this->finishWorkTime)) {

            return true;
        } else {

            return false;
        }
    }


    function jsonSerialize() {

        return array(
            "uuid" => $this->uuid,
            "aOO" => $this->amountOfOrders,
            "sWT" => $this->startWorkTime,
            "fWT" => $this->finishWorkTime,
            "mL" => $this->maxLength,
            "mW" => $this->maxWeight,
            "lL" => $this->limitLength,
            "lW" => $this->limitWeight,
            "cWIT" => $this->canWorkInsideTTR,
            "sSU" => $this->sourceStoreUuid,
        );
    }
}

class Warehouse implements JsonSerializable{

    private
        $uuid,
        $address,
        $addressLongitude,
        $addressLatitude;


    public function Warehouse($warehouse) {

        $this->uuid = "w-".(string)$warehouse->uuid;
        $this->address = (string)$warehouse->contact["address"];
    }


    public function getUuid() {

        return $this->uuid;
    }

    public function getAddress() {

        return $this->address;
    }

    public function getAddressLatitude() {

        return $this->addressLatitude;
    }

    public function getAddressLongitude() {

        return $this->addressLongitude;
    }

    public function getAddressCoordinates() {

        $addressCoordinates = $this->addressLatitude.", ".$this->addressLongitude;
        return $addressCoordinates;
    }


    public function setAddressLongitudeLatitude($longitude, $latitude) {

        $this->addressLongitude = round($longitude, 6);
        $this->addressLatitude = round($latitude, 6);
    }


    function jsonSerialize() {

        return array(
            "uuid" => $this->uuid,
            "address" => $this->address,
            "addressLongitude" => $this->addressLongitude,
            "addressLatitude" => $this->addressLatitude
        );
    }
}

class Order {

    private
        $uuid,
        $name,
        $deliveryFromDay,
        $deliveryFromHour,
        $deliveryToDay,
        $deliveryToHour,
        $deliveryToTime,
        $sourceStoreUuid,
        $latitude,
        $longitude,
        $address,
        $yandexAddress,

        $maxWeight,
        $maxLength,
        $wholeOrderWeight,
        $wholeOrderLength,

        $courier,
        $itineraryListNumber,

        $isInsideTTR,

        $isTaken,

        $goodOrderArray;

    public function Order($order) {

        $this->uuid = $order->getUuid();
        $this->name = $order->getName();
        $this->deliveryFromDay = $order->getDeliveryFromDay();
        $this->deliveryFromHour = $order->getDeliveryFromHour();
        $this->deliveryToDay = $order->getDeliveryToDay();
        $this->deliveryToHour = $order->getDeliveryToHour();
        $this->maxWeight = $order->getMaxWeight();
        $this->maxLength = $order->getMaxLength();
        $this->wholeOrderLength = $order->getWholeOrderLength();
        $this->wholeOrderWeight = $order->getWholeOrderWeight();
        $this->sourceStoreUuid = $order->getSourceStoreUuid();
        $this->latitude = round($order->getAddressLatitude(), 6);
        $this->longitude = round($order->getAddressLongitude(), 6);
        $this->address = $order->getAddress();
        $this->yandexAddress = $order->getYandexAddress();
        $this->goodOrderArray = $order->getGoodOrderArray();

        $this->itineraryListNumber = 0;

        $this->isTaken = false;

        if ($this->deliveryToHour < 4) {

            $this->deliveryToHour += 24;
        }

        $this->deliveryToTime = $order->getDeliveryToTime();
        $deliveryTimeArray1 = explode("T", $this->deliveryToTime);      // Y-m-dTh:i:s.uP
        $deliveryTimeArray2 = explode("+", $deliveryTimeArray1[1]);     // h:i:s.uP
        $this->deliveryToTime = (string)$deliveryTimeArray1[0]." ".(string)$deliveryTimeArray2[0];
    }


    public function setIsTaken() {

        $this->isTaken = true;
    }

    public function setCourier($courierUuid) {

        $this->courier = $courierUuid;
    }

    public function setItineraryListNumber($number) {

        $this->itineraryListNumber = $number;
    }

    public function setIsInsideTTR($isInsideTTR) {

        $this->isInsideTTR = $isInsideTTR;
    }


    public function getUuid() {

        return $this->uuid;
    }

    public function getName() {

        return $this->name;
    }

    public function getDeliveryFromHour() {

        return $this->deliveryFromHour;
    }

    public function getDeliveryToTime() {

        return $this->deliveryToTime;
    }

    public function getDeliveryToHour() {

        return $this->deliveryToHour;
    }

    public function getSourceStoreUuid() {

        return $this->sourceStoreUuid;
    }

    public function getMaxWeight() {

        return $this->maxWeight;
    }

    public function getWholeOrderWeight() {

        return $this->wholeOrderWeight;
    }

    public function getMaxLength() {

        return $this->maxLength;
    }

    public function getWholeOrderLength() {

        return $this->wholeOrderLength;
    }

    public function getCourier() {

        return $this->courier;
    }

    public function getItineraryListNumber() {

        return $this->itineraryListNumber;
    }

    public function getIsTaken() {

        return $this->isTaken;
    }

    public function getIsInsideTTR() {

        return $this->isInsideTTR;
    }

    public function getLatitude() {

        return $this->latitude;
    }

    public function getLongitude() {

        return $this->longitude;
    }

    public function getAddress() {

        return $this->address;
    }

    public function getYandexAddress() {

        return $this->yandexAddress;
    }

    public function getGoodOrderArray() {

        return $this->goodOrderArray;
    }

    public function getAddressCoordinates() {

        $addressCoordinates = $this->latitude.", ".$this->longitude;
        return $addressCoordinates;
    }
}