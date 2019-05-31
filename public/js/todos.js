const Todos = {
  template: '#todos',
  name:'todos',
  mounted:function(){
    if(!this.loading){
      this.loading = true
      this.message = "Buscando todos"
      this.$http.get('/v1/account/todos', {}, {emulateJSON:true}).then(function(res){
        this.data = res.data
        this.loading = false
        this.message = ""
      })    
    }
  },
  methods: {
    remove:function({type,target}){
      var self = this
      if(!this.loading){
        if(confirm("Una vez confirmado los datos no se podrán recuperar. ¿Estás seguro que deseas eliminar esta fórmula?")){
          this.loading = true
          if(target.id){
            self.$http.post('/v1/account/colord', {id:target.id}, {emulateJSON:true}).then(function(res){
              if(res.data.status==='success'){
                self.message = "Se ha eliminado correctamente la fórmula propia."
                self.$http.post('/v1/account/colors', {}, {emulateJSON:true}).then(function(res){
                  self.data = res.data
                })    
              }
              self.loading = false
            })
          }
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
      loading:false,
      filters:filters,
      message:'',
      messageType:'',
      data:{}
    }
  }
}