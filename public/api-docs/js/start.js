$(function(){

	var token = localStorage.getItem("token")

	if(token){
		var ad = $.parseJSON(token)
		$(".login").hide()
		$(".company").text(ad.company)
		$(".client-auth").removeClass("w-hidden").show()
	} else {
		$(".client-welcome").show()
	}

	$('.btn-reset').click(function(e){
		e.preventDefault()
		localStorage.clear()
		$(".login").find('input[name="email"]').val("")
		$(".login").find('input[name="passwd"]').val("")
		$(".login,.client-welcome").removeClass("w-hidden").show()
		$(".client-auth").hide()
		window.scrollTo(0,0)
		return false
	})

	$('.login').submit(function(){
		var that = this
		, email = $(this).find('input[name="email"]').val()
		, passwd = $(this).find('input[name="passwd"]').val()
		, userd = {email:email}

		$(this).find('input[type="submit"]').prop("disabled",true).val("Please wait...")

		$.ajax({
			method:'post',
			url: endpoint + '/v1/auth/signin',
			success: function(res){
				if(res.status=='error'){
					$(that).parent().find('.w-form-fail').show()
				} else if(res.status=='success'){
					$(that).parent().find('.w-form-fail').show()
				} else {
					userd.name = res.data.email
					localStorage.setItem("token", JSON.stringify(res.data))
					$(that).parent().find('.w-form-done').show()					
					setTimeout(function(){
						location.href = 'api-docs.html'	
					},3000)		
				}
			},
			error : function(){
				$(that).parent().find('.w-form-fail').show()
				$(that).find('input[type="submit"]').prop("disabled",false).val("Authenticate")
			}
		})

		return false;
	})
})