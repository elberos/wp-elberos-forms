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


if ( !class_exists( MailSettings::class ) ) 
{

class MailSettings
{
	
	public static function update_key($key, $value)
	{
		if (!add_option($key, $value, "", "no"))
		{
			update_option($key, $value);
		}
	}
	
	
	public static function show()
	{
		
		if ( isset($_POST["nonce"]) && (int)wp_verify_nonce($_POST["nonce"], basename(__FILE__)) > 0 )
		{
			$enable = isset($_POST['enable']) ? $_POST['enable'] : '';
			$host = isset($_POST['host']) ? $_POST['host'] : '';
			$port = isset($_POST['port']) ? $_POST['port'] : '';
			$login = isset($_POST['login']) ? $_POST['login'] : '';
			$password = isset($_POST['password']) ? $_POST['password'] : '';
			$ssl_enable = isset($_POST['ssl_enable']) ? $_POST['ssl_enable'] : '';
			$email_to = isset($_POST['email_to']) ? $_POST['email_to'] : '';
			
			static::update_key("elberos_forms_mail_enable", $enable);
			static::update_key("elberos_forms_mail_host", $host);
			static::update_key("elberos_forms_mail_port", $port);
			static::update_key("elberos_forms_mail_login", $login);
			static::update_key("elberos_forms_mail_password", $password);
			static::update_key("elberos_forms_mail_ssl_enable", $ssl_enable);
			static::update_key("elberos_forms_mail_email_to", $email_to);
		}
		
		
		$item = 
		[
			'enable' => get_option( 'elberos_forms_mail_enable', '' ),
			'host' => get_option( 'elberos_forms_mail_host', '' ),
			'port' => get_option( 'elberos_forms_mail_port', '' ),
			'login' => get_option( 'elberos_forms_mail_login', '' ),
			'password' => get_option( 'elberos_forms_mail_password', '' ),
			'ssl_enable' => get_option( 'elberos_forms_mail_ssl_enable', '' ),
			'email_to' => get_option( 'elberos_forms_mail_email_to', '' ),
		];
		
		?>
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e('Mail Settings', 'elberos-forms')?></h2>
		<div class="wrap">			
			<form id="form" method="POST">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
				<div class="metabox-holder" id="poststuff">
					<div id="post-body">
						<div id="post-body-content">
							<div class="add_or_edit_form" style="width: 60%">
								<? static::display_form($item) ?>
							</div>
							<input type="submit" id="submit" class="button-primary" name="submit"
								value="<?php _e('Save', 'elberos-forms')?>" >
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
	
	
	
	public static function display_form($item)
	{
		?>
		
		<!-- Mail enable -->
		<p>
		    <label for="enable"><?php _e('Mail Enable:', 'elberos-forms')?></label>
		<br>
			<select id="enable" name="enable" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['enable'])?>" >
				<option value="" <?php selected( $item['enable'], "" ); ?>>Select value</option>
				<option value="yes" <?php selected( $item['enable'], "yes" ); ?>>Yes</option>
				<option value="no" <?php selected( $item['enable'], "no" ); ?>>No</option>
			</select>
		</p>
		
		<!-- Mail host -->
		<p>
		    <label for="host"><?php _e('Mail host:', 'elberos-forms')?></label>
		<br>
            <input id="host" name="host" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['host'])?>" >
		</p>
		
		
		<!-- Mail port -->
		<p>
		    <label for="port"><?php _e('Mail port:', 'elberos-forms')?></label>
		<br>
            <input id="port" name="port" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['port'])?>" >
		</p>
		
		
		<!-- Mail login -->
		<p>
		    <label for="login"><?php _e('Mail login:', 'elberos-forms')?></label>
		<br>
            <input id="login" name="login" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['login'])?>" >
		</p>
		
		
		<!-- Mail password -->
		<p>
		    <label for="password"><?php _e('Mail password:', 'elberos-forms')?></label>
		<br>
            <input id="password" name="password" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['password'])?>" >
		</p>
		
		
		<!-- Mail ssl enable -->
		<p>
		    <label for="ssl_enable"><?php _e('SSL Enable:', 'elberos-forms')?></label>
		<br>
			<select id="ssl_enable" name="ssl_enable" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['ssl_enable'])?>" >
				<option value="" <?php selected( $item['ssl_enable'], "" ); ?>>Select value</option>
				<option value="yes" <?php selected( $item['ssl_enable'], "yes" ); ?>>Yes</option>
				<option value="no" <?php selected( $item['ssl_enable'], "no" ); ?>>No</option>
			</select>
		</p>
		
		
		<!-- Mail email to -->
		<p>
		    <label for="email_to"><?php _e('Mail email to:', 'elberos-forms')?></label>
		<br>
            <input id="email_to" name="email_to" type="text" style="width: 100%"
				value="<?php echo esc_attr($item['email_to'])?>" >
		</p>
		
		
		<?php
	}
	
}

}