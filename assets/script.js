/**
 * Load All maps
 */
function wpuinsertmaps_init() {
    var $elements = document.querySelectorAll('.wpuinsertmaps-element');
    for (var i = 0, len = $elements.length; i < len; i++) {
        wpuinsertmaps_load($elements[i]);
    }
}

/**
 * Load a single map
 * @param  {object} $el DOM Element
 */
function wpuinsertmaps_load($el) {
    var markers = JSON.parse(JSON.parse($el.getAttribute('data-map')));
    if (!markers[0]) {
        return;
    }
    var latlngbounds = new google.maps.LatLngBounds(),
        infoWindow = new google.maps.InfoWindow(),
        map = new google.maps.Map($el, {
            center: {
                lat: parseFloat(markers[0].lat, 10),
                lng: parseFloat(markers[0].lng, 10)
            },
            zoom: 8
        });

    // Insert each marker
    for (var i in markers) {
        wpuinsertmaps_marker(markers[i], map, infoWindow, latlngbounds);
    }

    // Center map and adjust Zoom based on the position of all markers.
    map.setCenter(latlngbounds.getCenter());
    map.fitBounds(latlngbounds);
}

/**
 * Insert a marker
 * @param  {object} data         marker information
 * @param  {object} map          map object
 * @param  {object} infoWindow   infowindow object
 * @param  {object} latlngbounds bounds object
 */
function wpuinsertmaps_marker(data, map, infoWindow, latlngbounds) {
    var myLatlng = new google.maps.LatLng(data.lat, data.lng),
        marker = new google.maps.Marker({
            map: map,
            position: myLatlng,
            title: data.name
        });

    // Add an infoWindow when click happens
    google.maps.event.addListener(marker, "click", function(e) {
        infoWindow.setContent("<div><a target='_blank' style='text-decoration:underline;' href='https://www.google.fr/maps?q=" + data.lat + "," + data.lng + "'>" + data.name + "</a></div>");
        infoWindow.open(map, marker);
    });

    // Extend bounds to the marker position
    latlngbounds.extend(marker.position);
}
