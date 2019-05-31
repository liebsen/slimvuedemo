
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
