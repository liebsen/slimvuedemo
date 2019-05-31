

const Account = {
  template: '#account',
  name: 'account',
  mounted: function(){
    localStorage.removeItem("quote")    
    localStorage.removeItem("texture")    
  },
  methods: {
  },
  data: function() {
    return{
      loading:false,
      messageType:"",
      message:"",
      data:{message:'',status:''}
    }
  }
}