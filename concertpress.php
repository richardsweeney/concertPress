<?php
/*
Plugin Name: ConcertPress
Plugin URI: http://richardsweeney.com/portfolio-item/concertpress/
Description: A concert diary plugin for classical musicians
Version: 1.1.1
Author: Richard Sweeney
Author URI: http://richardsweeney.com/
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/



class RPS_ConcertPress {

	// Pagination array
	public $pagination = array(), $numResultsPerPage;

 /**
 	* Constructor function. Contains all WP hooks and actions
 	*/
	public function __construct(){

		add_action( 'init', array( &$this, 'defineConstants' ) );

		add_action( 'template_redirect', array( &$this, 'addCSS' ) );
		add_action( 'admin_menu', array( &$this, 'addAdminPages' ) );

		register_activation_hook( __FILE__, array( &$this, 'activatePlugin' ) );


		/********************** Ajax methods ********************/

		/* Add events, programmes and venues */
		add_action( 'wp_ajax_new-event-ajax', array( &$this, 'ajax_process_new_event' ) );
		add_action( 'wp_ajax_new-programme-ajax', array( &$this, 'ajax_process_new_programme' ) );
		add_action( 'wp_ajax_new-venue-ajax', array( &$this, 'ajax_process_new_venue' ) );

		/* Delete progs or venues */
		add_action( 'wp_ajax_check-prog-venue-associations', array( &$this, 'ajax_check_prog_venue_associations' ) );
		add_action( 'wp_ajax_delete-event-prog-venue-ajax', array( &$this, 'ajax_delete_event_prog_or_venue' ) );

		/* Refresh after AJAX */
		add_action( 'wp_ajax_redraw-tables', array( &$this, 'ajax_redraw_tables' ) );

		/* Get / set options */
		add_action( 'wp_ajax_get-options', array( &$this, 'getOptions' ) );
		add_action( 'wp_ajax_save-options', array( &$this, 'saveOptions' ) );

		/* Add shortcode to echo the events to the front-end */
		add_shortcode( 'cpevents', array( &$this, 'printEvents' ) );
		add_action( 'wp_before_admin_bar_render', array( &$this, 'add_link_to_menu_bar' ) );

		/* Add widget */
		add_action( 'widgets_init', array($this, 'register_widget') );

	}



 /**
 	* Define constants for use in the script
 	*/
	public function defineConstants() {

		global $wpdb;

		define( 'CONCERTPRESS_VERSION', '1.0' );
		define( 'CP_EVENTS',  $wpdb->prefix . "concertpress_events" );
		define( 'CP_PROGRAMMES', $wpdb->prefix . "concertpress_programmes" );
		define( 'CP_VENUES', $wpdb->prefix . "concertpress_venues" );
		define( 'WEBSITE_URL', get_bloginfo('url') );
		define( 'CP_URL',  plugin_dir_url( __FILE__ ) );

		// Add the option if it's not set
		if( get_option( 'rps_cp_num_results' ) === false ) {
			$result = 10;
			add_option( 'rps_cp_num_results', $result );
		} else {
			$result = get_option( 'rps_cp_num_results' );
		}
		$this->numResultsPerPage = $result;

	}



 /**
  * On Plugin Activation
  */
	public function activatePlugin() {

		// Register uninstall hook
		register_uninstall_hook( __FILE__, array( &$this, 'uninstallPlugin' ) );

		// Create the tables
		global $wpdb;

		// Create events table
		$c_sql = "CREATE TABLE IF NOT EXISTS " .  $wpdb->prefix . "concertpress_events (
			event_id mediumint(9) NOT NULL AUTO_INCREMENT,
			date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
			enddate DATETIME DEFAULT '0000-00-00 00:00:00',
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			multidate mediumtext NOT NULL,
			venue_id mediumint(9) NOT NULL,
			prog_id mediumint(9) NOT NULL,
			PRIMARY KEY (event_id)
			);";
		$wpdb->query( $c_sql );


		// Create programmes table
		$p_sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "concertpress_programmes (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			title mediumtext NOT NULL,
			details longtext NOT NULL,
			PRIMARY KEY (id)
			);";
		$wpdb->query( $p_sql );


		// Create venues table
		$v_sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "concertpress_venues (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			name mediumtext NOT NULL,
			url mediumtext NOT NULL,
			address mediumtext NOT NULL,
			lat DOUBLE DEFAULT NULL,
			lng DOUBLE DEFAULT NULL,
			PRIMARY KEY (id)
			);";
		$wpdb->query( $v_sql );

	}



 /**
  * On Plugin Uninstall
  */
	function uninstallPlugin() {

		global $wpdb;

		delete_option( 'rps_cp_num_results' );

		// Delete the Extra Tables on unistall
		$sql = "DROP TABLE " . CP_EVENTS . ", " . CP_VENUES . ", " . CP_PROGRAMMES . ";";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );

	}



 /**
  * Add the various admin pages
  */
	public function addAdminPages() {

		// Create an array to store all the pages
		$pages = array();

		// Add the pages & push them to the array
		$pages[] = add_menu_page( 'ConcertPress Settings Page', 'ConcertPress', 'edit_posts', __FILE__, array( &$this, 'echoSettingsPage' ), '', 26 );
		$pages[] = add_submenu_page( __FILE__, 'Events', 'Events', 'edit_posts', 'events', array( &$this, 'echoEventsPage' ) );
		$pages[] = add_submenu_page( __FILE__, 'Programmes', 'Programmes', 'edit_posts', 'programmes', array( &$this, 'echoProgrammesPage' ) );
		$pages[] = add_submenu_page( __FILE__, 'Venues', 'Venues', 'edit_posts', 'venues', array( &$this, 'echoVenuesPage' ) );
		$pages[] = add_submenu_page( __FILE__, 'Archives', 'Archives', 'edit_posts', 'archives', array( &$this, 'echoArchivesPage' ) );

		// Add my JS and CSS only to my plugin pages
		foreach( $pages as $page ) {
			add_action( 'load-' . $page, array( &$this, 'addJS' ) );
			add_action( 'load-' . $page, array( &$this, 'addCSS' ) );
		}

		// Add maps to the venues page
		add_action( 'load-' . $pages[3], array( &$this, 'addMaps' ) );

	}


 /**
  * Add JavaScript to admin header
  */
	public function addJS() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script('concertpress_js', CP_URL . 'js/cp-js.min.js', array( 'jquery' ), 1.0 );
		$data = array(
			'plugin_url' => CP_URL,
			'nonce' => wp_create_nonce( 'cp-check' ),
			'numResultsPerPage' => $this->numResultsPerPage
		);
		wp_localize_script( 'concertpress_js', 'phpvars', $data );

	}


	/* Add google maps */
	public function addMaps(){
		wp_enqueue_script( 'maps', 'http://maps.google.com/maps/api/js?sensor=false' );
	}



 /**
  * Add CSS to admin header
  */
	public function addCSS() {

		wp_enqueue_style( 'concertpress_css', CP_URL . 'css/cp-css.css' );
		if( is_admin() ) {
		 	wp_enqueue_style( 'jquery_ui_smoothness.css', CP_URL . 'css/jquery-ui.css' );
	  }

	}

