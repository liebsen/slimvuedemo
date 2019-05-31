(function() {
    var burger = document.querySelector('.menu-burger');
    var menu = document.querySelector('#'+burger.dataset.target);

    tosAgree = function(target){
      localStorage.setItem("tosagree",true)
      document.querySelector('.tosprompt').classList.remove('slideIn')
      document.querySelector('.tosprompt').classList.add('fadeOut')      
      setTimeout(() => {
        document.querySelector('.tosprompt').style.display = 'none';
      },1000)
    }

    $(document).on('click','.menu-burger, .menu-items',function() {
      $('.menu-bg, .menu-items, .menu-burger').toggleClass('fs');
      $('.menu-burger').text() == "☰" ? $('.menu-burger').text('✕') : $('.menu-burger').text('☰');      
    });

    if(!localStorage.getItem("tosagree")){
      document.querySelector('.tosprompt').classList.add('slideIn')
    } else {
      document.querySelector('.tosprompt').style.display = 'none';
    }
})();