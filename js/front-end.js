(function () {

	var mapCanvas = document.getElementById('map-canvas'),
			name = mapCanvas.getAttribute('data-name'),
			url = mapCanvas.getAttribute('data-url'),
			lat = mapCanvas.getAttribute('data-lat'),
			lng = mapCanvas.getAttribute('data-lng'),
			address = mapCanvas.getAttribute('data-address');

		var mapsURL = ( url !== '' ) ? '<p><a href="' + url + '">' + url + '</a></p>' : '',
		    latLng = new google.maps.LatLng(lat, lng),
				myOptions = {
				zoom: 14,
					center: latLng,
					mapTypeId: google.maps.MapTypeId.ROADMAP
				},
				map = new google.maps.Map(mapCanvas, myOptions),
				contentString = '<div id="content">'
				  + '<h2 id="firstHeading" class="firstHeading">' + name + '</h2>'
				  + '<div id="bodyContent">'
				  + '<p>' + address + '</p>'
				  + mapsURL
				  + '</div>'
				  + '</div>',
				infowindow = new google.maps.InfoWindow({
					content: contentString
				}),
				marker = new google.maps.Marker({
					position: latLng,
					map: map,
				  title: name
				});

	google.maps.event.addListener(marker, 'click', function () {
	  infowindow.open(map, marker);
	});

})();;
