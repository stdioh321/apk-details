<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apk Details</title>
    <link rel="stylesheet" href="./node_modules/bootstrap/dist/css/bootstrap.min.css">
    <script src="node_modules/axios/dist/axios.min.js"></script>
    <script src="node_modules/vue/dist/vue.js"></script>
    <style>
        .apk-details-list {}

        .apk-details-list>div {
            border-bottom: solid black 1px;
        }

        .apk-details-list>div p {
            /* word-wrap: break-word; */
        }
    </style>
</head>

<body>
    <div id="app" class="container">

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h1>Apk Details</h1>

                <hr />
                <form @submit.prevent="onSubmitApk($event)">
                    <div class="form-row">
                        <label for="apkFile" class="form-group col-12 font-weight-bolder">Add your apk file</label>
                        <div class="form-group col-12">
                            <input type="file" id="apkFile" name="apk" class="form-control" required @input="onFileApkChange($event)" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6 offset-6">
                            <button class="btn btn-block btn-primary" type="submit" :disabled="loadingApk || !apkFile">
                                <template v-if="loadingApk">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Loading...
                                </template>
                                <template v-else>
                                    Send
                                </template>
                            </button>
                        </div>
                    </div>
                    <div class="form-row" v-if="loadingApk">
                        <!-- {loadingApk.progress} -->
                        <div class="form-group col-md-12">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped" role="progressbar" aria-valuemin="0" aria-valuemax="100" :style="{'width': loadingApk.progress+'%'}" :aria-valuenow="loadingApk.progress">{{loadingApk.progress}}%</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row" v-if="errorMessage">
                        <div class="form-group col-md-12">
                            <div class="alert alert-danger">
                                {{errorMessage}}
                            </div>
                        </div>
                    </div>
                </form>

                <div v-if="apkDetails" class="apk-details-list row">
                    <div class="col-md-12 pb-1">
                        <img :src="apkDetails.appIcon || 'assets/images/image404.png'" :title="apkDetails.appName" class="mw-100">

                    </div>
                    <div class="col-md-6">
                        <h5>Name</h5>
                        <p>{{apkDetails.appName}}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Package</h5>
                        <p>{{apkDetails.packageName}}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Version</h5>
                        <p>{{apkDetails.version}}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Size</h5>
                        <p>{{apkDetails.size}}MB</p>
                    </div>
                    <div class="col-md-6" v-if="apkDetails.urlApk">
                        <h5>Url Apk</h5>
                        <p>
                            <a :href="apkDetails.urlApk" target="_blank">Download</a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5>Unity Version</h5>
                        <p v-if="apkDetails.unityVersion === ''">Version not found</p>
                        <p v-else-if="apkDetails.unityVersion === null">It's not a Unity App</p>
                        <p v-else>{{apkDetails.unityVersion}}</p>
                    </div>
                    <div class="col-md-6" v-if="apkDetails.unityTechnology">
                        <h5>Unity Technology</h5>
                        <p>{{apkDetails.unityTechnology}}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Data Directory</h5>
                        <p>{{apkDetails.dataDir}}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Sdk Version</h5>
                        <p>{{apkDetails.sdkVersion}}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Target Sdk Version</h5>
                        <p>{{apkDetails.targetSdkVersion}}</p>
                    </div>
                    <div class="col-md-6" v-if="apkDetails.usesPermission">
                        <h5>Uses Permissions</h5>

                        <div class="small text-wrap" style="word-wrap: break-word;" v-for="item in apkDetails.usesPermission">
                            {{item}}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                message: "Hello",
                apkFile: null,
                apkDetails: null,
                loadingApk: false,
                errorMessage: null
            },
            methods: {
                onFileApkChange(e) {
                    this.apkFile = e.target.files[0];
                    console.log(this.apkFile);
                    // if (!this.apkFile.type.match(/application\/vnd.android.package-archive/i)) {
                    //     this.apkFile = null;
                    //     this.errorMessage = "The file must be a apk.";
                    // }

                },
                onSubmitApk(f) {
                    this.errorMessage = null;
                    if (this.loadingApk) return false;
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
    </script>
</body>


</html>