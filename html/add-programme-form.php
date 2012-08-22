
<div class="prog-hide">

	<p>
		<label for="programmeTitle">Programme title</label>
		<input class="cp-clear" type="text" id="programme-title" name="programmeTitle">
	</p>

	<p>
		<label for="programmeDetails" id="prog-desc">Programme description (optional)</label><br>
		<?php
			$textEditorSettings = array(
				'media_buttons' => false,
				'textarea_name' => 'programmeDetails'
			);
			wp_editor( '', 'programme-details', $textEditorSettings );
		?>
	</p>

</div>