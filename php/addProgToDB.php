<?php	
	
	// Prepare the SQL
	$the_programme = array(
		'programme_title' => $programme['title'],
		'programme_details' => $programme['details']
	);
	
	// If we're editing a programme
	if( $editArray['flag'] == 'yes' ) {
	
		$prog_formats = array( '%s', '%s' );
		
		// Where statement for SQL query
		$where = array(
			'programme_id' => $editArray['id']
		);
		$formats_where = array( '%d' );

		// Update the programme
		$wpdb->update( CP_PROGRAMMES, $the_programme, $where, $prog_formats, $formats_where );

		$insertID = $editArray['id'];
		
	// Otherwise add a new programme
	} else {
		
		// Add date programme was created
		$the_programme['programme_created'] = current_time( 'mysql' );
		$prog_formats = array( '%s', '%s', '%s' );
		
		// Add the new programme
		$wpdb->insert( CP_PROGRAMMES, $the_programme, $formats );

		// Get the ID of the new programme
		$insertID = $wpdb->insert_id;
		
	}
