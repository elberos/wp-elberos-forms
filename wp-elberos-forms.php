<?php
/**
 * Plugin Name: Elberos Forms
 * Plugin URI:  https://github.com/elberos/elberos-forms
 * Description: Elberos Forms
 * Version:     0.1.0
 * Author:      Ildar Bikmamatov <support@elberos.org>
 * Author URI:  https://github.com/elberos
 * License:     Apache License 2.0
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
 
 
if ( !class_exists( 'Elberos_Forms_Plugin' ) ) 
{

class Elberos_Forms_Plugin
{
	
	/**
	 * Init Plugin
	 */
	public static function init()
	{
		add_action(
			'admin_init', 
			function()
			{
				require_once __DIR__ . "/include/forms-settings.php";
				require_once __DIR__ . "/include/forms-data.php";
				require_once __DIR__ . "/include/mail-settings.php";
			}
		);
		add_action('admin_menu', 'Elberos_Forms_Plugin::register_admin_menu');
		add_action('rest_api_init', 'Elberos_Forms_Plugin::register_api');
		add_action('send_headers', 'Elberos_Forms_Plugin::send_headers');
		
		// Add Cron
		add_filter( 'cron_schedules', 'Elberos_Forms_Plugin::cron_schedules' );
		if ( !wp_next_scheduled( 'elberos_forms_cron_send_mail' ) )
		{
			wp_schedule_event( time() + 60, 'elberos_forms_two_minute', 'elberos_forms_cron_send_mail' );
		}
		add_action( 'elberos_forms_cron_send_mail', 'Elberos\Forms\MailSender::cron_send_mail' );
	}	
	
	
	
	/**
	 * Cron schedules
	 */
	public static function cron_schedules()
	{
		$schedules['elberos_forms_two_minute'] = array
		(
			'interval' => 120, // Каждые 2 минуты
			'display'  => __( 'Once Two Minute', 'elberos-forms' ),
		);
		return $schedules;
	}
	
	
	
	/**
	 * Register Admin Menu
	 */
	public static function register_admin_menu()
	{
		add_menu_page(
			'Elberos Forms', 'Elberos Forms', 
			'manage_options', 'elberos-forms',
			function ()
			{
				\Elberos\Forms\Settings::show();
			},
			null
		);
		
		add_submenu_page(
			'elberos-forms', 
			'Forms data', 'Forms data', 
			'manage_options', 'elberos-forms-data', 
			function()
			{
				\Elberos\Forms\Data::show();
			}
		);
		
		add_submenu_page(
			'elberos-forms', 
			'Mail Settings', 'Mail Settings', 
			'manage_options', 'elberos-mail-settings', 
			function()
			{
				\Elberos\Forms\MailSettings::show();
			}
		);
	}
	
	
	
	/**
	 * Send headers
	 */
	public static function send_headers()
	{
		// headers
		$utm_source = isset($_GET['utm_source']) ? $_GET['utm_source'] : null;
		$utm_medium = isset($_GET['utm_medium']) ? $_GET['utm_medium'] : null;
		$utm_campaign = isset($_GET['utm_campaign']) ? $_GET['utm_campaign'] : null;
		$utm_content = isset($_GET['utm_content']) ? $_GET['utm_content'] : null;
		$utm_term = isset($_GET['utm_term']) ? $_GET['utm_term'] : null;
		$utm_place = isset($_GET['utm_place']) ? $_GET['utm_place'] : null;
		$utm_pos = isset($_GET['utm_pos']) ? $_GET['utm_pos'] : null;
		
		if (
			$utm_source != null ||
			$utm_medium != null ||
			$utm_campaign != null ||
			$utm_content != null ||
			$utm_term != null
		)
		{
			$utm = [
				's' => $utm_source,
				'm' => $utm_medium,
				'cmp' => $utm_campaign,
				'cnt' => $utm_content,
				't' => $utm_term,
				'pl' => $utm_place,
				'ps' => $utm_pos,
			];
			
			setcookie( "f_utm", json_encode($utm), time() + 7*24*60*60, "/" );
		}
	}
	
	
	
	/**
	 * Register API
	 */
	public static function register_api()
	{
		register_rest_route
		(
			'elberos_forms',
			'submit_form',
			array(
				'methods' => 'POST',
				'callback' => function ($arr){ return \Elberos\Forms\Api::submit_form($arr); },
			)
		);
	}
	
}

Elberos_Forms_Plugin::init();

require_once __DIR__ . "/include/forms.php";
require_once __DIR__ . "/include/forms-api.php";
require_once __DIR__ . "/include/forms-data-helper.php";
require_once __DIR__ . "/include/mail-sender.php";

}
