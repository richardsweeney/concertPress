
<div class="wrap cp-inside" data-page="venues">

	<h2 id="section-header">Venues</h2>

	<p id="add-p" data-type="venue"><a href="#" class="button-secondary">Add a new venue</a></p>

	<div id="cp-add-container">

		<form method="post" action="" class="cp-form">

			<?php wp_nonce_field('concertpress_newvenue'); ?>

			<div class="add-event-section" id="venue-section">

			<?php include_once( 'add-venue-form.php' ); ?>

	  	<!-- <div id="map-canvas"></div> -->

			</div>

			<p>
				<input type="submit" class="cp-submit button-primary" name="submit" id="venue-submit" value="">
				<span id="or">or</span>
				<input type="submit" class="button-secondary" name="cancel" id="cancel" value="cancel">
				<img id="ajax-loader" src="<?php echo CP_URL; ?>img/ajax-loader.gif" alt="Loading…">
			</p>

		</form>

	</div>

	<div id="delete-container" title="Delete Venues"></div>

	<div id="cp-table-container" data-page="venues">


