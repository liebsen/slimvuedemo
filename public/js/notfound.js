const NotFound = {
  template: '#notfound',
  mounted:function(){
    $('section.hero').addClass('is-danger')
  },
  data: function() {
    return{
    }
  }
}
