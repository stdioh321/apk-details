
Vue.use(Vuex);
Vue.component('v-select', VueSelect.VueSelect);

var app = new Vue({
    el: '#app',
    data: {
        message: "Hello",
        apkFile: null,
        apkDetails: null,
        loadingApk: false,
        errorMessage: null,
        strPackage: null,
        loadingPackage: false,
        appsOptions : [],
        appSelected:null,
        loadingDecompile:false,
        errorMessageDecompile:null

    },
    methods: {
        onDecompileApk(apkName=""){
            if(this.loadingDecompile == true) return;
            this.errorMessageDecompile = null;
            this.loadingDecompile = true;
            axios.get("/decompile.php?file-name="+apkName)
            .then(res=>{
                if(this.apkDetails){
                    this.apkDetails['decompiledApkUrl'] = res.data.url;
                }                
                this.loadingDecompile = false;
            })
            .catch(err=>{
                if(err && err.response && err.response.status == 400){                
                        this.errorMessageDecompile = err.response.data.message;                    
                }else{
                    this.errorMessageDecompile = "Server error";                    
                }
                this.loadingDecompile = false;
            })
        },
        onAppChange(e){
            // console.log(this.appSelected);
            
            if(!e)
            {
                // this.appSelected = null;
                return;
            }
            let val;
            if(typeof e == 'string') val = e;
            else if (!e.package) val = e.name;
            else val = e.package;
            // this.appSelected = val;
            // this.onSubmitPackage(val);
            
            
        },
        onSearchBlur(){
            this.appsOptions = [];
        },
        onAppSearch(val="",loading){
            if(!val) return;
            this.appsOptions=[];
            loading(true);            
            this.search(loading, val);
            
            
        },
        search:_.debounce((loading, search)=>{
            axios.get("search.php?q=" +encodeURI(search))
        .then(res=>{
            app.appsOptions = res.data;        
            loading(false);                  
                
        })
        .catch(err=>{
            console.log(err);
            loading(false);
        });
        },1000),
        onFileApkChange(e) {
            this.apkFile = e.target.files[0];
            console.log(this.apkFile);
            // if (!this.apkFile.type.match(/application\/vnd.android.package-archive/i)) {
            //     this.apkFile = null;
            //     this.errorMessage = "The file must be a apk.";
            // }

        },
        onSubmitPackage(f) {
            if (this.loadingPackage || this.loadingApk || !this.appSelected) return false;
            let tmpPackage = typeof this.appSelected == 'string' ? this.appSelected : (this.appSelected.package ? this.appSelected.package : this.appSelected.name);

            this.loadingPackage = true;
            console.log(f);
            this.apkDetails = null;
            this.errorMessage = null;
            axios.get('upload_file.php?package=' + tmpPackage, {
                    onUploadProgress: (progressEvent) => {
                        // console.log(progressEvent);
                        // if (this.loadingApk) {
                        //     this.loadingApk.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                        // }
                    }
                })
                .then((response) => {
                    console.log(response);
                    this.apkDetails = response.data;

                    this.apkDetails.size = ((parseFloat(this.apkDetails.size) || 0) / 1024 / 1024).toFixed(2);

                    // console.log(this.apkDetails);
                    this.loadingPackage = false;
                })
                .catch(err => {
                    let error = err.toJSON();
                    console.log({
                        error: err
                    });
                    this.loadingPackage = false;
                    if (err.response && err.response.status === 400) {
                        this.errorMessage = err.response.data.message || "The Server was unable to resolve.";
                    } else if (!!error.message.match(/network\ error/i)) {
                        this.errorMessage = "The device is offline";
                    } else {
                        this.errorMessage = "The Server was unable to resolve.";
                    }
                });

        },
        onSubmitApk(f) {
            this.errorMessage = null;
            if (this.loadingPackage || this.loadingApk) return false;
            this.loadingApk = {
                progress: 0
            };
            this.apkDetails = null;
            let fData = new FormData();
            let tmpFile = f.target.apk.files[0];
            if (!tmpFile) return false;
            fData.append('apk', tmpFile);
            axios.post('upload_file.php',
                    fData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        },
                        onUploadProgress: (progressEvent) => {
                            // console.log(progressEvent);
                            if (this.loadingApk) {
                                this.loadingApk.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                            }
                        }
                    }
                ).then((response) => {
                    console.log(response);
                    this.apkDetails = response.data;

                    this.apkDetails.size = ((parseFloat(this.apkDetails.size) || 0) / 1024 / 1024).toFixed(2);

                    console.log(this.apkDetails);
                    f.target.apk.value = null;
                    this.apkFile = null;
                    this.loadingApk = false;
                })
                .catch(err => {
                    let error = err.toJSON();
                    console.log({
                        error: err
                    });
                    this.loadingApk = false;
                    if (err.response && err.response.status === 400) {
                        this.errorMessage = err.response.data.message || "The Server was unable to resolve.";
                    } else if (!!error.message.match(/network\ error/i)) {
                        this.errorMessage = "The device is offline";
                    } else {
                        this.errorMessage = "The Server was unable to resolve.";
                    }
                });
        }
    }
})
