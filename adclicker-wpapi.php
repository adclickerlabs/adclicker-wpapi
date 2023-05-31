<?php

/**
 * Plugin Name: AdClicker WP-API
 * Plugin URI: https://adclicker.io/
 * Description: AdClicker WP-API
 * Version: 1.0.4
 * Author: AdClicker
 * Author URI: https://github.com/braianstaimer
 * Requires at least: 4.0
 * Tested up to: 4.3
 *
 * Text Domain: AdflyScript
 * Domain path: /languages/
 */

include_once(ABSPATH . 'wp-includes/pluggable.php');

global $ADCLICKER_PATH_B64;
$ADCLICKER_PATH_B64 = "https://adclicker.io/url/#";

function fun_validate_valid_urls($url)
{
	$excluded_items = [
		"http"
	];

	foreach ($excluded_items as $item) {
		if (!str_contains($url, $item)) return false;
	}

	$include_all_except = get_option('adclicker_include_all_except', false);

	if ($include_all_except) {
		$excluded_domains = get_option('adclicker_excluded_domains', "");

		if (!$excluded_domains) return true; 

		$excluded_domains = explode(",", $excluded_domains);

		foreach ($excluded_domains as $domain) {
			if (str_contains($url, $domain)) return false;
		}

		return true;
	} else {
		$user_domains = get_option('adclicker_user_domains', "");
		if (!$user_domains) return false;

		$site_url = get_site_url();
		$site_url = str_replace("https://", "", $site_url);
		$site_url_split = explode(".", $site_url);
		$site_url = $site_url_split[count($site_url_split) - 2];

		$included_items = explode(",", $user_domains);

		foreach ($included_items as $item) {
			if (!$item) return false;
			else if (str_contains($url, $item)) return true;
		}

		return false;
	}
}


/**
 * Adclicker Integration
 */

function fun_generate_url($input)
{
	global $ADCLICKER_PATH_B64;

	$url = $input[1];

	if (!fun_validate_valid_urls($url)) return 'href="' . $url . '"';

	$user_id = get_option('adclicker_user_id', '');
	if (!$user_id) return 'href="' . $url . '"';

	$url_api = "api=" . $user_id . "&url=" . rawurlencode($url);
	$input = $ADCLICKER_PATH_B64 . base64_encode(base64_encode(base64_encode($url_api)));
	return 'id="download-section" class="download-link" target="_blank" href="' . $input . '"';
}

function generate_url($POST)
{
	$text = preg_replace_callback('#href="([^"]*)"#is', 'fun_generate_url', $POST);
	return $text;
}

if (!is_user_logged_in())
	add_filter('the_content', 'generate_url');
else {
	$show_ads = TRUE;
	$user = wp_get_current_user();
	$roles = (array) $user->roles;
	foreach ($roles as $rol) {
		if (
			$rol == 'author' ||
			$rol == 'editor' ||
			$rol == 'administrator'
		)
			$show_ads = FALSE;
	}
	if ($show_ads)
		add_filter('the_content', 'generate_url');
}

function display_adclicker_settings()
{
	add_menu_page('AdClicker Settings', 'AdClicker Settings', 'manage_options', 'adclickerwpapi', 'display_adclicker_settings_page');
}
add_action('admin_menu', 'display_adclicker_settings');

function display_adclicker_settings_page()
{
	if (isset($_POST['submit'])) {
		$user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : null;
		$user_domains = isset($_POST['user_domains']) ? explode(",", $_POST['user_domains']) : [];
		$include_all_except = isset($_POST['include_all_except']) ? $_POST['include_all_except'] : false;
		$excluded_domains = isset($_POST['excluded_domains']) ? explode(",", $_POST['excluded_domains']) : [];

		// validation and saving code

		update_option('adclicker_include_all_except', $include_all_except);
		update_option('adclicker_excluded_domains', implode(",", $excluded_domains));

		// more code
		update_option('adclicker_user_id', $user_id);
		update_option('adclicker_user_domains', implode(",", $user_domains));

		echo '<div class="notice notice-success is-dismissible"><p>Guardado exitosamente.</p></div>';
	}

	$user_id = get_option('adclicker_user_id', '');
	$user_domains = get_option('adclicker_user_domains', '');
	$include_all_except = get_option('adclicker_include_all_except', '');
	$excluded_domains = get_option('adclicker_excluded_domains', '');
?>
	<div class="wrap">
		<h1>AdClicker | Settings Panel</h1>
		<form method="post" action="">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="user_id">Id de usuario</label></th>
					<td><input name="user_id" type="text" id="user_id" value="<?php echo esc_attr($user_id); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="user_domains">Dominios (Ingrese los dominios a acortar separados por coma Ej. mega.nz, mediafire.com, 1ficher.com)</label></th>
					<td><input name="user_domains" type="text" id="user_domains" value="<?php echo esc_attr($user_domains); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="include_all_except">Incluir todos los dominios excepto</label></th>
					<td><input name="include_all_except" type="checkbox" id="include_all_except" value="1" <?php checked(1, $include_all_except, true); ?> /></td>
				</tr>
				<tr>
					<th scope="row"><label for="excluded_domains">Dominios a excluir (Ingrese los dominios a excluir separados por coma Ej. google.com, telegram.com, bit.ly)</label></th>
					<td><input name="excluded_domains" type="text" id="excluded_domains" value="<?php echo esc_attr($excluded_domains); ?>" class="regular-text" /></td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" />
			</p>
		</form>
	</div>
<?php
}
