
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
