const Section = {
  template: '#section',
  name:'section',
  mounted: function() {
    this.$root.loading = true 
    
    this.$http.post('/v1/sections'+location.pathname, {}, {emulateJSON:true}).then(function(res){
      this.data = res.data

      if(this.data.posts){
        this.data.content = this.data.content.replace('{{slider}}',$.templates('#slider').render(this.data))
        setTimeout(function(){
          self.slick()
        },100)
      }

      document.title = this.data.title

      this.$root.loading = false
    }, function(error){
      $('.hero-body').html($.templates('#notfound').render())
      this.$root.loading = false
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
      message:'',
      messageType:'',
      loading:false,
      url: this.$route.query.url
    }
  }
}

const Opener = {
  template: '#opener',
  mounted: function() {
    
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

const SignIn = {
  template: '#signin',
  methods: {
    submit : function({type, target}){
      if(!this.$root.processing){
        this.$root.processing = true
        this.$http.post('/v1/auth/signin', this.data, {emulateJSON:true}).then(function(res){
          if(res.data.status === 'success'){
            this.data = res.data
            this.$root.snackbar('success','La sesión fue iniciada correctamente. Redirigiendo...')
            setTimeout(function(){
              localStorage.setItem("token", JSON.stringify(res.data))
              app.$router.push('/account')  
            },2000)
          } else {
            this.$root.snackbar('error',res.data.message)
          }
          this.$root.processing = false
        }, function(error){
          this.$root.snackbar('error',error.statusText)
          this.$root.processing = false
        })
      }
    }
  },
  data: function() {
    return{
      data:{}
    }
  }
}

const SignUp = {
  template: '#signup',
  methods: {
    submit : function({type, target}){
      if(!this.$root.processing){
        if(!this.acceptTerms){
          return this.$root.snackbar('error','Debes aceptar nuestros términos y condiciones.')
        }
        this.$root.processing = true
        this.$http.post('/v1/auth/signup', this.data, {emulateJSON:true}).then(function(res){
          if(res.data.status === 'success'){
            this.$root.loading = false
            this.$root.snackbar('success','Se envió un email a tu cuenta. Por favor sigue el correspondiente enlace para validar tu cuenta.')
            setTimeout(function(){
              app.$router.push('/sign-in')
            },15000)
          } else {
            this.$root.snackbar('error',res.data.message)
          }
          this.$root.processing = false
        }, function(error){
          console.log(error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      acceptTerms:false,
      data:{}
    }
  }
}

const RecoverPassword = {
  template: '#recoverpassword',
  methods: {
    submit : function({type, target}){
      if(!this.$root.processing){
        this.$root.processing = true
        this.$http.post('/v1/auth/recover-password', this.data, {emulateJSON:true}).then(function(res){
          if(res.data.status === 'success'){
            this.$root.loading = false
            this.$root.snackbar('success','Revisa tu email y sigue el enlace para recuperar tu contraseña.')
          } else {
            this.$root.loading = false
            this.$root.snackbar('error',res.data.message)
          }
          this.$root.processing = false
        }, function(error){
          console.log(error.statusText)
        })
        return false
      }
    }
  },
  data: function() {
    return{
      data:{}
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
      if(!this.$root.processing){
        this.$root.processing = true
        this.$http.post('/v1/auth/update-password', this.data, {emulateJSON:true}).then(function(res){
          this.data = res.data
          if(res.data.status === 'success'){
            this.$root.processing = false
            this.$root.snackbar('success','Actualizaste correctamente tu contraseña. Te redirigiremos a la sección de ingreso. Por favor inicia sesión...')
            setTimeout(function(){
              this.$root.loading = false
              app.$router.push('/sign-in')
            },10000)
          } else {
            this.$root.processing = false
            this.$root.snackbar('error',res.data.message)
          }
        }, function(error){
          this.$root.processing = false
          this.$root.snackbar('error',error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      data:{}
    }
  }
}

const ChangePassword = {
  template: '#changepassword',
  methods : {
    submit : function({type, target}){
      if(!this.$root.processing){
        this.$root.processing = true

        this.$http.post('/v1/account/password', this.data, {emulateJSON:true}).then(function(res){
          this.$root.processing = false
          this.$root.snackbar(res.data.messageType,res.data.message)
        }, function(error){
          this.$root.processing = false
          this.$root.snackbar('error',error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      acceptTerms:false,      
      data:{},
      hash : location.hash.replace('#','')
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
        this.$root.processing = true
        this.$http.post('/v1/contact', this.data, {emulateJSON:true}).then(function(res){
          this.$root.processing = false
          if(res.body.status==='success'){
            this.$root.snackbar('success','Su mensaje ha sido enviado.<br>Gracias por tomarse el tiempo de escribirnos.<br>Le responderemos pronto.')
          }
        }, function(error){
          this.$root.processing = false
          this.$root.snackbar('error',error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      acceptTerms:false,
      data:{},
      hash: location.hash.replace('#','')
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
      

      this.$root.loading = true
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
            this.$root.loading = false
            self.message = "Image has been correctly uploaded."
            self.messageType = "is-success"
          }
        },
        error: function(data){
          this.$root.loading = false
          self.message = "Image has not been uploaded."
          self.messageType = "is-success"
          console.log("Hubo un error al subir el archivo");
        }
      })
    },    
    submit : function({type, target}){
      if(!this.$root.processing){
        this.$root.processing = true
        this.$http.post('/v1/account/update', this.data, {emulateJSON:true}).then(function(res){
          this.data = res.data
          localStorage.setItem("token", JSON.stringify(res.data))
          this.$root.processing = false
          this.$root.snackbar('success','Cuenta actualizada')
        }, function(error){
          this.$root.processing = false
          this.$root.snackbar('error',error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      acceptTerms:false,   
      data:{},   
      hash : location.hash.replace('#','')
    }
  }
}

const Todos = {
  template: '#todos',
  name:'todos',
  mounted:function(){
    this.$root.loading = true
    this.$http.get('/v1/account/todos', {}, {emulateJSON:true}).then(function(res){
      this.data = res.data
      this.$root.loading = false
    })    
  },
  methods: {
    remove:function({type,target}){
      if(confirm("Una vez confirmado los datos no se podrán recuperar. ¿Estás seguro que deseas eliminar esta fórmula?")){
        if(target.id){
          this.$root.loading = true
          this.$http.delete('/v1/account/todo/delete', {id:target.id}, {emulateJSON:true}).then(function(res){
            if(res.data.status==='success'){
              this.$root.snackbar('success','Se ha eliminado correctamente el todo.')
            }
            this.$root.loading = false
          })
        }
      }
    },
    more:function({type,target}){
      if(target.id){
        this.$router.push('/todos/' + target.id)
      }
    }
  },
  data: function() {
    return{
      data:{}
    }
  }
}

const Todo = {
  template: '#todo',
  name: 'todo',
  mounted: function() {
    this.$root.loading = true
    this.$http.get('/v1/account/todos/' + location.pathname.split('/').reverse()[0], {}, {emulateJSON:true}).then(function(res){
      this.data = res.data
      this.$root.loading = false
    })    
  },  
  methods: {
    submit: function(){
      this.$root.processing = true
      this.$http.put('/v1/account/todos/' + this.data.id, this.data, {emulateJSON:true}).then(function(res){
        if(res.data.id){
          this.$root.snackbar('success','Se ha actualizado el todo exitosamente')
        } else {
          this.$root.snackbar('error','Algo pasó que no se pudo cargar el todo exitosamente')
        }
        this.$root.processing = false
        this.$router.push('/todos')
      })
      return false;
    }
  },
  data: function() {
    return{
      data:{},
      url: this.$route.query.url
    }
  }
}

const AddTodo = {
  template: '#addtodo',
  name:'addtodo',
  mounted: function() {
  },  
  methods: {
    submit: function(){
      if(!this.$root.processing){  
        this.$root.processing = true
        this.$http.put('/v1/account/todos', this.data, {emulateJSON:true}).then(function(res){
          if(res.data.id){
            this.$root.snackbar('success','Se ha cargado el todo exitosamente')
          } else {
            this.$root.snackbar('error','Algo pasó que no se pudo cargar el todo exitosamente')
          }
          this.$root.processing = false
          this.$router.push('/todos')
        })
      }
      return false;
    }
  },
  data: function() {
    return{
      data:{},
      url: this.$route.query.url
    }
  }
}

const router = new VueRouter({
  mode: 'history',
  routes: [
    {path: '/', component: Section, meta : { title: 'SlimVueDemo'}},
    {path: '/opener', component: Opener, meta : { title: 'Redirigiendo...'}},
    {path: '/sign-in', component: SignIn,  meta : { title: 'Iniciar sesión'}},
    {path: '/sign-up', component: SignUp,  meta : { title: 'Crear nueva cuenta'}},
    {path: '/recover-password', component: RecoverPassword,  meta : { title: 'Recuperar contraseña'}},
    {path: '/update-password', component: UpdatePassword,  meta : { title: 'Actualizar contraseña'}},
    {path: '/session-ended', component: SessionEnded, meta : { title: 'Sesión finalizada'}},
    {path: '/session-expired', component: SessionExpired, meta : { title: 'Sesión expirada'}},
    {path: '/contact', component: Contact, meta : { title: 'Contacto'}},    
    {path: '/account', component: Account, meta : { title: 'SlimVueDemo Menu', requiresAuth: true}},
    {path: '/edit', component: EditAccount,  meta : { title: 'Mi cuenta', requiresAuth: true}},
    {path: '/password', component: ChangePassword,  meta : { title: 'Cambiar contraseña', requiresAuth: true}},
    {path: '/todos', component: Todos,  meta : { title: 'Todos', requiresAuth: true}},
    {path: '/todos/add', component: AddTodo,  meta : { title: 'Agregar Todo', requiresAuth: true}},
    {path: '/todos/*', component: Todo,  meta : { title: 'Ver Todo', requiresAuth: true}},
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
      $('.custom-navbar').hide()      
    }
    if(to.meta.requiresAuth){
      $('.footer, .scrollmap').hide()
    } else {
      $('.footer, .scrollmap').show()  
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
    loading: true,
    processing:false,    
    messageType:'default',
    message:'',    
    filters:filters
  },
  watch: {
    '$route' (to, from) {
      this.checkFlags(to)
    }
  },  
  mounted: function() {
    this.message = "cargando aplicación. Por favor espere..."
  },
  created: function () {
    $('.hidden-loading').removeClass('hidden-loading')
    this.$http.post('/v1/navitems', {}, {emulateJSON:true}).then(function(res){
      $('.footer').html(res.data.footer);
      $('.menu-items').prepend($.templates('#navbaritem').render(res.data.navitems))
      $('.menu-items').find('a[href="' + location.pathname + '"]').parent().addClass('is-active')
      this.message = ""
      this.loading = false
    }, function(error){
      if(error){
        this.message = error
        this.loading = false
        console.log("Error while retrieving navitems.")
      }
    })
    this.checkFlags(this.$route)
  },
  methods : {
    token: function(){
      return JSON.parse(localStorage.getItem("token")) || {}
    },
    endSessionWithConfirm:function(redirect){
      if(confirm("Está seguro que desea finalizar su sesión?")){
        if(redirect==undefined) redirect = '/session-ended'
        localStorage.removeItem("token")
        setTimeout(function(){
          if(location.pathname === redirect){
            location.href = redirect,true
          } else {
            app.$router.push(redirect)
          }      
        },200)
      }
    },    
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
    snackbar : function(messageType,message,timeout){
      if(timeout===undefined) timeout = 5000
      this.messageType = messageType
      this.message = message
      $('.ui-snackbar').removeClass('ui-snackbar--is-inactive ui-snackbar--success ui-snackbar--error ui-snackbar--default').addClass('ui-snackbar--' + messageType).addClass('ui-snackbar--is-active')
      setTimeout(() => {
        $('.ui-snackbar').removeClass('ui-snackbar--is-active').addClass('ui-snackbar--is-inactive')
      },timeout)
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