const Contact = {
  template: '#contact',
  name:'contact',
  created: function(){
    this.data.reason = this.hash
  },
  methods : {
    submit : function({type, target}){
      if(!this.acceptTerms){
        this.messageType = 'is-warning'
        this.message = "Debe aceptar los términos y condiciones"
      } else {
        if(filters.token().token){
          this.data.user_id = filters.token().id
          this.data.email = filters.token().email
          this.data.first_name = filters.token().first_name
          this.data.last_name = filters.token().last_name
        }
        this.loading = true
        this.$http.post('/v1/contact', this.data, {emulateJSON:true}).then(function(res){
          this.loading = false
          if(res.body.status==='success'){
            this.messageType = 'is-success'
            this.message = "Su mensaje ha sido enviado.<br>Gracias por tomarse el tiempo de escribirnos.<br>Le responderemos pronto."
          }
        }, function(error){
          this.loading = false
          this.messageType = 'is-danger'
          this.message = error.statusText
          console.log(error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:"",
      acceptTerms:false,
      data:{},
      hash: location.hash.replace('#','')
    }
  }
}

const SignIn = {
  template: '#signin',
  methods: {
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        this.message = ""
        this.messageType = ""
        var self = this
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/v1/auth/signin', data, {emulateJSON:true}).then(function(res){
          this.data = res.data
          if(res.data.status === 'success'){
            self.message = "La sesión fue iniciada correctamente. Redirigiendo..."
            self.messageType = "is-success"
            setTimeout(function(){
              localStorage.setItem("token", JSON.stringify(res.data))
              self.loading = false
              self.message = ""
              self.messageType = ""
              app.$router.push('/account')  
            },2000)
          } else {
            self.loading = false
            self.messageType = "is-danger"
            self.message = res.data.message 
          }
        }, function(error){
          console.log(error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:"",
      data:{}
    }
  }
}

const SignUp = {
  template: '#signup',
  methods: {
    submit : function({type, target}){
      if(!this.acceptTerms){
        this.messageType = 'is-danger'
        this.message = "Debes aceptar nuestros términos y condiciones."
      } else {
        this.loading = true
        this.$http.post('/v1/auth/signup', this.data, {emulateJSON:true}).then(function(res){
          if(res.data.status==='success'){
            this.loading = false
            this.messageType = 'is-success'
            this.message = "Se envió un email a tu cuenta. Por favor sigue el correspondiente enlace para validar tu cuenta."
            setTimeout(function(){
              self.message = ""
              self.messageType = ""
              app.$router.push('/sign-in')
            },15000)
          }
        }, function(error){
          this.loading = false
          this.messageType = 'is-danger'
          this.message = error.statusText
          console.log(error.statusText)
        })
      }
    }    
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:"",
      acceptTerms:false,
      data:{}
    }
  }
}

const RecoverPassword = {
  template: '#recoverpassword',
  methods: {
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        this.message = ""
        this.messageType = ""
        var self = this
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/v1/auth/recover-password', data, {emulateJSON:true}).then(function(res){
          if(res.data.status === 'success'){
            self.loading = false
            self.message = "Revisa tu email y sigue el enlace para recuperar tu contraseña."
            self.messageType = "is-success"
          } else {
            self.loading = false
            self.messageType = "is-danger"
            self.message = res.data.message 
          }
        }, function(error){
          console.log(error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:""
    }
  }
}

const UpdatePassword = {
  template: '#updatepassword',
  mounted:function(){
    this.token = this.$route.query.token||""
  },
  methods: {
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        this.message = ""
        this.messageType = ""
        var self = this
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/v1/auth/update-password', data, {emulateJSON:true}).then(function(res){
          this.data = res.data
          if(res.data.status === 'success'){
            self.loading = false
            self.message = "Actualizaste correctamente tu contraseña. Te redirigiremos a la sección de ingreso. Por favor inicia sesión..."
            self.messageType = "is-success"
            setTimeout(function(){
              self.loading = false
              self.message = ""
              self.messageType = ""
              app.$router.push('/sign-in')
            },10000)
          } else {
            self.loading = false
            self.messageType = "is-danger"
            self.message = res.data.message
          }
        }, function(error){
          console.log(error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      token:null,
      message:"",
      messageType:"",
      data:{}
    }
  }
}

const FormularColor = {
  template: '#formularcolor',
  name:'formularcolor',
  mounted:function(){
    var texture=JSON.parse(localStorage.getItem("texture"))||{}
    var self = this

    if(texture) {
      this.texture = texture
      this.selected = texture.id
    }

    localStorage.removeItem("quote")    
    localStorage.removeItem("texture")    
    this.$http.post('/v1/account/formularcolor', {}, {emulateJSON:true}).then(function(res){
      var ctrl = [], groups = { }
      this.data = res.data
      if(res.data.textures){
        // agrupamos por coincidencia en primera palabra del título (item.title).
        res.data.textures.forEach(function(item){
          var tag = item.title.split(' ')[0].toLowerCase()
          var list = groups[tag];
          if(list){
            list.push(item);
          } else {
            groups[tag] = [item];
          }
        })
        this.data.groups = groups
      }
      self.loading = false
    })
  },
  methods: {
    submit : function({type, target}){
      if(!this.selected){
        this.message = "Por favor seleccione un producto"
        return this.messageType = "is-warning"
      }
      if(!this.loading){
        this.$router.push('/formular-color-y-cotizar-datos')
      }
    },
    select : function({type, target}){
      var texture = JSON.parse(target.getAttribute('json'))
      if(texture){
        this.selected = texture.id
        localStorage.setItem("texture",JSON.stringify(texture))
        $('.wp-item').removeClass('selected')
        $(target).parents('.wp-item').addClass('selected')
      }
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:true,
      selected:0,
      message:'',
      messageType:'',
      texture:JSON.parse(localStorage.getItem("texture"))||{},
      data:{groups:{},message:{},status:''}
    }
  }
}

const FormularColorDatos = {
  template: '#formularcolordatos',
  name:'formularcolordatos',
  watch: {
    quote: {
      handler: function(data, oldData) {
        data.customer = [data.first_name,data.last_name].join(' ')
        data.colors = $('#color').find(':selected').html()
        localStorage.setItem("quote",JSON.stringify(data))
      },
      immediate: true,
      deep: true
    }    
  },
  mounted:function(){
    var self = this
    var token=JSON.parse(localStorage.getItem("token"))||{}
    var texture=JSON.parse(localStorage.getItem("texture"))||{}
    var quote=JSON.parse(localStorage.getItem("quote"))||{}
    if(!texture){
      alert("Algo no esta bien aguarde por favor...")
      return;
    }
    this.texture = texture
    this.quote = quote
    this.quote.performance = texture.performance
    this.quote.textures = texture.code
    this.quote.texture_id = texture.id
    this.quote.user_id = token.id

    if(typeof(texture.id) === 'undefined'){
      this.message = "Por favor seleccione una textura"
      return this.messageType = "is-warning"
    }

    this.$http.post('/v1/account/formularcolordatos', texture, {emulateJSON:true})
    .then(function(res){
      this.data = res.data
      this.pack_id = res.data.packs[0].id
      this.loading = false
    })
  },
  methods: {
    submit : function({type, target}){
      var quote=JSON.parse(localStorage.getItem("quote"))
      this.loading = true
      this.messageType = 'is-warning'
      this.message = "Actualizando cotización. Por favor espere..."
      this.$http.post('/v1/account/quotep', quote, {emulateJSON:true}).then(function(res){
        var quote = JSON.stringify(res.data)
        if(quote){
          delete quote.created
          delete quote.texture_hexcode
          if(res.status==='error'){
            self.messageType = 'is-error'
            self.message = "Hubo un error al guardar la cotización. Por favor vuelva a intentar en unos instantes."
          }
          localStorage.setItem("quote",quote)
          this.$router.push('/formular-color-y-cotizar-cotizacion')
        }
      })
    },
    updatePack: function(){
      this.updateQuote()
      this.updateM2()
    },
    updateM2: function () {
      this.updateQuote()
      const qty = (parseFloat(this.texture.performance * parseFloat(this.m2) / parseFloat(this.quote.kg)).toFixed(1))
      this.qty = qty
      this.quote.m2 = this.m2
      this.quote.qty = qty
    },
    updateQty: function () {
      this.updateQuote()
      const m2 = (parseFloat(parseFloat(this.qty)  / this.texture.performance * parseFloat(this.quote.kg)).toFixed(1))
      this.m2 = m2
      this.quote.qty = this.qty
      this.quote.m2 = m2
    },
    updateQuote: function () {
      if(this.pack_id && this.data.packs){
        var kg = 0
        this.data.packs.forEach((pack) => {
          if(pack.id===this.pack_id){
            kg = pack.kg
          }
        })
        this.quote.kg = kg  
        this.quote.pack_id = this.pack_id
        this.quote.packs = $('#pack').find(':selected').html()
      }      
    },
  },
  data: function() {
    return{
      filters:filters,
      loading:true,
      message:"",
      messageType:"",
      performance:0,
      packs:0,
      pack_id:0,
      kg:0,
      m2:0,
      qty:0,
      selection:{},
      data:{usertypes:{},colors:{},packs:{},performance:''},
      quote:{},
      texture:{}
    }
  }
}

const FormularColorCotizacion = {
  template: '#formularcolorcotizacion',
  name:'formularcolorcotizacion',
  watch: {
    selection: {
      handler: function(data, oldData) {
        if(oldData && Object.keys(oldData).length){
          this.submit(data)
        }
      },
      immediate: true,
      deep: true
    }
  },
  mounted:function(){
    var quote = JSON.parse(localStorage.getItem("quote"))
    this.quote = JSON.parse(JSON.stringify(quote))
    this.selection = {
      qty: quote.qty,
      discount: quote.discount,
      pack_id:quote.pack_id
    }

    this.$http.post('/v1/account/packs', {texture_id:quote.texture_id}, {emulateJSON:true}).then(function(res){
      this.packs = res.data
      this.loading = false
    })    
  },
  methods: {
    submit : function(selection){
      if(!this.loading){
        this.loading = true

        var data = {
          id:this.quote.id,
          discount:selection.discount||0,
          qty:selection.qty,
          pack_id:selection.pack_id,
          color_id:this.quote.color_id,
          texture_id:this.quote.texture_id
        }

        this.$http.post('/v1/account/quotep', data, {emulateJSON:true}).then(function(res){
          var quote = res.data

          if(res.status==='error'){
            this.messageType = 'is-error'
            this.message = "Hubo un error al guardar la cotización. Por favor vuelva a intentar en unos instantes."
          } else {
            this.messageType = ''
            this.message = ""
          }

          this.loading = false
          localStorage.setItem("quote",JSON.stringify(quote))
          this.quote = quote
        })
      }
    },
    download: function({type,target}){
      var uuid = $(target).attr('uuid')
      this.filters.download({
        url: '/v1/account/quote2pdf/' + uuid,
        target: target,
        type: 'application/pdf',
        filename: uuid + '.pdf',
        token: JSON.parse(localStorage.getItem("token"))
      })
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:true,
      message:"",
      messageType:"",
      data:{},
      packs:{},
      selection:{},
      quote:{}
    }
  }
}

const BaseDeDatos = {
  template: '#basededatos',
  name:'basededatos',
  mounted:function(){
    this.$http.post('/v1/account/basededatos', {}, {emulateJSON:true}).then(function(res){
      this.inputs = res.data.inputs 
      this.data = JSON.parse(JSON.stringify(res.data))
      this.selection.pack = parseInt(res.data.packs[0].id)
      this.loading = false
    })
  },
  methods: {
    submit: function(){
      if(!this.loading){
        var message = '';
        for(var i in this.inputs.user_colorants){
          if(this.inputs.user_colorants[i]===0||this.inputs.user_colorants[i].length===0){
            message = "Ingresa un valor para cada campo <b>Colorante</b>"
          }
        }
        if(this.inputs.iva === 0 || this.inputs.iva === ''){
          message = "Ingresa un porcentaje para el campo <b>IVA</b>"
        }
        if(this.inputs.margen === 0 || this.inputs.margen === ''){
          message = "Ingresa un porcentaje para el campo <b>Margen</b>"
        }
        if(message!=''){
          return this.message = message
        }
        this.loading = true
        this.$http.post('/v1/account/basededatosp', this.inputs, {emulateJSON:true}).then(function(res){
          if(res.data.status === 'success'){
            this.messageType = 'is-success'
            this.message = message = "Se ha actualizado la base de datos exitosamente"
            this.data.dates = res.data.dates  
          }
          this.loading = false;
        })
      }
    }
  },
  data: function() {
    return{
      loading:true,
      filters:filters,
      loading:true,
      message:"",
      messageType:"",
      data:{colorants:{},textures:{},user_colorants:{},user_textures:{},packs:{},bases:{},dates:{textures:'',colorants:''}},
      selection:{pack:0},
      dates:{},
      inputs:{colorants:{},textures:{},extra:{}}
    }
  }
}

const CotizacionesRealizadas = {
  template: '#cotizacionesrealizadas',
  name:'cotizacionesrealizadas',
  mounted:function(){
    if(!this.loading){
      this.loading = true
      this.selection.date_since = moment().subtract(1,'week').format('D-M-YY')
      this.selection.date_until = moment().format('D-M-YY')
      this.$http.post('/v1/account/quotes', {}, {emulateJSON:true}).then(function(res){
        this.data = res.data
        this.loading = false
      })    
    }
  },
  methods: {
    excel: function({type,target}){
      var since = this.selection.date_since.split('/').join('-')
      var until = this.selection.date_until.split('/').join('-')
      this.filters.download({
        url: '/v1/account/excel',
        data:{since:since,until:until,customer:this.selection.customer},
        target: target,
        type: 'text/csv',
        contentType:'application/x-www-form-urlencoded',
        filename: 'cotizaciones.csv',
        token: JSON.parse(localStorage.getItem("token"))
      })
    },
    filterCustomer: function({type,target}){
      if(!this.loading){
        this.loading = true
        this.$http.post('/v1/account/quotes', {customer: target.value}, {emulateJSON:true}).then(function(res){
          this.data = res.data
          this.loading = false
        })    
      }
    },
    more:function({type,target}){
      if(target.id){
        this.$router.push('/account/quote/' + target.id)
      }
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      selection:{date_since:'',date_until:''},
      data:{quotes:{},customers:{}}
    }
  }
}

const Quote = {
  template: '#quote',
  name:'quote',
  mounted:function(){
    if(!this.loading){
      this.loading = true
      this.$http.post('/v1/account/quote', {uuid: location.pathname.split('/').reverse()[0]}, {emulateJSON:true}).then(function(res){
        this.quote = res.data
        this.loading = false
      })    
    }
  },
  methods: {
    download:function({type,target}){
      var uuid = $(target).attr('uuid')
      this.filters.download({
        url: '/v1/account/quote2pdf/' + uuid,
        target: target,
        type: 'application/pdf',
        filename: uuid + '.pdf',
        token: JSON.parse(localStorage.getItem("token"))
      })
    }
  },
  data: function() {
    return{
      loading:false,
      messageType:'',
      message:'',
      filters:filters,
      quote:{}
    }
  }
}

const Color = {
  template: '#color',
  name:'color',
  mounted:function(){
    if(!this.loading){
      this.loading = true
      this.$http.post('/v1/account/color', this.getp(), {emulateJSON:true}).then(function(res){
        if(!this.mac_id){
          this.mac_id = res.data.machine.id
        }
        this.data.quote = res.data.quote
        this.data.machines = res.data.machines
        this.data.machine = res.data.machine
        this.data.formulas = res.data.formulas
        this.loading = false
      })    
    }    
  },
  methods: {
    getp: function(){
      return {uuid: location.pathname.split('/').reverse()[0],mac_id:this.mac_id}
    },
    updateMac: function(){
      this.loading = true
      this.$http.post('/v1/account/color', this.getp(), {emulateJSON:true}).then(function(res){
        this.data.quote = res.data.quote
        this.data.machines = res.data.machines
        this.data.machine = res.data.machine          
        this.data.formulas = res.data.formulas
        this.loading = false
      })
    },
    download:function({type,target}){
      if(!$(target).hasClass('is-loading')){
        $(target).addClass('is-loading')
        var uuid = $(target).attr('uuid')
        this.$http.post('/v1/account/comments', {uuid:this.data.quote.uuid,comments:this.data.quote.comments}, {emulateJSON:true}).then(function(res){
          if(!this.mac_id){
            this.message = "Por favor seleccione un equipo para imprimir."
            return $(target).removeClass('is-loading')
          }
          this.filters.download({
            url: '/v1/account/color2pdf/' + uuid + '/' + this.mac_id,
            target: target,
            type: 'application/pdf',
            filename: uuid + '.pdf',
            token: JSON.parse(localStorage.getItem("token"))
          })
        })
      }
    }
  },
  data: function() {
    return{
      loading:false,
      loadingBtn:false,
      selection:{},
      mac_id:0,
      filters:filters,
      data:{quote:{},machine:{},machines:{},formulas:{}},
      message:"",
      messageType:""
    }
  }
}

const FormulasPropias = {
  template: '#formulaspropias',
  mounted:function(){
  },
  methods: {
  },
  data: function() {
    return{
      loading:false
    }
  }
}

const Historico = {
  template: '#historico',
  name:'historico',
  mounted:function(){
    if(!this.loading){
      this.loading = true
      this.$http.post('/v1/account/colors', {}, {emulateJSON:true}).then(function(res){
        this.data = res.data
        this.loading = false
      })    
    }
  },
  methods: {
    remove:function({type,target}){
      var self = this
      if(!this.loading){
        if(confirm("Una vez confirmado los datos no se podrán recuperar. ¿Estás seguro que deseas eliminar esta fórmula?")){
          this.loading = true
          if(target.id){
            self.$http.post('/v1/account/colord', {id:target.id}, {emulateJSON:true}).then(function(res){
              if(res.data.status==='success'){
                self.message = "Se ha eliminado correctamente la fórmula propia."
                self.$http.post('/v1/account/colors', {}, {emulateJSON:true}).then(function(res){
                  self.data = res.data
                })    
              }
              self.loading = false
            })
          }
        }
      }
    },
    more:function({type,target}){
      if(target.id){
        this.$router.push('/account/color/' + target.id)
      }
    }
  },
  data: function() {
    return{
      loading:false,
      message:'',
      messageType:'',
      selection:{},
      filters:filters,
      data:{colors:{}}
    }
  }
}

const FormulaPropia = {
  template: '#formulapropia',
  name:'formulapropia',
  mounted:function(){
    this.updateMac()
  },
  methods: {
    updateMac: function(){
      if(!this.loading){
        this.loading = true
        this.$http.post('/v1/account/formula', {id: location.pathname.split('/').reverse()[0],mac_id:this.mac_id}, {emulateJSON:true}).then(function(res){
          this.data = res.data
          if(!this.mac_id){
            this.mac_id = res.data.machine.id
          }           
          this.loading = false
        })    
      }    
    },
    download:function({type,target}){
      if(!$(target).hasClass('is-loading')){
        $(target).addClass('is-loading')
        var colid = $(target).attr('colid')
        var colttl = $(target).attr('colttl')
        this.filters.download({
          url: '/v1/account/formula3pdf/' + colid + '/' + this.mac_id,
          target: target,
          type: 'application/pdf',
          filename: colttl + '.pdf',
          token: JSON.parse(localStorage.getItem("token"))
        })
      }
    }    
  },  
  data: function() {
    return{
      loading:false,
      filters:filters,
      mac_id:0,
      data:{color:{},formulas:{},machine:{},machines:{}},
      message:"",
      messageType:""
    }
  }
}

const CargarFormulasPropias = {
  template: '#cargarformulaspropias',
  name:'cargarformulaspropias',
  mounted:function(){
    this.updateMac()
  },
  methods: {
    setTexture: function({type,target}){
      if(target){
        this.selection.texture.code = target.options[target.selectedIndex].getAttribute('code')
        this.selection.texture.hexcode = target.options[target.selectedIndex].getAttribute('hexcode')
      }
    },
    setBase: function({type,target}){
      if(target){
        this.selection.base.code = target.options[target.selectedIndex].getAttribute('code')
      }
    },
    updateMac: function(){
      this.loading = true
      this.$http.post('/v1/account/cargarformulaspropias', {mac_id:this.mac_id}, {emulateJSON:true}).then(function(res){
        this.data = res.data
        if(!this.mac_id){
          this.mac_id = res.data.machine.id
        } 
        
        if(!this.selection.texture.code){
          this.selection.texture = res.data.textures[0]
          this.color.texture_id = res.data.textures[0].id
        }

        if(!this.selection.base.code){
          this.selection.base = res.data.bases[0]
          this.color.base_id = res.data.bases[0].id
        }

        this.data.colorants.forEach((colorant) => {
          this.color.units[colorant.id] = {}
          this.data.units.forEach((unit) => {
            this.color.units[colorant.id][unit] = 0
          })
        })

        this.color.mac_id = this.mac_id
        this.loading = false
      })
    },    
    submit: function(){
      if(!this.loading){  
        this.loading = true;
        this.$http.post('/v1/account/cargarformulaspropiasp', this.color, {emulateJSON:true}).then(function(res){
          if(res.data.status === 'success'){
            this.messageType = 'is-success'
            this.message = "Se ha cargado la fórmula exitosamente"
          }
          this.loading = false
        })
      }

      return false;
    }
  },
  data: function() {
    return{
      loading:true,
      filters:filters,
      message:"",
      color:{units:{}},
      mac_id:0,
      messageType:"",
      data:{colorants:{},textures:{},bases:{},machine:{}},
      selection:{colorant:0,texture:{},base:{}}
    }
  }
}

const Account = {
  template: '#account',
  name: 'account',
  mounted: function(){
    localStorage.removeItem("quote")    
    localStorage.removeItem("texture")    
  },
  methods: {
  },
  data: function() {
    return{
      loading:false,
      messageType:"",
      message:"",
      data:{message:'',status:''}
    }
  }
}

const EditAccount = {
  template: '#editaccount',
  mounted: function() {
    this.data = filters.token()
  },
  methods : {
    onFileChange(e) {
      var files = e.target.files || e.dataTransfer.files;
      if (!files.length)
        return;
      this.createImage(files[0])
      this.uploadImage(files[0])
    },
    createImage(file,code) {
      var reader = new FileReader();
      var code = this.code
      reader.onload = (e) => {
        var $id = $('#img'+code);
        $id.css({
          'background-image': 'url(' + e.target.result + ')',
          'background-size': 'cover'
        });
        var exif = EXIF.readFromBinaryFile(new base64ToArrayBuffer(e.target.result));
        //fixExifOrientation(exif.Orientation, $id);
      };
      reader.readAsDataURL(file);
    },
    removeImage: function (e) {
      this.image = '';
    }, 
    clickImage : function(code){
      this.upload = true
      this.code = code
      $("#uploads").click()
      return false
    },
    uploadImage : function(file){
      this.upload = true
      var code = this.code
      var formData = new FormData();
      formData.append('uploads[]', file);
      var token = $.parseJSON(localStorage.getItem("token")) || {}
      var self = this

      this.loading = true
      this.message = "Uploading image..."
      this.messageType = "is-info"
      //loading
      //$('.profile'+type+'--link').text("Subiendo...");
      $.ajax({
        type:'post',
        url: '/v1/account/profile-picture',
        data:formData,
        beforeSend: function (xhr) { 
          xhr.setRequestHeader('Authorization', 'Bearer ' + token.token); 
        },          
        xhr: function() {
          var myXhr = $.ajaxSettings.xhr();
          if(myXhr.upload){
            myXhr.upload.addEventListener('progress',function(e){
              if(e.lengthComputable){
                var max = e.total;
                var current = e.loaded;
                var percentage = (current * 100)/max;

                console.log("subiendo : " + parseInt(percentage))

                if(percentage >= 100) {
                  self.uploading = false
                }
              }
            }, false);
          }
          return myXhr;
        },
        cache:false,
        contentType: false,
        processData: false,
        success:function(res){
          if(res.status==='error'){
            self.message = res.error.split("\n")
            self.messageType = "is-danger"            
            console.log(res.proc[0].error)
          } else{
            var token = $.parseJSON(localStorage.getItem("token")) || {}
            token.picture = res.url
            localStorage.setItem("token", JSON.stringify(token))
            self.loading = false
            self.message = "Image has been correctly uploaded."
            self.messageType = "is-success"
          }
        },
        error: function(data){
          self.loading = false
          self.message = "Image has not been uploaded."
          self.messageType = "is-success"
          console.log("Hubo un error al subir el archivo");
        }
      })
    },    
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        this.$http.post('/v1/account/update', this.data, {emulateJSON:true}).then(function(res){
          this.data = res.data
          localStorage.setItem("token", JSON.stringify(res.data))
          this.loading = false
          this.messageType = 'is-success'
          this.message = "Cuenta actualizada"
        }, function(error){
          this.loading = false
          this.messageType = 'is-danger'
          this.message = error.statusText
          console.log(error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:"",
      acceptTerms:false,   
      data:{},   
      hash : location.hash.replace('#','')
    }
  }
}

const ChangePassword = {
  template: '#changepassword',
  methods : {
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/v1/account/password', data, {emulateJSON:true}).then(function(res){
          this.loading = false
          this.messageType = res.data.messageType
          this.message = res.data.message
          //helper.is_loaded()
        }, function(error){
          this.loading = false
          this.messageType = 'is-danger'
          this.message = error.statusText
          console.log(error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      loading:false,
      message:"",
      messageType:"",
      acceptTerms:false,      
      hash : location.hash.replace('#','')
    }
  }
}

const Section = {
  template: '#section',
  name:'section',
  mounted: function() {
    this.loading = true 
    var self = this
    this.$http.post('/v1/sections'+location.pathname, {}, {emulateJSON:true}).then(function(res){
      this.data = res.data

      if(this.data.posts){
        this.data.content = this.data.content.replace('{{slider}}',$.templates('#slider').render(this.data))
        setTimeout(function(){
          self.slick()
        },100)
      }

      document.title = this.data.title

      self.loading = false
    }, function(error){
      //helper.is_loaded()
      $('.hero-body').html($.templates('#notfound').render())
      self.loading = false
      console.log(error.statusText)
    })  
  },  
  methods: {
    slick : function(){
      /*$('.slick').slick({
        slidesToShow: 1,
        dots: true
      }).removeClass('loading').addClass('fadeIn')*/
    }
  },
  data: function() {
    return{
      data:{},
      loading:false,
      url: this.$route.query.url
    }
  }
}

const Opener = {
  template: '#opener',
  mounted: function() {
    var self = this
    localStorage.setItem("token", this.$route.query.token);
    setTimeout(function(){
      location.href = self.url;
    },1000)
  },  
  data: function() {
    return{
      url: this.$route.query.url
    }
  }
}

const SessionEnded = {
  template: '#sessionended',
  mounted:function(){
    $('section.hero').addClass('is-success')
  },
  data: function() {
    return{
      filters:filters,
      hash : location.hash.replace('#','')
    }
  }
}

const SessionExpired = {
  template: '#sessionexpired',
  mounted:function(){
    $('section.hero').addClass('is-info')
  },
  data: function() {
    return{
      filters:filters,
      hash : location.hash.replace('#','')
    }
  }
}

const NotFound = {
  template: '#notfound',
  mounted:function(){
    $('section.hero').addClass('is-danger')
  },
  data: function() {
    return{
    }
  }
}

const router = new VueRouter({
  mode: 'history',
  routes: [
    {path: '/', component: Section, meta : { title: 'Puntoweberplast'}},
    {path: '/opener', component: Opener, meta : { title: 'Redirigiendo...'}},
    {path: '/sign-in', component: SignIn,  meta : { title: 'Iniciar sesión'}},
    {path: '/sign-up', component: SignUp,  meta : { title: 'Crear nueva cuenta'}},
    {path: '/recover-password', component: RecoverPassword,  meta : { title: 'Recuperar contraseña'}},
    {path: '/update-password', component: UpdatePassword,  meta : { title: 'Actualizar contraseña'}},
    {path: '/session-ended', component: SessionEnded, meta : { title: 'Sesión finalizada'}},
    {path: '/session-expired', component: SessionExpired, meta : { title: 'Sesión expirada'}},
    {path: '/contact', component: Contact, meta : { title: 'Contacto'}},    
    {path: '/account', component: Account, meta : { title: 'Puntoweberplast', hideFooter: true,  requiresAuth: true}},
    {path: '/account/edit', component: EditAccount,  meta : { title: 'Mi cuenta', requiresAuth: true}},
    {path: '/account/password', component: ChangePassword,  meta : { title: 'Cambiar contraseña', requiresAuth: true}},
    {path: "*", component: Section, meta : { title: ''}}
  ]
});

router.beforeEach(function (to, from, next) { 
  document.title = to.meta.title;
  var token = $.parseJSON(localStorage.getItem("token")) || {}

  if(token.token){
    filters.refreshToken()
  }

  setTimeout(function() {
    var body = $("html, body");
    body.stop().animate({scrollTop:0}, 250, 'swing', function() { 
    });
  }, 10)

  if(to.meta.requiresAuth) {
    if(token) {
      next()
    } else {
      next('/')
    }    
  } else {
    next()
  }
})

router.afterEach(function (to, from, next) {
  setTimeout(function() {
    var ref = to.path.split('/').join('_')
    var token = $.parseJSON(localStorage.getItem("token")) || {}
    $('.navbar-brand').removeClass('is-active')
    $('.navbar-end .navbar-tabs li').removeClass('is-active')
    $('.navbar-end .navbar-tabs ul').find('a[href="' + to.path + '"]').parent().addClass('is-active')
    $('.navbar-menu, .navbar-burger').removeClass('is-active')

    if(to.meta.customNavbar){
      $('.custom-navbar .title').html(to.meta.title)
      $('.custom-navbar .icon').attr('src',to.meta.icon)
      $('.custom-navbar').show()
    } else {
      if(to.meta.hideFooter){
        $('.footer, .scrollmap').hide()
      } else {
        $('.footer, .scrollmap').show()  
      }
      $('.custom-navbar').hide()      
    }
  }, 10)
})

Vue.http.interceptors.push(function(request, next) {
  var token = $.parseJSON(localStorage.getItem("token")) || {}
  //request.headers.set('Access-Control-Allow-Credentials', true)
  request.headers.set('Authorization', 'Bearer '+ token.token)
  request.headers.set('Content-Type', 'application/json')
  request.headers.set('Accept', 'application/json')
  next()
})

const app = new Vue({ router: router,
  data : {
    customNavbar: false,
    hideSignIn:false,
    filters:filters
  },
  watch: {
    '$route' (to, from) {
      this.checkFlags(to)
    }
  },  
  mounted: function() {
  },
  created: function () {
    $('.hidden-loading').removeClass('hidden-loading')
    this.$http.post('/v1/navitems', {}, {emulateJSON:true}).then(function(res){
      $('.footer').html(res.data.footer);
      $('.navbar .navbar-start').prepend($.templates('#navbaritem').render(res.data.navitems))
      $('.navbar .navbar-start').find('a[href="' + location.pathname + '"]').parent().addClass('is-active')
    }, function(error){
      if(error)
      console.log("Error while retrieving navitems.")
    }) 
    this.checkFlags(this.$route)
  },
  methods : {
    homeClick:function(){
      var token = $.parseJSON(localStorage.getItem("token")) || {}
      this.$router.push((token.token?'/account':'/'))
    },     
    scrollUp: function(){
      var body = $("html, body");
      body.stop().animate({scrollTop:0}, 500, 'swing', function() { 
      });
    },
    scrollDown: function(){
      var body = $("html, body");
      body.stop().animate({scrollTop:$(document).height()}, 500, 'swing', function() { 
      });
    },
    checkFlags:function(route){
      this.hideSignIn = false
      if($.inArray(route.path,['/','/sign-in']) > -1){
        this.hideSignIn = true
      }
    },
    tosAgree: function(){
      localStorage.setItem("tosagree",true)
      document.querySelector('.tosprompt').classList.remove('slidin')
      document.querySelector('.tosprompt').classList.add('fadeout')      
      setTimeout(() => {
        document.querySelector('.tosprompt').style.display = 'none';
      },1000)
    }
  }
}).$mount('#app')