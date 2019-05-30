<?php

/**
 * Adds support for Pematon's custom theme.
 * This includes meta headers, touch icons and other stuff.
 *
 * @author Peter Knut
 * @copyright 2014-2015 Pematon, s.r.o. (http://www.pematon.com/)
 */
class AdminerTheme
{
	/** @var string */
	private $themeName;

	/**
	 * @param string $themeName File with this name and .css extension should be located in css folder.
	 */
	function AdminerTheme($themeName = "default-blue")
	{
		define("PMTN_ADMINER_THEME", true);

		$this->themeName = $themeName;
	}

	/**
	 * Prints HTML code inside <head>.
	 * @return false
	 */
	public function head()
	{
		$userAgent = filter_input(INPUT_SERVER, "HTTP_USER_AGENT");
		?>

		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, target-densitydpi=medium-dpi"/>

		<link rel="icon" type="image/ico" href="images/favicon.ico">
		<link href="css/bootstrap-min.css" type="text/css" rel="stylesheet" />
		<link href="css/bootstrap-datetimepicker.min.css" type="text/css" rel="stylesheet" />
		<link href="css/font-awesome.min.css" type="text/css" rel="stylesheet" />
		<link href="css/summernote.css" type="text/css" rel="stylesheet" />
		<link href="css/source-sans-pro.css" type="text/css" rel="stylesheet" />
		<link rel="stylesheet" type="text/css" href="css/<?php echo htmlspecialchars($this->themeName) ?>.css?2">
		
		<?php
			// Condition for Windows Phone has to be the first, because IE11 contains also iPhone and Android keywords.
			if (strpos($userAgent, "Windows") !== false):
		?>
			<meta name="application-name" content="Adminer"/>
			<meta name="msapplication-TileColor" content="#ffffff"/>
			<meta name="msapplication-square150x150logo" content="images/tileIcon.png"/>
			<meta name="msapplication-wide310x150logo" content="images/tileIcon-wide.png"/>

		<?php elseif (strpos($userAgent, "iPhone") !== false || strpos($userAgent, "iPad") !== false): ?>
			<link rel="apple-touch-icon-precomposed" href="images/touchIcon.png"/>

		<?php elseif (strpos($userAgent, "Android") !== false): ?>
			<link rel="apple-touch-icon-precomposed" href="images/touchIcon-android.png?2"/>

		<?php else: ?>
			<link rel="apple-touch-icon" href="images/touchIcon.png"/>
		<?php endif; ?>


		<style>
		#map_canvas {
		    height: 200px;
		}
		</style>
		<?php if($_GET['edit'] === 'dealers'):?>
		<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAnCpylsXEujI2Jb07gggRfcewyYwJSbqU&libraries=places"></script>
		<?php endif;?>
		<script type="text/javascript" src="js/jquery.min.js"></script>
		<script type="text/javascript" src="js/bootstrap.min.js"></script>
		<script type="text/javascript" src="js/moment.js"></script>
		<script type="text/javascript" src="js/bootstrap-datetimepicker.js"></script>
		<script type="text/javascript" src="js/jsrender.min.js"></script>
		<script type="text/javascript" src="js/summernote.min.js"></script>

		<script type="text/x-jsrender" id="map">  
			<div id="map_canvas"></div>
        </script>

		<script type="text/javascript">

			$(function(){
			<?php if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"):?>
				$('#tables .select').each(function(){
					var t = $(this).text()
					if(t === t.toLowerCase()){
						$(this).css('display','none')
					}
				})
			<?php endif;?>

				$("#h1").attr("href","https://refocus.the-blue-ocean-company.com")
				
				$("input,textarea").each(function(){
					var name = $(this).attr("name")||""

					if(name.indexOf("_slug") > -1){
						//$(this).prop('readonly',true).css({opacity:0.5,cursor:'pointer'})

						$(this).css({opacity:0.5,cursor:'pointer'})
						var result = name.match(/\[(.*)\]/);
						var target = result[1].slice(0, result[1].lastIndexOf("_"));
						var $o = $('input[name="fields[' + target + ']"]');

						$o.on('keyup change',function(){
							var result = name.match(/\[(.*)\]/);
							var target = result[1].slice(0, result[1].lastIndexOf("_"));
							var $t = $('input[name="fields[' + target + '_slug]"]');
							$t.val(convertToSlug($(this).val().trim()))
						})
					}

					if(name.indexOf("_html") > -1){
						$(this).before('<div class="progress summernote-progress hide"><div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"><span class="sr-only"></span></div></div>')

						$(this).summernote({
						    height: 200,   
						    minHeight: null,
						    maxHeight: null,
						    onImageUpload: function(files, editor, welEditable) {
						      sendFile(files[0], editor, welEditable);
						    }
						  });
					}
					if(name.indexOf("_datetime") > -1){
						var format = 'YYYY-MM-DD HH:mm:ss';
						if(moment($(this).val(),'DD/MM/YYYY HH:mm:ss').isValid()){
							console.log("valid")
							$(this).val(moment($(this).val(),'DD/MM/YYYY HH:mm:ss').format(format))
						}

						$(this).datetimepicker({
							format: format
						})
					}

					if(name.indexOf("_places") > -1){

						var that = this
						setTimeout(function(){
							var lat = parseFloat($('input[name="fields[lat]"]').val())
							var lng = parseFloat($('input[name="fields[lng]"]').val())
							var title = $('input[name="fields[title]"]').val()

							$(that)
								.attr('id',name)

								$('#form')
									.before($.templates('#map').render({name:name}))

							initMap(lat,lng,title)
							initAutocomplete(name)
						},1000)
					}
				})
			})

