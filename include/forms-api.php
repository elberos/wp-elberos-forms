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


if ( !class_exists( Api::class ) ) 
{

class Api
{
	
	/**
	 * Get field by name
	 */
	public static function getFieldByName($fields, $field_name)
	{
		foreach ($fields as $field)
		{
			if ($field['name'] == $field_name)
			{
				return $field;
			}
		}
		return null;
	}
	
	
	
	/**
	 * Api submit form
	 */
	public function submit_form($params)
	{
		global $wpdb;
		
		$table_forms_name = $wpdb->prefix . 'elberos_forms';
		$table_forms_data_name = $wpdb->prefix . 'elberos_forms_data';
		$form_api_name = isset($_POST["form_api_name"]) ? $_POST["form_api_name"] : "";
		$form_title = isset($_POST["form_title"]) ? $_POST["form_title"] : "";
		$forms_wp_nonce = isset($_POST["_wpnonce"]) ? $_POST["_wpnonce"] : "";
		$wp_nonce_res = (int)wp_verify_nonce($forms_wp_nonce, 'wp_rest');
		
		/* Check wp nonce */
		if ($wp_nonce_res == 0)
		{
			return 
			[
				"success" => false,
				"message" => __("Ошибка формы. Перезагрузите страницу.", "elberos-forms"),
				"fields" => [],
				"code" => -1,
			];
		}
		
		/* Find form */
		$forms = $wpdb->get_results
		(	
			$wpdb->prepare
			(
				"select * from $table_forms_name where api_name=%s", $form_api_name
			),
			ARRAY_A,
			0
		);
		$form = isset($forms[0]) ? $forms[0] : null;
		if ($form == null)
		{
			return 
			[
				"success" => false,
				"message" => "Форма не найдена",
				"fields" => [],
				"code" => -1,
			];
		}
		
		$form_id = $form['id'];
		$form_settings = @json_decode($form['settings'], true);
		$form_settings_fields = isset($form_settings['fields']) ? $form_settings['fields'] : [];
		$form_data = [];
		$data = isset($_POST["data"]) ? $_POST["data"] : [];
		$utm = isset($_POST["utm"]) ? $_POST["utm"] : [];
		
		/* Validate fields */
		$fields = [];
		foreach ($data as $key => $value)
		{
			$field = static::getFieldByName($form_settings_fields, $key);
			if ($field == null)
			{
				continue;
			}
			
			$title = isset($field['title']) ? $field['title'] : "";
			$required = isset($field['required']) ? $field['required'] : false;
			if ($value == "" && $required)
			{
				$fields[$key][] = __("Пустое поле '" . $title . "'", "elberos-forms");
			}
			
			$form_data[$key] = $value;
		}
		
		/* Add missing fields */
		foreach ($form_settings_fields as $field)
		{
			$title = isset($field['title']) ? $field['title'] : "";
			$key = isset($field['name']) ? $field['name'] : "";
			if ($key == null)
			{
				continue;
			}
			if (isset($data[$key]))
			{
				continue;
			}
			$required = isset($field['required']) ? $field['required'] : false;
			if ($required)
			{
				$fields[$key][] = __("Пустое поле '" . $title . "'", "elberos-forms");
			}
			
			$form_data[$key] = "";
		}
		
		/* If validate fields error */
		if (count ($fields) > 0)
		{
			return 
			[
				"success" => false,
				"message" => __("Ошибка валидации данных", "elberos-forms"),
				"fields" => $fields,
				"code" => -2,
			];
		}
		
		/* Add UTM */
		$f_utm = isset($_COOKIE['f_utm']) ? $_COOKIE['f_utm'] : null;
		if ($f_utm) $f_utm = @json_decode( stripslashes($f_utm), true);
		if ($f_utm)
		{
			$utm['utm_source'] = isset($f_utm['s']) ? $f_utm['s'] : null;
			$utm['utm_medium'] = isset($f_utm['m']) ? $f_utm['m'] : null;
			$utm['utm_campaign'] = isset($f_utm['cmp']) ? $f_utm['cmp'] : null;
			$utm['utm_content'] = isset($f_utm['cnt']) ? $f_utm['cnt'] : null;
			$utm['utm_term'] = isset($f_utm['t']) ? $f_utm['t'] : null;
		}
		
		/* Insert data */
		$data_s = json_encode($form_data);
		$utm_s = json_encode($utm);
		$gmtime_add = gmdate('Y-m-d H:i:s');
		
		$q = $wpdb->prepare(
			"INSERT INTO $table_forms_data_name
				(
					form_id, form_title, data, utm, gmtime_add
				) 
				VALUES( %d, %s, %s, %s, %s )",
			[
				$form_id, $form_title, $data_s, $utm_s, $gmtime_add
			]
		);
		$wpdb->query($q);
		
		return
		[
			"success" => true,
			"message" => "Ok",
			"fields" => [],
			"code" => 1,
		];
	}
	
}

}