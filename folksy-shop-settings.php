<div class="wrap">
	<h2>Folksy Shop Settings</h2>
	<form method="post" action="options.php">
	<table class="form-table">
	  <tr>
		  <th><label for="folksy_shop_options[folksy_username]">Folksy username</label></th>
			<td>
			<?php if ( !empty( $folksyShopOptions['folksy_username'] ) && ( false === $unlockFlag ) ) { ?>
				<input type="text" name="folksy_shop_options[folksy_username]" value="<?php echo $folksyShopOptions['folksy_username']; ?>" disabled="" style="color: #A0A0A0" />
				<input class="button" type="submit" value="Unlock" name="folksy_shop_options[unlock]" />
				<p>Caution: unlocking the Folksy username will remove all currently synced items and shop categories.</p>
			<?php } else { ?>
				<input type="text" name="folksy_shop_options[folksy_username]" value="<?php echo $folksyShopOptions['folksy_username']; ?>" />
			<?php } ?>
			</td>
		</tr>
	</table>
	<p class="submit">
		<?php settings_fields( 'folksy_shop_options' ); ?>
		<input class="button button-primary" type="submit" value="Save Settings" name="save" />
	</p>
	</form>
</div>