/**
 * AJAX error checking and submit for new event
 */
	public function ajax_process_new_event() {

		global $wpdb;

		check_ajax_referer( 'cp-check' );

		// Our response to feed back to the AJAX request
		$response = array(
			'prog' => array(
				'id' => ''
			),
			'venue' => array(
				'id' => ''
			),
			'event' => array(
				'id' => ''
			)
		);

		// Get flags for venue & prog select list flags
		$venueSelectFlag = (int) $_POST['venueSelectFlag'];
		$progSelectFlag = (int) $_POST['progSelectFlag'];


		/******************************
				 \\  The Programme //
		*******************************/


		// If the select list was used
		if( $progSelectFlag == 1 ){

			$response['prog']['id'] = (int) $_POST['prog_select_id'];
			$response['prog']['result'] = 1;
			$progExists = 'no';

		} else {

			$progExists = $this->checkIfProgExists( $_POST['prog']['title'] );
			$response['prog']['result'] = ($progExists == 'yes') ? 'exists' : '';

		}


		/******************************
				   \\  The Venue //
		*******************************/


		// If the select list was used
		if( $venueSelectFlag == 1 ) {

			$response['venue']['id'] = (int) $_POST['venue_select_id'];
			$response['venue']['result'] = 1;
			$venueExists = 'no';

		} else {

			$venueExists = $this->checkIfVenueExists( $_POST['venue']['name'] );
			$response['venue']['result'] = ($venueExists == 'yes') ? 'exists' : '';

		}


		/******************************
			  	 \\  The Event //
		*******************************/


		// If neither the programme nor the venue exist:
		// add them to the database
		if( $progExists == 'no' && $venueExists == 'no' ) {

			if( $progSelectFlag != 1 ) {

				$addProg = $this->addUpdateProg( $_POST['prog'] );
				$response['prog']['id'] = $addProg['id'];
				$response['prog']['result'] = $addProg['result'];

			}

			if( $venueSelectFlag != 1 ) {

				$addVenue = $this->addUpdateVenue( $_POST['venue'] );
				$response['venue']['id'] = $addVenue['id'];
				$response['venue']['result'] = $addVenue['result'];

			}

		}

		$editArray = array(
			'flag' => trim( wp_filter_nohtml_kses( $_POST['editflag'] ) ),
			'id' => (int) trim( $_POST['edit_id'] )
		);

		// If the responses for the venue & programme are ok
		if( $response['prog']['result'] === 1 && $response['venue']['result'] === 1 ){

			$addEvent = $this->addUpdateEvent( $response['prog']['id'], $response['venue']['id'], $_POST['event'], $editArray );

			// Get insert ID
			$response['event']['id'] = $addEvent['id'];

			// 0 = unchanged, 1 = ok, 'error' = error, 'exists' = event exists
			$response['event']['result'] = $addEvent['result'];

			// Prepare a date to send back to AJAX function
			$response['date'] = date( 'jS F Y', strtotime( $addEvent['date'] ) );

		} else {

			$response['event']['result'] = '';

		}



		/******************************
			   \\  The Response //
		*******************************/


		// 'Added' or 'updated' flag to add to our feedback message
		$response['addUpdate'] = ( $editArray['flag'] == 'yes' ) ? 'updated' : 'added';

		$response = json_encode( $response );
	  header( "Content-Type: application/json" );
  	echo $response;
		die();

	}




 /**
 	* AJAX function to add new programmes to the DB
 	*/
	public function ajax_process_new_programme() {

		// Security check
		check_ajax_referer( 'cp-check' );

		// Check if we're editing a programme
		$editArray = array(
			'flag' => trim( wp_filter_nohtml_kses( $_POST['editflag'] ) ),
			'id' => (int) trim( wp_filter_nohtml_kses( $_POST['edit_id'] ) )
		);

		$response = array(
			'addUpdate' => ( $editArray['flag'] == 'yes' ) ? 'updated' : 'added',
			'id' => ''
		);

		if( $editArray['flag'] == 'no' ) {
			$progExists = $this->checkIfProgExists( $_POST['prog']['title'] );
		} else {
			$progExists = 'no';
		}

		if( $progExists == 'no' ) {

			$addProg = $this->addUpdateProg( $_POST['prog'], $editArray );
			$response['result'] = $addProg['result'];
			$response['id'] = $addProg['id'];

		} else {

			$response['result'] = 'exists';

		}

  	$response = json_encode( $response );
	  header( "Content-Type: application/json" );
  	echo $response;
		die;

	}



 /**
 	* AJAX function to add new venues to the DB
 	*/
	public function ajax_process_new_venue() {

		// Security check
		check_ajax_referer( 'cp-check' );

		// Check if we're editing a venue
		$editArray = array(
			'flag' => trim( wp_filter_nohtml_kses( $_POST['editflag'] ) ),
			'id' => (int) trim( wp_filter_nohtml_kses( $_POST['edit_id'] ) )
		);

		// generate the response
  	$response = array(
  		'addUpdate' => ( $editArray['flag'] == 'yes' ) ? 'updated' : 'added',
  		'id' => ''
    );

    if( $editArray['flag'] == 'no' ) {
			$venueExists = $this->checkIfVenueExists( $_POST['venue']['name'] );
		} else {
			$venueExists = 'no';
		}

		if( $venueExists == 'no' ) {

			$addVenue = $this->addUpdateVenue( $_POST['venue'], $editArray );
			$response['id'] = $addVenue['id'];
			$response['result'] = $addVenue['result'];

		} else {

			$response['result'] = 'exists';

		}

  	$response = json_encode( $response );
	  header( "Content-Type: application/json" );
  	echo $response;

		die();

	}


 /**
 	* AJAX function to check if a venue or programme to be deleted is associated with any event
 	*/
	public function ajax_check_prog_venue_associations() {

		global $wpdb;

		check_ajax_referer( 'cp-check' );

		$epv = trim( $_POST['eventProgVenue'] );
		if( $epv == 'prog' || $epv == 'progs' ) {
			$query = "SELECT event_id FROM " . CP_EVENTS . " WHERE prog_id = %d;";
		} else if( $epv == 'venue' || $epv == 'venues' ) {
			$query = 'SELECT event_id FROM ' . CP_EVENTS . ' WHERE venue_id = %d';
		}

		$results = array();

		foreach( $_POST['IDs'] as $ID ) {
			$ID = (int) $ID;
			$sql = $wpdb->prepare( $query, $ID );
			$result = $wpdb->get_results( $sql );
			if($result) {
				$results[] = $result;
			}
		}

		$results = json_encode( $results );
	  header( "Content-Type: application/json" );
		echo $results;
		die();

	}



 /**
 	* Delete an item from the DB
 	*/
	public function ajax_delete_event_prog_or_venue() {

		global $wpdb;

		check_ajax_referer( 'cp-check' );

		$epv = trim( $_POST['eventProgVenue'] );

		switch( $epv ) {
			case 'event':
			case 'events':
			case 'archives':
				$sqlQuery = "DELETE FROM " . CP_EVENTS . " WHERE event_id = %d LIMIT 1;";
				break;
			case 'prog':
			case 'progs':
				$sqlQuery = "DELETE FROM " . CP_PROGRAMMES . " WHERE id = %d LIMIT 1;";
				break;
			case 'venue':
			case 'venues':
				$sqlQuery = "DELETE FROM " . CP_VENUES . " WHERE id = %d LIMIT 1;";
				break;
		}

		$results = array();
		foreach( $_POST['delIDs'] as $delID ) {
			$delID = (int) $delID;
			$sql = $wpdb->prepare( $sqlQuery, $delID );
			$result = $wpdb->query( $sql );
			if( $result === false ){
				$result = 'error';
			}
			$results[] = $result;
		}
		$results = json_encode( $results );
	  header( "Content-Type: application/json" );

		echo $results;
		die();

	}




 /**
 	* Helper function to add / update a programme to the DB
 	*
 	* @prog_id int the programme ID
 	* @venue_id int the venue ID
 	* @eventArray array event date & time, multidate flag & end date
 	* @editArray array edit flag & eventID
 	*
 	* @return $wpdb result, eventID, event date
 	*/
	public function addUpdateEvent( $prog_id, $venue_id, $eventArray, $editArray = array( 'flag' => 'no' ) ) {

		global $wpdb;

		// Get event date
		$date = $eventArray['date'];

		// Get prog ID, venue ID and multidate flag
		$event = array(
			'prog_id' => $prog_id,
			'venue_id' => $venue_id,
			'multidate' => (int) $eventArray['multidateflag']
		);

		// If it's a multi-day event
		if( $event['multidate'] == 1 ) {

			// There's no time
			// Convert to MySQL datestamp
			$event['date'] = $date . ' 00:00:00';
			$event['enddate'] = $eventArray['enddate'] . ' 00:00:00';

		} else {

			$hour = (int) $eventArray['hour'];
			$min = (int) $eventArray['min'];

			// Convert to MySQL datestamp (with time)
			$event['date'] = $date . ' ' . $hour . ':' . $min . ':00';
			$event['enddate'] = 0;

		}


		// If we're editing a venue
		if( $editArray['flag'] == 'yes' ) {

			// Where statement for SQL query
			$where = array(
				'event_id' => (int) $editArray['id']
			);

			// For the prepared SQL statement
			$event_formats = array( '%d', '%d', '%s', '%s', '%s' );
			$where_formats = array( '%d' );

			// Update the programme
			$result = $wpdb->update( CP_EVENTS, $event, $where, $event_formats, $where_formats );

			$id = (int) $editArray['id'];

		} else {

			// It's a new event, add a created timestamp
			$event['created'] = current_time( 'mysql' );
			$event_formats = array( '%d', '%d', '%s', '%s', '%s', '%s' );
			$result = $wpdb->insert( CP_EVENTS, $event, $event_formats );
			$id = (int) $wpdb->insert_id;

		}

		// To avoid type problems between PHP and JS & to help retain sanity, convert PHP false to a string value
		if( $result === false ) {
			$result = 'error';
		}

		return compact( 'result', 'id', 'date' );

	}




 /**
 	* Helper function to add / update a programme to the DB
 	* @prog array programme title & description
 	* @editArray array edit flag & editID
	*
	* @return $wpdb result & insertID
 	*/
	public function addUpdateProg( $prog, $editArray = array( 'flag' => 'no' ) ) {

		global $wpdb;

		$prog = array(
			'title' => sanitize_text_field( $prog['title'] ),
			'details' => wp_kses_post( $prog['details'] )
		);

		// If we're editing a venue
		if( $editArray['flag'] == 'yes' ) {

			// Where statement for SQL query
			$where = array(
				'id' => (int) $editArray['id']
			);

			$prog_formats = array( '%s', '%s' );
			$where_formats = array( '%d' );

			// Update the programme
			$result = $wpdb->update( CP_PROGRAMMES, $prog, $where, $prog_formats, $where_formats );

			$id = (int) $editArray['id'];

		} else {

			// Add the date created to new venues
			$prog['created'] = current_time( 'mysql' );
			$prog_formats = array( '%s', '%s', '%s' );

			// Add the programme
			$result = $wpdb->insert( CP_PROGRAMMES, $prog, $prog_formats );

			// Get the ID of the new venue
			$id = (int) $wpdb->insert_id;

		}

		// To avoid type problems between PHP and JS & to help retain sanity, convert PHP false to a string value
		if( $result === false ) {

			$result = 'error';

		}

		return compact( 'result', 'id' );

	}




 /**
 	* Helper function to add / update a programme to the DB
 	* @venue array venue name, url & address
 	* @editArray array edit flag & editID
	*
	* @return $wpdb result & insertID
 	*/
 	public function addUpdateVenue( $venue, $editArray = array( 'flag' => 'no' ) ) {

		global $wpdb;

		$venue = array(
			'name' => sanitize_text_field( $venue['name'] ),
			'url' => trim( esc_url_raw( $venue['URL'] ) ),
			'address' => sanitize_text_field( $venue['address'] ),
			'lat' => '',
			'lng' => ''
		);

		if( !empty( $venue['address'] ) ) {

			$latlng = $this->gmapsGeocode( $venue['address'] );

			if( !empty( $latlng ) ) {
				$venue['lat'] = $latlng['lat'];
				$venue['lng'] = $latlng['lng'];
			}

		}

		if( $editArray['flag'] == 'yes' ) {

			// Where statement for SQL query
			$where = array(
				'id' => (int) $editArray['id']
			);
			$venue_formats = array( '%s', '%s', '%s', '%s', '%s' );
			$where_formats = array( '%d' );
			$result = $wpdb->update( CP_VENUES, $venue, $where, $venue_formats, $where_formats );
			$id = (int) $editArray['id'];

		} else {

			$venue['created'] = current_time( 'mysql' );
			$venue_formats = array( '%s', '%s', '%s', '%s', '%s', '%s' );
			$result = $wpdb->insert( CP_VENUES, $venue, $venue_formats );
			$id = $wpdb->insert_id;

		}

		// To avoid type problems between PHP and JS & to help
		// retain sanity, convert PHP false to a string value
		if( $result === false ) {
			$result = 'error';
		}

		return compact( 'result', 'id' );

	}



 /**
 	* redraws the list of venues, progs or events after AJAX submission
 	*/
	public function ajax_redraw_tables(){

		$page = $_POST['page'];

		$pageNo = (int) $_POST['pageNo']; // The 'page' we're on
		$numResults = (int) $_POST['numResults'];

		error_log($archives);

		if($_POST['archives'] == 'yes') {
			$this->echoArchivesTableAndPagination( $numResults, $pageNo );
		} else {
			switch( $page ) {

				case 'event':
				case 'events':
					$this->echoEventsTableAndPagination( $numResults, $pageNo );
					break;
				case 'prog':
				case 'progs':
					$this->echoProgTableAndPagination( $numResults, $pageNo );
					break;
				case 'venue':
				case 'venues':
					$this->echoVenueTableAndPagination( $numResults, $pageNo );
					break;

			}
		}

		die();

	}




 /**
 	*	Helper function to check if programme already exists
 	* @title string programme title
	*
	* @return string if programme exits
	*/
	public function checkIfProgExists( $title ){

		global $wpdb;

		// Get programme names from the DB
		$allProgs = "SELECT title FROM " . CP_PROGRAMMES;
		$allProgs = $wpdb->get_results( $allProgs , ARRAY_A );

		if( !empty( $allProgs ) ) {
			foreach( $allProgs as $everyProg ) {
				if( strtolower( $everyProg['title'] ) == strtolower( $title ) ) {
					$progExists = 'yes';
					break;
				} else {
					$progExists = 'no';
				}
			}
		} else {
			$progExists = 'no';
		}
		return $progExists;

	}




 /**
	* Check if venue name has been used before
 	* @title string venue name
	*
	* @return string if venue exits
	*/
	public function checkIfVenueExists( $venueName ){

		global $wpdb;

		// Get all the venue names from the DB
		$allVenues = "SELECT name FROM " . CP_VENUES;
		$allVenues = $wpdb->get_results( $allVenues , ARRAY_A );

		if( !empty( $allVenues ) ) {
			foreach( $allVenues as $allVenue ) {
				if( strtolower( $allVenue['name'] ) == strtolower( $venueName ) ) {
					$exists = 'yes';
					break;
				} else {
					$exists = 'no';
				}
			}
		} else {
			$exists = 'no';
		}
		return $exists;
	}




 /**
	* Echo settings page
	*/
	public function echoSettingsPage() {
		include_once( 'html/settings-page.php' );
	}



 /**
	* List upcoming events
	*/
	public function echoEventsPage() {
		include_once( 'html/add-event.php' );
		$this->echoEventsTableAndPagination($this->numResultsPerPage);
	}



 /**
	* Called on page load and when an event has been deleted / edited to refresh the list of events
	*
	* @pageNo int current page number
	* @numResults int number of results to display per page
	*/
	public function echoEventsTableAndPagination( $numResults, $pageNo = 1 ) {

		global $wpdb;
		$archives = false;

		// Pagination
		$args = array(
			'page' => 'events',
			'pageNo' => $pageNo,
			'numResults' => $numResults
		);
		$this->doPagination( $args );

		// Get the events
		$sql = $this->events_sql( 'future' );
		$events = $wpdb->get_results( $sql, ARRAY_A );

		$noConcertMessage = '<p>There are no events in the database yet.</p>';

		include_once( 'html/list-events.php' ); // Method to draw the venues table & pagination

	}




 /**
	* List archived events
	*/
	public function echoArchivesPage() {
		$this->echoArchivesTableAndPagination($this->numResultsPerPage);
	}



 /**
	* Called on page load and when an event has been deleted / edited to refresh the list of events
	*
	* @pageNo int current page number
	* @numResults int number of results to display per page
	*/
	public function echoArchivesTableAndPagination( $numResults, $pageNo = 1 ) {

		global $wpdb;
		$archives = true;

		// Pagination
		$args = array(
			'page' => 'events',
			'scope' => 'past',
			'pageNo' => $pageNo,
			'numResults' => $numResults
		);
		$this->doPagination( $args );

		$sql = $this->events_sql( 'past' );
		$events = $wpdb->get_results( $sql, ARRAY_A );

		$noConcertMessage = '<p>There are no events in the archives yet.<br>Events that have ocurred will automatically move to the archives.</p>';

		echo '<div id="delete-container" title="Delete Events"></div>';
		echo '<div id="cp-table-container">';
		// As we don't have a proper header and/or container in the list-events file
		echo '<div class="wrap cp-inside" data-page="events">';
		echo '<h2 id="section-header">Archives</h2><br>';
		include_once( 'html/list-events.php' ); // Method to draw the venues table & pagination
		echo '</div>';

	}




 /**
	* Create the venue and programme select lists dynamically
	*
	* @venueOrProg string the type of thing
	*
	* @return (echo) the select list
	*/
	public function echoVenueOrProgSelectList( $venueOrProg ){

		global $wpdb;

		$idEtc = 'select-' . $venueOrProg;

		if( $venueOrProg == 'venue' || $venueOrProg == 'venues' ) {

			// Get venue details from the DB
			$sql = "SELECT name, id FROM " . CP_VENUES . ";";
			$allResults = $wpdb->get_results( $sql, ARRAY_A );

			$firstOpt = ' -- select a venue -- ';

		} else if ( $venueOrProg == 'prog' || $venueOrProg == 'progs' ) {

			// Get programme details from the DB
			$sql = "SELECT title AS name, id FROM " . CP_PROGRAMMES . ";";
			$allResults = $wpdb->get_results( $sql, ARRAY_A );

			$firstOpt = ' -- select a programme -- ';

		}

		// If there are results
		if( !empty( $allResults ) ) {

			// Create the list
			$list = '<select id="select-' . $venueOrProg . '" name="select-' . $venueOrProg . '" class="cp-select long select-' . $venueOrProg . '">
				<option value="">' . $firstOpt . '</option>';

			foreach( $allResults as $result ) {

				// Stripslashes
				$result = array_map( 'stripslashes_deep', $result );

				// Append the option to the list
				$list .= '<option value="' . $result['id'] . '">' . $result['name'] . '</option>';

			}

			// Close the tags
			$list .= '</select> &nbsp;<strong>or</strong>&nbsp; ';

		}

		// Print the list
		echo $list;

	}



 /**
  * List Programmes
	*/
	public function echoProgrammesPage() {

		// Include the HTML to add / edit a programme
		include_once( 'html/add-programme.php' );
		$this->echoProgTableAndPagination($this->numResultsPerPage);

	}



 /**
	* Called on page load and when a programme has been deleted / edited to refresh the list of events
	*
	* @pageNo int current page number
	* @numResults int number of results to display per page
	*/
	public function echoProgTableAndPagination( $numResults, $pageNo = 1 ) {

		global $wpdb;

		// Pagination
		$args = array(
			'page' => 'progs',
			'pageNo' => $pageNo,
			'numResults' => $numResults
		);
		$num = $this->doPagination( $args );

		echo $num;

		// Get programme ID, title and details from the DB. Order by creation date, limit according to pagination vars
		$sql = "SELECT * FROM " . CP_PROGRAMMES . " ORDER BY created DESC LIMIT " . $this->pagination['offset'] . ", " . $this->pagination['limit'] . ";";
		$programmes = $wpdb->get_results( $sql , ARRAY_A );

		// HTML to display list of programmes
		include_once( 'html/list-programmes.php' );

	}





 /**
 	*	Echo the venues
 	*/
	public function echoVenuesPage() {

		// Include the HTML to add a new venue
		include_once( 'html/add-venue.php' );

		// Method to draw the venues table & pagination
		$this->echoVenueTableAndPagination($this->numResultsPerPage);

	}




 /**
	* Called on page load and when a venue has been deleted / edited to refresh the list of events
	*
	* @pageNo int current page number
	* @numResults int number of results to display per page
	*/
	public function echoVenueTableAndPagination( $numResults, $pageNo = 1 ) {

		global $wpdb;

		// Pagination
		$args = array(
			'page' => 'venues',
			'pageNo' => $pageNo,
			'numResults' => $numResults
		);
		$this->doPagination( $args );

		// Get the name, URL, address and ID from the venues database, order by date they were created.
		// Limit the query by the value set in the pagination array
		$sql = "SELECT * FROM " . CP_VENUES . " ORDER BY created DESC LIMIT " . $this->pagination['offset'] . ", " . $this->pagination['limit'] . ";";

		// Get the results
		$venues = $wpdb->get_results( $sql , ARRAY_A );

		// Include the HTML to list the values
		include_once( 'html/list-venues.php' );

	}



 /**
 	* Hook into the googlemaps API & convert an address into map coordinates
 	*
 	* @address string the address to Geocode
 	*
 	* @return array latitide and longitude
 	*/
	public function gmapsGeocode( $address ) {

		$map_url = 'http://maps.google.com/maps/api/geocode/json?address=';
		$map_url .= urlencode( $address ) . '&sensor=false';

		$request = wp_remote_get( $map_url );
		$json = wp_remote_retrieve_body( $request );
		$json = json_decode( $json );

		if( !empty( $json->results[0] ) ) {
			$lat = $json->results[0]->geometry->location->lat;
			$lng = $json->results[0]->geometry->location->lng;
			return compact( 'lat', 'lng' );
		} else {
			return false;
		}

	}



 /**
 	* Helper function to create an excerpt of set length from a string
 	* Strips all HTML tags and line breaks
 	*
 	* @param array
 	* [1] the text string to shorten
 	* [2] the default text to display if the text string is empty
 	* [3] the length of the excerpt to return (in words)
 	*
 	* @return string the excerpt we've created or default text
	*/
	private function createExcerpt( $args = array() ) {

		$defaults = array(
			'text' => '',
			'default_text' => 'No programme details provided.',
			'excerpt_length' => 10
		);
		//
		$args = wp_parse_args( $args, $defaults );

		// If there is text provided
		if( !empty( $args['text'] ) ) {

			// Get rid of all HTML tags
			$the_excerpt = trim( ( $args['text'] ) );

			$the_excerpt = preg_replace( '/<.*?>/', " " , $the_excerpt );

			// Get rid of any line breaks and replace with a empty space
			$the_excerpt = preg_replace( '/(?:(?:\r\n|\r|\n)\s*)+/s', '&nbsp;', $the_excerpt );

			// Make an array out of the remaining words, using the space as a deliminator
			$the_excerpt = explode( ' ', $the_excerpt );

			// Get the length of this array
			$ar_length = count( $the_excerpt );

			// Get the specified length of the excerpt
			$ex_length = (int) $args['excerpt_length'];

			// If the array is longer than the specified length
			if( $ar_length > $ex_length ) {

				// Chop the array to the specified length (in words)
				$the_excerpt = array_splice( $the_excerpt, 0, $ex_length );

				// Turn the array back into a string
				$the_excerpt = implode( ' ', $the_excerpt );

				// Show that the text has been truncated
				$the_excerpt .= ' &hellip;';

			} else { // The array is shorter that the specifed length, no need to shorten it

				// Turn the array back into a string
				$the_excerpt = implode( ' ', $the_excerpt );

			}

		} else { // If there is no provided text

			// Display the default text
			$the_excerpt = $args['default_text'];
		}

		// Return our lovely clean excerpt
		return $the_excerpt;

	}


 /**
 	* Calculates whether or not pagination is required
 	*
 	*
 	* @args array
 	* [0] numResults int number of results to display per page
 	* [1] page string which page we're on
 	*	[2] scope string 'future' or 'past'
 	* [3] pageNo int which page we're currently on
 	*
	*/
	public function doPagination( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'numResults' => 10,
			'page' => 'events',
			'scope' => 'future',
			'pageNo' => 1
		);
		$args = wp_parse_args( $args, $defaults );

		$limit = $this->pagination['limit'] = ( $args['numResults'] == -1 ) ? 10000 : $args['numResults'];

		// Get the number of concerts in the DB
		if( $args['scope'] == 'future' ) {
			$when = '>=';
		} else if( $args['scope'] == 'past' ) {
			$when = '<=';
		}

		switch( $args['page'] ){
			case 'events' :
				$singPage = 'event';
				$sql = "SELECT event_id
					FROM " . CP_EVENTS . " AS e
					JOIN " . CP_VENUES . " AS v
					JOIN " . CP_PROGRAMMES . " AS p
					WHERE e.venue_id = v.id
					AND e.prog_id = p.id
					AND date " . $when . " NOW();";
				break;
			case 'progs' :
				$singPage = 'programme';
				$sql = "SELECT id FROM " . CP_PROGRAMMES . ";";
				break;
			case 'venues' :
				$singPage = 'venue';
				$sql = "SELECT id FROM " . CP_VENUES . ";";
				break;
		}

		// Perform the query
		$results = $wpdb->get_results( $sql );

		// The number of rows in the table
		$results = $wpdb->num_rows;

		// set the pagination var to false if there are now rows returned, otherwise set it to true
		$this->pagination['paginate'] = ( $results != 0 ) ? true : false;

		// Whether or not to so pagination links
		// If there are more results that the limit of results to display on the page
		$paginate = ( $results > $limit ) ? true : false;

		// Get the current 'page' no
		$pageNo = (int) $args['pageNo'];

		// Calculate offset for SQL Query in relation to $limit
		// eg: If we show 10 concerts per page and are on page 3
		// 3 * 10 = 30
		// 30 - 10 = 20
		$offset = $this->pagination['offset'] = ( $pageNo * $limit ) - $limit;

		// Counter
		$i = 0;

		// The number of pages (rounded up)
		$numPages = ceil( $results / $limit );

		// Create an array of pagination links
		$this->pagination['pageLinks'] = array();

		if( $numPages > 1 ) {
			// Create pagination links
			for( ; $i < $numPages; $i++ ) {
				// our pages start from 1, not 0
				$l = $i + 1;
				// Current navigation state
				if( $pageNo == $l ) {
					$this->pagination['pageLinks'][] = '<a href="#" class="page-numbers current">' . $l . '</a>';
				} else {
					// Create a link to the page
					$this->pagination['pageLinks'][] = '<a data-pageno="' . $l . '" href="' . WEBSITE_URL . '/wp-admin/admin.php?page=' . $args['page'] . '#page=' . $l . '" class="page-numbers">' . $l . '</a>';
				}
			}
		}


		// Displaying x of x concert variables
		$start = $offset + 1;
		$end = $offset + $limit;


		// So we don't echo '10 to 15 of 12 concerts' (for example)
		if( $end > $results ) {
			$end = $results;
		}

		if( $args['page'] == 'progs' ) {
			$args['page'] = 'programmes';
		}

		if( $paginate === true ) {
			// So we don't echo '11 to 11 of 11 concerts';
			if( $start == $end ) {
				$this->pagination['displayNum'] = "Displaying $singPage $end of $results.";
			} else {
				$this->pagination['displayNum'] = "Displaying $start to $end of $results {$args['page']}";
			}
		} else {
			if( $results == 1 ) {
				$this->pagination['displayNum'] = "Displaying $results $singPage";
			} else {
				$this->pagination['displayNum'] = "Displaying $results {$args['page']}";
			}
		}
	}



 /**
	* Echo pagination links
	*/
	public function doPaginationPlease() {

		if( $this->pagination['paginate'] === true ) {
		?>
		<div class="tablenav-pages">
			<span class="displaying-num" data-limit="<?php echo $this->pagination['limit']; ?>"><?php echo $this->pagination['displayNum']; ?></span>
			<?php
				// Show pagination if appropriate
				if( !empty( $this->pagination['pageLinks'] ) ) {
					// This is an array of links so, we need to loop through them
					foreach( $this->pagination['pageLinks'] as $pageLink ) {
						echo $pageLink;
					}
				}
			?>
		</div>
		<?php
		}

	}

	public function getOptions() {

		// Add the option if it's not set
		if( get_option( 'rps_cp_num_results' ) === false ) {
			$result = 10;
			add_option( 'rps_cp_num_results', $result );
		} else {
		 	$result = get_option( 'rps_cp_num_results' );
		}
		echo $result;
		die();

	}



	public function saveOptions() {

		$numResults = (int) $_POST['numResults'];
		update_option( 'rps_cp_num_results', $numResults );
		$this->numResultsPerPage = $numResults;
		echo $numResults;
		die();

	}



 /**
 	* Helper function to return SQL query to show all concerts
 	*
 	* @param string scope of query = 'past' or 'future'
 	*
 	* @return string the SQL query
 	*/
	public function events_sql( $scope ){

		$scope = ( $scope == 'future' ) ? '>=' : '<=';
		return "SELECT date, multidate, enddate, event_id, prog_id,
			venue_id, v.name, v.url, v.address,
			p.title, p.details
			FROM " . CP_EVENTS . " AS e
			JOIN " . CP_VENUES . " AS v
			JOIN " . CP_PROGRAMMES . " AS p
			WHERE e.venue_id = v.id
			AND e.prog_id = p.id
			AND e.date " . $scope . " NOW()
			ORDER BY date ASC LIMIT " . $this->pagination['offset'] . ", " . $this->pagination['limit'] . ";";

	}


 /**
	* Helper function to create a human-readable date format
	*
	* @eventDate string event start date (MySQL date stamp)
	* @multiDate int multi date flag
	* @eventEndDate string event end date (MySQL date stamp)
	*
	* @return formatted date
	*/
	public function echoNiceDate( $eventDate, $multiDate, $eventEndDate ) {
		$hour = date( 'H', strtotime( $eventDate ) );
		if( $multiDate == '1' ) {
			$niceEventDate = date( 'jS M Y', strtotime( $eventDate ) ) . ' - ' . date( 'jS M Y', strtotime( $eventEndDate ) );
		} else {
			if( $hour != '00' ) {
				$niceEventDate = date( 'jS M Y, H:i', strtotime( $eventDate ) );
			} else {
				$niceEventDate = date( 'jS M Y', strtotime( $eventDate ) );
			}
		}
		return $niceEventDate;
	}


 /**
 	* Echo the concert to the front-end
 	*
 	* $args
 	* [0] - scope string 'future' or 'past'
 	* [1] - limit int number of events to display (-1 = all)
 	* [2] - show_excerpt string 'yes', or 'no' to show excerpt of full programme description
 	* [3] - excerpt_length int the length of the excerpt
 	* [4] - link_to_event string 'yes' or 'no' whether or not to show a link to the full event details
 	* [5] - link_container string the container for the 'read more' link: 'p, span or div'
 	* [6] - show_maps string 'yes' or 'no' whether or not to show a google map (only for single events)
 	* [7] - no_event_message string the message to display if there are currently no events
 	* [8] - sidebar boolean whether or not we're echoing the widget html
 	*
 	* @return string the list of events
 	*/
	public function printEvents( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'scope'            => 'future', // scope of events to show
			'limit'            => -1, // number of events to show
			'order'            => 'asc',
			'show_excerpt'     => 'yes', // show full prog description
			'excerpt_length'   => 10, // length of the excerpt
			'link_to_event'    => 'yes', // whether or not to show a link to the full event details
			'link_text'        => 'View full event details', // Text for the link
			'link_container'   => 'p', // HTML tag continer for the link
			'show_maps'        => 'yes', // Whether or not to show the map (only works for single events)
			'no_event_message' => 'There are currently no upcoming events to display', // Message to display if there are no events
			'sidebar'          => false // If we're echoing the sidebar widget
		);
		$args = wp_parse_args( $args, $defaults );

		// Display upcoming or archived events
		$scope = $args['scope'];
		$scope = ( $scope == 'future' ) ? '>=' : '<=';

		// No prog details flag
		$noDetails = ( empty( $event['details'] ) ) ? true : false;

		// If the display limit is set to -1, show all the events
		$displayLimit = ( $args['limit'] == -1 ) ? 10000 : (int) $args['limit'];

		// If no event id is set in the url, show all events
		if( isset( $_GET['event_id'] ) && !$args['sidebar'] ) {

			$eventID = (int) $_GET['event_id'];
			$singleEvent = true;

			// Get future or past events from the DB
			$mysql = "SELECT date, multidate, enddate, event_id, prog_id, venue_id,
				p.title, p.details,
				v.name, v.url, v.address, v.lat, v.lng
				FROM " . CP_EVENTS . " AS e
				JOIN " . CP_PROGRAMMES . " AS p
				JOIN " . CP_VENUES . " AS v
				WHERE e.event_id = " . $eventID . "
				AND e.venue_id = v.id
				AND e.prog_id = p.id
				LIMIT 1;";

		} else {

			$singleEvent = false;

			$order = ($args['order'] == 'asc') ? 'ASC' : 'DESC';

			// Get future or past events from the DB
			$mysql = "SELECT date, multidate, enddate, event_id, prog_id, venue_id,
				p.title, p.details,
				v.name, v.url, v.address, v.lat, v.lng
				FROM " . CP_EVENTS . " AS e
				JOIN " . CP_PROGRAMMES . " AS p
				JOIN " . CP_VENUES . " AS v
				WHERE e.venue_id = v.id
				AND e.prog_id = p.id
				AND e.date " . $scope . " NOW()
				ORDER BY date " . $order . " LIMIT " . $displayLimit . ";";

		}

		// Get results from DB
		$events = $wpdb->get_results( $mysql, ARRAY_A );

		// If there are no results, show a message
		if( empty( $events ) ) {

		 $html = $args['no_event_message'];

		} else {

			// HTML blob to contain the events
			$html = '<div class="concertpress-container">';

			foreach( $events as $event ) {

				$slashEvent = $event;
				$event = array_map( 'stripslashes_deep', $event );

				$niceEventDate = $this->echoNiceDate( $event['date'], $event['multidate'], $event['enddate'] );

				$venue = $event['name'];
				$url = $event['url'];
				$latitude = $event['lat'];
				$longitude = $event['lng'];
				$prog = $event['title'];
				$details = $event['details'];
				$address = $event['address'];

				// Venue, with/without URL
				$venue = ( !empty( $url ) ) ? '<a class="url" href="' . $url . '" title="' . $venue . '">' . $venue . '</a>' : $venue;

				// Venue Address
				$address = ( !empty( $address ) ) ? '<span class="address adr">' . $address . '</span>' : '';

				// Venue coordinates
				$geo = ( $latitude!= '0' ) ? '<div class="geo"><abbr class="latitude" title="' . $latitude. '"></abbr><abbr class="longitude" title="' . $longitude. '"></abbr></div>' : '';

				/* Each event starts here */
				$html .= '<div class="vevent">';

				if( $singleEvent === false ) {
					// If there are prog details
					if( !empty( $details ) ) {
						if( $args['show_excerpt'] == 'yes' ) {
							if( $args['excerpt_length'] == 0 ) {
								$excerpt = '';
							} else {
								$detailsArray = array(
									'text' => $details,
									'excerpt_length' => $args['excerpt_length']
								);
								$excerpt = $this->createExcerpt( $detailsArray );
								$excerpt = '<p>' . $excerpt . '</p>';
							}
						} else {
							$excerpt = $details;
						}
					}
					// Time & Venue
					$html .= '<span class="time dtstart">' . $niceEventDate  . '</span>';
					$html .= '<span class="location"><strong>Venue: </strong>' . $venue . '</span>';
					$html .= $address;
					$html .= '<span class="prog-title summary"><strong>Programme: </strong>' . $prog . '</span>';
					$html .= '<div class="description">' . $excerpt . '</div>';

					if( $args['link_to_event'] == 'yes' ) {
						$tag = $args['link_container'];
						$allowedTags = array( 'p', 'span', 'div' );
						if( $tag != '' && in_array( $tag, $allowedTags ) ) {
							$tag = $tag;
						} else {
							$tag = 'p';
						}
						// Create the link
						$html .= '<' . $tag . ' class="cp-event-link"><a class="event-link" href="http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '?event_id=' . $event['event_id'] . '">' . $args['link_text'] . '</a></' . $tag . '>';
					}

				} else {

					$html .= '<div class="event-details-container">';
					$html .= '<span class="time dtstart">' . $niceEventDate . '</span>';
					$html .= '<span class="location"><strong>Venue:</strong> ' . $venue . '</span>';
					$html .= $address . $geo;
					$html .= '<span class="prog-title summary"><strong>Programme:</strong> ' . $prog . '</span>';
					$html .= '<div class="description">' . $details . '</div>';
					$html .= '</div>'; // event-details-container

					// If they want a map
					if( $args['show_maps'] == 'yes' && !empty( $address ) ) {

						$html .= '<div id="map-canvas" data-name="' . $event['name'] . '" data-url="' . $event['url'] . '" data-address="' . $event['address'] . '" data-lat="' . $event['lat'] . '" data-lng="' . $event['lng'] . '"></div>
							<script src="http://maps.google.com/maps/api/js?sensor=false"></script>
							<script src="' . CP_URL . 'js/front-end.js"></script>';

					}
				}
				$html .= '</div>';
			}
			$html .= '</div>';
		}
		return $html;

	}

	public function add_link_to_menu_bar() {
	  global $wp_admin_bar;
	  $wp_admin_bar->add_menu( array(
			'id'     => 'my-link-sub-1',
			'title'  => 'Event',
			'href'   => admin_url() . 'admin.php?page=events',
			'parent' => 'new-content'
	  ));
	}

	function register_widget() {
		register_widget('ConcertPressWidget');
	}

}

