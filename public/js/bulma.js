(function() {
    var burger = document.querySelector('.burger');
    var menu = document.querySelector('#'+burger.dataset.target);

    tosAgree = function(target){
      localStorage.setItem("tosagree",true)
      document.querySelector('.tosprompt').classList.remove('slideIn')
      document.querySelector('.tosprompt').classList.add('fadeOut')      
      setTimeout(() => {
        document.querySelector('.tosprompt').style.display = 'none';
      },1000)
    }

    burger.addEventListener('click', function() {
      burger.classList.toggle('is-active');
      menu.classList.toggle('is-active');
    });

    if(!localStorage.getItem("tosagree")){
      document.querySelector('.tosprompt').classList.add('slideIn')
    } else {
      document.querySelector('.tosprompt').style.display = 'none';
    }
})();