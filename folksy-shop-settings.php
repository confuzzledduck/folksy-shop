<div class="wrap">
	<h2>Folksy Shop Settings</h2>
	<form method="post" action="options.php">
	<?php settings_fields( 'folksy_shop_options' ); ?>
	<p>
		Folksy Username: <input type="text" name="folksy_shop_options[folksy_username]" /><br />
		<input class="button-primary" type="submit" value="Save Settings" name="save" />
	</p>
	</form>
</div>