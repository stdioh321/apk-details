<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apk Details</title>

    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="icon" type="image/png" href="favicon.png">

    <link rel="stylesheet" href="node_modules/vue-select/dist/vue-select.css">
    <link rel="stylesheet" href="./node_modules/bootstrap/dist/css/bootstrap.min.css">
    <script src="node_modules/underscore/underscore.js"></script>
    <script src="node_modules/axios/dist/axios.min.js"></script>
    <script src="node_modules/vue/dist/vue.js"></script>
    <script src="node_modules/vuex/dist/vuex.min.js"></script>
    <script src="node_modules/vue-select/dist/vue-select.js"></script>


    <style>
        .apk-details-list {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap;
        }

        .apk-details-list>li {
            flex: 1 0 50%;
            max-width: 100%;
        }

        .apk-details-list>li:first-child {
            flex: 1 0 100% !important;
        }

        .apk-details-list>div p {
            /* word-wrap: break-word; */
        }

        .v-select-item-wrapper {
            display: flex;
            align-items: center;
            max-width: 100%;
            /* border: solid red 1px; */
            /* overflow: scroll; */
        }

        .v-select-item-wrapper .item-img>img {
            border: solid rgb(240, 240, 240) 1px;
            border-radius: 5px;
            max-width: 100%;
        }

        .v-select-item-wrapper .item-img {
            max-width: 50px;

        }

        .v-select-item-wrapper .item-info {
            /* display: flex;
            flex-wrap: wrap;
            flex-direction: column;
            flex: 1; */

            width: 100%;
            max-width: 100%;
            /* border: solid blue 1px; */
        }

        .v-select-item-wrapper .item-info>* {
            display: block;
            word-break: break-all;
            width: 100%;
            max-width: 100%;
            /* border: solid blue 1px; */
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
                            <button class="btn btn-block btn-primary" type="submit" :disabled="loadingApk || loadingPackage || !apkFile">
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

                </form>
                <hr />
                <form @submit.prevent="onSubmitPackage($event)">
                    <div class="form-row">
                        <label for="package" class="form-group col-md-12 font-weight-bolder">Package</label>
                        <div class="form-group col-md-9">
                            <!-- <input type="text" id="package" placeholder="Package" class="form-control" v-model="strPackage" /> -->
                            <v-select id="package" taggable placeholder="App" v-model="appSelected" :options="appsOptions" label="name" @search="onAppSearch" @input="onAppChange" @search:blur="onSearchBlur">
                                <template v-slot:option="option">
                                    <div class="v-select-item-wrapper">
                                        <div class="item-img">
                                            <img :src="option.imgUrl || 'assets/images/image404.png'" alt="">
                                        </div>
                                        <div class="item-info pl-3">
                                            <strong>{{ option.name }}</strong>
                                            <small>{{ option.package }}</small>
                                        </div>
                                    </div>
                                </template>
                                <template v-slot:no-options="{ search, searching }">
                                    <template v-if="searching">
                                        No results found for <em>{{ search }}</em>.
                                    </template>
                                    <em style="opacity: 0.5;" v-else>Start typing to search for a app.</em>
                                </template>
                            </v-select>
                        </div>
                        <div class="form-group col-md-3">
                            <button type="submit" class="btn btn-primary btn-block" :disabled="loadingApk || loadingPackage || !appSelected  ">
                                <template v-if="loadingPackage">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Loading...
                                </template>
                                <template v-else>
                                    Send
                                </template>

                            </button>
                        </div>
                    </div>
                </form>
                <div class="row my-2" v-if="errorMessage">
                    <div class="col-md-12">
                        <div class="alert alert-danger">
                            {{errorMessage}}
                        </div>
                    </div>
                </div>
                <hr />
                <ul v-if="apkDetails" class="apk-details-list list-group list-group-flush ">

                    <li class="list-group-item">
                        <img :src="apkDetails.appIcon || 'assets/images/image404.png'" :title="apkDetails.appName" class="mw-100">
                    </li>
                    <li class="list-group-item">
                        <h5>Name</h5>
                        <p>{{apkDetails.appName}}</p>
                    </li>
                    <li class="list-group-item">
                        <h5>Package</h5>
                        <p>{{apkDetails.packageName}}</p>
                    </li>
                    <li class="list-group-item">
                        <h5>Version</h5>
                        <p>{{apkDetails.version}}</p>
                    </li>
                    <li class="list-group-item">
                        <h5>Size</h5>
                        <p>{{apkDetails.size}}MB</p>
                    </li>
                    <li class="list-group-item" v-if="apkDetails.urlApk">
                        <h5>Url Apk</h5>
                        <div v-for="url in apkDetails.urlApk">
                            <a :href="url" target="_blank">Download</a>
                        </div>
                    </li>
                    <li class="list-group-item">
                        <h5>Unity Version</h5>
                        <p v-if="apkDetails.unityVersion === ''">Version not found</p>
                        <p v-else-if="apkDetails.unityVersion === null">It's not a Unity App</p>
                        <p v-else>{{apkDetails.unityVersion}}</p>
                    </li>
                    <li class="list-group-item" v-if="apkDetails.unityTechnology">
                        <h5>Unity Technology</h5>
                        <p>{{apkDetails.unityTechnology}}</p>
                    </li>
                    <li class="list-group-item">
                        <h5>Data Directory</h5>
                        <p>{{apkDetails.dataDir}}</p>
                    </li>
                    <li class="list-group-item">
                        <h5>Sdk Version</h5>
                        <p>{{apkDetails.sdkVersion}}</p>
                    </li>
                    <li class="list-group-item">
                        <h5>Target Sdk Version</h5>
                        <p>{{apkDetails.targetSdkVersion}}</p>
                    </li>
                    <li class="list-group-item" v-if="apkDetails.usesPermission">
                        <h5>Uses Permissions</h5>

                        <div class="small text-wrap" style="word-wrap: break-word;" v-for="item in apkDetails.usesPermission">
                            {{item}}
                        </div>

                    </li>
                </ul>
            </div>
        </div>
    </div>
    <script src="index.js">
    </script>
</body>


</html>