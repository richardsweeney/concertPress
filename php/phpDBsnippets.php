<?php		


/**
	* Helper function: Date and time error checks & sanitization.
	* 
	* @return array MySQL datetime start and end times
	*/

	function checkDateAndTime() {
	
		$concertDate = array();
		
		// If no date is not set
		if( empty( $_POST['concert']['date'] ) ) {
			
			// Set error flag and message
			$this->errors['noDate'] = true;
			$this->errors['messages'][] = "You must set a date for the event";
		
		} else {
			
			// If the date is not properly formatted
			if( !preg_match( '!\d{4}-\d{2}-\d{2}!', $_POST['concert']['date'] ) ) {
				
				// Set error flag and message
				$this->errors['wrongDate'] = true;
				$this->errors['messages'][] = "Please set the date in the format <strong>YYYY-MM-DD</strong>";
		
			} else {
				
				// Otherwise convert the date to a more human-friendly date + save the value
				$date = $_POST['concert']['date'];
				
			}
			
			if( !empty( $_POST['concert']['endDate'] ) ) {
				
				// Security check
				if( !preg_match( '!\d{4}-\d{2}-\d{2}!', $_POST['concert']['endDate'] ) ) {
				
					// Set error flag and message
					$this->errors['wrongDate'] = true;
					$this->errors['messages'][] = "Please set the date in the format <strong>YYYY-MM-DD</strong>";
				
				} else {	
					
					// Set the end date to a MySQL timestamp
					$concertDate['endDate'] = $_POST['concert']['endDate'] . ' 00:00:00';
				
				}
			
			} else {
				
				// If the end date was not used set this value to null
				$concertDate['endDate'] = null;
			
			}
			
		}
		
		if( isset( $_POST['concert']['hour'] ) && isset( $_POST['concert']['min'] ) ) {
		
			// If the hour or minute values are not numeric
			if( !preg_match( '!\d{2}!', $_POST['concert']['hour'] ) && !preg_match( '!\d{2}!', $_POST['concert']['min'] ) ) {
				
				// Set error message
				$this->errors['messages'][] = "The time of the concert is not properly formatted";
				
				// Bad stuff going on here
				wp_die( "You don't have permission to do that!" );
			
			} else {
				
				// Otherwise, save the values
				$hour = (int) $_POST['concert']['hour'];
				$min = (int) $_POST['concert']['min'];
				
				// If the date var has been set, convert date, time and min
				// values to a MySQL timestamp (YYYY-MM-DD HH:MM:SS)
				if( isset( $date ) ) {
					$concertDate['dateTime'] = $date . ' ' . $hour . ':' . $min . ':00';
				}
			
			}
	
		}
		
		// Return the array
		return $concertDate;
	
	}
	


			
 /**
 	* Helper function: Venue error checks & sanitization.
 	* Converts address to latitude & longitude coordinates if provided
 	* 
 	* @return array sanitized array of values
 	*/
	function checkVenue( $editFlag ) {
	  
	  global $wpdb;
	  
		// Venue array
		$venue = array( 'lat' => 0, 'lng' => 0 );
		
		// Set and sanitize
		$venue['venue-name'] = trim( wp_filter_nohtml_kses( $_POST['venue']['venue-name'] ) );
		$venue['venue-url'] = trim( esc_url_raw( $_POST['venue']['venue-url'] ) );
		$venue['venue-address'] = trim( wp_filter_nohtml_kses( $_POST['venue']['venue-address'] ) );
		
		// If no new venue is added
		if( empty( $venue['venue-name'] ) ) {
			
				// Check if they used the select box
			if( !empty( $_POST['select-venue']['venue-id'] ) ) {
				
				// They used the select list, so set the venue_id = the id of the selected venue
				$venue['id'] = (int) $_POST['select-venue']['venue-id'];
			
			// The select venue hasn't been used.
			} else {
				
				// The venue name is empty - set error flag & message
				$this->errors['venue']['noVenue'] = true;
				$this->errors['messages'][] = 'Please add/select a venue.';
			
			}

		}
		

		// If there is a venue set
		if( $editFlag == 'no' && !$this->errors['venue']['noVenue'] ) {
		
			// Not relevanat if the select list was used
			if( !empty( $venue['venue-name'] ) ) {
			
				$venueExists = $this->checkIfVenueExists( $venue['venue-name'] );
				
				if( $venueExists == 'yes' ) {
					
					// Venue name already exists
					$this->errors['venue']['venueExists'] = true;
					$this->errors['messages'][] = "The venue <strong> {$venue['venue-name']} </strong> already exisits.";
					
				}							
										
			}
			
			if( $venueExists == 'no' ) {
				
				// If there's an address given
				if( !empty( $venue['venue-address'] ) ) {
				
					// Geocode the address
					$venue_ll = $this->gmapsGeocode( $venue['venue-address'] );
					
					// If it all works ok
					if( !empty( $venue_ll ) ) {
						
						// Save the values to the $venue array
						$venue['lat'] = $venue_ll['lat'];
						$venue['lng'] = $venue_ll['lng'];
						
					}
					
				}
				
			}
			
		}
		
		return $venue;

	}
	
	
	
	
 /**
 	* Helper function: Programme error checks & sanitization.
 	* 
 	* @return array sanitized array of values
 	*/
	function checkProgramme( $editFlag = false ) {
	  
	  global $wpdb;
	  
		$programme = array();
		
		$programme['programme-title'] = ( !empty( $_POST['programme']['programme-title'] ) ) ? trim( wp_filter_nohtml_kses( $_POST['programme']['programme-title'] ) ) : '';
		$programme['programme-details'] = ( !empty( $_POST['programme']['programme-details'] ) ) ? wp_kses_post( $_POST['programme']['programme-details'] ) : '';
		
		// Check if programme title is set
		if( empty( $programme['programme-title'] ) ) {

			// If not, check if they used the select box
			if( !empty( $_POST['select-programme']['programme-id'] ) ) {

				// They used the select list, so set the proramme_id = the id of the selected programme
				$programme['id'] = (int) $_POST['select-programme']['programme-id'];
				
			} else {
				
				// There is no programme set
				$this->errors['programme']['noProgramme'] = true;
				$this->errors['messages'][] = 'Please add/select a programme.';
			
			}
		
		}


		// If there is a programme set				
		if( !$editFlag && !$this->errors['programme']['noProgramme'] ) {
		
			// Not relevanat if the select list was used
			if( !empty( $programme['programme-title'] ) ) {
				
				// Get programme names from the DB
				$allProgs = "SELECT programme_title FROM " . CP_PROGRAMMES;
				$allProgs = $wpdb->get_results( $allProgs , ARRAY_A );
				
				// Loop through results
				foreach( $allProgs as $allProg ) {
				
					// Check new programme name agains existing names
					if( strtolower( $allProg['programme_title'] ) == strtolower( $programme['programme-title'] ) ) {
						
						// Programme name already exists
						$this->errors['programme']['programmeExists'] = true;
						$this->errors['messages'][] = 'The programme <strong>' . $programme['programme-title'] . '</strong> already exists.';
						
						// Exit the loop
						break;
						
					}
					
				}
				
			}
			
		}
		
		return $programme;
		
	}
	
	
