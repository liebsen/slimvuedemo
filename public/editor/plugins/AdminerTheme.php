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
		$apikey = 'AIzaSyAnCpylsXEujI2Jb07gggRfcewyYwJSbqU';
		//$apikey = 'AIzaSyALeVBzfq6VUT7Y9r3Zk_5ZiI8jHXAQ_lQ';
		$gmaps = ['users'];
		$graphics = ['quotes'];
		$noadd = ['quotes','quotes_items','contacts','config'];
		$nomod = ['quotes','quotes_items','contacts'];

		$userAgent = filter_input(INPUT_SERVER, "HTTP_USER_AGENT");
		?>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, target-densitydpi=medium-dpi"/>
		<link rel="icon" type="image/ico" href="images/favicon.ico">
		<link href="css/bootstrap-min.css" type="text/css" rel="stylesheet" />
		<link href="css/bootstrap-datetimepicker.min.css" type="text/css" rel="stylesheet" />
		<link href="css/font-awesome.min.css" type="text/css" rel="stylesheet" />
		<link href="css/summernote.css" type="text/css" rel="stylesheet" />
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

		<?php if(in_array($_GET['select'],$gmaps) || in_array($_GET['edit'],$gmaps)):?>
		<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $apikey;?>&libraries=places"></script>
		<?php endif;?>

		<?php if(in_array($_GET['select'],$graphics)):?>
		<script type="text/javascript" src="js/Chart.min.js"></script>
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
			<?php endif;?>

				var items = $('#tables .select').get();
				var ul = $('#tables');
				var gmaphides = ['locality','administrative_area_level_1','administrative_area_level_2','formatted_address','country','vicinity','map_icon','map_url','utc','lat','lng']

				items.sort(function(a,b){
				  var keyA = $(a).text();
				  var keyB = $(b).text();

				  if (keyA < keyB) return -1;
				  if (keyA > keyB) return 1;
				  return 0;
				});

				$.each(items, function(i, li){
					var t = $(li).text()
					if( t != t.toLowerCase()){
						$(li).addClass('enabled')
						$(li).attr('href', $(li).attr('href') + '&order[0]=id&desc[0]=1')

						if(t.split(' ')[0].length > 2){
							$(li).addClass('secondary')
						}
						$(li).text(t.replace(t.split(' ')[0],''))
						ul.append(li);
					}
				});

				$("#h1").attr("href","?username=<?php echo $this->database();?>").attr('target','_self')
				
				<?php if(!empty($_REQUEST['edit'])):?>

				var roles = [2]

				$("select").each(function(){
					var name = $(this).attr("name")||""

					if($.inArray(name,['fields[pulse_id]','fields[fraction_id]']) > -1){
						$(this).prop('readonly',true)
					}

					if(name.indexOf("[role_id]") > -1){
						$(this).find('option').each((id,item) => {
							const role_id = parseInt($(item).val())
							if($.inArray(role_id,roles) === -1){
								$(item).remove()
							}
						})
					}
				})

				$("input,textarea").each(function(){
					var name = $(this).attr("name")||""

					if($.inArray(name,['fields[id]','fields[uuid]']) > -1){
						$(this).prop('disabled',true)
					}

					if(name.indexOf("[created]") > -1 && this.value === ''){
						this.value = moment().format('YYYY-MM-DD HH:SS:00')
					}

					if(name.indexOf("[updated]") > -1 && this.value === ''){
						this.value = moment().format('YYYY-MM-DD HH:SS:00')
					}


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
								.after($.templates('#map').render())

							initMap([{
								lat:lat,
								lng:lng,
								title:title
							}])

							initAutocomplete(name)
						},1000)
					}
				})

			<?php endif;?>

			<?php if(empty($_REQUEST['where'])):?>

				$('#fieldset-search').parent().hide()

			<?php endif;?>

			<?php if(!empty($_REQUEST['select'])):?>

				$('.tabs').append('<a href="#" class="j-toggle" j-toggle="#form">Buscar 🔎</a>')

			<?php endif;?>

			<?php if(in_array($_REQUEST['edit'],['machines'])):?>

				var machine_fields = ['fields[ounce_id]','fields[pulse_id]','fields[fraction_id]']
				var ouncepulse = [0,1,2]
				var ouncefraction = [0,1,2]

				$('select[name="fields[ounce_id]"]').change((e) => {
					const selected = parseInt($(e.target).val())
					$('select[name="fields[pulse_id]"]').val(ouncepulse[selected])
					$('select[name="fields[fraction_id]"]').val(ouncefraction[selected])
				})

				$('select[name="fields[type_id]"]').change((e) => {
					const selected = parseInt($(e.target).val())
					if(selected != 1){
						$("select").each(function(){
							var name = $(this).attr("name")||""
							machine_fields.forEach((hide) => {
								if($.inArray(name, machine_fields) > -1){
									$(this).parent().parent().hide()
								}
							})
						});						
					} else {
						$("select").each(function(){
							var name = $(this).attr("name")||""
							machine_fields.forEach((hide) => {
								if($.inArray(name, machine_fields) > -1){
									$(this).parent().parent().show()
								}
							})
						});
					}
				})

				if($('select[name="fields[type_id]"]').val() != 1){
					$("select").each(function(){
						var name = $(this).attr("name")||""
						machine_fields.forEach((hide) => {
							if($.inArray(name, machine_fields) > -1){
								$(this).parent().parent().hide()
							}
						})
					});
				}


			<?php endif;?>

			<?php if(in_array($_REQUEST['edit'],$gmaps)):?>

				$("th").each(function(){
					gmaphides.forEach((hide) => {
						if($.inArray($(this).text(), gmaphides) > -1){
							$(this).parent().hide()
						}
					})
				});

			<?php endif;?>

			<?php if(in_array($_REQUEST['edit'],$nomod)):?>

				$('input,textarea,select').prop('disabled',true)
				$('#form p').hide()

			<?php endif;?>

			<?php if(in_array($_REQUEST['select'],$noadd)):?>

				$('.tabs a:first-child').hide()
				$('fieldset.jsonly').hide()

			<?php endif;?>

			<?php if(in_array($_REQUEST['select'],$nomod)):?>

				$('#table input').hide()
				$('#table thead td:first-child').text('')
				$('#table tbody td:first-child a').text('Ver')

			<?php endif;?>

			<?php if(in_array($_REQUEST['select'],$graphics)):?>

				var MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Deciembre'];
				var labels = []
				var data = []
				var totals = []

				$('#form').after("<div><canvas id='canvas'></canvas></div>")

				$("tr").each((id,item) => {

					var total = $(item).find('td[id*="[total]"]')
					var date = $(item).find('td[id*="[created]"]')

					if(date.length && total.length){
						var d1 = $(date).text().split('/')
						var m = parseInt(d1[1]) -1
						var d = parseInt(d1[0])
						var label = [d,MONTHS[m]].join('_')
						var total = parseFloat($(total).text())

						if(!totals[label]){
							totals[label] = 0
							labels.push(label)
						}

						totals[label]+= total
					}
				});

				for(var i in totals) {
					data.push(parseFloat(totals[i],2))
				}

				new Chart('canvas', {
					type: 'line',
					data: {
						labels: labels.reverse(),
						datasets: [{
							backgroundColor: '#5de16cb3',
							borderColor: '#009133',
							data: data.reverse(),
							label: 'Total',
							fill: 'start'
						}]
					}
				})


			<?php endif;?>


			<?php if(in_array($_REQUEST['select'],$gmaps)):?>

				var markers = []

				$("th").each(function(){
					gmaphides.forEach((hide) => {
						if($(this).text().indexOf(hide) > -1){
							$(this).hide()
						}
					})
				});

				$("td").each(function(){
					if($(this).attr('id')){
						gmaphides.forEach((hide) => {
							if($(this).attr('id').indexOf(hide) > -1){
								$(this).hide()
							}
						})

						if($(this).attr('id').indexOf('_places') > -1 && $(this).text().length){
							const lat = $(this).parent().find('td[id*=lat]').text()
							const lng = $(this).parent().find('td[id*=lng]').text()
							const title = $(this).text()
							markers.push({
								title:title,
								lat:lat,
								lng:lng
							})
						}
					}
				})

				if(markers.length){
					$('#form')
						.after($.templates('#map').render())
					initMap(markers)
				}

			<?php endif;?>

			})


			<?php if(in_array($_GET['select'],$gmaps) || in_array($_GET['edit'],$gmaps)):?>

			var map = null;
			var markers = [];
			var bounds = new google.maps.LatLngBounds();
			var infowindow = new google.maps.InfoWindow(); 


			function initMap(locations) {

				var myLatLng = {lat: 0, lng: 0};

				map = new google.maps.Map(document.getElementById('map_canvas'), {
					center: myLatLng,
					zoom: 10,
					disableDefaultUI: true
				});

				for (i = 0; i < locations.length; i++) {  
				  markers[i] = new google.maps.Marker({
				    position: new google.maps.LatLng(locations[i].lat, locations[i].lng),
				    map: map
				  });

				  //extend the bounds to include each marker's position
				  bounds.extend(markers[i].position);

				  google.maps.event.addListener(markers[i], 'click', (function(marker, i) {
				    return function() {
				      infowindow.setContent(locations[i].title);
				      infowindow.open(map, marker);
				    }
				  })(markers[i], i));
				}

				//now fit the map to the newly inclusive bounds
				map.fitBounds(bounds);

				//(optional) restore the zoom level after the map is done scaling
				/*
				var listener = google.maps.event.addListener(map, "idle", function () {
				    map.setZoom(12);
				    google.maps.event.removeListener(listener);
				});*/
			}

			function moveMarker(lat,lng) {
			    var newLatLng = new google.maps.LatLng(lat, lng);
			    markers[0].setPosition(newLatLng);
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

			<?php endif;?>


			<?php if(!empty($_REQUEST['select']) || !empty($_REQUEST['edit'])):?>

			$(() => {
				$('table').show()
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
					if (!menu) return
					var button = menu.getElementsByTagName("h1")[0];
					if (!button) return
					button.addEventListener("click", function() {
						if (menu.className.indexOf(" open") >= 0) {
							menu.className = menu.className.replace(/ *open/, "");
						} else {
							menu.className += " open";
						}
					}, false);

					$('.j-toggle').click((e) => {
						$($(e.target).attr('j-toggle')).slideToggle()
					})
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
		return 'puntoweber_db';
	}	
}