// The End

$rps_concertPress = new RPS_ConcertPress();


class ConcertPressWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'concertpress_id', // Base ID
			'ConcertPress', // Name
			array( 'description' => __( 'Display a list of upcoming events', 'text_domain' ), )
		);
	}


	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	*/
	public function widget( $args, $instance ) {
		global $rps_concertPress;
		$num_events = $instance['num_events'];
		$args = array(
			'limit' => $num_events,
			'excerpt_length' => 10,
			'link_to_event' => 'no',
			'sidebar' => true
		);
		echo '<div class="concertpress-widget-container">' . "\n";
		echo '<div class="title">Upcoming Events</div>' . "\n";
		echo $rps_concertPress->printEvents( $args );
		echo '</div>' . "\n";
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	*/
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['num_events'] = (int) $new_instance['num_events'];
		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	*/
	public function form( $instance ) {
		if(isset($instance['num_events']) && $instance['num_events'] != 0) {
			$num_events = $instance['num_events'];
		} else {
			$num_events = 3;
		}
	?>
		<p>
			<label for="<?php echo $this->get_field_name( 'num_events' ); ?>"><?php _e( 'Number of events to display' ); ?></label>&nbsp;
			<select name="<?php echo $this->get_field_name( 'num_events' ); ?>" id="<?php echo $this->get_field_id( 'num_events' ); ?>">
				<?php for($i = 1; $i <= 5; $i++): ?>
					<option <?php selected( $num_events, $i ); ?> val="<?php echo $i; ?>"><?php echo $i; ?></option>
				<?php endfor; ?>
			</select>
		</p>
	<?php
	}

}
