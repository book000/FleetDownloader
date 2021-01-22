<?php
// Sample to download all Fleet //

// Run out/main.js
system("node out/main.js", $ret);
if ($ret != 0) {
    echo "Error $ret\n";
    system("node out/main.js", $ret);
}
if ($ret != 0) {
    echo "Error $ret\n";
    exit(1);
}

// Get fleetline.json
$json = json_decode(file_get_contents(__DIR__ . "/fleetline.json"), true);

foreach ($json["hydrated_threads"] as $thread) {
    $user_id_str = $thread["user_id_str"];
    $userdata_dir = __DIR__ . "/data/$user_id_str/";
    if (!file_exists($userdata_dir)) {
        mkdir($userdata_dir, 0777, true);
    }
    foreach ($thread["fleets"] as $fleet) {
        $imgurl = $fleet["media_entity"]["media_url_https"];
        $imgurl = preg_replace("/\.([^.]+)$/", "?format=$1&name=orig", $imgurl);
        $fleet_id = $fleet["fleet_id"];
        $created_at = $fleet["created_at"];
        $created_date = date("Y-m-d_H-i-s", strtotime(preg_replace("/\.[0-9]+Z/", "Z", $created_at)));
        print_r($fleet);

        if (isset($fleet["media_entity"]["media_info"]["video_info"]["variants"])) {
            $max_size_variant = array_reduce($fleet["media_entity"]["media_info"]["video_info"]["variants"], function ($max_size_variant, $variant) {
                if ($variant["content_type"] != "video/mp4") {
                    return $max_size_variant;
                }
                return ($max_size_variant["bit_rate"] < $variant["bit_rate"]) ? $variant : $max_size_variant;
            }, ["bit_rate" => 0]);
            if (isset($max_size_variant["url"])) {
                echo $max_size_variant["url"];
                $urlinfo = parse_url($max_size_variant["url"]);
                print_r($urlinfo);
                $pathinfo = pathinfo($urlinfo["path"]);
                print_r($pathinfo);
                $filename = $pathinfo["filename"];
                $extension = isset($pathinfo["extension"]) ? $pathinfo["extension"] : "mp4";
                echo "{$userdata_dir}{$filename}-{$created_date}.{$extension}";
                if (file_exists("{$userdata_dir}{$filename}-{$created_date}.{$extension}")) {
                    continue;
                }
                $data = file_get_contents($max_size_variant["url"]);
                file_put_contents("{$userdata_dir}{$filename}-{$created_date}.{$extension}", $data);
            }
        } else {
            $urlinfo = parse_url($imgurl);
            print_r($urlinfo);
            $pathinfo = pathinfo($urlinfo["path"]);
            print_r($pathinfo);
            $filename = $pathinfo["filename"];
            $extension = isset($pathinfo["extension"]) ? $pathinfo["extension"] : "jpg";
            echo "{$userdata_dir}{$filename}-{$created_date}.{$extension}";
            if (file_exists("{$userdata_dir}{$filename}-{$created_date}.{$extension}")) {
                continue;
            }
            $data = file_get_contents($imgurl);
            file_put_contents("{$userdata_dir}{$filename}-{$created_date}.{$extension}", $data);
        }
    }
}

