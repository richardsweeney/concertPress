
		<div class="tablenav top">

			<select id="action" name="action">
				<option value="-1" selected="selected"> -- Bulk Actions -- </option>
				<option value="delete">Delete</option>
			</select>

			<select id="num-events" name="num-events">
				<option value="10" selected="selected">Show 10 results per page</option>
				<option value="25">Show 25 results per page</option>
				<option value="50">Show 50 results per page</option>
				<option value="10000">Show all results</option>
			</select>

			<!-- <input type="submit" name="" id="doaction" data-type="event" class="delete-mulitple button-secondary action" value="Apply"> -->

			<?php $this->doPaginationPlease(); ?>

		</div>

		<form id="cp-programmes-form" action="" method="post">

			<table id="programme-table" class="widefat">

			<?php wp_nonce_field('concertpress-action') ?>

				<thead>

					<tr>
						<th scope="col"><input class="cp-checkbox-action" id="cp-action-checkbox-head" type="checkbox" /></th>
						<th scope="col">Event Date</th>
						<th scope="col">Programme</th>
						<!-- <th scope="col">Programme Description</th> -->
						<th scope="col">Venue</th>
						<!-- <th scope="col">Venue Address</th> -->
					</tr>

				</thead>

				<tfoot>

					<tr>
						<th scope="col"><input class="cp-checkbox-action" id="cp-action-checkbox-foot" type="checkbox" /></th>
						<th scope="col">Event Date</th>
						<th scope="col">Programme</th>
						<!-- <th scope="col">Programme Description</th> -->
						<th scope="col">Venue</th>
						<!-- <th scope="col">Venue Address</th> -->
					</tr>

				</tfoot>

				<tbody>

				<?php

					if( !empty( $events ) ) : foreach( $events as $event ) :

						$event = array_map( 'stripslashes_deep', $event );

						// If there's an event description
						if( !empty( $event['details'] ) ) {

							$args = array(
								'text' => $event['details'],
								'excerpt_length' => 7
							);
							// Create an excerpt
							$theExcerpt = $this->createExcerpt( $args );

						} else {

							//Otherwise just display a placeholder
							$theExcerpt = 'No programme details provided';

						}

						// Shorthand
						$eventID = $event['event_id'];
						$venueID = $event['venue_id'];
						$progID = $event['prog_id'];

						$eventDate = $event['date'];

						$startDate = date( 'Y-m-d', strtotime( $eventDate ) );
						$multiDate = $event['multidate'];
						$endDate = $event['enddate'];
						if( $multiDate == '1' ) {
							$endDate = date( 'Y-m-d', strtotime( $event['enddate'] ) );
						} else {
							$endDate = '';
						}
						$hour = date( 'H', strtotime( $eventDate ) );
						$min = date( 'i', strtotime( $eventDate ) );

						$niceEventDate = $this->echoNiceDate( $event['date'], $event['multidate'], $event['enddate'] );

						// Create edit link
						$editLink = '<a class="edit-event" data-event-id="' . $eventID . '" data-prog-id="' . $progID . '" data-venue-id="' . $venueID . '" data-date="' . $startDate . '" data-enddate="' . $endDate . '" data-multidate="' . $multiDate . '" data-hour="' . $hour . '" data-min="' . $min . '" href="#">';

						// Copy link
						$copyLink = '<a class="copy-event" data-event-id="' . $eventID . '" data-prog-id="' . $progID . '" data-venue-id="' . $venueID . '" href="#">Copy</a>';

						// Delete link
						$delLink = '<a class="cp-delete" data-type="event" data-id="' . $eventID . '" data-name="' . $event['title'] . '" href="#">Delete</a>';


						// Prog title
						$eventTitle = ( !empty( $event['title'] ) ) ? $event['title'] : 'No event detials provided';

						// Venue URL
						$venue = ( !empty( $event['url'] ) ) ? '<a href="' . $event['url'] . '" title="' . $event['name'] . '">' . $event['name'] . '</a>' : $event['name'];

						// Venue Address
						$venueAddress = ( !empty( $event['address'] ) ) ? $event['address'] : 'No address provided';


						?>

						<tr id="<?php echo $eventID; ?>" valign="top">

							<th scope="row" class="check-column"><input class="cp-checkbox" type="checkbox" name="event-checkox" value="<?php echo $eventID; ?>" /></th>

							<td class="cp_date">

								<?php echo $editLink; ?><abbr title="<?php echo $eventDate; ?>"><?php echo $niceEventDate; ?></abbr></a>

								<div class="row-actions">

									<?php if(!$archives): ?>
									<span class="edit"><?php echo $editLink . 'Edit</a>'; ?></span>
									|
									<span class="delete"><?php echo $delLink; ?></span>
									|
									<span class="copy"><?php echo $copyLink; ?></span>
									<?php else: ?>
									<span class="delete"><?php echo $delLink; ?></span>
									<?php endif; ?>

								</div>

							</td>

							<td class=""><?php echo $eventTitle; ?></td>

							<!-- <td class=""><?php echo $theExcerpt; ?></td> -->

							<td class=""><?php echo $venue; ?></td>

							<!-- <td class=""><?php echo $venueAddress; ?></td> -->

						</tr>

					<?php endforeach; else : ?>

						<tr>
							<th></th>
							<td><?php echo $noConcertMessage ; ?></td>
							<td></td><td></td><!-- <td></td><td></td> -->
						</tr>

					<?php	endif; ?>

				</tbody>

			</table>

		</form>

		<div class="tablenav">

			<?php $this->doPaginationPlease(); ?>

		</div>

	</div> <!-- #cp-add-container -->

</div> <!-- .wrap.cp-inside -->