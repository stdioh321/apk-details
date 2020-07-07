<?php
$baseUrl = "https://m.apkpure.com/android/%s/download?from=details";
$url = isset($_GET['package']) ? sprintf($baseUrl, $_GET['package']) : null;

if ($urlApk = getApkUrl($url)) {
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
