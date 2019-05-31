
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