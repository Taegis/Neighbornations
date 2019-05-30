<!DOCTYPE html>
<html>
  <head>
    <?php

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    session_start();

    include 'common-data.php';


    ?>

    <title>WorldMap</title>
    <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
    <!-- The line below is only needed for old environments like Internet Explorer and Android 4.x -->
    <script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=requestAnimationFrame,Element.prototype.classList,URL"></script>
    <script src="https://openlayers.org/en/v4.6.5/build/ol.js"></script>
  </head>
  <body>
    <div id="map" class="map"></div>
    <div id="info">&nbsp;</div>
    <script>

      var style = new ol.style.Style({
        fill: new ol.style.Fill({
          color: 'rgba(255, 255, 255, 0.6)'
        }),
        stroke: new ol.style.Stroke({
          color: '#319FD3',
          width: 1
        }),
        text: new ol.style.Text()
      });

      //define the map with specific layers, etc.
      var map = new ol.Map({
        layers: [
          new ol.layer.Vector({
            renderMode: 'image',
            source: new ol.source.Vector({
              url: 'Data/map.geojson',
              format: new ol.format.GeoJSON(),
              wrapX: false
            }),
            style: function(feature) {
              style.getText().setText(feature.get('name'));
              return style;
            }
          }),
          new ol.layer.Vector({
            source: new ol.source.Vector({
              features: getSettlementFeatures(),
              wrapX: false
            })
          })
        ],
        target: 'map',
        view: new ol.View({
          center: [0, 0],
          zoom: 1
        })
      });

      //layer for highlighting features
      var featureOverlay = new ol.layer.Vector({
        source: new ol.source.Vector({
          wrapX: false
        }),
        map: map,
        style: new ol.style.Style({
          stroke: new ol.style.Stroke({
            color: '#f00',
            width: 1
          }),
          fill: new ol.style.Fill({
            color: 'rgba(255,0,0,0.1)'
          })
        })
      });

      var highlight;
      var displayFeatureInfo = function(pixel) {

        var feature = map.forEachFeatureAtPixel(pixel, function(feature) {
          return feature;
        });

        var info = document.getElementById('info');
        if (feature) {
          info.innerHTML = feature.get('ID') + ': ' + feature.get('name');
        } else {
          info.innerHTML = '&nbsp;';
        }

        if (feature !== highlight) {
          if (highlight) {
            featureOverlay.getSource().removeFeature(highlight);
          }
          if (feature) {
            featureOverlay.getSource().addFeature(feature);
          }
          highlight = feature;
        }

      };

      map.on('pointermove', function(evt) {
        if (evt.dragging) {
          return;
        }
        var pixel = map.getEventPixel(evt.originalEvent);
        displayFeatureInfo(pixel);
      });

      map.on('click', function(evt) {
        displayFeatureInfo(evt.pixel);
      });

    //following function returns an array of features (settlements) to include in the map layer
    function getSettlementFeatures(){
      <?php

        //define settlement data string with a starting | so we can add the results of our query
        $strSEData = '|';

        //open connection
        $conn = new mysqli($ServerName, $DBUser, $DBPassword, $Database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        //run query
        $result = $conn->query("select * from Settlements");
        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                  //add the results to strSEData so that we can use it later
                  $strSEData = $strSEData.$row['SEOwner'].';'.$row['SECoordinates'].';'.$row['SEProvince'].'|';
            }

        }
        else {
            echo "0 results";
        }
        $conn->close();
      ?>

      //put the results from our query in the settlements string
      var sqlSettlements = '<?php echo $strSEData; ?>';
      var arrSettlementData = sqlSettlements.split("|");

      //arrFeaturesToReturn will be what is returned in the function
      var arrFeaturesToReturn = [];

      var x;
      
      //loop through each settlement in the array
      for (x = 1; x < arrSettlementData.length - 1; ++x) {
        //split the contents of the settlement into another array
        var arrSingleSettlementData = arrSettlementData[x].split(";");

        //assign the values out to use later
        var strSEName = arrSingleSettlementData[0];
        var strSECoords = arrSingleSettlementData[1];
        var strSEProvinceID = arrSingleSettlementData[2];

        //split the coordinates of the settlement up
        var arrCoords = strSECoords.split(",");

        //define the ol feature for the settlement
        var iconFeature = new ol.Feature({
          geometry: new ol.geom.Point([0, 0]),
          name: 'Null Island',
          population: 4000,
          rainfall: 500
        });

        //create a style for the settlement - using the coordinates we got earlier
        var iconStyle = new ol.style.Style({
          image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
            anchor: [arrCoords[0], arrCoords[1]],
            anchorXUnits: 'pixels',
            anchorYUnits: 'pixels',
            src: 'https://openlayers.org/en/v4.6.5/examples/data/icon.png'
          }))
        });

        //assign the style to the feature
        iconFeature.setStyle(iconStyle);

        //add the feature to our array of features to return
        arrFeaturesToReturn.push(iconFeature);
      }

      return arrFeaturesToReturn;

    }

    getSettlementFeatures();

    </script>
  </body>
</html>