<?php
	
	// Venue vars to add to the DB
	$the_venue = array( 
		'venue_name' => $venue['name'],
		'venue_url' => $venue['url'],
		'venue_address' => $venue['address'],
		'venue_lat' => $venue['lat'],
		'venue_lng' => $venue['lng']
	);
	
	// If we're editing a venue
	if( $editArray['flag'] == 'yes' ) {

		// Prepare the SQL
		$venue_formats = array( '%s', '%s', '%s', '%s', '%s' );
		
		// Where statement for SQL query
		$where = array(
			'venue_id' => $editArray['id']
		);
		$where_formats = array( '%d' );

		// Update the programme
		$wpdb->update( CP_VENUES, $the_venue, $where, $venue_formats, $where_formats );
		
		$insertID = $editArray['id'];
		
	} else {
	
		// Add the date created to new venues	
		$the_venue['venue_created'] = current_time( 'mysql' );
		$venue_formats = array( '%s', '%s', '%s', '%s', '%s', '%s' );

		$wpdb->insert( CP_VENUES, $the_venue, $venue_formats );

		// Get the ID of the new venue
		$insertID = $wpdb->insert_id;
			
	}