			var map = null;
			var marker = null;

			function initMap(lat,lng,title) {
				var myLatLng = {lat: lat, lng: lng};

				map = new google.maps.Map(document.getElementById('map_canvas'), {
					center: myLatLng,
					zoom: 15,
					disableDefaultUI: true
				});
				
				marker = new google.maps.Marker({
					position: myLatLng,
					map: map,
					title: ""
				});
			}

			function moveMarker(lat,lng) {
			    var newLatLng = new google.maps.LatLng(lat, lng);
			    marker.setPosition(newLatLng);
			    map.setCenter(newLatLng);
			}

			function initAutocomplete (field){
			    var input = document.getElementById(field);
			    var options = {
			      componentRestrictions: {country: "ar"}
			    };

			    var autocomplete = new google.maps.places.Autocomplete(input,options);

			    autocomplete.addListener('place_changed', function() {
			      	var place = autocomplete.getPlace();
			      	if (!place.geometry) {
				        // User entered the name of a Place that was not suggested and
				        // pressed the Enter key, or the Place Details request failed.
				        console.log("No details available for input: '" + place.name + "'");
				        return;
				      }
				      if (place.geometry.viewport) {
				        var coords = place.geometry.location.toJSON();
				        var address = place.address_components;

				        moveMarker(coords.lat,coords.lng)

				        var inject = {
							lat: coords.lat,
							lng: coords.lng,
							locality: address[1].long_name,
							administrative_area_level_1: address[3].long_name,
							administrative_area_level_2: address[2].long_name,
							country: address[4].long_name,
							vicinity: place.vicinity,
							mapicon: place.icon,
							mapurl: place.url,
							formatted_address: place.formatted_address,
							utc: place.utc_offset
				        };

				        for(var i in inject){
							$('input[name="fields['+i+']"]').val(inject[i]);
				        };
				    }
			    });
			}


			<?php if(!empty($_REQUEST['select']) || !empty($_REQUEST['edit'])):?>

			$(() => {
				$('table.nowrap').show()
			})

			<?php endif;?>
			
			function convertToSlug(Text)
			{
			    return Text
			        .toLowerCase()
			        .replace(/[^\w ]+/g,'')
			        .replace(/ +/g,'-')
			        ;
			}

			function sendFile(file, editor, welEditable) {
			    data = new FormData();
			    data.append("file", file);
			    $(".summernote-progress").removeClass("hide").hide().fadeIn();
			    $.ajax({
			        data: data,
			        type: "POST",
			        xhr: function() {
			            var myXhr = $.ajaxSettings.xhr();
			            if (myXhr.upload) myXhr.upload.addEventListener("progress",progressHandlingFunction, false);
			            return myXhr;
			        },        
			        url: endpoint + "/upload/simple",
			        cache: false,
			        contentType: false,
			        processData: false,
			        success: function(url) {
			          $(".summernote-progress").fadeOut();
			          editor.insertImage(welEditable, url);
			        }
			    });
			}   

			function progressHandlingFunction(e){
			    if(e.lengthComputable){
			        var perc = Math.floor((e.loaded/e.total)*100);
			        $(".progress-bar").attr({"aria-valuenow":perc}).width(perc+"%");
			        // reset progress on complete
			        if (e.loaded == e.total) {
			            $(".progress-bar").attr("aria-valuenow","0.0");
			        }
			    }
			}		
		</script>

		<script>
			(function(window) {
				"use strict";

				window.addEventListener("load", function() {
					prepareMenuButton();
				}, false);

				function prepareMenuButton() {
					var menu = document.getElementById("menu");
					var button = menu.getElementsByTagName("h1")[0];
					if (!menu || !button) {
						return;
					}

					button.addEventListener("click", function() {
						if (menu.className.indexOf(" open") >= 0) {
							menu.className = menu.className.replace(/ *open/, "");
						} else {
							menu.className += " open";
						}
					}, false);
				}

			})(window);

			var endpoint = '<?php echo getenv('API_URL');?>';

		</script>

		<?php

		// Return false to disable linking of adminer.css and original favicon.
		// Warning! This will stop executing head() function in all plugins defined after AdminerTheme.
		return false;
	}

	function database() {
		return 'epicadv197_reframe';
	}	
}
