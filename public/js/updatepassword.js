
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