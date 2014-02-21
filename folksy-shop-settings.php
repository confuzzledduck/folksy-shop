<div class="wrap">
	<h2>Folksy Shop Settings</h2>
	<form method="post" action="options.php">
	<table class="form-table">
<?php if ( !empty( $folksyShopOptions['folksy_username'] ) && ( false === $unlockFlag ) ) { ?>
		<tr>
		  <th><label for="folksy_shop_options[folksy_username]">Folksy username</label></th>
			<td>
				<input type="text" name="folksy_shop_options[folksy_username]" value="<?php echo $folksyShopOptions['folksy_username']; ?>" disabled="" style="color: #A0A0A0" />
				<input class="button" type="submit" value="Unlock" name="folksy_shop_options[unlock]" />
				<p style="margin-bottom: 0px;">Caution: unlocking the Folksy username will remove all currently synced items and shop categories. All other settings will be retained.</p>
			</td>
		</tr>
<?php } else { ?>
		<tr>
			<th><label for="folksy_shop_options[folksy_username]">Folksy username</label></th>
			<td>
				<input type="text" name="folksy_shop_options[folksy_username]" value="<?php echo $folksyShopOptions['folksy_username']; ?>" />
			</td>
		</tr>
<?php } ?>
		<tr>
			<th><label for="folksy_shop_options[folksy_unavailable_action]">When an item is unavailable on Folksy</label></th>
			<td>
				<select name="folksy_shop_options[folksy_unavailable_action]">
					<option value="quanitity" <?php echo ( isset( $folksyShopOptions['folksy_unavailable_action'] ) && ( 'quantity' == $folksyShopOptions['folksy_unavailable_action'] ) ) ? 'selected="selected"' : ''; ?>>Set quantity to zero</option>
					<option value="hide" <?php echo ( isset( $folksyShopOptions['folksy_unavailable_action'] ) && ( 'hide' == $folksyShopOptions['folksy_unavailable_action'] ) ) ? 'selected="selected"' : ''; ?>>Hide from site (set as draft)</option>
					<option value="delete" <?php echo ( isset( $folksyShopOptions['folksy_unavailable_action'] ) && ( 'delete' == $folksyShopOptions['folksy_unavailable_action'] ) ) ? 'selected="selected"' : ''; ?>>Delete from site (trash)</option>
					<option value="nothing" <?php echo ( !isset( $folksyShopOptions['folksy_unavailable_action'] ) || ( 'nothing' == $folksyShopOptions['folksy_unavailable_action'] ) ) ? 'selected="selected"' : ''; ?>>Do nothing</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="folksy_shop_options[folksy_images_download]">Download images as attachments</label></th>
			<td>
				<select name="folksy_shop_options[folksy_images_download]">
					<option value="yes" <?php echo ( isset( $folksyShopOptions['folksy_images_download'] ) && ( true == $folksyShopOptions['folksy_images_download'] ) ) ? 'selected="selected"' : ''; ?>>Yes</option>
					<option value="no" <?php echo ( !isset( $folksyShopOptions['folksy_images_download'] ) || ( false == $folksyShopOptions['folksy_images_download'] ) ) ? 'selected="selected"' : ''; ?>>No</option>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="folksy_shop_options[folksy_sections_slug]">Shop sections URL</label></th>
			<td>
				<input type="text" name="folksy_shop_options[folksy_sections_slug]" value="<?php echo $folksyShopOptions['folksy_sections_slug']; ?>" />
			</td>
		</tr>
		<tr>
			<th><label for="folksy_shop_options[folksy_items_slug]">Shop items URL</label></th>
			<td>
				<input type="text" name="folksy_shop_options[folksy_items_slug]" value="<?php echo $folksyShopOptions['folksy_items_slug']; ?>" />
			</td>
		</tr>
	</table>
	<p class="submit">
		<?php settings_fields( 'folksy_shop_options' ); ?>
		<input class="button button-primary" type="submit" value="Save Settings" name="save" />
	</p>
	</form>
</div>