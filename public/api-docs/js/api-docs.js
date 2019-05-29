var showResponse = function(that,url,status,xhr) {
	$(that).next().removeClass('w-hidden')
	$(that).next().find('.request-url').text(url)
	$(that).next().find('.response-code').text(JSON.stringify(xhr.status, null, "\t"))
	$(that).next().find('.response-body').text(decodeURI(JSON.stringify(xhr.responseJSON, null, "\t")))
	$(that).next().find('.response-headers').text(xhr.getAllResponseHeaders())
}
, disableForm = function (form,value) {
	var submit = $(form).find('button[type="submit"]')
	if(submit){
		submit.prop("disabled",true).text("Enviando...")
	}
}
, enableForm = function (form,value) {
	var submit = $(form).find('button[type="submit"]')
	if(submit){
		submit.prop("disabled",false).text("Enviar")
	}
}

$(document).on('click','.m-toggle', function(){
	$(this).next().slideToggle()
})

$(document).on('submit','.method-form', function(){

	disableForm(this)

	var that = this
	, url = endpoint + $(this).attr('action')
	, authorization = $(this).find('input[name="header--Authorization"]').val()
	, method = $(this).attr('method')
	, dataArr = $(this).serializeArray()
	, beforeSend = null
	, data = {};

	for (var i in dataArr) {
		if( dataArr[i].name.indexOf('header--') == -1){
			data[dataArr[i].name] = dataArr[i].value
		}
	}

	if(data.id){
		url = url.replace('{id}',data.id)
		if($(data).length == 1){
			data = []
		}
	}

	if(authorization){
		beforeSend = function (xhr) { 
	    	xhr.setRequestHeader('Authorization', authorization)
	    }
	}

	$.ajax({
		method : method
		, url : url
		, contentType : "application/json; charset=utf-8"
		, dataType : "json"
		, beforeSend : beforeSend
		, data : method!='get'? JSON.stringify(data) : data
		, error : function(xhr,status){
			showResponse(that,url,status,xhr)
			enableForm(that)	
		}
	})
	.then(function(data, status, xhr) {
		showResponse(that,url,status,xhr)
		enableForm(that)
	})

	return false
})

$(function(){
	var token = localStorage.getItem("token")
	, ad = $.parseJSON(token)

	if(ad){
		$('.non-authorized-client').hide()
		$('.client-auth-code').text('Authorization: Bearer ' + ad.token)
		$('.header-links a').first().append(' to <strong>' + ad.first_name + '</strong>')
	}

	$('.wiki').html($.templates("#model").render(model,helper)).promise().done(function(){
		Webflow.require("tabs").ready()
	})
})