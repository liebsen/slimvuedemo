(function() {
  var scrolling = false;
  var states = {
    start : ['.navbar:not(.custom-navbar)','.scrollmap .up'],
    end: ['.scrollmap .down']
  }

  $( window ).scroll( function() {
    scrolling = true;
  });

  setInterval( function() {
    if ( scrolling ) {
      scrolling = false
      var scrollTop = $(window).scrollTop()
      var top = false
      var bottom = false

      states.start.forEach((target) => {
        if(scrollTop < 100) {
          if($(target).hasClass('active')){
            $(target).removeClass('active')
          }
        } else {
          if(!$(target).hasClass('active')){
            $(target).addClass('active')
          }
        }
      })

      states.end.forEach((target) => {
        if(scrollTop + $(window).height() > $(document).height() - 100) {
          if($(target).hasClass('active')){
            $(target).removeClass('active')
          }

        } else {
          if(!$(target).hasClass('active')){
            $(target).addClass('active')
          }
        }      
      })
    }
  }, 250 );
})();