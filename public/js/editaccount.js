

const EditAccount = {
  template: '#editaccount',
  mounted: function() {
    this.data = filters.token()
  },
  methods : {
    onFileChange(e) {
      var files = e.target.files || e.dataTransfer.files;
      if (!files.length)
        return;
      this.createImage(files[0])
      this.uploadImage(files[0])
    },
    createImage(file,code) {
      var reader = new FileReader();
      var code = this.code
      reader.onload = (e) => {
        var $id = $('#img'+code);
        $id.css({
          'background-image': 'url(' + e.target.result + ')',
          'background-size': 'cover'
        });
        var exif = EXIF.readFromBinaryFile(new base64ToArrayBuffer(e.target.result));
        //fixExifOrientation(exif.Orientation, $id);
      };
      reader.readAsDataURL(file);
    },
    removeImage: function (e) {
      this.image = '';
    }, 
    clickImage : function(code){
      this.upload = true
      this.code = code
      $("#uploads").click()
      return false
    },
    uploadImage : function(file){
      this.upload = true
      var code = this.code
      var formData = new FormData();
      formData.append('uploads[]', file);
      var token = $.parseJSON(localStorage.getItem("token")) || {}
      var self = this

      this.loading = true
      this.message = "Uploading image..."
      this.messageType = "is-info"
      //loading
      //$('.profile'+type+'--link').text("Subiendo...");
      $.ajax({
        type:'post',
        url: '/v1/account/profile-picture',
        data:formData,
        beforeSend: function (xhr) { 
          xhr.setRequestHeader('Authorization', 'Bearer ' + token.token); 
        },          
        xhr: function() {
          var myXhr = $.ajaxSettings.xhr();
          if(myXhr.upload){
            myXhr.upload.addEventListener('progress',function(e){
              if(e.lengthComputable){
                var max = e.total;
                var current = e.loaded;
                var percentage = (current * 100)/max;

                console.log("subiendo : " + parseInt(percentage))

                if(percentage >= 100) {
                  self.uploading = false
                }
              }
            }, false);
          }
          return myXhr;
        },
        cache:false,
        contentType: false,
        processData: false,
        success:function(res){
          if(res.status==='error'){
            self.message = res.error.split("\n")
            self.messageType = "is-danger"            
            console.log(res.proc[0].error)
          } else{
            var token = $.parseJSON(localStorage.getItem("token")) || {}
            token.picture = res.url
            localStorage.setItem("token", JSON.stringify(token))
            self.loading = false
            self.message = "Image has been correctly uploaded."
            self.messageType = "is-success"
          }
        },
        error: function(data){
          self.loading = false
          self.message = "Image has not been uploaded."
          self.messageType = "is-success"
          console.log("Hubo un error al subir el archivo");
        }
      })
    },    
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        this.$http.post('/v1/account/update', this.data, {emulateJSON:true}).then(function(res){
          this.data = res.data
          localStorage.setItem("token", JSON.stringify(res.data))
          this.loading = false
          this.messageType = 'is-success'
          this.message = "Cuenta actualizada"
        }, function(error){
          this.loading = false
          this.messageType = 'is-danger'
          this.message = error.statusText
          console.log(error.statusText)
        })
      }
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:"",
      acceptTerms:false,   
      data:{},   
      hash : location.hash.replace('#','')
    }
  }
}
