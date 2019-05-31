
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
