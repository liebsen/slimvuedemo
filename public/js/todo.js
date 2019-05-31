

const Todo = {
  template: '#todo',
  name: 'todo',
  mounted: function() {
    if(!this.loading){
      this.loading = true
      this.message = "Buscando todos"
      this.$http.get('/v1/account/todos/' + location.pathname.split('/').reverse()[0], {}, {emulateJSON:true}).then(function(res){
        this.data = res.data
        this.loading = false
        this.message = ""
      })    
    }
  },  
  data: function() {
    return{
      loading:false,
      message:'',
      messageType:'',
      data:{},
      url: this.$route.query.url
    }
  }
}