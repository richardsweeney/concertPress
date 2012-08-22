

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

			<!-- <input type="submit" name="" id="doaction" data-type="prog" class="delete-mulitple button-secondary action" value="Apply"> -->

			<?php $this->doPaginationPlease(); ?>

		</div>

		<form id="cp-programmes-form" action="" method="post">

			<table id="programme-table" class="widefat">
				<thead>
					<tr>
						<th scope="col"><input class="cp-checkbox-action" id="cp-action-checkbox-head" type="checkbox" /></th>
						<th class="" scope="col">Title</th>
						<th class="" scope="col">Details</th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th scope="col"><input class="cp-checkbox-action" id="cp-action-checkbox-foot" type="checkbox" /></th>
						<th scope="col">Title</th>
						<th scope="col">Details</th>
					</tr>
				</tfoot>

				<tbody>

				<?php

					if( !empty( $programmes ) ) : foreach( $programmes as $programme ) :

						// Stripslashes from all programmes
						$programme = array_map( 'stripslashes_deep', $programme );

						$args = array(
							'text' => $programme['details'],
							'excerpt_length' => 20
						);
						$theExcerpt = $this->createExcerpt( $args );

						// Shorthand
						$progID = $programme['id'];
						$progTitle = $programme['title'];
						$progDetails = $programme['details'];

						$editLink = '<a class="edit-programme" data-edit-id="' . $progID . '" data-name="' . addslashes( $progTitle ) . '" href="#">';
						$delLink = '<a class="cp-delete" data-type="prog" data-id="' . $progID . '" data-name="' . addslashes( $progTitle ) . '" href="#">Delete</a>';

					?>

					<tr class="cp-table-row" id="<?php echo $progID; ?>" valign="top">

						<th scope="row" class="check-column"><input class="cp-checkbox" type="checkbox" name="venue-checkox" value="<?php echo $progID; ?>" /></th>

						<td>

							<strong><?php echo $editLink . $progTitle . '</a>'; ?></strong>

							<div class="row-actions">

								<span class="edit"><?php echo $editLink . 'Edit</a>'; ?></span>
								|
								<span class="delete"><?php echo $delLink; ?></span>

							</div>

						</td>

						<td class="content-excerpt"><?php echo $theExcerpt; ?></td>

						<td class="hide content-full-<?php echo $progID; ?>"><?php echo $progDetails; ?></td>

					</tr>

					<?php endforeach; else : ?>

					<tr valign="top">

						<th scope="row"></th>

						<td class="">

							<p>There are no programmes in the database yet</p>

						</td>

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