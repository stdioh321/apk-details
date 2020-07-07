<?php

try {
    // error_reporting(0);
    // ini_set('display_errors', 0);


    if (isset($_FILES['apk'])) {
        $file = $_FILES['apk'];
        if (is_file($file['tmp_name'])) {
            exec("aapt d badging " . $file['tmp_name'], $out, $ret);
            if ($ret != 0) {
                http_response_code(400);
                echo json_encode(["message" => "It is no Android Apk"]);
                return;
            }


            $urlDownloadApk = "https://m.apkpure.com/android/%s/download?from=details";

            $apk = [
                "appName" => null,
                "packageName" => null,
                "version" => null,
                "size" => null,
                "unityVersion" => null,
                "unityTechnology" => null,
                "dataDir" => null,
                "sdkVersion" => null,
                "targetSdkVersion" => null,
                "usesPermission" => null,
                "appIcon" => null
            ];
            preg_match("/package: name=\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $file['tmp_name']), $tmp);
            $apk['packageName'] = isset($tmp[1]) ? $tmp[1] : null;

            preg_match("/application: label=\'([\w\d\. ]+)/i", shell_exec("aapt d badging " . $file['tmp_name']), $tmp);
            $apk['appName'] =  isset($tmp[1]) ? $tmp[1] : null;

            preg_match("/versionName=\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $file['tmp_name']), $tmp);
            $apk['version'] =  isset($tmp[1]) ? $tmp[1] : null;

            preg_match("/sdkVersion:\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $file['tmp_name']), $tmp);
            $apk['sdkVersion'] =  isset($tmp[1]) ? $tmp[1] : null;

            preg_match("/targetSdkVersion:\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $file['tmp_name']), $tmp);
            $apk['targetSdkVersion'] =  isset($tmp[1]) ? $tmp[1] : null;

            preg_match_all("/uses-permission: name=\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $file['tmp_name']), $tmp);
            $apk['usesPermission'] =  isset($tmp[1]) ? $tmp[1] : null;

            $apk['size'] = $file['size'];

            $apk['dataDir'] = "/data/user/0/" . $apk['packageName'];

            shell_exec("rm -f app_icon.png");

            $tmpIcons = shell_exec("unzip -l " . $file['tmp_name'] . " | grep -i app_icon.png");
            preg_match_all('/(res.+app_icon.png)/i', $tmpIcons, $tmp, PREG_PATTERN_ORDER);
            $currIcon =  (count($tmp) > 1 && count($tmp[1]) > 0) ? $tmp[1][count($tmp[1]) - 1]  : null;
            if ($currIcon) {
                shell_exec("unzip -jo " . $file['tmp_name'] . " " . $currIcon);
                error_log($currIcon);
                $tmpImage = file_get_contents(__DIR__ . "/app_icon.png");

                $apk['appIcon'] = "data:image/png;base64," . base64_encode($tmpImage);
            }

            shell_exec("rm -f unity_builtin_extra");
            shell_exec("unzip -jo " . $file['tmp_name'] . " assets/bin/Data/Resources/unity_builtin_extra");


            if (is_file(__DIR__ . "/unity_builtin_extra")) {
                preg_match('/\d+[\w\.]+/i', file_get_contents(__DIR__ . "/unity_builtin_extra"), $v);
                $apk['unityVersion'] = empty($v) ? '' : $v[0];
                $libs = shell_exec('unzip -l ' . $file['tmp_name']);
                if (preg_match('/libmono/i', $libs)) {
                    $apk['unityTechnology'] = "Mono";
                } elseif (preg_match('/libilcpp/i', $libs)) {
                    $apk['unityTechnology'] = "Ilcpp";
                } elseif (preg_match('/libil2cpp/i', $libs)) {
                    $apk['unityTechnology'] = "Il2cpp";
                }
            } else {
                $apk['unityVersion'] = null;
            }
            $urlApk = sprintf($urlDownloadApk, $apk['packageName']);
            $tmpHeaders = @get_headers($urlApk);
            // print_r($tmpHeaders);
            if ($tmpHeaders && strpos($tmpHeaders[0], '301')) {
                $apk["urlApk"] = $urlApk;
            }
            header('Content-type: application/json');
            echo json_encode($apk);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Apk is missing"]);
        return;
    }
} catch (\Throwable $th) {
    http_response_code(500);
    echo json_encode(["message" => $th->getMessage(), "code" => $th->getCode()]);
}
