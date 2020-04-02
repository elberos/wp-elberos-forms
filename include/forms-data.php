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


if ( !class_exists( Data::class ) ) 
{

class Data
{
	public static function show()
	{
		$table = new Data_Table();
		$table->display();		
	}
}


class Data_Table extends \WP_List_Table 
{
	public $forms_settings = [];
	
	function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'elberos-forms-data',
            'plural' => 'elberos-forms-data',
        ));
    }
	
	function get_forms_settings_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'elberos_forms';
	}

	
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'elberos_forms_data';
	}
		
	// Вывод значений по умолчанию
	function get_default()
	{
		return array(
			'id' => 0,
			'form_id' => '',
			'data' => '',
			'utm' => '',
		);
	}
	
	// Валидация значений
	function item_validate($item)
	{
		return true;
	}
	
	// Колонки таблицы
	function get_columns()
    {
        $columns = array(
            'form_name' => __('Form Name', 'elberos-forms'),
            'form_title' => __('Form Title', 'elberos-forms'),
            'data' => __('DATA', 'elberos-forms'),
            'utm' => __('UTM', 'elberos-forms'),
            'gmtime_add' => __('Дата', 'elberos-forms'),
            'buttons' => __('', 'elberos-forms'),
        );
        return $columns;
    }
	
	// Сортируемые колонки
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', true),
            'api_name' => array('api_name', true),
        );
        return $sortable_columns;
    }
	
	// Действия
	function get_bulk_actions()
    {
		return null;
    }
	
	// Вывод каждой ячейки таблицы
	function column_default($item, $column_name)
    {
        return isset($item[$column_name])?$item[$column_name]:'';
    }
	
	// Заполнение колонки cb
	function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }
	
	
	// Колонка DATA
	function column_data($item)
	{
		$text = "";
		$arr = json_decode($item['data'], true);
		$res = [];
		foreach ($arr as $key => $value)
		{
			if ($value == "") continue;
			$title = FormsDataHelper::get_field_title($item['form_id'], $key);
			if ($title == "") continue;
			$res[] = $title . ": ". mb_substr($value, 0, 30);
		}
		return implode($res, "<br/>\n");
	}
	
	
	// Колонка UTM
	function column_utm($item)
	{
		$text = "";
		$arr = json_decode($item['utm'], true);
		$res = [];
		foreach ($arr as $key => $value)
		{
			if ($value == "") continue;
			$key = FormsDataHelper::decode_utm_key($key);
			if ($key == "") continue;
			$res[] = $key . ": ". mb_substr($value, 0, 30);
		}
		return implode($res, "<br/>\n");
	}
	
	function column_gmtime_add($item)
	{
		return \Elberos\wp_from_gmtime($item['gmtime_add']);
	}
	
	// Колонка name
	function column_buttons($item)
	{
		$actions = array(
			'show' => sprintf(
				'<a href="?page=elberos-forms-data&action=show&id=%s">%s</a>',
				$item['id'], 
				__('Show item', 'elberos-forms')
			),
		);
		
		return $this->row_actions($actions, true);
	}
	
	
	// Создает элементы таблицы
    function prepare_items()
    {
        global $wpdb;
        $table_name = $this->get_table_name();
        $forms_settings_table_name = $this->get_forms_settings_table_name();
		
        $per_page = 10; 

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
       
        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
		
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
		
        $this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*, forms.name as form_name FROM $table_name as t
				INNER JOIN $forms_settings_table_name as forms on (forms.id = t.form_id)
				ORDER BY $orderby $order LIMIT %d OFFSET %d",
				$per_page,
				$paged
			),
			ARRAY_A
		);


        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
	
	
    function process_bulk_action()
    {
    }
	
	function display_item()
	{
		global $wpdb;
		
		$action = $this->current_action();
		$table_name = $this->get_table_name();
		$message = "";
		$notice = "";
		$nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : false;
		$item = [];
		$default = $this->get_default();
		
		if (isset($_REQUEST['id']))
		{
			$item_id = (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
			$item = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $item_id), ARRAY_A
			);
			if (!$item)
			{
				$item = $default;
				$notice = __('Item not found', 'elberos-forms');
			}
		}
		else
		{
			$item = $default;
		}
		
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
		
		$res_data = array_map
		(
			function($item)
			{
				return "
					<tr class='forms_data_item'>
						<td class='forms_data_item_key'>".esc_html($item['title'])."</td>
						<td class='forms_data_item_value'>".esc_html($item['value'])."</td>
					</tr>
				";
			},
			$form_data_res
		);
		
		?>
		<style>
			.forms_data_display_item td{
				padding: 5px;
			}
			.forms_data_item_key{
				text-align: right;
				font-weight: bold;
			}
		</style>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h2><?php _e('Forms Data Item', 'elberos-forms')?></h2>
			<button type="button" class='button-primary' onclick="history.back();"> Back </button>
			<div class="metabox-holder" id="poststuff">
				<div id="post-body">
					<div id="post-body-content">
						<table class="forms_data_display_item">
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>ID</td>
								<td class='forms_data_item_value'><?php echo esc_html($item['id']); ?></td>
							</tr>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>Title</td>
								<td class='forms_data_item_value'><?php echo esc_html($item['form_title']); ?></td>
							</tr>
							<?php echo implode($res_data, ""); ?>
							<tr class='forms_data_item'>
								<td class='forms_data_item_key'>Дата</td>
								<td class='forms_data_item_value'><?php echo esc_html(\Elberos\wp_from_gmtime($item['gmtime_add'])); ?></td>
							</tr>
						</table>
					</div>
				</div>
			</div>
			
		</div>
		
		<?php
	}
	
	function display_table()
	{
		$this->prepare_items();
		$message = "";
		?>
		<div class="wrap">
			<h2><?php echo get_admin_page_title() ?></h2>

			<?php
			// выводим таблицу на экран где нужно
			echo '<form action="" method="POST">';
			parent::display();
			echo '</form>';
			?>

		</div>
		<?php
	}
	
	function display()
	{
		FormsDataHelper::load_forms_settings();
		
		$action = $this->current_action();
		if ($action == 'show')
		{
			$this->display_item();
		}
		else
		{
			$this->display_table();
		}
	}
	
}

}