
<div class="wrap cp-inside" data-page="events">

	<h2 id="section-header">Events</h2>

	<p id="add-p" data-type="event"><a href="#" class="button-secondary">Add a new event</a></p>

	<div id="cp-add-container">

		<form method="post" action="" class="cp-form">

			<?php wp_nonce_field('cp-new-event'); ?>

			<div class="add-event-section">

			<h3>Add a date &amp; time for the event</h3>

			<p>

				<label for="concertDate">Event date</label>

				<input type="text" placeholder="<?php echo date('Y-m-d'); ?>" id="concert-date" class="cp-clear date" name="concertDate">

				&nbsp;<strong>at</strong>&nbsp;

				<select name="concertHour" id="concert-hour">

					<option value="00"> -- </option>

					<?php for( $hour = 9; $hour <= 23; $hour ++ ) : ?>

						<option value="<?php echo str_pad( $hour, 2, "0", STR_PAD_LEFT ); ?>"><?php echo str_pad( $hour, 2, "0", STR_PAD_LEFT ); ?></option>

					<?php endfor; ?>

				</select>

				<strong>:</strong>

				<select name="concertMin" id="concert-min">

					<option value="00"> -- </option>

					<?php for( $min = 0; $min <= 55; $min += 5 ) : ?>

						<option value="<?php echo str_pad( $min, 2, "0", STR_PAD_LEFT ); ?>"><?php echo str_pad( $min, 2, "0", STR_PAD_LEFT ); ?></option>

					<?php endfor; ?>

				</select>

				<label for="multiDate" id="inline-label">

					&nbsp;&nbsp;

					<input type="checkbox" id="multi-date" name="multiDate" value="multiDate">

					<span class="cp_header checkbox_label">This is a multi-day event</span>

				</label>

			</p>

			<p id="less-pad" class="endDate">

				<label for="concertEndDate">Event end date</label>

				<input type="text" placeholder="<?php echo date('Y-m-d'); ?>" id="concert-end-date" class="cp-clear date" name="concertEndDate">

			</p>

			</div>


			<div class="add-event-section">

				<h3>Add or select a venue</h3>

				<p id="selectListVenueP">

					<?php $this->echoVenueOrProgSelectList( 'venue' ); ?>

					<a class="button-secondary" id="show-venue" href="#">add a new venue</a>

				</p>

				<?php include_once( 'add-venue-form.php' ); ?>

			</div>


			<div class="add-event-section">

				<h3>Add or a select a programme</h3>

				<p id="selectListProgP">

				<?php $this->echoVenueOrProgSelectList( 'prog' ); ?>

					<a class="button-secondary" id="show-prog" href="#">add a new programme</a>

				</p>

				<?php include_once( 'add-programme-form.php' ); ?>

			</div>

			<p>
				<input type="submit" class="cp-submit button-primary" name="submit" id="event-submit" value="">
				<span id="or">or</span>
				<input type="submit" class="button-secondary" name="cancel" id="cancel" value="cancel">
				<img id="ajax-loader" src="<?php echo CP_URL; ?>img/ajax-loader.gif" alt="Loading…">
			</p>

		</form>

	</div>

	<div id="delete-container" title="Delete Events"></div>

	<div id="cp-table-container" data-page="events">

