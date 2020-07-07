<?php
$baseUrl = "https://m.apkpure.com/android/%s/download?from=details";
$apkFile = null;
try {
    // error_reporting(0);
    // ini_set('display_errors', 0);


    if (isset($_GET['package'])) {
        $apkUrl = getApkUrl(sprintf($baseUrl, $_GET['package']));

        if ($apkUrl) {
            $apkFile = __DIR__ . "/downloads/tmp_" . time();
            file_put_contents($apkFile, file_get_contents($apkUrl['url']));
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Apk no found"]);
            unlink($apkFile);
            return;
        }
    } else if (isset($_FILES['apk'])) {
        $apkFile = $_FILES['apk']['tmp_name'];
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Apk is missing"]);
        unlink($apkFile);
        return;
    }

    if (is_file($apkFile)) {
        exec("aapt d badging " . $apkFile, $out, $ret);
        if ($ret != 0) {
            http_response_code(400);
            echo json_encode(["message" => "It is no Android Apk"]);
            return;
        }




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
        preg_match("/package: name=\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $apkFile), $tmp);
        $apk['packageName'] = isset($tmp[1]) ? $tmp[1] : null;

        preg_match("/application: label=\'([\w\d\. ]+)/i", shell_exec("aapt d badging " . $apkFile), $tmp);
        $apk['appName'] =  isset($tmp[1]) ? $tmp[1] : null;

        preg_match("/versionName=\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $apkFile), $tmp);
        $apk['version'] =  isset($tmp[1]) ? $tmp[1] : null;

        preg_match("/sdkVersion:\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $apkFile), $tmp);
        $apk['sdkVersion'] =  isset($tmp[1]) ? $tmp[1] : null;

        preg_match("/targetSdkVersion:\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $apkFile), $tmp);
        $apk['targetSdkVersion'] =  isset($tmp[1]) ? $tmp[1] : null;

        preg_match_all("/uses-permission: name=\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $apkFile), $tmp);
        $apk['usesPermission'] =  isset($tmp[1]) ? $tmp[1] : null;

        $apk['size'] = filesize($apkFile);

        $apk['dataDir'] = "/data/user/0/" . $apk['packageName'];

        shell_exec("rm -f app_icon.png");

        $tmpIcons = shell_exec("unzip -l " . $apkFile . " | grep -i app_icon.png");
        preg_match_all('/(res.+app_icon.png)/i', $tmpIcons, $tmp, PREG_PATTERN_ORDER);
        $currIcon =  (count($tmp) > 1 && count($tmp[1]) > 0) ? $tmp[1][count($tmp[1]) - 1]  : null;
        if ($currIcon) {
            shell_exec("unzip -jo " . $apkFile . " " . $currIcon);
            error_log($currIcon);
            $tmpImage = file_get_contents(__DIR__ . "/app_icon.png");

            $apk['appIcon'] = "data:image/png;base64," . base64_encode($tmpImage);
        }

        shell_exec("rm -f unity_builtin_extra");
        shell_exec("unzip -jo " . $apkFile . " assets/bin/Data/Resources/unity_builtin_extra");


        if (is_file(__DIR__ . "/unity_builtin_extra")) {
            preg_match('/\d+[\w\.]+/i', file_get_contents(__DIR__ . "/unity_builtin_extra"), $v);
            $apk['unityVersion'] = empty($v) ? '' : $v[0];
            $libs = shell_exec('unzip -l ' . $apkFile);
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
        $urlApk = sprintf($baseUrl, $apk['packageName']);
        $tmpHeaders = @get_headers($urlApk);
        if ($tmpHeaders && strpos($tmpHeaders[0], '301')) {
            $apk["urlApk"] = $urlApk;
        }
        header('Content-type: application/json');
        echo json_encode($apk);
        unlink($apkFile);
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Apk is missing"]);
        unlink($apkFile);
        return;
    }
} catch (\Throwable $th) {
    http_response_code(500);
    unlink($apkFile);
    echo json_encode(["message" => $th->getMessage(), "code" => $th->getCode()]);
}


function downloadUrlToFile($url, $outFileName)
{
    if (is_file($url)) {
        copy($url, $outFileName);
    } else {
        $options = array(
            CURLOPT_FILE    => fopen($outFileName, 'w'),
            CURLOPT_TIMEOUT =>  28800, // set this to 8 hours so we dont timeout on big files
            CURLOPT_URL     => $url
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        curl_close($ch);
    }
}
function downloadFile($url, $path)
{
    $newfname = $path;
    $file = fopen($url, 'rb');
    if ($file) {
        $newf = fopen($newfname, 'wb');
        if ($newf) {
            while (!feof($file)) {
                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
            }
        }
    }
    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
}




function getApkUrl($url = "")
{
    try {
        $tmpHeaders = @get_headers($url);
        if ($tmpHeaders && strpos($tmpHeaders[0], "301")) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTMLFile($url);
            $apkUrl = $dom->getElementsByTagName("iframe")[0]->getAttribute('src');
            $tmpHeaders = @get_headers($apkUrl);

            if ($tmpHeaders && strpos($tmpHeaders[0], "302")) {
                $m = null;

                foreach ($tmpHeaders as $tmp) {
                    if (preg_match("/location: (http.*)/i", $tmp, $m)) {
                        $m = count($m) > 1 ? $m[1] : null;
                        break;
                    }
                }
                if ($m) {
                    $tmpHeaders = @get_headers($m);
                    if ($tmpHeaders && strpos($tmpHeaders[0], "200")) {
                        $file = [
                            "url" => $m,
                            "type" => null,
                            "filename" => null
                        ];
                        foreach ($tmpHeaders as $header) {
                            if (preg_match("/Content-Type: (.*)/i", $header, $tmp)) {
                                $tmp = count($tmp) > 1 ? $tmp[1] : null;
                                $file['type'] = $tmp;
                            }
                            if (preg_match("/filename=\"(.*)\"/i", $header, $tmp)) {
                                $tmp = count($tmp) > 1 ? $tmp[1] : null;
                                $file['filename'] = $tmp;
                            }
                        }
                        return $file;
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    } catch (\Throwable $th) {
        return null;
    }
}
