<?php

/*!
 *  Elberos Forms
 *
 *  (c) Copyright 2019-2020 "Ildar Bikmamatov" <support@elberos.org>
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


namespace Elberos\Forms;


if ( !class_exists( MailSender::class ) ) 
{
	
	class MailSender
	{
		
		/**
		 * Send mail
		 */
		public static function sendMail($title, $message, $params = [])
		{
			$uuid = isset($params['uuid']) ? $params['uuid'] : wp_generate_uuid4();
			$enable = get_option( 'elberos_forms_mail_enable', 'no' );
			$host = get_option( 'elberos_forms_mail_host', '' );
			$port = get_option( 'elberos_forms_mail_port', '' );
			$login = get_option( 'elberos_forms_mail_login', '' );
			$password = get_option( 'elberos_forms_mail_password', '' );
			$ssl_enable = get_option( 'elberos_forms_mail_ssl_enable', '' );
			$email_to = get_option( 'elberos_forms_mail_email_to', [] );
			
			if ($enable != "yes")
			{
				return [-2, 'Mail is Disable'];
			}
			
			// Create message
			$message = \Swift_Message::newInstance()
				->setFrom($login)
				->setContentType('text/html')
				->setCharset('utf-8')
				->setSubject("=?utf-8?b?" . base64_encode($title) . "?=")
				->setBody($message)
				->setMaxLineLength(998)
			;

			// Add User Agent
			$headers = $message->getHeaders();
			$headers->addTextHeader('User-Agent', 'PHP Swiftmail');
			
			// Add email to
			$message->setTo($email_to);
			
			// Add uuid
			//$message->setId($uuid);
			
			// Create transport
			$transport = \Swift_SmtpTransport::newInstance($host, $port);
			
			// Authentification
			$transport->setUsername($login)->setPassword($password);
			
			// Set ssl
			if ($ssl_enable) $transport->setEncryption('ssl');
			
			$error_code = 0;
			$error_message = "";
			try
			{
				$mailer = \Swift_Mailer::newInstance($transport);
				$result = $mailer->send($message);
				$error_code = 1;
				$error_message = "Ok";
			}
			catch (\Exception $e)
			{
				$error_code = -1;
				$error_message = $e->getMessage();
			}
			
			// Close connection
			$transport->stop();
			
			// Return
			return [$error_code, $error_message];
		}
		
		
		
		/**
		 *
		 */
		public static function getFormsMail($item)
		{
			$site_name = get_bloginfo("", "name");
			$form_id = $item['form_id'];
			$form_title = FormsDataHelper::get_form_title($form_id);
			$item_title = $item['title'];
			$title = "Новый заказ " . $form_title . " с сайта " . $site_name;
			
			$form_data_res = []; $form_data_utm = [];
			$form_data = @json_decode($item['data'], true);
			$form_utm = @json_decode($item['utm'], true);
			foreach ($form_data as $key => $value)
			{
				if ($value == "") continue;
				$key_title = FormsDataHelper::get_field_title($item['form_id'], $key);
				if ($key_title == "") continue;
				$form_data_res[] = [
					'key'=>$key,
					'title'=>$key_title,
					'value'=>$value,
				];
			}
			foreach ($form_utm as $key => $value)
			{
				if ($value == "") continue;
				$key_title = FormsDataHelper::decode_utm_key($key);
				if ($key_title == "") continue;
				$form_data_res[] = [
					'key'=>$key,
					'title'=>$key_title,
					'value'=>$value,
				];
			}
			
			if ($item_title != "")
			{
				array_unshift(
					$form_data_res,
					[
						'key'=>"",
						'title'=>"Название",
						'value'=>$item_title,
					]
				);
			}
			array_unshift(
				$form_data_res,
				[
					'key'=>"",
					'title'=>"Форма",
					'value'=>$form_title,
				]
			);
			array_unshift(
				$form_data_res,
				[
					'key'=>"",
					'title'=>"Сайт",
					'value'=>$site_name,
				]
			);
			
			$res_data = array_map
			(
				function($item)
				{
					return "
						<tr class='forms_data_item'>
							<td class='forms_data_item_key' style='padding: 2px; text-align: right;'>".
								esc_html($item['title']).":</td>
							<td class='forms_data_item_value' style='padding: 2px; text-align: left;'>".
								esc_html($item['value'])."</td>
						</tr>
					";
				},
				$form_data_res
			);
			
			ob_start();
			?>
			<html>
			<head>
			<title><?php echo $title; ?></title>
			</head>
			<body>
			<div style="font-family:verdana;font-size:16px">
			<h1>Новый заказ</h1>
			<table class="forms_data_display_item">
				<?php echo implode($res_data, ""); ?>
			</table>
			</div>
			</body>
			</html>
			<?php
			$message = ob_get_contents();
			ob_end_clean();
			
			return [$title, $message];
		}
		
		
		
		/**
		 * Cron send mail
		 */
		public static function cron_send_mail()
		{
			global $wpdb;
			
			// Load Forms Settings
			FormsDataHelper::load_forms_settings();
			
			// Load items
			$table_name = $wpdb->prefix . 'elberos_forms_data';
			$items = $wpdb->get_results
			(
				$wpdb->prepare
				(
					"SELECT t.* FROM $table_name as t
					WHERE
						send_email_code=0
					LIMIT 5",
					[]
				),
				ARRAY_A
			);
			
			foreach ($items as $item)
			{
				$send_email_uuid = $item['send_email_uuid'];
				if ($send_email_uuid == "") $send_email_uuid = wp_generate_uuid4();
				list ($title, $message) = static::getFormsMail($item);
				list ($send_email_code, $send_email_error) = 
					static::sendMail
					(
						$title,
						$message,
						[
							'uuid'=>$send_email_uuid
						]
					);
				
				//var_dump($message);
				
				$wpdb->query
				(
					$wpdb->prepare
					(
						"UPDATE $table_name SET
							send_email_uuid=%s,
							send_email_code=%d,
							send_email_error=%s
						WHERE id = %d",
						[
							$send_email_uuid,
							$send_email_code,
							$send_email_error,
							$item['id'],
						]
					)
				);
				
				flush();
			}
		}
		
	}
	
	
}