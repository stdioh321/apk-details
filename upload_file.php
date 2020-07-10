<?php
$baseUrlPure = "https://m.apkpure.com/android/%s/download?from=details";
$baseUrlCombo = "https://apkcombo.com/pt-br/%s/download/apk";
$apkFile = null;

try {
    error_reporting(0);
    ini_set('display_errors', 0);


    if (isset($_GET['package'])) {

        $apkUrl = getApkUrlEvozi($_GET['package']);

        // print_r($apkUrl);
        // $apkUrl = getApkUrlCombo($_GET['package']);
        // $apkUrl = $apkUrl ? $apkUrl : getApkUrl($_GET['package']);
        if ($apkUrl) {
            $fileName = null;
            $tmpHeader = @get_headers($apkUrl['url'], 1);
            if ($tmpHeader && strpos($tmpHeader[0], '200')) {
                preg_match("/filename=\"(.*)\"/i", $tmpHeader['Content-Disposition'], $fileName);
                $fileName = count($fileName) > 0 ? $fileName[1] : null;
            }
            $fileName = $fileName && $fileName != "" ? $fileName : $_GET['package'];
            $apkFile = __DIR__ . "/downloads/" . ($fileName);
            file_put_contents($apkFile, file_get_contents($apkUrl['url']));
        } else {
            unlink($apkFile);
            throw new Exception("Unable to find the apk's url", 400);
            return;
        }
    } else if (isset($_FILES['apk'])) {
        $apkFile = $_FILES['apk']['tmp_name'];
    } else {
        unlink($apkFile);
        throw new Exception("Missing parameters", 400);
        return;
    }

    if (is_file($apkFile)) {
        exec("aapt d badging " . $apkFile, $out, $ret);

        if ($ret != 0) {
            exec("unzip -l " . $apkFile . " | grep -i '.apk'", $out, $ret);
            if ($ret == 0) {
                exec("unzip -oj " . $apkFile . " -d " . __DIR__ . "/downloads/tmp/", $out, $ret);

                $dir = scandir(__DIR__ . "/downloads/tmp/");
                // print_r($ret);
                $tmpApk = null;
                foreach ($dir as $f) {
                    if (preg_match("/\.apk$/i", $f, $tmp)) {
                        $tmpApk = __DIR__ . "/downloads/tmp/" . $f;
                        break;
                    }
                }
                if ($tmpApk) {
                    preg_match("/package: name=\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $tmpApk), $tmp);
                    $tmpApk = isset($tmp[1]) ? $tmp[1] : null;
                    $tmpApk = $tmpApk ? __DIR__ . "/downloads/tmp/" . $tmpApk . ".apk" : null;
                    // print_r($tmpApk);
                    if (is_file($tmpApk)) {
                        $apkFile = $tmpApk;
                    } else {
                        throw new Exception("It is not Android Apk 1", 400);
                        return;
                    }
                } else {
                    throw new Exception("It is not Android Apk 2", 400);
                    return;
                }
            } else {
                throw new Exception("It is not Android Apk 3", 400);
                return;
            }
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
            "appIcon" => null,
            "urlApk" => []
        ];
        preg_match("/package: name=\'([\w\d\.]+)/i", shell_exec("aapt d badging " . $apkFile), $tmp);
        $apk['packageName'] = isset($tmp[1]) ? $tmp[1] : null;

        preg_match("/application: label=\'(.+)\' icon/i", shell_exec("aapt d badging " . $apkFile), $tmp);
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
            // error_log($currIcon);
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

        // if ($urlApk = getApkUrl($apk['packageName'])) {
        //     array_push($apk["urlApk"], $urlApk['url']);
        // }
        // if ($urlApk = getApkUrlCombo($apk['packageName'])) {
        //     array_push($apk["urlApk"], $urlApk['url']);
        // }
        $tmpHeaders = @get_headers(sprintf($baseUrlPure, $apk['packageName']));
        if ($tmpHeaders && strpos($tmpHeaders[0], '301')) {
            array_push($apk['urlApk'], sprintf($baseUrlPure, $apk['packageName']));
        }
        $out = shell_exec("curl -I " . sprintf($baseUrlCombo, $apk['packageName']));
        $ret = preg_match("/HTTP\/2 200/i", $out);

        if (preg_match("/HTTP\/2 200/i", $out) == 0) {
            array_push($apk['urlApk'], sprintf($baseUrlCombo, $apk['packageName']));
        }



        header('Content-type: application/json');
        http_response_code(200);

        $outZipPath =  __DIR__ . '/downloads/decompiled/' . $apk['packageName'] . '/';

        $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === 0 ? 'https://' : 'http://';
        $host = $protocol . $_SERVER['HTTP_HOST'] . '/downloads/decompiled/' . $apk['packageName'];
        // $apk['urlDecompiled'] = $host;
        echo json_encode($apk);

        // $decompileCommand = "jadx -d $outZipPath $apkFile && zip -r $outZipPath" . "source.zip $outZipPath";
        // $pid = execBackground($decompileCommand);
        // error_log($decompileCommand);
        // exec($decompileCommand);

        //unlink($apkFile);
    } else {
        unlink($apkFile);
        throw new Exception("It was not possible to get the Apk", 400);
        return;
    }
} catch (\Throwable $th) {

    http_response_code($th->getCode() == 400 ? 400 : 500);
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




function getApkUrl($package = "")
{
    try {
        $baseUrl = "https://m.apkpure.com/android/%s/download?from=details";
        $url =  sprintf($baseUrl, $package);
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
function getApkUrlCombo($package = "")
{

    $host = "https://apkcombo.com";
    $baseUrl = "https://apkcombo.com/us/%s/download/apk";
    $url = sprintf($baseUrl, $package);
    preg_match("/href=\'(.+)\'/i", shell_exec("curl " . $url), $m);

    if ($m) {

        $m = count($m) > 1 ? $m[1] : null;

        if ($m) {
            $m = $host . $m;

            $out = shell_exec("curl " . $m);
            preg_match("/<a href=[\"\'](.+)[\"\'] class=\"app\"/i", $out, $m);

            if ($m) {

                $m = count($m) > 1 ? $m[1] : null;

                if ($m) {

                    $file = [
                        "url" => $m,
                        "type" => null,
                        "filename" => null
                    ];
                    return $file;
                } else {
                    return null;
                }
            } else {
                throw new Exception("Unable to get apk url. 1", 400);
                return null;
            }
        }
        return null;
    } else {
        throw new Exception("Unable to get apk url. 2", 400);
        return null;
    }
}


function getApkUrlEvozi($package = "")
{
    $baseUrl = "https://apps.evozi.com/apk-downloader/?id=%s";
    $url = sprintf($baseUrl, $package);
    $out = file_get_contents($url);
    preg_match("/\('#apk_info'\)\.html\('<div class=\"mt-2 mb-2\"(.|\r\n)* =  { ([\w-]+)   : (\w+),  ([\w-]+): [\w-]+, +(.+): +([\w-]+),(.|\r\n)*fetch'/i", $out, $m);

    if ($m && count($m) > 7) {
        $d = $m[2] . "=" . $m[3] . "&" . $m[4] . "=" . $package . "&" . $m[5] . "=";
        preg_match("/var " .  $m[6]  . " += '([\w-]+)';/i", $out, $m);
        if ($m && count($m) > 1) {
            $d = $d . $m[1] . "&fetch=false";
        } else {
            throw new Exception("Error parsing the page", 400);
        }

        $command = 'curl -f --header "X-Forwarded-For: ' . rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254) . '" -f "https://api-apk.evozi.com/download" --data-raw "' . trim($d) . '"';
        // $j = '{"status":"success","packagename":"com.farproc.wifi.analyzer","url":"\/\/storage.evozi.com\/apk\/dl\/16\/09\/04\/com.farproc.wifi.analyzer.apk?h=j1iyPd5ZNEpEM8TvwuliPw&t=1594323811&vc=139","obb_url":null,"filesize":"1.8 MB","obb_filesize":null,"sha1":"070f530172f74d5ca63e660b437ae9a9fdd69d3b","version":"3.11.2","version_code":139,"fetched_at":"2020-04-01 02:27:10","cache":true,"state":"cache"}';
        exec($command, $out, $ret);

        // error_log($command);
        if ($ret == 0 && $out && count($out) >= 1 && json_decode($out[0])->status != "error") {
            // $tmpUrl = "https:" . json_decode($out[0])->url;

            $tmpUrlApk =  "https:" . json_decode($out[0])->url;
            $file = [
                "url" => $tmpUrlApk,
                "type" => null,
                "filename" => null
            ];
            // header("Content-Type: application/json");
            return $file;
        } else {
            throw new Exception("Unable to the get the apk", 400);
        }
    }
}


// class AsyncOperation extends Thread
// {
//     public function __construct($arg)
//     {
//         $this->arg = $arg;
//     }

//     public function run()
//     {
//         error_log("Assync");
//     }
// }


function execBackground($command = "")
{
    $cmd = "$command >/dev/null 2>&1 &";
    return shell_exec($cmd);
}
