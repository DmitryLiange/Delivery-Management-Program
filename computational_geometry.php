<?php

class Point {

    private
        $x,
        $y;

    public function Point($x, $y) {

        $this->x = $x;
        $this->y = $y;
    }

    public function getX() {

        return $this->x;
    }

    public function getY() {

        return $this->y;
    }
}

class Segment {

    private
        $point1,
        $point2;

    public function Segment($point1, $point2) {

        $this->point1 = $point1;
        $this->point2 = $point2;
    }

    public function getPoint1() {

        return $this->point1;
    }

    public function getPoint2() {

        return $this->point2;
    }

    public function getDirection($point){

        return ($point->getX() - $this->point1->getX()) * ($this->point2->getY() - $this->point1->getY())
        - ($point->getY() - $this->point1->getY()) * ($this->point2->getX() - $this->point1->getX());
    }

    public function isOnSegment($point) {

        if ((min($this->point1->getX(), $this->point2->getX()) <= $point->getX()
                && $point->getX() <= max($this->point1->getX(), $this->point2->getX()))
            && (min($this->point1->getY(), $this->point2->getY()) <= $point->getY()
                && $point->getY() <= max($this->point1->getY(), $this->point2->getY()))) {

            return true;
        }

        return false;
    }
}


// Функция подсчета расстояния между двумя точками в метрах по заданным для них координатам
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $lat1 *= M_PI / 180;
    $lat2 *= M_PI / 180;
    $lon1 *= M_PI / 180;
    $lon2 *= M_PI / 180;

    $d_lon = $lon1 - $lon2;

    $slat1 = sin($lat1);
    $slat2 = sin($lat2);
    $clat1 = cos($lat1);
    $clat2 = cos($lat2);
    $sdelt = sin($d_lon);
    $cdelt = cos($d_lon);

    $y = pow($clat2 * $sdelt, 2) + pow($clat1 * $slat2 - $slat1 * $clat2 * $cdelt, 2);
    $x = $slat1 * $slat2 + $clat1 * $clat2 * $cdelt;

    return round(atan2(sqrt($y), $x) * 6372795);
}


$kremlCoordinates = array(
    "x" => 55.751255,
    "y" => 37.615096
);
$kremlPoint = new Point($kremlCoordinates["x"], $kremlCoordinates["y"]);
$TTR = array(
    1 => array(
        "x" => 55.767839,
        "y" => 37.537905
    ),
    2 => array(
        "x" => 55.752535,
        "y" => 37.531167
    ),
    3 => array(
        "x" => 55.739753,
        "y" => 37.534772
    ),
    4 => array(
        "x" => 55.733991,
        "y" => 37.542926
    ),
    5 => array(
        "x" => 55.723286,
        "y" => 37.552024
    ),
    6 => array(
        "x" => 55.715535,
        "y" => 37.575370
    ),
    7 => array(
        "x" => 55.709042,
        "y" => 37.584125
    ),
    8 => array(
        "x" => 55.700997,
        "y" => 37.608844
    ),
    9 => array(
        "x" => 55.701802,
        "y" => 37.614705
    ),
    10 => array(
        "x" => 55.705994,
        "y" => 37.621142
    ),
    11 => array(
        "x" => 55.703208,
        "y" => 37.655947
    ),
    12 => array(
        "x" => 55.711834,
        "y" => 37.673456
    ),
    13 => array(
        "x" => 55.723898,
        "y" => 37.712681
    ),
    14 => array(
        "x" => 55.737491,
        "y" => 37.698055
    ),
    15 => array(
        "x" => 55.746443,
        "y" => 37.699916
    ),
    16 => array(
        "x" => 55.755338,
        "y" => 37.692406
    ),
    17 => array(
        "x" => 55.758505,
        "y" => 37.685120
    ),
    18 => array(
        "x" => 55.763828,
        "y" => 37.685077
    ),
    19 => array(
        "x" => 55.767756,
        "y" => 37.688167
    ),
    20 => array(
        "x" => 55.771070,
        "y" => 37.689454
    ),
    21 => array(
        "x" => 55.777336,
        "y" => 37.679798
    ),
    22 => array(
        "x" => 55.781366,
        "y" => 37.668880
    ),
    23 => array(
        "x" => 55.793046,
        "y" => 37.653376
    ),
    24 => array(
        "x" => 55.794061,
        "y" => 37.648269
    ),
    25 => array(
        "x" => 55.791764,
        "y" => 37.634193
    ),
    26 => array(
        "x" => 55.793239,
        "y" => 37.615868
    ),
    27 => array(
        "x" => 55.791686,
        "y" => 37.574384
    ),
    28 => array(
        "x" => 55.779655,
        "y" => 37.557604
    ),
    29 => array(
        "x" => 55.774382,
        "y" => 37.553226
    ),
    30 => array(
        "x" => 55.773390,
        "y" => 37.546059
    ),
    31 => array(
        "x" => 55.769979,
        "y" => 37.542025
    )
);
$segmentTTRArray = array();
foreach ($TTR as $numberString => $TTRPoint) {

    $number = (int)$numberString;
    $point1 = new Point($TTRPoint["x"], $TTRPoint["y"]);

    if ($number != 31) {

        $point2 = new Point($TTR[$number + 1]["x"], $TTR[$number + 1]["y"]);
    } else {

        $point2 = new Point($TTR[1]["x"], $TTR[1]["y"]);
    }

    $segmentTTRArray[$number] = new Segment($point1, $point2);
}

// Функция проверки пересечения двух отрезков
function doSegmentsIntersect($segment1, $segment2) {

    $d1 = $segment1->getDirection($segment2->getPoint1());
    $d2 = $segment1->getDirection($segment2->getPoint2());
    $d3 = $segment2->getDirection($segment1->getPoint1());
    $d4 = $segment2->getDirection($segment1->getPoint2());

    if ((($d1 > 0 && $d2 < 0) || ($d1 < 0 && $d2 > 0)) && (($d3 > 0 && $d4 < 0) || ($d3 < 0 && $d4 > 0))) {

        return true;
    } else if ($d1 == 0 && $segment1->isOnSegment($segment2->getPoint1())) {

        return true;
    } else if ($d2 == 0 && $segment1->isOnSegment($segment2->getPoint2())) {

        return true;
    } else if ($d3 == 0 && $segment2->isOnSegment($segment1->getPoint1())) {

        return true;
    } else if ($d4 == 0 && $segment2->isOnSegment($segment1->getPoint2())) {

        return true;
    }

    return false;
}

// Функция проверки нахождения точки внутри ТТК
function isInsideTTR($orderPoint) {

    $isInsideTTR = true;
    $orderSegment = new Segment($orderPoint, $GLOBALS["kremlPoint"]);
    $segmentTTRArray = $GLOBALS["segmentTTRArray"];

    foreach ($segmentTTRArray as $segmentTTR) {

        if (doSegmentsIntersect($orderSegment, $segmentTTR)) {

            $isInsideTTR = false;
            break;
        }
    }

    return $isInsideTTR;
}