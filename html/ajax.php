<script>

	jQuery( function( $ ) {

		console.log( phpvars.plugin_url, phpvars.nonce );

		var rpsCP = {

			$addDiv: $( 'div#cp-add-container' ).hide(), // Div that contains the add/edit form
			$header: $( 'h2#section-header' ), // Section header
			$ajaxLoader: $( 'img#ajax-loader' ).hide(), // Ajax loader
			$submit: $( 'input.cp-submit' ), // Submit button
			$deleteDiv: $( 'div#delete-container' ), // Delete container

			regExURL: /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/, // URL check
			regExDate: /\d{4}-\d{2}-\d{2}/, // Date check

			loadingGif: '<img src="' + phpvars.plugin_url + 'img/ajax-loader.gif" class="loading-gif" alt="loading…">', // Loading gif

			addedFlag: false, // Edit flag (for ajax methods)
			editID: 0, // Edit ID (for ajax methods)
			eventProgVenue: $( 'div.cp-inside' ).attr( 'data-page' ), // Which page we're on
			deleteVals: {}, // Object to store values to delete stuff from the DB
			numResultsPerPage: +$( 'div.cp-inside' ).attr( 'data-num-results' ) || 10, // The number or results to display on each 'page'

			// HTML5 history variables
			popped: ( 'state' in window.history ),
			initialURL: location.href,
			pageNo: 1,

			event: {

				$date: $( 'input#concert-date' ),
				$endDate: $( 'input#concert-end-date' ),
				$multiDate: $( 'input#multi-date' ),

				$hour: $( 'select#concert-hour' ),
				$min: $( 'select#concert-min' ),

				$venueSelect: $( 'select#select-venue' ),
				$progSelect: $( 'select#select-prog' ),

				$showVenue: $( 'a#show-venue' ),
				$venueHide: $( '.venue-hide' ),
				$showProg: $( 'a#show-prog' ),
				$progHide: $( '.prog-hide' )

			},

			prog: {

				$progTitle: $( 'input#programme-title' )

			},

			venue: {

				$venueName: $( 'input#venue-name' ),
				$venueURL: $( 'input#venue-url' ),
				$venueAddress: $( 'input#venue-address' )

			},



		 /* JS equivalent to PHP stripslashes */
			stripslashes: function( str ){

		    str = str.replace( /\\'/g, '\'' );
    		str = str.replace( /\\"/g, '"' );
		    str = str.replace( /\\0/g, '\0' );
		    str = str.replace( /\\\\/g, '\\' );

		    return str;

			},



			/* Hide stuff - self invoking */
			hideStuff: (function(){

				// adminpage is printed in the head, by WP, as is ajaxurl
				if( adminpage === 'concertpress_page_events' ) {

					$( '.venue-hide, .prog-hide' ).hide();

				}

				$( '#map-canvas' ).hide();

			}()),



			/* Removes all messages appended to the DOM */
			removeMessageDivs: function() {

				//If the div is there
				if( $( 'div.message' ).length ) {

					// remove it
					$( 'div.message' ).remove();

				}

			},



			/* Scroll to the top of the screen */
			scrollUp: function() {

				$( 'body, html' ).animate({ scrollTop: 0 }, 500 );

			},



			/* Show the add/edit div */
			showAddEditDiv: function( opts ) {

				console.log( this.event.$multiDate.is( ':checked' ) );

				// Set default scroll value to true
				var dis = this,
					defaults = { scroll: true },
					opts = $.extend( defaults, opts );

				// If the div is hidden
				if( !this.$addDiv.is( ':visible' ) ) {

					// Fade it in
					this.$addDiv.fadeIn( 500, function(){

						// If we should scroll
						if( opts.scroll === true ) {

							// Scroll!
							dis.scrollUp();

						}

					});

				// Div is visible
				} else {

					// Still might need to scroll
					if( opts.scroll === true ) {

						// Scroll baby, scroll.
						dis.scrollUp();

					}

				}

			},




			/* Hide the add/edit div */
			hideAddEditDiv: function( opts ) {

				// Sort out defaults & scope of this
				var dis = this,
					defaults = {
						scroll: true,
						removeMessages: true
					},
					opts = $.extend( defaults, opts );

				// Clear all inputs
				$( 'input.cp-clear' ).val( '' );

				// If we're on the events page
				if( adminpage === 'concertpress_page_events' ) {

					// Hide new venue/prog fields
					dis.event.$venueHide.add( dis.event.$progHide ).hide();

					// If the multidate checkbox is checked
					if( dis.event.$multiDate.is( ':checked' ) ) {

						// Uncheck it, trigger the event to hide the enddate
						dis.event.$multiDate.attr( 'checked', false ).trigger( 'change' );

					}

					// Reset all the select lists
					dis.event.$hour.add( dis.event.$min ).attr( 'disabled', false ).val( 0 );

				}

				// Show add new venue/prog buttons
				dis.event.$showVenue.add( dis.event.$showProg ).show();

				// If we're not on the venues page, set the tinyMCE editor content to zilcho
				if( rpsCP.eventProgVenue !== 'venue' ) {
					tinyMCE.activeEditor.setContent( '' );
				}



				// Hide the div
				dis.$addDiv.slideUp( 500 );

				// If we should scroll
				if( opts.scroll === true ) {

					// Scroll
					dis.scrollUp();
				}

				// If we should remove the messages on screen
				if( opts.removeMessages === true ) {

					// Do it!
					dis.removeMessageDivs();

				}

			},




			/* Append message to the DOM */
			messageFeedback: function( params ) {

				// Sort out this & default paramaters
				var dis = this,
					defaults = {
						message: '',
						type: 'updated',
						callback: ''
					},
					params = $.extend( defaults, params ),
					msg = '<div class="message ' + params.type + '">' + params.message + '</div>'; // Message to append to the DOM

				// Remove any currently visible message
				dis.removeMessageDivs();

				// Append message
				dis.$header.after( msg );

				// Hide it, fade it in
				$( '.message' ).hide().fadeIn( 400, function(){

					// Scroll up so the user sees the message
					dis.scrollUp();

					// If there's a callback function
					if( typeof params.callback == 'function' ) {

						// Call the function, in the correct scope
						params.callback.call( dis );

					}

				});

			},




			/* jQuery UI 'dialog' */
			// Attatch it to the 'delete' div
			$dialog: $( 'div#delete-container' ).dialog({

				// Set defaults
				autoOpen: false,
				modal: true,
				width: 550,
				resizable: false,
				buttons: {
					'cancel': function() {
						$(this).dialog( 'close' );
					},
					'delete': function(){
						$(this).dialog( 'close' );
						rpsCP.deleteEventProgOrVenue();
					}
				},
				show: 'clip'
			}),




			/* Reload our tables after a prog/venue/event has been added and/or with pagination */
			reloadAfterAjax: function( pageNumber, numResults ) {

			 	var dis = this,
			 		pageNo = pageNumber || 1, // get 'page' number
			 		numResults = numResults || dis.numResultsPerPage, // number of results to display per 'page'
			 		tableContainer = $( '#cp-table-container' ).html( dis.loadingGif ); // Get the div that contains the table and append a loading gif

			 	// Our data to send to our ajax function
				var data = {
					action: 'redraw-tables',
					page: dis.eventProgVenue,
					pageNo: pageNo,
					numResults: numResults
				};

				// console.log( data );

				// Make the AJAX call - returns the HTML to redraw the table listing events/progs/venues + pagination
			 	var jqxhr = $.post( ajaxurl, data, function( results ) {

					// console.log( results );

			 		// Add the new, updated HTML to the DOM
			 		tableContainer.hide().html( results ).fadeIn( 300 );

					// Set the select list = specified number of results per page
					$( 'select#num-events' ).val( numResults );

					// Set 'global' variable
					dis.numResultsPerPage = numResults;

			 	});

			 	// Update page number - stops popstate event from firing multiple times
			 	dis.pageNo = pageNo;

			 	console.log( dis.pageNo, pageNo );

			},



			/* Add datepicker to the date fields */
			datePicker: function() {

				// Get our date fields
				this.event.$date.add( this.event.$endDate ).datepicker({

					// format options
					showOn: "both",
					buttonImage: phpvars.plugin_url + 'img/calendar_icon.png',
					buttonImageOnly: false,
					dateFormat: 'yy-mm-dd',
					firstDay: 1,
					numberOfMonths: 2

				});

			},



			/* Toggle end date for multi-date events */
			multiDateChange: function() {

				var dis = this,
					$endDate = $( 'p.endDate' );

				// If the multidate checkbox is not checked
				if( !dis.event.$multiDate.is(':checked') ) {

					// Make sure the end date input is hidden
					$endDate.hide();

					// The hour and minute select lists should be enabled
					dis.event.$hour.add( dis.event.$min ).attr( 'disabled', false );

				}

				// If the multidate checkbox is clicked
				dis.event.$multiDate.change( function() {

					// Toggle the end date input
					$endDate.stop( true, true ).fadeToggle( 300 );

					// If it's checked
					if( dis.event.$multiDate.is(':checked') ) {

						// Disable the hour and minute select lists
						dis.event.$hour.add( dis.event.$min ).attr( 'disabled', true );

					} else { // Otherwise

						// Enable the hour and minute select lists
						dis.event.$hour.add( dis.event.$min ).attr( 'disabled', false );

					}

				});

			},



			/* toggle add new venue/events input fields when select box is used */
			venueProgSelectChange: function( selectType ) {

				// If the venue select menu changes
				if( selectType === 'venue' ) {

					// Check if the new venue fields are already visible
					if( rpsCP.event.$venueHide.is( ':visible' ) ) {

						// Hide them
						rpsCP.event.$venueHide.fadeOut( 200 );

						// Fade in the 'add new' button
						rpsCP.event.$showVenue.fadeIn( 300 );

					}

				} else if( selectType === 'prog' )	{

					// Check if the new prog fields are already visible
					if( rpsCP.event.$progHide.is( ':visible' ) ) {

						// Hide them
						rpsCP.event.$progHide.fadeOut( 300 );

						// Fade in the 'add new' button
						rpsCP.event.$showProg.fadeIn( 400 );

					}

				}

			},



		 	/* Add a new thing */
			addNewEventProgOrVenue: function( page ) {

				var dis = this,
					submitVal = 'Add ';

				// Edit flag
				dis.editFlag = 'no';

				// For our AJAX refresh function
				dis.eventProgVenue = page;

				// Get rid of any message on screen
				dis.removeMessageDivs();

				// Depending on the page...
				switch( page ) {

					case 'event':

						// Set button text, empty textares & all selectboxes/checkboxes
						submitVal += 'event';
						tinyMCE.activeEditor.setContent( '' );
						$( 'form.cp-form select' ).val( 0 );
						$( 'input#multi-date' ).attr( 'checked', false );

						break;

					case 'prog':

						// Set button text, empty the textarea
						submitVal += 'programme';
						tinyMCE.activeEditor.setContent( '' );

						break;

					case 'venue':

						// Set button text, hide the maps div
						submitVal += 'venue';
						$( '#map-canvas' ).hide();

						break;

				}

				// Set submit button text
				dis.$submit.val( submitVal );

				// Show the div
				dis.showAddEditDiv();

				// Clear any inputs
				$( 'input.cp-clear' ).val( '' );

				// If we're not on the events page
				// This is to avoid calling the datepicker: might be confusing to the user.
				if( page !== 'event' ) {

					// Add focus to the first input
					$( 'input.cp-clear:first' ).focus();

				}

			},


		 /**
		 	* From the HTML5 history API.
		 	* Checks for initial popstate on pageload (Firefox doesn't fire this)
		 	* Changes the 'page' with the back button
		 	*/
			history: function( event ){

				// Check if the popstate is triggered on page load
				var initialPop = !rpsCP.popped && location.href == rpsCP.initialURL,
					pageNo;

	  		rpsCP.popped = true;

				// Don't do anything on initial pop if it fires on page load
				if ( initialPop === true ) {

					return true;

				// We only want it to fire when the user uses the back button
				} else {

	 	  	 	// If there's no hash in the URL
		    	if( location.hash == '' ) {

		    		// We're on page one
		    		pageNo = 1;

		    	} else {

	  	  		// Otherwise, set it to the last character in the URL hash (= the page number)
		     		pageNo = location.hash.split( '=' );
		     		pageNo = +pageNo[1];

		    	}

		    	console.log( rpsCP.pageNo, pageNo );

		    	// Stops the event triggering twice:
		    	// rpsCP.pageNo is redeclared after the reloadAfterAjax method
		    	if( pageNo !== rpsCP.pageNo ) {

							// Reload our tables with the correct page number
						rpsCP.reloadAfterAjax( pageNo );

					}

	    	}

	    	// Not strictly sure this is necessary, but it won't do any harm!
	    	event.preventDefault();

			},



			/* create a google maps instance */
			createMap: function( args ){

				var mapsURL = ( args.url !== '' ) ? '<p><a href="' + args.url + '">' + args.url + '</a></p>' : '', // Add a link to the venue website if there is one
	      	myLatlng = new google.maps.LatLng( args.lat, args.lng ); // latitude and longitude

	      // map options
				var myOptions = {
					zoom: 14, // Zoom level
					center: myLatlng, // Center the map to my coordinates
					mapTypeId: google.maps.MapTypeId.ROADMAP // Type of map
				};

				// Create the map
				var map = new google.maps.Map( document.getElementById( 'map-canvas' ), myOptions );

				// Content to add to map when user clicks on placemarker
				var contentString = '<div id="content">'
			    + '<h2 id="firstHeading" class="firstHeading">' + args.name + '</h2>'
			    + '<div id="bodyContent">'
			    + '<p>' + args.address + '</p>'
			    + mapsURL
			    + '</div>'
			    + '</div>';

				// Create the infowindow
				var infowindow = new google.maps.InfoWindow({
					content: contentString
				});

				// Add the marker
				var marker = new google.maps.Marker({
					position: myLatlng,
					map: map,
				  title: name
				});

				// Add click listener
				google.maps.event.addListener( marker, 'click', function() {
				  infowindow.open( map,marker );
				});

			} // There may be a better way to integrate maps with jQuery, beats me right now though.

		}; // End rpsCP object



		console.log( rpsCP.pageNo );

	 /**
	 	* HTML5 History API
	 	* Bind to 'popstate' to listen for the URL changes
	 	*/
		$( window ).bind( 'popstate', rpsCP.history );




		// Invoke the various methods on page load
		rpsCP.datePicker();
		rpsCP.multiDateChange();


		/* When the user uses the select list for programmes or venues on the events page*/
		$( 'select.cp-select' ).change( function() {

			// Get the type of thing
			var selectType = ( $(this).hasClass( 'select-venue' ) ) ? 'venue' : 'prog';

			// Do something!
			rpsCP.venueProgSelectChange( selectType );

		});



		/* When the user clicks on add a new… */
		$( 'div.cp-inside' ).on( 'click', 'p#add-p', function( event ) {

			// Get the type of thing
			var type = $(this).attr( 'data-type' );

			// Do the stuff
			rpsCP.addNewEventProgOrVenue( type );

			// Save this button to re-append to the DOM later
			rpsCP.button = $(this).clone();

			$( this ).remove();

			event.preventDefault();

		});



		$( 'input#cancel' ).click( function( event ){

			// Append new concert button to the DOM
			rpsCP.$header.after( rpsCP.button );
			rpsCP.hideAddEditDiv();
			event.preventDefault();

		});


	 /**
	 	* Edit an existing venue
	 	*/
		rpsCP.editEvent = function( clicker ) {

			var dis = this,
				editEvent = {
					edit_id: +clicker.attr( 'data-event-id' ), // Event ID
					prog_id: +clicker.attr( 'data-prog-id' ), // Programme ID
					venue_id: +clicker.attr( 'data-venue-id' ), // Venue ID
					date: clicker.attr( 'data-date' ), // Event date
					hour: clicker.attr( 'data-hour' ), // hour
					min: clicker.attr( 'data-min' ), // min
					multidate: clicker.attr( 'data-multidate' ), // multidate flag
					enddate: clicker.attr( 'data-enddate' ) // End date
				};

			// Clear input fields
			$( 'input.cp-clear' ).val( '' );

			// Reset the hour & min select lists + clear date fields in case they were used before while editing another event
			dis.event.$hour.add( dis.event.$min ).val( 0 );

			// Set date inputs
			dis.event.$date.val( editEvent.date );
			dis.event.$endDate.val( editEvent.enddate );

			// If the event has a time attacted to it
			if( editEvent.hour != '00' ) {

				// Set hour and minute vals
				dis.event.$hour.val( editEvent.hour );
				dis.event.$min.val( editEvent.min );

			}

			// If the event spans over several days
			if( editEvent.multidate == '1' ) {

				if( !dis.event.$multiDate.is( 'checked' ) ) {

					dis.event.$multiDate.attr( 'checked', true ).trigger( 'change' );

				}

			} else {

				if( dis.event.$multiDate.is( 'checked' ) ) {

					dis.event.$multiDate.attr( 'checked', false ).trigger( 'change' );

				}

			}

			// Set venue & programme select lists to the correct value
			dis.event.$venueSelect.val( editEvent.venue_id );
			dis.event.$progSelect.val( editEvent.prog_id );

			// Change submit button text
			dis.$submit.val( 'Save changes' );

			dis.editFlag = 'yes'; // Set edit flag

			// Remove messages
			dis.removeMessageDivs();

			// Set edit it
			dis.editID = editEvent.edit_id;

			// Show the add / edit div
			dis.showAddEditDiv();

		}


		/* When the user clicks 'edit' an event */
		$( 'div#cp-table-container' ).on( 'click', 'a.edit-event', function( event ) {

			rpsCP.editEvent( $(this) );
			event.preventDefault();

		});




	 /**
	 	* Edit an existing venue
	 	*/
		rpsCP.copyEvent = function( clicker ) {

			var dis = this,
				event = {
					prog_id: +clicker.attr( 'data-prog-id' ), // Get programme ID
					venue_id: +clicker.attr( 'data-venue-id' ) // Get venue ID
				};

			// Set select lists to appropraite values
			dis.event.$venueSelect.val( event.venue_id );
			dis.event.$progSelect.val( event.prog_id );

			// Set select button text
			dis.$submit.val( 'Add event' );

			// Set edit flag
			dis.editFlag = 'no';

			// Hide message divs, show add / edit div
			dis.removeMessageDivs();
			dis.showAddEditDiv();

		}


		/* If the user clicks 'copy' event */
		$( 'div#cp-table-container' ).on( 'click', 'a.copy-event', function( event ) {

			rpsCP.copyEvent( $(this) );
			event.preventDefault();

		});






	 /**
	 	* Edit an existing venue
	 	*/
		rpsCP.editVenue = function( clicker ) {

			var name = this.stripslashes( clicker.attr( 'data-name' ) ), // Get venue name
				url = clicker.attr( 'data-url' ), // URL
				address = clicker.attr( 'data-address' ), // address
				lat = +clicker.attr( 'data-lat' ), // Latitude
				lng = +clicker.attr( 'data-lng' ); // Longigutude

			this.editFlag = 'yes'; // Set edit flag
			this.eventProgVenue = 'venue';// For our AJAX refresh function
			this.editID = +clicker.attr( 'data-edit-id' ); // Get edit ID

			this.removeMessageDivs();

			// Set the input values
			this.venue.$venueName.val( name );
			this.venue.$venueURL.val( url );
			this.venue.$venueAddress.val( address );

			// If the map canvas has been appended to the div:
			if( $( '#map-canvas' ).length ) {

				// Remove it
				$( '#map-canvas' ).remove();

			}

			// If there are coordinates provided
			if( lat !== 0 && lng !== 0 ) {

				// Append map container to the top
				var $mapDiv = $( '<div id="map-canvas" />' );
				$( '#venue-section' ).append( $mapDiv );

				// Create a map
				this.createMap({
					name: name,
					address: address,
					url: url,
					lat: lat,
					lng: lng
				});

			}

			// Set header & submit button text / value
			this.$submit.val( 'Save changes' );

			// this.$dialog.dialog( 'open' );
			this.showAddEditDiv();


		}

		/* If the user clicks edit venue */
		$( 'div#cp-table-container' ).on( 'click', 'a.edit-venue', function( event ) {

			rpsCP.editVenue( $(this) );
			event.preventDefault();

		});



	 /**
	 	* Checks if a programme / venue is associated with an event
	 	*/
		rpsCP.progOrVenueAssociationCheck = function() {

			// Fix scope, check for more than one thing
			var dis = this,
				defaults = {
					multiple: false
				},
				vals = $.extend( defaults, dis.deleteVals );

			// show loading gif
			dis.$ajaxLoader.show();

			// AJAX data
			var data = {
				action: 'check-prog-venue-associations',
				IDs: vals.id,
				eventProgVenue: vals.type,
				_ajax_nonce: phpvars.nonce
			};

			// Make the AJAX call
			var myxhr = $.post( ajaxurl, data, function( results ) {

				// Hide the loading gif
				dis.$ajaxLoader.hide();

				var html,
					type,
					numRows = +results, // AJAX results returns the number of results - ie the num of events associated with the prog / venue
					s = ( numRows > 1 ) ? 's' : ''; // Whether or not to pluralise the thing type for messages (eg: venue/venues )

				// Sort out proper names for the thing types
				if( rpsCP.eventProgVenue === 'progs' ) {

					type = 'programme';

				} else if( rpsCP.eventProgVenue === 'venues' ) {

					type = 'venue';

				}

				console.log( rpsCP.eventProgVenue );

				// We're only deleting one thing
				if( vals.multiple === false ) {

					// No associated events - show a warning anyway
					if( numRows === 0 ) {

						html = '<p>Are you sure you want to delete the ' + type + ' <strong>' + rpsCP.deleteVals.name + '</strong>?</p>';

					// There are associated events
					} else {

						html = '<p>Are you sure you want to delete the ' + type + ' <strong>' + rpsCP.deleteVals.name + '</strong>?</p>';
						html += '<p>The ' + type + ' is associated with ' + numRows + ' event' + s + ' that will also be deleted!</p>';

					}

				// We're deleting mulitple things
				} else {

					// If there's only one item in the delete array
					if( vals.id.length == 1 ) {

						html = '<p>Are you sure you want to delete this ' + type + '?</p>';
						html += '<p>This ' + type + ' is associated with one or more events that will also be deleted!</p>';

					} else {

						html = '<p>Are you sure you want to delete these ' + type + 's?</p>';
						html += '<p>These ' + type + 's are associated with several events that will also be deleted!</p>';

					}

				}

				html += '<p><strong>This cannot be undone!</strong></p>';

				// Show the dialogue
				rpsCP.$dialog.html( html ).dialog( 'open' );

			});

		};



	 /**
	 	* Delete a programme
		*/
		rpsCP.deleteEventProgOrVenue = function(){

			var dis = this,
				defaults = {
					multiple: false // Whether or not there are multiple events to delete
				},
				vals = $.extend( defaults, dis.deleteVals );

			// Date to send via AJAX
			var data = {
				action : 'delete-event-prog-venue-ajax',
				delIDs: vals.id,
				eventProgVenue: dis.eventProgVenue,
				_ajax_nonce: phpvars.nonce
			};

			// Make the ajax call
		 	var jqxhr = $.post( ajaxurl, data, function( response ) {

				//console.log( vals, response );

				var i = 0,
					l = response.length,
					error = false;

				// Check our response array for error flags
				for( ; i < l; i++ ) {

					// If an error was produced when deleting any of the things
					if( response[i] === 'error' ){

						// Set error flag to true
						error = true;

						// Exit the loop
						break;

					}

				}

				var type, // Event, programme or venue
					params = {
						type: 'updated' // prepare messages
					};

				// No errors
				if( error === false ) {

					// Prepare feedback text
					switch( dis.eventProgVenue ){

						case 'prog' :
						case 'progs' :
							type = 'programme';
							break;

						case 'event' :
						case 'events' :
							type = 'event';
							break;

						case 'venue' :
						case 'venues' :
							type = 'venue';
							break;

					}

					// If only one item was affected
					if( vals.multiple === false ) {

						// Display a message
						params.message = '<p>The ' + type + ' <strong>' + vals.name + '</strong> has been deleted.</p>';

					// An array was used
					} else {

						// If the array has more than one value
						if( data.delIDs.length > 1 ) {

							// Display a (plural) message
							params.message = '<p>The ' + type + 's have been deleted.</p>';

						// This only happens if the user selects only one row via the checkbox
						// but we should account for this possibility and not display a confusing message
						} else {

							// Display a (singular) message
							params.message = '<p>The ' + type + ' has been deleted.</p>';

						}

					}

				// There were errors
				} else {

					// Display a message
					params.message = '<p>Sorry, something went wrong. Some ' + type + '(s) may not have been deleted. Please try again.</p>';
					params.type = 'error';

				}

				/* In all cases: */

				// Show message feedback
				dis.messageFeedback( params );

				// Hide the div & loading gif
				dis.hideAddEditDiv({ removeMessages: false });
				dis.$ajaxLoader.hide();

				// Reload our table
				dis.reloadAfterAjax();

				// Uncheck the checkboxes, reset select lists
				$( 'input.cp-checkbox-action' ).attr( 'checked', false );
				$( 'select#action' ).val( 0 );

			});

		}



		// If the user clicks the 'delete' link from the hidden table links
		$( 'div#cp-table-container' ).on( 'click', 'a.cp-delete', function( event ) {

			var $dis = $(this);

			// Set our delete object
			rpsCP.deleteVals = {
				id: [+$dis.attr( 'data-id' )], // Must be an array to account for multiple IDs
				name: rpsCP.stripslashes( $dis.attr( 'data-name' ) )
			};

/*
			// Set the correct page variable
			rpsCP.eventProgVenue = $( '#cp-table-container' ).attr( 'data-page' );
*/

			// If we're on the events page
			if( rpsCP.eventProgVenue === 'events' ) {

				// Message to display in the dialogue
				html = '<p>Are you sure you want to delete this event?</p>';
				html += '<p><strong>This cannot be undone!</strong></p>';

				// Open dialogue, show the message
				rpsCP.$dialog.html( html ).dialog( 'open' );

			// Otherwise
			} else {

				// Check the programme / venue associations
				rpsCP.progOrVenueAssociationCheck();

			}

			// Prevent default action
			event.preventDefault();

		});



		// When the 'delete' select list changes
		$( 'div#cp-table-container' ).on( 'change', 'select#action', function( event ) {

			// If the value is set to delete and there are some checked checkboxes
			if( $( this ).val() == 'delete' && $( 'input.cp-checkbox:checked' ).length ) {

				// Create an array to store the IDs of the things to delete
				var delIDs = [];

				// Loop through all checked checkboxes
				$.each( $( 'input.cp-checkbox:checked' ), function(){

					// Push them to the delIDs array
					delIDs.push( +$(this).val() );

				});

				// Values to delete
				rpsCP.deleteVals = {
					multiple: true, // multiple events have been selected
					id: delIDs // Array of IDs to be deleted
				};


				// Check the programme / venue associations
				rpsCP.progOrVenueAssociationCheck();

				// Invoke the method
				// rpsCP.deleteEventProgOrVenue();

			}

		});




	 /**
	 	* Submit venue. Check venue name is not empty and venue name does not already exist
		* If there are no errors, submit the venue to the DB
		*/
		rpsCP.submitVenue = function( clicker ){

			// Sort out scope, prepare error message handling
			var dis = this,
				params = {
					message: '',
					type: 'error'
				},
				errorMessages = [];

			// Reset added flag
			dis.addedFlag = false;

			// show loading gif
			dis.$ajaxLoader.show();

			// If the field is empty
			if( dis.venue.$venueName.val() == '' ) {

				errorMessages.push( '<p>You must provide a title for the new venue.</p>' );
					// Add focus venue name
				params.callback = function(){
					dis.venue.$venueName.focus();
				};

			}

			// If the URL field is not empty, do a bit of REGEX to check the URL
			if( !dis.venue.$venueURL.val() == '' && !dis.regExURL.test( dis.venue.$venueURL.val() ) ) {

				errorMessages.push( '<p>The venue URL (the website) seems invalid.</p>' );
				params.callback = function(){
					dis.venue.$venueURL.focus();
				};

			}

			// If there are error messages
			if( errorMessages.length ) {

				var i = 0, // counter
					l = errorMessages.length;

				// Loop through messages
				for( ; i < l; i++ ) {

					// Append messages to the DOM
					params.message += errorMessages[i];

				}

				// Show error message(s)
				dis.messageFeedback( params );

				// Hide loading gif
				dis.$ajaxLoader.hide();

			} else { // There are no errors (so far!)

				// Prepare data to send via AJAX
				var data = {
					action : 'new-venue-ajax',
					editflag: dis.editFlag,
					edit_id: +dis.editID,
					venue: {
						name: dis.venue.$venueName.val(),
						URL: dis.venue.$venueURL.val(),
						address: dis.venue.$venueAddress.val()
					},
					_ajax_nonce: phpvars.nonce
				};

				// Make the ajax call
			 	var jqxhr = $.post( ajaxurl, data, function( response ) {

					var params = {
						message: '',
						type: 'error'
					};

			 		// 0 = unchanged, 1 = ok, 'error' = error, 'exists' = programme exists
			 		switch( response.result ) {

						case 'exists' :

							// Venue name already exists
							params.message = '<p>The venue <strong>'  + data.venue.name + '</strong> already exists.</p>';
							params.callback = function(){
								// Clear venue name input & return focus to it
								dis.venue.$venueName.val( '' ).focus();
							};
							break;

						case 'error' :

							// Database error
							params.message = '<p>Sorry, there was an error submitting the venue to the database. Please try again.</p>';
							break;

						case 0 :

							// Nothing was changed
							params.message = '<p>Nothing was changed!</p>';

							// trigger the cancel button
							$( 'input#cancel' ).trigger( 'click' );

							break;

						case 1 :

							// Updated / added message
							params.message = '<p>The venue <strong>' + data.venue.name + '</strong> was successfully ' + response.addUpdate + '.</p>';
							params.type = 'updated';

							// Clear inputs
							$( 'input.cp-clear' ).val('');

							// Set added flag
							dis.addedFlag = true;

							// If we've edited venue
							if( dis.editFlag === 'yes' ){

								// Hide the add/edit div - keep the messages
								dis.hideAddEditDiv({ removeMessages: false });

								// If the maps div was added to the dom
								if( $( '#map-canvas' ).length ) {

									// Remove it
									$( '#map-canvas' ).remove();

								}

							}

							// Reload list of venues
							dis.reloadAfterAjax();

							// Reset values;
							dis.editFlag = 'no';
							dis.editID = 0;

							// Set submit button value
							dis.$submit.val( 'Add venue' );

							break;

					} // End switch statement

					// Log responses etc
					console.log( data, response, params );

					// In all cases, hide the loader gif and display the relevant message
					dis.$ajaxLoader.hide();
					dis.messageFeedback( params );

				});

			}

		}


		/* When the user clicks submit venue */
		$( 'input#venue-submit' ).click( function( event ){

			rpsCP.submitVenue( $(this) );
			event.preventDefault();

		});





	 /**
	 	* Edit an existing programme
	 	*/
		rpsCP.editProgramme = function( clicker ){

			// Remove messags
			this.removeMessageDivs();

			this.editFlag = 'yes'; // Set edit flag
			this.eventProgVenue = 'prog';// For our AJAX refresh function
			this.editID = +clicker.attr( 'data-edit-id' ); // Get edit ID

			var progTitle = this.stripslashes( clicker.attr( 'data-name' ) ), // Get programme title
				progDesc = $( 'td.content-full-' + this.editID ).html(); // Get programme desc

			// Set the input & textarea values
			this.prog.$progTitle.val( progTitle );
			tinyMCE.activeEditor.setContent( progDesc );

			// Set submit button text
			this.$submit.val( 'Save changes' );

			// Show the add/edit div
			this.showAddEditDiv();

		}


		/* When the user clicks 'edit' a programme */
		$( 'div#cp-table-container' ).on( 'click', 'a.edit-programme', function( event ){

			rpsCP.editProgramme( $(this) );
			event.preventDefault();

		});




	 /**
		* Submit a new event. Check required fileds are not empty
		* If new a venue name and a new programme title have been added...
		* check that they don't already exist.
		* If not and there are no errors, submit the event to the DB
		* Display messages to the user
	 	*/
		rpsCP.submitEvent = function( clicker ){

			var dis = this,
				errorMessages = []; // Array to store our error messages

			// Prepare our AJAX data in advance
			var data = {
				action : 'new-event-ajax',
				editflag: rpsCP.editFlag,
				edit_id: +rpsCP.editID,
				progSelectFlag: 0,
				venueSelectFlag: 0,
				event: {
					date: rpsCP.event.$date.val(),
					multidateflag: 0
				},
				prog: {},
				venue: {},
				_ajax_nonce: phpvars.nonce
			};

			// Remove message, show loading gif
			dis.removeMessageDivs();
			dis.$ajaxLoader.show();


			// If the date field is empty
			if( dis.event.$date.val() == '' ) {

				errorMessages.push( 'You must provide a date.' );

			// If the date is in the wrong format (must be YYYY-MM-DD)
			} else if( !dis.regExDate.test( dis.event.$date.val() ) ) {

				errorMessages.push( 'The date must be in the format YYYY-MM-DD. For example: 2012-25-12 ' );

			}


			// If multi-date is checked
			if( dis.event.$multiDate.is( ':checked' ) ) {

				// Add multi date flag to AJAX object
				data.event.multidateflag = 1;

				// If the start and end date have values
				if( dis.event.$date.val() != '' && dis.event.$endDate.val() != '' ) {

					// Convert the date to a format the Date object understands
					var sDate = dis.event.$date.val().replace( /-/g, "/" ),
						eDate = dis.event.$endDate.val().replace( /-/g, "/" );

					// Convert to a javascript Date object
					sDate = new Date( sDate );
					eDate = new Date( eDate );

					console.log( sDate, eDate );

					// If the end date is before the start date
					if( eDate <= sDate ){

						errorMessages.push( 'The end date for the event shoule be later the start date!' );

					}

				}

				// If the input is empty
				if( dis.event.$endDate.val() == '' ) {

					errorMessages.push( 'You must provide an end date for multi-day events.' );

				// If the date is in the wrong format (must be YYYY-MM-DD)
				} else if( !dis.regExDate.test( dis.event.$endDate.val() ) ) {

					errorMessages.push( 'The end date is not properly formatted. It must be in the format YYYY-MM-DD. For example: 2012-25-12 ' );

				}

			}


			//If there is a select list (if there are venues stored in the DB)
			if( dis.event.$venueSelect.length ) {

				// If no new venue has been added and the select menu hasn't been used
				// (the default value for '-- select a venue --' is '' )
				if( dis.venue.$venueName.val() == '' && dis.event.$venueSelect.val() == '' ) {

					errorMessages.push( 'Please add or select a venue.' );

				}

			} else {

				// no select list, just check the new venue input
				if( dis.venue.$venueName.val() == '' ) {

					errorMessages.push( 'Please add a venue.' );

				}

			}


			// If the url field is not empty, check the value is a proper URL
			if( !dis.venue.$venueURL.val() == '' && !dis.regExURL.test( rpsCP.venue.$venueURL.val() ) ) {

				errorMessages.push( 'There seems to be a problem with the venue URL' );

			}


			//If there is a select list (if there are programmes stored in the DB)
			if( dis.event.$progSelect.length ) {

				// If no new programme has been added and the select menu hasn't been used
				// (the default value for '-- select a prorgamme --' is '' )
				if( dis.prog.$progTitle.val() == '' && dis.event.$progSelect.val() == '' ) {

					errorMessages.push( 'Please add or select a programme.' );

				}

			} else {

				// no select list, just check the new venue input
				if( dis.prog.$progTitle.val() == '' ) {

					errorMessages.push( 'Please add a programme.' );

				}

			}

			// For our feedback message(s)
			var params = {
				message: '<p>There were some errors with the event you\'re adding</p>',
				type: 'error'
			};

			// If there are errors
			if( errorMessages.length ) {

				// Prepare for the loop
				var i = 0,
					l = errorMessages.length;

				// Create a ul of error messages (nice!)
				params.message += '<ul id="cp-errors-list">';

				// Loop through the error messages
				for( ; i < l; i++ ) {

					// Add a list item for each error message
					params.message += '<li>' + errorMessages[i] + '</li>';

				}

				// Close the 'ul'
				params.message += '</ul>';

				// Show the message
				dis.messageFeedback( params );

				// Hide ajaxLoader.
				dis.$ajaxLoader.hide();

				console.log( params );


			// no errors - prepare our AJAX call
			} else {


				// If it's a multi-date event
				if( data.event.multidateflag === 1 ) {

					// Provide the end date
					data.event.enddate = dis.event.$endDate.val();

				// otherwise, no end date and a time for the event
				} else {

					// Hour and minute values
					data.event.hour = +dis.event.$hour.val();
					data.event.min = +dis.event.$min.val();

				}


				// If the venue select list has been appended to the DOM
				if( dis.event.$venueSelect.length && dis.event.$venueSelect.val() != '' ) {

					// If the venue select list has been used, set our flag
					data.venueSelectFlag = 1;

					// Provide the ID of the venue from the select list
					data.venue_select_id = +dis.event.$venueSelect.val();

					// Get the name for later use
					data.venue.name = dis.event.$venueSelect.find( 'option:selected' ).text();

				// Otherwise, they added a new venue
				} else {

					// New venue details
					data.venue.name = dis.venue.$venueName.val();
					data.venue.URL = dis.venue.$venueURL.val();
					data.venue.address = dis.venue.$venueAddress.val();

				}


				// If the prog select list has been appended to the DOM
				if( dis.event.$progSelect.length && dis.event.$progSelect.val() != '' ) {

					// The select menu was used
					data.progSelectFlag = 1;

					// Provide the ID of the programme from the select list
					data.prog_select_id = +dis.event.$progSelect.val();

					// Get the name for later use
					data.prog.title = dis.event.$progSelect.find( 'option:selected' ).text();

				} else { // Otherwise they added a new programme

					// New programme details
					data.prog.title = dis.prog.$progTitle.val();
					data.prog.details = tinyMCE.activeEditor.getContent();

				}


				// Do that AJAX Voodoo baby
			 	var jqxhr = $.post( ajaxurl, data, function( response ) {

					// Give this a default value of error
					var params = {
						type: 'error',
						message: ''
					};

					console.log( response );

					// If there was an error with adding the programme, venue or event
					if( response.prog.result === 'error' || response.venue.result === 'error' || response.event.result === 'error' ) {

						params.message += '<p>Sorry! There was an error adding the event to the database. Snap. Please try again.</p>.'

					// No database errors:
					} else {

						// If both the programme & venue names have been used before
						if( response.prog.result === 'exists' && response.venue.result === 'exists' ){

							params.message += '<p>A programme called <strong>' + data.prog.title + '</strong> and a venue called <strong>' + data.venue.name + '</strong> already exist!</p>'
								+ '<p>Please select an existing programme or venue from the drop-down menu or add a new programme or venue.</p>';
							params.callback = function(){
								// Clear the prog title, venue name, focus on venue name input (it's first)
								rpsCP.prog.$progTitle.val( '' );
								rpsCP.venue.$venueName.val( '' ).focus();
							}

						// Programme name has been used before
						} else if( response.prog.result === 'exists' ) {

							params.message += '<p>A programme called <strong>' + data.prog.title + '</strong> already exists.</p>'
								+ '<p>Please select an existing programme from the drop-down menu or add a new programme.</p>';
							params.callback = function(){
								// Clear the prog title, focus on the input
								rpsCP.prog.$progTitle.val( '' ).focus();
							}


						// Venue name has been used before
						} else if( response.venue.result === 'exits' ) {

							params.message += '<p>The venue <strong>' + data.venue.name + '</strong> already exists.</p>'
								+ '<p>Please select an existing venue from the drop-down menu or add a new venue.</p>';
							params.callback = function(){
								// Clear the venue name, focus on the input
								dis.venue.$venueName.val( '' ).focus();
							}

						}

						// If the event was added / updated ok
						if( response.event.result === 1 ) {

							// Updated / added message
							params.message += '<p>The event: <strong>' + data.prog.title + '</strong> at <strong>' + data.venue.name + '</strong> on <strong>' +  response.date + '</strong> was successfully ' + response.addUpdate + '.</p>';
							params.type = 'updated';

							/* Now reset all the input fields, checkboxes, select lists etc */

							// Empty any input field we've used
							$( 'input.cp-clear' ).val( '' );
							tinyMCE.activeEditor.setContent( '' );

							// Uncheck the multidate checkbox
							dis.event.$multiDate.attr( 'checked', false ).trigger( 'change' );

							// Reset all the select lists
							dis.event.$hour.add( dis.event.$min ).attr( 'disabled', false ).val( 0 );

							dis.event.$venueSelect.add( dis.event.$progSelect ).val( 0 );

							// Hide new venue/prog fields
							dis.event.$venueHide.add( dis.event.$progHide ).hide();

							// Show add new venue/prog buttons
							dis.event.$showVenue.add( dis.event.$showProg ).show();

							// Hide the enddate input
							$( 'p.endDate' ).hide();

							// If we were editing something:
							if( dis.editFlag === 'yes' ) {

								//hide the add new div
								dis.hideAddEditDiv({ removeMessages: false });

							}

							// Reset values;
							dis.editFlag = 'no';
							dis.editID = 0;

							// Set submit button text / value
							dis.$submit.val( 'Add event' );

							// Reload the list of programmes
							dis.reloadAfterAjax();

							// If a new prog has been added
							if( data.progSelectFlag === 0 ) {

								// Create the new option to add to the list
								var newProgOption = '<option value="' + response.prog.id + '">' + data.prog.title + '</option>';

								// Check there'a s programme select list (there won't if there are no progs in the DB)
								if( !dis.event.$progSelect.length ) {

									// There's no list, we need to create it
									var newProgSelect = $( '<select />', {
										id: 'select-prog',
										name: 'select-prog',
										class: 'cp-select long select-prog'
									});

									// Append the default option
									newProgSelect.append( '<option value=""> -- select a programme -- </option>' ).append( newProgOption );

									// Add it to the dom
									$( 'p#select-list-prog-p' ).prepend( newProgSelect );

									// Add the 'or' inbetween the select list and the add new button
									$( 'select#select-prog' ).after( ' &nbsp;<strong>or</strong>&nbsp; ' );

								} else {

									// Append the new programme to the select list
									dis.event.$progSelect.append( newProgOption );

								}

								rpsCP.event.$progSelect = $( 'select#select-prog' );

							}

							// If a new venue has been added
							if( data.venueSelectFlag === 0 ) {

								// Create the new option to append to the venue list
								var newVenueOption = '<option value="' + response.venue.id + '">' + data.venue.name + '</option>';

								// Create the select list if it's not there
								if( !dis.event.$venueSelect.length ) {

									// Create the select list
									var newVenueSelect = $( '<select />', {
										id: 'select-venue',
										name: 'select-venue',
										class: 'cp-venue long select-prog'
									});

									// Append the default option and the newly created programme
									newVenueSelect.append( '<option value=""> -- select a venue -- </option>' ).append( newVenueOption );

									// Add it to the dom
									$( 'p#select-list-venue-p' ).prepend( newVenueSelect );

									// Add the new option
									$( 'select#select-venue' ).after( ' &nbsp;<strong>or</strong>&nbsp; ' );

								} else {

									// Append the new venue to the select list
									dis.event.$venueSelect.append( newVenueOption );

								}

								rpsCP.event.$venueSelect = $( 'select#select-venue' );

							}

						} else if( response.event.result === 0 ) {

							// Nothing was changed in the DB
							params.message = '<p>Nothing was changed</p>';

							// Trigger cancel action
							$( 'input#cancel' ).trigger( 'click' );

						}

						// Add whatever message to the DOM
						dis.messageFeedback( params );

					}

					// Log data
					console.log( data, response );

				});

			}

			dis.$ajaxLoader.hide();

		}


		/* When the user submits an event */
		$( 'input#event-submit' ).click( function( event ){

			rpsCP.submitEvent( $(this) );
			event.preventDefault();

		});


		/* When the user clicks on 'add new programme' on events page */
		rpsCP.event.$showProg.click( function showProgDiv( event ){

			// Show the new prog div, add focus to the prog title
			rpsCP.event.$progHide.fadeIn( 500 ).find( 'input:first' ).focus();

			// Reset venue select list, so there's no confusion over whether the select list or new venue should be used
			rpsCP.event.$progSelect.val( 0 );

			// Hide the button
			$( this ).hide();
			event.preventDefault();

		});

		/* When the user clicks on 'add new venue' on events page */
		rpsCP.event.$showVenue.click( function showVenueDiv( event ){

			// Show the new prog div, add focus to the prog title
			rpsCP.event.$venueHide.fadeIn( 500 ).find( 'input:first' ).focus();

			// Reset venue select list, so there's no confusion over whether the select list or new venue should be used
			rpsCP.event.$venueSelect.val( 0 );

			// Hide the button
			$( this ).hide();
			event.preventDefault();

		});




		// Submit prog. Check input is not empty and prog title does not already exist
		// If there are no errors, submit the programme to the DB
		rpsCP.submitProgramme = function( clicker ){

			var dis = this,
				noName,
				params = {
					type: 'error' // Set up feeback message stuff
				};

			// Hide any messages
			dis.removeMessageDivs();

			// If the field is empty
			if( dis.prog.$progTitle.val() == '' ) {

				// Provide feedback
				params.message = '<p>You must provide a title for the new programme.</p>';
				params.callback = function(){
					dis.prog.$progTitle.focus();
				};

				// Show message feedback
				dis.messageFeedback( params );

			// The title is not empty
			} else {

				// Show the loader gif
				dis.$ajaxLoader.show();

				// The data to send to our AJAX function
				var data = {
					action : 'new-programme-ajax',
					editflag: dis.editFlag,
					edit_id: +dis.editID,
					prog: {
						title: dis.prog.$progTitle.val(),
						details: tinyMCE.activeEditor.getContent()
					},
					_ajax_nonce: phpvars.nonce
				};

				// Make the request
			 	var jqxhr = $.post( ajaxurl, data, function( response ) {

			 		// 0 = unchanged, 1 = ok, 'error' = error, 'exists' = programme exists
			 		switch( response.result ) {

						case 'exists' :

							// If the programme title already exists
							params.message = '<p>The programme title <strong>'  + data.prog.title + '</strong> already exists. Please choose a different title.</p>';
							params.callback = function(){
								dis.prog.$progTitle.val( '' ).focus();
							};
							break;

						case 'error' :

							// Database error
							params.message = '<p>Sorry, there was an error submitting the programme to the database. Please try again.</p>';
							break;

						case 0 :

							// Nothing was changed
							params.message = '<p>Nothing was changed!</p>';

							// As nothing was changed, this is akin to clicking the cancel button, so click it!
							$( 'input#cancel' ).trigger( 'click' );

							break;

						case 1 :

							// Updated / added message
							params.message = '<p>The programme <strong>' + data.prog.title + '</strong> was successfully ' + response.addUpdate + '.</p>';
							params.type = 'updated';

							dis.reloadAfterAjax();

							// Clear content from input & textarea
							dis.prog.$progTitle.val('');
							tinyMCE.activeEditor.setContent('');

							// Set submit button value
							dis.$submit.val( 'Add programme' );

							// If we're editing a programme
							if( dis.editFlag == 'yes' ){

								// Hide the div, keep the messages
								dis.hideAddEditDiv({ removeMessages: false });

							}

							// Reset values;
							dis.editFlag = 'no';
							dis.editID = 0;

							break;

					} // End switch statement

					// Log responses etc
					console.log( data, response, params );

					// In all cases, hide the loader gif and display the relevant message
					dis.removeMessageDivs();
					dis.$ajaxLoader.hide();
					dis.messageFeedback( params );

				});

			}

		};


		/* When the user clicks submit submit */
		$( 'input#prog-submit' ).click( function( event ){

			// Call submit function
			rpsCP.submitProgramme( $(this) );
			event.preventDefault();

		});


	 /**
	 	* Pagination via AJAX
	 	*/
	 	// When the user clicks on pagination links
		$( '#cp-table-container' ).on( 'click', 'a.page-numbers', function( event ){

			// If we're already on the page, do nothing
			if( !$( this ).hasClass( 'current' ) ) {

				// Get the number of the 'page' to go to
				var pageNo = +$( this ).attr( 'data-pageno' );

				// Push the the page to the history
				history.pushState( null, 'Page: ' + pageNo , this.href );

				// Get the page we're on
				rpsCP.eventProgVenue = $( 'div#cp-table-container' ).attr( 'data-page' );

				// Reload our tables with the correct page number
				rpsCP.reloadAfterAjax( pageNo );

			}

			event.preventDefault();

		});



		/**************** MISC *******************/




	 /**
	 	* If the user clicks the esc key, hide the new / edit div
	 	*/
		$( document ).keyup( function( event ) {

			if ( event.keyCode == 27 ) {

				$( '#cancel' ).click();

			}

		});




	 /**
	 	* If the user clicks on a checkbox, select/deselect all table rows
	 	*/
		$( 'div#cp-table-container' ).on( 'change', 'input.cp-checkbox-action:checkbox', function(){

			// If the table head / foot checkbox is checked
			if( $( this ).is( ':checked' ) ) {

				// Check all the checkboxes
				$( 'input.cp-checkbox, input.cp-checkbox-action' ).attr( 'checked', true );

			// Otherwise
			} else {

				// Uncheck them
				$( 'input.cp-checkbox, input.cp-checkbox-action' ).attr( 'checked', false );

			}

		});



	 /**
	 	* Set number of events to display per page
	 	*/
		// Change number of results to display per page
		$( 'div#cp-table-container' ).on( 'change', 'select#num-events', function( event ) {

			// Get the number of results to display per page
			var numResults = +$( 'select#num-events option:selected' ).val();

			// Reset pagination, show number of results chosen
			rpsCP.reloadAfterAjax( 1, numResults );

		});



	 /**
	 	* If the user clicks the lable on the multidate checkbox: trigger the change
	 	*/
		$( 'span.checkbox_label' ).click( function(){

			$checkBox = $( 'input[name="multiDate"]' );

			if( $checkBox.is( ':checked' ) ) {

				$checkBox.attr( 'checked', false ).trigger( 'change' );

			} else {

				$checkBox.attr( 'checked', true ).trigger( 'change' );

			}

		});


	}); // The end

</script>

