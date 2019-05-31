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
    {path: '/account', component: Account, meta : { title: 'SlimVueDemo Menu', hideFooter: true,  requiresAuth: true}},
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