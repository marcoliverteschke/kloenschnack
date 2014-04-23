<section class="tabbar hanging-tabs">
	<section class="container">
		<ul class="clearfix">
			<li><a href="/">kloenschnack</a></li>
			<li><a href="/logout">abmelden</a></li>
		</ul>
	</section>
</section>
<section class="container">
	<h1>Einstellungen</h1>
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
	<form action="/settings" method="POST">
		<input name="user[new_password]" type="password" placeholder="Passwort ändern" size="30">
		<input name="user[new_password_confirm]" type="password" placeholder="Passwort wiederholen" size="30">
		<input type="submit" value="Speichern">
	</form>
</section>
