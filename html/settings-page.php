<div class="wrap">

	<br>
	<h2>Welcome to ConcertPress</h2>

	<div id="about-cp">

		<h3>ConcertPress is an events management plugin designed specifically for classical musicians.</h3>
		<p>You can view, add and edit events on the <strong>events</strong> page.</p>
		<p>New venues and programmes can be created whilst adding a new event. It's also possible to view, edit and add venues and programmes on the <strong>venues</strong> and <strong>programmes</strong> pages.</p>
		<p>Events that have occurred will be moved to the archives page both here and on the front end of the website.</p>
		<hr>
		<p>You can show events on your website by using the shortcode	<strong>[cpevents]</strong> on a page, post or custom post type.</p>
		<p>You can change the output of the shortcode with the following parameters:</p>

	 	<dl>

	 		<dt><strong>scope</strong> either 'future' or 'past'</dt>
		 		<dd>Display either upcoming or past events. Default: 'future'.</dd>

			<dt><strong>limit</strong> eg: 100</dt>
				<dd>The number of events to display (-1 = display all). Default: '-1'</dd>

	 		<dt><strong>show_excerpt</strong> either 'yes', or 'no'.</dt>
	 			<dd>Show excerpt or the full programme description. Default: 'yes'</dd>

	 		<dt><strong>excerpt_length</strong> eg: 10</dt>
	 			<dd>The length (in words) of the excerpt. Default: '10'</dd>

	 		<dt><strong>link_to_event</strong> either 'yes' or 'no'</dt>
	 			<dd>Whether or not to show a link to the full event details on a separate page. Default: 'yes'</dd>

	 		<dt><strong>link_text</strong> eg: 'read more'</dt>
	 			<dd>The text to display if a link to full event details is provided. Default 'View full event details'</dd>

	 		<dt><strong>link_container</strong> either 'p', 'div' or 'span'</dt>
	 			<dd>The container for the 'read more' link. Default: 'p'</dd>

	 		<dt><strong>show_maps</strong> either 'yes' or 'no'</dt>
	 			<dd>Whether or not to show a google map (only shown  single events). Default: 'yes'</dd>

	 		<dt><strong>no_event_message</strong></dt>
	 			<dd>The message to display if there are currently no events Default: 'There are currently no events to display'</dd>

		</dl>

		<hr>

		<h3>A few examples</h3>

		<ul>
			<li>
				Default usage:<br>
				<strong>[cpevents]</strong>
			</li>
			<li>
				Show 10 events from the archives:<br>
				<strong>[cpevents scope="past" limit="10"]</strong>
			</li>
			<li>
				Show all upcoming events with full programme description. Don't show a link to full event details:<br>
				<strong>[cpevents show_excerpt="no" link_to_event="no"]</strong>
			</li>
			<li>
				Show the 20 next upcoming events with programme excerpt of no more than 5 words.<br>Show a link to a page with full details &amp; wrap the link in a span that contains the text 'More information':<br>
				<strong>[cpevents limit="20" excerpt_length="5" link_container="span" link_text="More information"]</strong>
			</li>
		</ul>

		<p><br>Developers note: you can use WordPress' do_shortcode() function to hard-code the shortcode into your theme like so:</p>
		<p><strong>&lt;?php echo do_shortcode('[cpevents limit="50"]'); ?&gt;</strong></p>
		<p>Or if you prefer:</p>
		<p><strong>&lt;?php global $rps_concertPress; ?&gt;<br>
			&lt;?php $options = array('limit' => 50); ?&gt;<br>
			&lt;?php echo $rps_concertPress->printEvents($options); ?&gt;</strong></p>

		<hr>

		<p>This plugin was written by me, <a href="http://richardsweeney.com/">Richard Sweeney</a> in 2011/2012. If you like twitter, I go by <a href="http://twitter.com/#!/richardsweeney/">@richardsweeney</a>.</p>
		<p>ConcertPress relies heavily on JavaScript and won't work at all if you have it disabled.</p>

	</div>

</div>