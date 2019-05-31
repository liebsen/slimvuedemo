
const SessionExpired = {
  template: '#sessionexpired',
  mounted:function(){
    $('section.hero').addClass('is-info')
  },
  data: function() {
    return{
      filters:filters,
      hash : location.hash.replace('#','')
    }
  }
}
