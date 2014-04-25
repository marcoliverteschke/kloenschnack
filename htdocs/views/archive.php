<section class="tabbar hanging-tabs">
	<section class="container">
		<ul class="clearfix">
			<li><a href="/">kloenschnack</a></li>
			<li><a href="/logout">abmelden</a></li>
		</ul>
	</section>
</section>
<section class="container">
	<h1>Archiv / Suche</h1>
	<?php
		if(is_array($messages) && count($messages) > 0)
		{
			print '<ul class="messages">';
			foreach($messages as $message)
			{
				print sprintf('<li>%s</li>', $message);
			}
			print '</ul>';
		}
		if(is_array($errors) && count($errors) > 0)
		{
			print '<ul class="messages errors">';
			foreach($errors as $error)
			{
				print sprintf('<li>%s</li>', $error);
			}
			print '</ul>';
		}
	?>
	<form action="/archive" method="GET">
		<input name="search" type="search" placeholder="Suchbegriff eingeben" size="60" value="<?php print isset($search) && strlen($search) > 0 ? $search : ''; ?>">
		<input type="submit" value="Suchen">
	</form>
	
	<?php
		
		if(isset($hits) && strlen($hits) > 0)
		{
			print sprintf('<script>var hits = %s</script>', $hits);
			
			print '<section id="hits"></section>';
		}
		
	?>
</section>
