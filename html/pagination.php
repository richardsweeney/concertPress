
<!-- pagination -->

	<?php if( $this->pagination['paginate'] ) : ?>

	<div class="tablenav-pages">
	
		<span class="displaying-num"><?php echo $this->pagination['displayNum']; ?></span>
		
		<?php
			
			// Show pagination if appropriate
			if( !empty( $this->pagination['pageLinks'] ) ) {
			
				// This is an array of links so, we need to loop through them
				foreach( $this->pagination['pageLinks'] as $pageLink ) {
					
					// Echo the link
					echo $pageLink;
					
				}
				
			}
			
		?>
	
	</div>
	
	<?php endif; ?>