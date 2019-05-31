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
