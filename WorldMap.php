<!DOCTYPE html>
<html>
  <head>
    <?php

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    session_start();


    if (empty($_SESSION["Username"])){
      $_SESSION["Username"] = '';
      $Username = '';
    }
    else{
      $Username = $_SESSION["Username"];
    }

    include 'common-data.php';


    ?>

    <title>WorldMap</title>
    <link rel='stylesheet' href='http://netdna.bootstrapcdn.com/bootstrap/3.0.2/css/bootstrap.min.css'>
    <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css">
    <link rel="stylesheet" href="css/MapStyle.css">
    <!-- The line below is only needed for old environments like Internet Explorer and Android 4.x -->
    <script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=requestAnimationFrame,Element.prototype.classList,URL"></script>
    <script src="https://openlayers.org/en/v4.6.5/build/ol.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
  </head>


  <body>
    <div class="sidenav" id="">
      <div class="center provinceinfo" id='SideTitle'>
        <span style="color: #21334D;">Province Name - </span><span style="color: #21334D;" id='PrvName'>None</span><br>
        <span style="color: #21334D;">Owner: </span><span style="color: #21334D;" id='PrvOwner'>None</span><br>
        <span style="color: #21334D;">Nation: </span><span style="color: #21334D;" id='PrvNation'>None</span>
      </div>

      <div class="center provinceinfoBottom">
        <button onclick="onPlaceSettlementClick();">Place Settlement</button>
      </div>


    </div>
    <div class="main" id="map" class="map"></div>
    <script>

      var Username = '';
      var USMoney = 0;
      var USMoney = 0;
      var FirstTimeLogin = 1;
      var ToggleSettlementPlace = 0;

      onPageOpen();


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
            },
            zIndex: 0
          }),
          SettlementLayer = new ol.layer.Vector({
            name: 'SettlementLayer',
            source: new ol.source.Vector({
              features: getSettlementFeatures(),
              wrapX: false
            }),
            zIndex: 5
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
            color: '#0052d6',
            width: 1
          }),
          fill: new ol.style.Fill({
            color: 'rgba(0,0,255,0.1)'
          })
        }),
        zIndex: 1
      });

      featureOverlay.setZIndex(1);

      var highlight;
      var displayFeatureInfo = function(pixel) {

        var arrFeaturesAtPixel = [];

        //add each feature to the array
        var feature = map.forEachFeatureAtPixel(pixel, function(feature) {
          arrFeaturesAtPixel.push(feature);
        }
        );

        //if there is more than one feature at the clicked point, determine which one is the settlement and return that one
        if (arrFeaturesAtPixel.length > 1) {
          if (arrFeaturesAtPixel[0].get('fetype') == 'Settlement') {
            //console.log('Settlement is 1st');
            feature = arrFeaturesAtPixel[0];
          }
          else {
            //console.log('Settlement is 2nd');
            feature = arrFeaturesAtPixel[1];
          }
        }
        else {
          //console.log('No Settlement at click');
          feature = arrFeaturesAtPixel[0];
        }


        var info = document.getElementById('PrvName');
        if (feature) {
          info.innerHTML = feature.get('name') + " : " + feature.get('fetype');
        } else {
          info.innerHTML = '&nbsp;';
        }

        //highlight the province
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

      map.on('click', function(evt) {

        //get click pixel and define variables
        var pixel = map.getEventPixel(evt.originalEvent);
        var placeCoords;
        var placeProvince;

        //if the click is to drag the map, ignore it
        if (evt.dragging) {
          return;
        }

        displayFeatureInfo(pixel);

        if (ToggleSettlementPlace == 1) {

          //get coordinates to place settlement
          placeCoords = map.getCoordinateFromPixel(pixel);
          placeCoords = ol.proj.toLonLat(placeCoords);
          placeCoords = placeCoords.toString();

          //log coordinates(longitude and latitude) on click
          //console.log(placeCoords);

          //get the province so that we have a province ID to associate with the settlement
          var feature = map.forEachFeatureAtPixel(pixel, function(feature) {
            return feature;
          });

          placeProvince = feature.get('ID');

          //call placeSettlement to check if possible and perform backend duties
          $.post("QueryInsertUpdate.php",
            {
              action: "placeSettlement",
              placeCoords: placeCoords,
              placeProvince: placeProvince
            },
            function(data, status){
              // alert("Data: " + data + "\nStatus: " + status);
              if (data == 'Success'){
                //refresh page (or add feature... but that's more work)
                ToggleSettlementPlace = 0;
                location.reload();
              }
              else {
                console.log(data);
                ToggleSettlementPlace = 0;
              }
            });
        }
      });


    //following function returns an array of features (settlements) to include in the map layer
    function getSettlementFeatures(){
      <?php

        //define settlement data string so we can add the results of our query
        $strSEData = '';

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

            //remove the final | at the end of the data string
            $strSEData = substr($strSEData, 0, -1);

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
      for (x = 0; x < arrSettlementData.length; ++x) {
        //split the contents of the settlement into another array
        var arrSingleSettlementData = arrSettlementData[x].split(";");

        //assign the values out to use later
        var strSEName = arrSingleSettlementData[0];
        var strSECoords = arrSingleSettlementData[1];
        var strSEProvinceID = arrSingleSettlementData[2];
        var strSEColor = '#42ebf4'; 

        if (strSEName == Username) {
          strSEColor = '#0068FF';
          FirstTimeLogin = 0;
        }
        else {
          strSEColor = '#31b240';
        }

        //split the coordinates of the settlement up and convert to numbers from str
        var arrCoords = strSECoords.split(",");
        arrCoords[0] = Number(arrCoords[0]);
        arrCoords[1] = Number(arrCoords[1]);

        //define the ol feature for the settlement - assign the coordinates we got before
        var iconFeature = new ol.Feature({
          geometry: new ol.geom.Point(ol.proj.fromLonLat([arrCoords[0], arrCoords[1]])),
          name: 'Null Island',
          population: 4000,
          rainfall: 500,
          fetype: 'Settlement'
        });

        //create a style for the settlement
        var iconStyle = new ol.style.Style({
          image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
            color: strSEColor,
            crossOrigin: 'anonymous',
            anchor: [0.5, 46],
            anchorXUnits: 'fraction',
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

    function onPageOpen(){
      if ('<?php echo $_SESSION["Username"]; ?>' == '') {
        window.location.href = "index.php";
      }

      <?php
        $strSQLUserData = '';

        //open connection
        $conn = new mysqli($ServerName, $DBUser, $DBPassword, $Database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        //run query
        $result = $conn->query("select * from Users where Username = '$Username'");
        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                //add the results to strSEData so that we can use it later
                $strSQLUserData = $strSQLUserData.$row['Username'].';'.$row['USMoney'].';'.$row['USManpower'].'|';
            }

            //remove the final | at the end of the data string
            $strSQLUserData = substr($strSQLUserData, 0, -1);

        }
        else {
            //echo "0 results";
        }
        $conn->close();
      ?>

      var strUserData = "<?php echo $strSQLUserData; ?>";
      var arrUserData = strUserData.split(";");

      Username = arrUserData[0];
      USMoney = arrUserData[1];
      USManpower = arrUserData[2];

    }

    function onPlaceSettlementClick() {

      if (FirstTimeLogin == 1){
        alert("Click somewhere on the map to place your settlement");
        ToggleSettlementPlace = 1;
      }
      else {
        alert("You've already placed your settlement");
      }

    }

    </script>
  </body>
</html>