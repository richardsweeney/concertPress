
<div class="wrap cp-inside" data-page="progs">

	<h2 id="section-header">Programmes</h2>

	<p id="add-p" data-type="prog"><a href="#" class="button-secondary">Add a new programme</a></p>

	<div id="cp-add-container">

		<form method="post" action="" class="cp-form">

			<div class="add-event-section">

			<?php include_once( 'add-programme-form.php' ); ?>

			</div>

			<p>
				<input type="submit" class="cp-submit button-primary" name="submit" id="prog-submit" value="">
				<span id="or">or</span>
				<input type="submit" class="button-secondary" name="cancel" id="cancel" value="cancel">
				<img id="ajax-loader" src="<?php echo CP_URL; ?>img/ajax-loader.gif" alt="Loading…">
			</p>

		</form>

	</div>

	<div id="delete-container" title="Delete Programmes"></div>

	<div id="cp-table-container" data-page="progs">