"use strict";

/*!
 *  Elberos Forms
 *  URL: https://github.com/elberos/wp-elberos-forms 
 *
 *  (c) Copyright 2020 "Ildar Bikmamatov" <support@elberos.org>
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


/**
 * Send data
 */
function ElberosFormSendData ( form_api_name, send_data, callback )
{
	send_data['form_api_name'] = form_api_name;
	if (send_data['data'] == undefined) send_data['data'] = {};
	if (send_data['utm'] == undefined) send_data['utm'] = {};
	
	var gclid = null;
	try{
		gclid = ga.getAll()[0].get('clientId');
	}
	catch(ex){
	}
	if (gclid)
	{
		send_data['utm']['gclid'] = gclid;
	}
	
	var contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
	var processData = true;
	if (send_data instanceof FormData)
	{
		contentType = false;
		processData = false;
	}
	
	$.ajax({
		url: "/wp-json/elberos_forms/submit_form/",
		data: send_data,
		dataType: 'json',
		method: 'post',
		
		cache: false,
        contentType: contentType,
        processData: processData,
		
		beforeSend: (function(send_data){ return function(xhr)
		{
			// this picks up value set in functions.php to allow authentication
			// to be passed through with function so WP knows to allow deletion.
			xhr.setRequestHeader('X-WP-Nonce', send_data['_wpnonce']);
		}})(send_data),
		
		success: (function(callback){
			return function(data, textStatus, jqXHR)
			{
				if (data.success)
				{
					callback(data);
				}
				else
				{
					callback(data);
				}
			}
		})(callback),
		
		error: (function(callback){
			return function(data, textStatus, jqXHR){
				
				callback({
					message: "System error",
					code: -100,
				});
				
			}
		})(callback),
	});
	
}



/**
 * Submit form
 */
function ElberosFormSubmit ( $form, settings, callback )
{
	var form_api_name = settings.api_name;
	var form_title = settings.form_title != undefined ? settings.form_title : "";
	var wp_nonce = $form.find('.web_form__wp_nonce').val();
	
	var data = {};
	var arr = $form.find('.web_form__field_value');
	for (var i=0; i<arr.length; i++)
	{
		var $item = $(arr[i]);
		var name = $item.attr('data-name');
		data[name] = $item.val();
	}
	
	$form.find('.web_form__result').removeClass('.web_form__result--error');
	$form.find('.web_form__result').removeClass('.web_form__result--success');
	$form.find('.web_form__result').html('Ожидайте. Идет отправка запроса');
	ElberosFormClearFieldsResult( $form );
	
	ElberosFormSendData
	(
		form_api_name,
		{
			'_wpnonce': wp_nonce,
			'form_title': form_title,
			'data': data
		},
		(function($form, settings, callback){ return function (res)
		{
			var metrika_event = settings.metrika_event;
			var redirect = settings.redirect;
			
			if (callback != undefined && callback != null) callback(res);
			
			if (res.code == 1)
			{
				$form.find('.web_form__result').addClass('.web_form__result--success');
				if (settings.success_message == undefined)
				{
					$form.find('.web_form__result').html(res.message);
				}
				else
				{
					$form.find('.web_form__result').html(settings.success_message);
				}
				sendSiteEvent('metrika_event', metrika_event);
				setTimeout
				(
					(function(redirect){ return function(){ document.location = redirect; }})(redirect),
					1000
				);
			}
			else
			{
				$form.find('.web_form__result').addClass('.web_form__result--error');
				$form.find('.web_form__result').html(res.message);
				
				if (res.code == -2)
				{
					ElberosFormSetFieldsResult($form, res);
				}
			}
			
		}})($form, settings, callback),
	);
}



/**
 * Submit form
 */
function ElberosFormDialog($content, settings)
{
	var class_name = window['ElberosDialog'];
	if (settings.dialog_class_name != undefined && window[settings.dialog_class_name] != undefined)
	{
		class_name = window[settings.dialog_class_name];
	}
	
	var dialog = new class_name();
	dialog.setContent($content.html());
	dialog.open();
	if (settings.dialog_title != undefined) dialog.setTitle(settings.dialog_title);
	
	var callback = null;
	if (typeof settings.callback != "undefined") callback = settings.callback;
	else
	{
		callback = function (dialog, settings)
		{
			return function(res)
			{
				if (res.code == 1)
				{
					if (settings.auto_close == undefined) dialog.close();
					else if (settings.auto_close == true) dialog.close();
				}
			}
		};
	}
	
	dialog.$el.find('.button--submit').click(
		(function(dialog, settings){return function(){
			
			var $form = dialog.$el.find('form');
			
			ElberosFormSubmit
			(
				$form,
				settings,
				(callback != null) ? callback(dialog, settings) : null
			);
			
		}})(dialog, settings)
	);
	return dialog;
}



/**
 * Clear fields result
 */
function ElberosFormClearFieldsResult($form)
{
	$form.find('.web_form__field_result').html('');
	$form.find('.web_form__field_result').removeClass('web_form__field_result--error');
}



/**
 * Set fields result
 */
function ElberosFormSetFieldsResult($form, data)
{
	var arr = $form.find('.web_form__field_result');
	for (var i=0; i<arr.length; i++)
	{
		var $item = $(arr[i]);
		var name = $item.attr('data-name');
		var msg = "";
		if (data.fields != undefined && data.fields[name] != undefined)
		{
			msg = data.fields[name].join("<br/>");
		}
		$item.addClass('web_form__field_result--error');
		$item.html(msg);
	}
}