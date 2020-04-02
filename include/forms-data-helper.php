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


if ( !class_exists( FormsDataHelper::class ) ) 
{

class FormsDataHelper
{
	static $forms_settings;
	
	
	public static function get_forms_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'elberos_forms';
	}

	
	public static function get_forms_data_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'elberos_forms_data';
	}
	
	
	public static function load_forms_settings()
	{
		global $wpdb;
		$forms_table_name = static::get_forms_table_name();
		static::$forms_settings = $wpdb->get_results
		(
			$wpdb->prepare
			(
				"SELECT t.* FROM $forms_table_name as t", []
			),
			ARRAY_A
		);
	}
	
	
	public static function get_form_settings($form_id)
	{
		$forms_settings = static::$forms_settings;
		foreach ($forms_settings as $item)
		{
			if ($item['id'] == $form_id)
			{
				return @json_decode($item['settings'], true);
			}
		}
		return null;
	}
	
	
	
	public static function get_form_title($form_id)
	{
		$forms_settings = static::$forms_settings;
		foreach ($forms_settings as $item)
		{
			if ($item['id'] == $form_id)
			{
				return $item['name'];
			}
		}
		return null;
	}
	
	
	public static function get_field_settings($form_settings, $field_name)
	{
		if ($form_settings == null)
		{
			return null;
		}
		if (!isset($form_settings['fields']))
		{
			return null;
		}
		foreach ($form_settings['fields'] as $item)
		{
			if (!isset($item['name']))
			{
				continue;
			}
			if ($item['name'] == $field_name)
			{
				return $item;
			}
		}
		return null;
	}
	
	
	// Decode DATA keys
	public static function get_field_title($form_id, $key)
	{
		$form_settings = static::get_form_settings($form_id);
		$field_settings = static::get_field_settings($form_settings, $key);
		if ($field_settings == null) return $key;
		return $field_settings["title"];
	}
	
	
	// Decode UTM keys
	public static function decode_utm_key($key)
	{
		if ($key == "goal_type") return "";
		if ($key == "utm_source") return __('UTM Source', 'elberos-forms');
		if ($key == "utm_medium") return __('UTM Medium', 'elberos-forms');
		if ($key == "utm_campaign") return __('UTM Campaign', 'elberos-forms');
		if ($key == "utm_content") return __('UTM Content', 'elberos-forms');
		if ($key == "utm_term") return __('UTM Term', 'elberos-forms');
		return "";
	}
	
}

}