<?php
// Sample to notify Discord of new Fleet. //

// ----- configuration ----- //
$discord_token = "";
$discord_channel = "";

function DiscordSendFile($token, $channelid, $message, $path)
{
    $headers = array(
        "Content-Type: multipart/form-data",
        "Authorization: Bot $token",
        "User-Agent: DiscordBot (https://example.com, v0.0.1)"
    );

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://discord.com/api/channels/$channelid/messages",
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS  => [
            "content" => $message,
            "file" => new CURLFile($path),
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $a = curl_exec($ch);
    echo curl_error($ch);
    file_put_contents(__DIR__ . "/test.txt", print_r(curl_getinfo($ch), true) . "\n\n\n\n\n" . $a);
}

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
    $userdata = json_decode(file_get_contents(__DIR__ . "/users/$user_id_str.json"), true);
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

                $username = $userdata["name"];
                $screen_name = $userdata["screen_name"];
                DiscordSendFile($discord_token, $discord_channel, "`$username` @$screen_name", "{$userdata_dir}{$filename}-{$created_date}.{$extension}");
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

            $username = $userdata["name"];
            $screen_name = $userdata["screen_name"];
            DiscordSendFile($discord_token, $discord_channel, "`$username` @$screen_name", "{$userdata_dir}{$filename}-{$created_date}.{$extension}");
        }
    }
}

