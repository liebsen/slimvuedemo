var model = [
	{
		"method" : "post"
		, "url" : "/v1/ecmalog"
		, "description" : "Informa de errores Javascript del usuario."
		, "headers" : []
		, "body" : [
			{
				"name" : "url"
				, "placeholder" : "https://puntoweberplast.com/account/cotizaciones"
				, "description" : "URL desde donde se detectó el error"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "line"
				, "placeholder" : "344"
				, "description" : "Línea de código del error"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "browser"
				, "placeholder" : "Safari 12"
				, "description" : "Nombre y versión del navegador"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "message"
				, "placeholder" : "ReferenceError: Can't find variable: _gaq"
				, "description" : "Informe del error"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "extra"
				, "placeholder" : ""
				, "description" : "Informe profundo del error"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]
	},
	{
		"method" : "put"
		, "url" : "/v1/testp"
		, "description" : "Comprueba que los métodos PUT y DELETE esten habilitados y correctamente configurados en el servidor."
		, "headers" : []
		, "body" : []
	},	
	{
		"method" : "post"
		, "url" : "/v1/auth/token"
		, "description" : "Obtiene las credenciales actuales del usuario."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : []
	},
	{
		"method" : "post"
		, "url" : "/v1/auth/signin"
		, "description" : "Autentifica a un usuario previamente creado por la administración."
		, "headers" : []
		, "body" : [
			{
				"name" : "email"
				, "placeholder" : "lorenaledesma@gmail.com"
				, "description" : "Email del usuario"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "password"
				, "placeholder" : "**********"
				, "description" : "Contraseña del usuario. (Solo seguro si via SSL)"
				, "datatype" : "String"
				, "required" : "required"				
			}

		]
	},
	{
		"method" : "post"
		, "url" : "/v1/auth/recover-password"
		, "description" : "Recupera sesión perdida."
		, "headers" : []
		, "body" : [
			{
				"name" : "email"
				, "placeholder" : "lorenaledesma@gmail.com"
				, "description" : "Email del usuario"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]
	},
	{
		"method" : "post"
		, "url" : "/v1/auth/update-password"
		, "description" : "Actualiza contraseña."
		, "headers" : []
		, "body" : [
			{
				"name" : "password"
				, "placeholder" : "*********"
				, "description" : "Nueva contraseña del usuario"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]
	},
	{
		"method" : "post"
		, "url" : "/v1/sections/{id}"
		, "description" : "Obtiene datos de una sección del Blog."
		, "headers" : []
		, "body" : [
			{
				"name" : "id"
				, "placeholder" : "terminos-y-condiciones"
				, "description" : "URL de la sección"
				, "datatype" : "String"
				, "required" : "required"				
			},
		]
	},
	{
		"method" : "post"
		, "url" : "/v1/account/formularcolor"
		, "description" : "Obtiene información para la selección de textura en el proceso de cotización. Obtiene los productos que cuyos costos fueron correctamente configurados en el módulo Base de datos. fueronnPrimer paso del asistente para cotizar."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : []
	},
	{
		"method" : "post"
		, "url" : "/v1/account/formularcolordatos"
		, "description" : "Obtiene información para la selección de textura en el proceso de cotización. Segundo paso del asistente para cotizar."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "id"
				, "placeholder" : "5"
				, "description" : "Identificación única de textura"
				, "datatype" : "Integer"
				, "required" : "required"				
			},
			{
				"name" : "code"
				, "placeholder" : "GTX"
				, "description" : "Codigo de textura"
				, "datatype" : "String"
				, "required" : "required"				
			}

		]
	},
	{
		"method" : "post"
		, "url" : "/v1/account/packs"
		, "description" : "Obtiene información sobre envases de productos disponibles."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : []
	},
	{
		"method" : "post"
		, "url" : "/v1/account/basededatos"
		, "description" : "Obtiene información para la configuración de costos."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : []
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/basededatosp"
		, "description" : "Actualiza información para la configuración de costos."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "user_colorants"
				, "placeholder" : "[Object object]"
				, "description" : "Serie preferida de costos de colorantes."
				, "datatype" : "Object"
				, "required" : "required"				
			},
			{
				"name" : "user_textures"
				, "placeholder" : "[Object object]"
				, "description" : "Serie preferida de costos de productos."
				, "datatype" : "Object"
				, "required" : "required"				
			}
		]
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/cargarformulaspropias"
		, "description" : "Obtiene información para generar una nueva formula basada en las preferencias del usuario."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : []
	},
	{
		"method" : "post"
		, "url" : "/v1/account/cargarformulaspropiasp"
		, "description" : "Genera una nueva formula basada en las preferencias del usuario."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "title"
				, "placeholder" : "Turquesa marino"
				, "description" : "Título del color"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "texture_id"
				, "placeholder" : "115"
				, "description" : "Identificación de la textura"
				, "datatype" : "Integer"
				, "required" : "required"				
			},
			{
				"name" : "base_id"
				, "placeholder" : "2"
				, "description" : "Identificación de la base"
				, "datatype" : "Integer"
				, "required" : "required"				
			},
			{
				"name" : "units"
				, "placeholder" : "[Object object]"
				, "description" : "Serie de colorantes necesarios para obtener esta tonalidad."
				, "datatype" : "Integer"
				, "required" : "required"				
			}
		]
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/quote"
		, "description" : "Obtiene información de una cotización."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "uuid"
				, "placeholder" : "b30796c1-ec8a-7a2-2971332c10fa443cd"
				, "description" : "Código de la cotización"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]	
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/quotep"
		, "description" : "Genera una nueva cotización. En base a la solicitud y la Base de Datos de la aplicación se calcula el costo exclusivo para cada usuario."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "user_id"
				, "placeholder" : "115"
				, "description" : "Identificación única de usuario."
				, "datatype" : "Integer"
				, "required" : "required"				
			},
			{
				"name" : "formula_id"
				, "placeholder" : "1488"
				, "description" : "Identificación única de fórmula."
				, "datatype" : "Integer"
				, "required" : "required"				
			},
			{
				"name" : "m2"
				, "placeholder" : "40.15"
				, "description" : "Cantidad de metros cuadrados a cubrir."
				, "datatype" : "Float"
				, "required" : "required"				
			},
			{
				"name" : "performance"
				, "placeholder" : "2.1"
				, "description" : "Se guarda la performance para estadísticas."
				, "datatype" : "Float"
			},
			{
				"name" : "qty"
				, "placeholder" : "8"
				, "description" : "Cantidad de envases solicitados."
				, "datatype" : "Integer"
				, "required" : "required"				
			},
			{
				"name" : "pack"
				, "placeholder" : "15KG"
				, "description" : "Identificación por código por cada envase."
				, "datatype" : "Integer"
				, "required" : "required"				
			},
			{
				"name" : "kg"
				, "placeholder" : "15"
				, "description" : "Cantidad de kgs. por cada envase."
				, "datatype" : "Float"
				, "required" : "required"				
			},
			{
				"name" : "texture"
				, "placeholder" : "GTX"
				, "description" : "Código de la textura"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "color"
				, "placeholder" : "Almendra"
				, "description" : "Código del color"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "consumer"
				, "placeholder" : "15"
				, "description" : "Cantidad de kgs. por cada envase."
				, "datatype" : "Integer"
				, "required" : "required"				
			},
			{
				"name" : "first_name"
				, "placeholder" : "Lorena"
				, "description" : "Nombre del comprador/a."
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "last_name"
				, "placeholder" : "Ledesma"
				, "description" : "Apellido del comprador/a"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "phone"
				, "placeholder" : "011 4557 8245"
				, "description" : "Teléfono del comprador/a"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "email"
				, "placeholder" : "comprador@gmail.com"
				, "description" : "Email del comprador."
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "comments"
				, "placeholder" : "Necesitan otra entrega para la próxima semana."
				, "description" : "Observaciones realizadas por el usuario/a."
				, "datatype" : "Text"
				, "required" : "required"				
			}
		]
	},
	{
		"method" : "post"
		, "url" : "/v1/account/colors"
		, "description" : "Obtiene listado de colores disponibles para determinada base/textura para cada usuario."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : []	
	},
	{
		"method" : "post"
		, "url" : "/v1/account/colord"
		, "description" : "Elimina una fórmula del listado. Importante: Una vez realizada esta operación no hay forma de recuperar los datos."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "id"
				, "placeholder" : "2355"
				, "description" : "Identificación de la fórmula"
				, "datatype" : "Integer"
				, "required" : "required"				
			}
		]	
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/comments"
		, "description" : "Guarda comentarios sobre una formulación."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "comments"
				, "placeholder" : "Base no reemplazable."
				, "description" : "Observaciones sobre la formulación."
				, "datatype" : "Text"
				, "required" : "required"				
			}
		]	
	},		
	{
		"method" : "post"
		, "url" : "/v1/account/quotes"
		, "description" : "Obtiene listado de cotizaciones realizadas y clientes."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "customer"
				, "placeholder" : "Lorena Ledesma"
				, "description" : "Filtro de cliente"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]	
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/color"
		, "description" : "Obtiene información sobre una fórmulación."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "uuid"
				, "placeholder" : "b30796c1-ec8a-7a2-2971332c10fa443cd"
				, "description" : "Identificación de la fórmula"
				, "datatype" : "Text"
				, "required" : "required"				
			}
		]	
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/formula/{id}"
		, "description" : "Obtiene información sobre una fórmula propia."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "id"
				, "placeholder" : "155"
				, "description" : "Identificación de la fórmula"
				, "datatype" : "Text"
				, "required" : "required"				
			}
		]	
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/excel"
		, "description" : "Genera listado excel de cotizaciones de acuerdo al periodo seleccionado"
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "since"
				, "placeholder" : "5-5-19"
				, "description" : "Fecha de inicio del periodo seleccionado"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "uuid"
				, "placeholder" : "5-6-19"
				, "description" : "Fecha de fin del periodo seleccionado"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]	
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/quote2pdf/{id}"
		, "description" : "Imprime PDF cotización."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "id"
				, "placeholder" : "fe51a780-3e9d-f7d-21461365f0ac32346"
				, "description" : "Identificación de la cotización"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]	
	},	
	{
		"method" : "post"
		, "url" : "/v1/account/color2pdf/{id}/{macid}"
		, "description" : "Imprime PDF de formulación de acuerdo al equipo seleccionado."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "id"
				, "placeholder" : "fe51a780-3e9d-f7d-21461365f0ac32346"
				, "description" : "Identificación de la fórmula"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "macid"
				, "placeholder" : "55"
				, "description" : "Identificación del equipo"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]	
	},
						{
		"method" : "post"
		, "url" : "/v1/account/formula3pdf/{id}/{macid}"
		, "description" : "Imprime PDF de fórmula propia de acuerdo al equipo seleccionado."
		, "headers" : [
			{
				"name" : "Authorization" 
				, "value" : "Bearer {jwt}"
				, "description" : "Identificación de cliente"
				, "required" : "required"				
			}
		]
		, "body" : [
			{
				"name" : "id"
				, "placeholder" : "fe51a780-3e9d-f7d-21461365f0ac32346"
				, "description" : "Identificación de la fórmula"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "macid"
				, "placeholder" : "55"
				, "description" : "Identificación del equipo"
				, "datatype" : "String"
				, "required" : "required"				
			}
		]	
	},
	{
		"method" : "post"
		, "url" : "/v1/navitems"
		, "description" : "Obtiene los datos básicos de la aplicación. Las secciones son cargadas on-demand."
		, "headers" : []
		, "body" : []
	},
	{
		"method" : "post"
		, "url" : "/v1/contact"
		, "description" : "Guarda un registro de contacto y notifica su contenido al administrador."
		, "headers" : []
		, "body" : [
			{
				"name" : "full_name"
				, "placeholder" : "Lorena Ledesma"
				, "description" : "Nombre completo del contactante"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "reason"
				, "placeholder" : "Entregas"
				, "description" : "Motivo de la consulta"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "email"
				, "placeholder" : "lorenaledesma@gmail.com"
				, "description" : "Email del contactante"
				, "datatype" : "String"
				, "required" : "required"				
			},
			{
				"name" : "comment"
				, "placeholder" : "Hola quería saber si las entregas son en el día. Muchas gracias."
				, "description" : "Consulta del contactante"
				, "datatype" : "Text"
				, "required" : "required"				
			}
		]
	}	
]
, helper = {
	getHeaders : function(value){
		var token = localStorage.getItem("token")
		, t = $.parseJSON(token)		
		return t ? 'Bearer ' + t.token : value
	},
	getAccessBadge : function(value){
		return Object.keys(value).length ? "👑" : ""
	}
	, getUrl : function(url,body) {
		var id = ""
		if(body.length) id = body[0].placeholder
		return url.replace('{id}',id)
	}
	, getEndpoint : function(){
		return endpoint
	}
}