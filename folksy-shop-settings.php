<div class="wrap">
	<h2>Folksy Shop Settings</h2>
	<form method="post" action="options.php">
	<table class="form-table">
	  <tr>
		  <th>Folksy Username</th>
			<td><input type="text" name="folksy_shop_options[folksy_username]" value="<?php echo $folksyShopOptions['folksy_username']; ?>" /></td>
		</tr>
	</table>
	<p class="submit">
		<?php settings_fields( 'folksy_shop_options' ); ?>
		<input class="button button-primary" type="submit" value="Save Settings" name="save" />
	</p>
	</form>
</div>