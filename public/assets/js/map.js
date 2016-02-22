var map;
        var geocoder;
        function initialize() {
            var mapOptions = {
                zoom: 8,
                center: new google.maps.LatLng(51.471162,-0.452371)
            };
            map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions);
        }

        function getInfo() {
            drawInfo("EGLL");
            drawInfo("EGGW");
             drawInfo("EGLF");
             drawInfo("EGHI");
             drawInfo("EGKA");
             drawInfo("EGMD");
             drawInfo("EGMC");
        }

        google.maps.event.addDomListener(window, 'load', initialize);
        var openedInfoWindow;

        function drawInfo(icao) {

            $.ajax({
                        dataType: 'json',
                        url: "/soap?icao="+icao
                    })
                    .done(function( data ) {
                        data = JSON.parse(JSON.stringify(data));

                        jQuery.each(data, function(index, item) {
                            var myLatLng = {lat: parseFloat(item.lat), lng: parseFloat(item.lng)};

                            var marker = new google.maps.Marker({
                                position: myLatLng,
                                map: map,
                                title: 'Hello World!',
                                icon: "/assets/img/warning-icon-th.png"
                            });


                            var infoWindow = new google.maps.InfoWindow({
                                content: item.message
                            });

                            google.maps.event.addListener(marker, 'click', function() {
                                if (typeof openedInfoWindow !== 'undefined') {
                                    openedInfoWindow.close();
                                }
                                infoWindow.open(map,marker);
                                openedInfoWindow = infoWindow;
                            });

                        })});
            return false;
        }