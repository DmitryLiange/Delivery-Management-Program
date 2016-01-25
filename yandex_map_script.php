<?php
if (isset($_POST["jsonAddressArray"])) {

    $addressArray = json_decode($_POST["jsonAddressArray"]);
}
?>

<html>
<head>
    <title>Путь курьера</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
    <script language="javascript">

        function init() {
            var myMap = new ymaps.Map('map', {
                    center: [55.751255, 37.615096],
                    zoom: 12,
                    controls: []
                }),
                multiRoute = new ymaps.multiRouter.MultiRoute({
                    referencePoints: [
                        <?php
                        $addressArray = $GLOBALS["addressArray"];
                        foreach ($addressArray as $address) {

                            echo "[".$address."], ";
                        }
                        ?>
                    ],
                    params: {
                        results: 1
                    }
                }, {
                    options: {
                        boundsAutoApply: true
                    }
                });
            myMap.geoObjects.add(multiRoute);
        }

        ymaps.ready(init);


    </script>
    <style>
        #map {
            width: 96%; height: 96%; padding: 0; margin: 5pt 5pt; border: solid 3pt #666;
        }
        html, body {
            width: 100%; height: 100%; padding: 0; margin: 0;
        }
    </style>
</head>
<body>
<div id="map"></div>
</body>
</html>