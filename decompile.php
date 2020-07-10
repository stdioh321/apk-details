<?php


try {
    if (isset($_GET['file-name'])) {
        $fName = $_GET['file-name'];
        if (!strpos($fName, ".apk")) throw new Exception("It's not a apk", 400);

        $dir = scandir(__DIR__ . "/downloads/");
        $f = array_search($fName, $dir);
        if ($f) {
            $outPath = __DIR__ . "/downloads/decompiled/" . explode(".apk", $fName)[0];
            $fullFName = __DIR__ . "/downloads/$fName";
            $decompileCommand = "jadx -d $outPath $fullFName && zip -r $outPath" . "/source.zip $outPath";
            exec($decompileCommand, $out, $ret);
            if ($ret == 0 && $out) {
                $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === 0 ? 'https://' : 'http://';
                $urlSource = $protocol . $_SERVER['HTTP_HOST'] . "/downloads/decompiled/" . explode(".apk", $fName)[0];
                header("Content-Type: application/json");
                http_response_code(200);
                echo json_encode([
                    "url" => $urlSource
                ]);
            } else {
                throw new Exception("Error Decompiling apk", 400);
            }
        } else {
            throw new Exception("File not found", 400);
        }
    } else {
        throw new Exception("Parameter missing", 400);
    }
} catch (\Throwable $th) {
    //throw $th;
    $code = $th->getCode();
    $msg = $th->getMessage();

    error_log("$code - $msg");
    header("Content-Type: application/json");
    http_response_code($code == 400 ? 400 : 500);
    echo json_encode([
        "message" => $code == 400 ? $msg : "Server Error",
        "code" => $code
    ]);
}
