

const SessionEnded = {
  template: '#sessionended',
  mounted:function(){
    $('section.hero').addClass('is-success')
  },
  data: function() {
    return{
      filters:filters,
      hash : location.hash.replace('#','')
    }
  }
}