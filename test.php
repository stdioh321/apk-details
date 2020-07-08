<?php
try {
    if (!isset($_GET["q"])) {
        http_response_code(400);
        header("Content-Type: application/json");
        print_r(["message" => "Parameters missing"]);
        return false;
    }
    $q = $_GET["q"];
    $url = "https://apkcombo.com/pt-br/search?q=" . $q;

    $content = shell_exec("curl " . $url);
    if (strpos($content, "<!DOCTYPE html>")) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//div[contains(@class,'lapps')]/a[contains(@class,'lapp')]");
        $search = [];
        foreach ($nodes as $node) {
            $tmpImg = $xpath->query("descendant::img[@src]", $node)[0]->getAttribute("data-src");
            $tmpName = $xpath->query("descendant::div[contains(@class,'info')]/strong", $node)[0]->nodeValue;
            $tmpPackage = explode("/", $node->getAttribute("href"))[3];
            $search[] = new Search($tmpPackage, $tmpName, $tmpImg);
        }
        header("Content-Type: application/json");
        print_r(json_encode($search));
    } else {
        http_response_code(400);
        header("Content-Type: application/json");
        print_r(["message" => "Curl error"]);
        return false;
    }
} catch (\Throwable $th) {
    http_response_code(500);
    header("Content-Type: application/json");
    print_r(["message" => $th->getMessage()]);
    return false;
}

class Search
{
    public $package;
    public $name;
    public $imgUrl;

    function __construct($package = "", $name = "", $imgUrl = "")
    {
        $this->package = $package;
        $this->name = $name;
        $this->imgUrl = $imgUrl;
    }
}
