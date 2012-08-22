

		<div class="tablenav top">

			<select id="action" name="action">
				<option value="-1" selected="selected"> -- Bulk Actions -- </option>
				<option value="delete">Delete</option>
			</select>

			<select id="num-events" name="num-events">
				<option value="10" selected="selected">Show 10 results per page</option>
				<option value="25">Show 25 results per page</option>
				<option value="50">Show 50 results per page</option>
				<option value="-1">Show all results</option>
			</select>

			<!-- <input type="submit" name="" id="doaction" data-type="venue" class="delete-mulitple button-secondary action" value="Apply"> -->

			<?php $this->doPaginationPlease(); ?>

		</div>

		<form id="cp-venues-form" action="" method="post">

			<?php wp_nonce_field('concertpress-action'); ?>

			<table id="venue-table" class="widefat">

				<thead>

					<tr>
						<th scope="col"><input class="cp-checkbox-action" id="cp-action-checkbox-head" type="checkbox" /></th>
						<th class="" scope="col">Name</th>
						<th class="" scope="col">URL</th>
						<th class="" scope="col">Address</th>
						<th class="" scope="col">Map coordinates</th>
					</tr>

				</thead>

				<tfoot>

					<tr>
						<th scope="col"><input class="cp-checkbox-action" id="cp-action-checkbox-foot" type="checkbox" /></th>
						<th scope="col">Name</th>
						<th scope="col">URL</th>
						<th scope="col">Address</th>
						<th class="" scope="col">Map coordinates</th>
					</tr>

				</tfoot>

				<tbody>
				<?php

					if( !empty( $venues ) ) : foreach( $venues as $venue ) :

					// Stripslashes from all programmes
					$venue = array_map( 'stripslashes_deep', $venue );

					// Shorthand
					$venueName = $venue['name'];
					$venueAddress = empty( $venue['address'] ) ? 'No address provided' : $venue['address'];
					$venueURL = empty( $venue['url'] ) ? 'No URL provided' : '<a href="' . $venue['url'] . '">' . $venue['url'] . '</a>';
					$venueID = $venue['id'];

					$coordinates = ( $venue['lat'] != 0 ) ? 'lat: ' . $venue['lat'] . '&deg;, lng: ' . $venue['lng'] . '&deg;' : '';

					// Links with all the data stuff
					$editLink = '<a class="edit-venue" data-edit-id="' . $venueID . '" data-name="' . addslashes( $venueName ) . '" data-address="' . $venue['address'] . '" data-url="' . $venue['url'] . '" data-lat="' . $venue['lat'] . '" data-lng="' . $venue['lng'] . '" title="Edit Venue" href="#">';
					$delLink = '<a class="cp-delete" data-type="venue" data-id="' . $venueID . '" data-name="' . addslashes( $venueName ) . '" href="#">Delete</a>';
					?>

					<tr class="cp-table-row" id="<?php echo $venueID; ?>" valign="top">

						<th scope="row" class="check-column"><input class="cp-checkbox" type="checkbox" name="prog-checkox" value="<?php echo $venueID; ?>" /></th>

						<td class="venue-name">

							<strong><?php echo $editLink . $venueName . '</a>'; ?></strong>

							<div class="row-actions">

								<span class="edit"><?php echo $editLink . 'Edit</a>'; ?></span>
								|
								<span class="delete"><?php echo $delLink; ?></span>

							</div>

						</td>

						<td class="venue-url"><?php echo $venueURL; ?></td>

						<td class="venue-address"><?php echo $venueAddress; ?></td>

						<td class="venue-coordinates"><?php echo $coordinates; ?></td>

					</tr>

					<?php endforeach; else : ?>

					<tr id="no-venue-placeholder" valign="top">

						<th scope="row"></th>

						<td class=""><p>There are no venues in the database yet</p></td>
						<td></td><td></td><td></td>

					</tr>

				<?php endif; ?>

				</tbody>

			</table>

		</form>

		<div class="tablenav">

			<?php $this->doPaginationPlease(); ?>

		</div>

	</div>

</div>