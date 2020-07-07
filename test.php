<?php
// $baseUrl = "https://m.apkpure.com/android/%s/download?from=details";
$package = isset($_GET['package']) ? $_GET['package'] : null;

if ($urlApk = getApkUrlCombo($package)) {
    header('Content-type: application/json');
    echo json_encode($urlApk);
} else {
    echo "Apk not found";
}


// if ($tmpHeaders && strpos($tmpHeaders[0], '301')) {
//     $dom = new DOMDocument();
//     libxml_use_internal_errors(true);
//     $dom->loadHTMLFile($tmpUrl);
//     $apkUrl = $dom->getElementsByTagName("iframe")[0]->getAttribute('src');
//     $apkFile = __DIR__ . "/downloads/" . time();
//     downloadFile($apkUrl, $apkFile);
// }


function getApkUrl($url = "")
{
    try {
        $baseUrl = "https://m.apkpure.com/android/%s/download?from=details";
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
    try {
        $host = "https://apkcombo.com";
        $baseUrl = "https://apkcombo.com/pt-br/%s/download/apk";
        $url = sprintf($baseUrl, $package);
        if (preg_match("/href=\'(.+)\'/i", shell_exec("curl " . $url), $m)) {
            $m = count($m) > 1 ? $m[1] : null;
            if ($m) {
                $m = $host . $m;
                if (preg_match("/<a href=\"(.+)\" class=\"app\"/i", shell_exec("curl " . $m), $m)) {
                    $m = count($m) > 1 ? $m[1] : null;
                    if ($m) {
                        $tmpHeaders = @get_headers($m);
                        if ($tmpHeaders && strpos($tmpHeaders[0], "200")) {
                            $file = [
                                "url" => $m,
                                "type" => null,
                                "filename" => null
                            ];
                            // print_r($tmpHeaders);
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
            }
            return null;
        }
    } catch (\Throwable $th) {
        return null;
    }
}
