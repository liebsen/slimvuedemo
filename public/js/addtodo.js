const AddTodo = {
  template: '#addtodo',
  name:'addtodo',
  mounted: function() {
  },  
  methods: {
    submit: function(){
      if(!this.loading){  
        this.loading = true
        this.message = "Guardando todo. Por favor espere..."
        this.$http.put('/v1/account/todos', this.data, {emulateJSON:true}).then(function(res){
          if(res.data.id){
            this.messageType = 'is-success'
            this.message = "Se ha cargado el todo exitosamente"
          } else {
            this.message = "Algo pasó que no se pudo cargar el todo exitosamente"
          }
          this.loading = false
        })
      }

      return false;
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