
const Section = {
  template: '#section',
  name:'section',
  mounted: function() {
    this.loading = true 
    var self = this
    this.$http.post('/v1/sections'+location.pathname, {}, {emulateJSON:true}).then(function(res){
      this.data = res.data

      if(this.data.posts){
        this.data.content = this.data.content.replace('{{slider}}',$.templates('#slider').render(this.data))
        setTimeout(function(){
          self.slick()
        },100)
      }

      document.title = this.data.title

      self.loading = false
    }, function(error){
      $('.hero-body').html($.templates('#notfound').render())
      self.loading = false
      console.log(error.statusText)
    })  
  },  
  methods: {
    slick : function(){
      /*$('.slick').slick({
        slidesToShow: 1,
        dots: true
      }).removeClass('loading').addClass('fadeIn')*/
    }
  },
  data: function() {
    return{
      data:{},
      loading:false,
      url: this.$route.query.url
    }
  }
}