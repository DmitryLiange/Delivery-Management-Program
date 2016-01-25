<?php

// Функция для сравнения заказов по времени доставки
function cmp($order1, $order2) {

    $order1Time = $order1->getDeliveryToHour();
    $order2Time = $order2->getDeliveryToHour();

    if ($order1Time >= 4) {

        if ($order2Time >= 4) {

            return ($order1Time == $order2Time) ? 0 : (($order1Time > $order2Time) ? 1 : -1);
        } else {

            return -1;
        }
    } else {

        if ($order2Time >= 4) {

            return 1;
        } else {

            return ($order1Time == $order2Time) ? 0 : (($order1Time > $order2Time) ? 1 : -1);
        }
    }
}

// Функция для сравнения курьеров по времени прибытия/убытия на работу/с работы
function cmp1($courier1, $courier2) {

    $courier1StartTime = $courier1->getStartWorkTime();
    $courier2StartTime = $courier2->getStartWorkTime();

    $courier1FinishTime = $courier1->getFinishWorkTime();
    $courier2FinishTime = $courier2->getFinishWorkTime();

    if ($courier1StartTime == $courier2StartTime) {

        if ($courier1FinishTime == $courier2FinishTime) {

            return 0;
        } else {

            return ($courier1FinishTime > $courier2FinishTime) ? 1 : -1;
        }
    } else {

        return ($courier1StartTime > $courier2StartTime) ? 1 : -1;
    }
}

// Функция для сравнения заказов для одного курьера по нумерации в списке курьера
function cmp2($orderId1, $orderId2) {

    $newOrderArray = $GLOBALS["newOrderArray"];
    $order1 = $newOrderArray[$orderId1];
    $order2 = $newOrderArray[$orderId2];

    $order1Number = $order1->getItineraryListNumber();
    $order2Number = $order2->getItineraryListNumber();

    return ($order1Number == $order2Number) ? 0 : (($order1Number > $order2Number) ? 1 : -1);
}