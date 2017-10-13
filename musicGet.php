<?php

class Music_163
{


    function curl_get($url)
    {
        $refer    = "http://music.163.com/";
        $header[] = "Cookie: " . "appver=1.5.0.75771;";
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $refer);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


    function postAndGetResult($url, $data)
    {
        $url       = "http://music.163.com/" . $url;
        $post_data = $data;
        $referrer  = "http://music.163.com/";
        $URL_Info  = parse_url($url);
        $values    = [];
        $result    = '';
        $request   = '';
        foreach ($post_data as $key => $value) {
            $values[] = "$key=" . urlencode($value);
        }
        $data_string = implode("&", $values);
        if (!isset($URL_Info["port"])) {
            $URL_Info["port"] = 80;
        }
        $request .= "POST " . $URL_Info["path"] . " HTTP/1.1\n";
        $request .= "Host: " . $URL_Info["host"] . "\n";
        $request .= "Referer: $referrer\n";
        $request .= "Content-type: application/x-www-form-urlencoded\n";
        $request .= "Content-length: " . strlen($data_string) . "\n";
        $request .= "Connection: close\n";
        $request .= "Cookie: " . "appver=1.5.0.75771;\n";
        $request .= "\n";
        $request .= $data_string . "\n";
        $fp      = fsockopen($URL_Info["host"], $URL_Info["port"]);
        fputs($fp, $request);
        $i = 1;
        while (!feof($fp)) {
            if ($i >= 15) {
                $result .= fgets($fp);
            } else {
                fgets($fp);
                $i++;
            }
        }
        fclose($fp);
        return $result;
    }

    function music_search($word, $type, $limit = 100, $offset = 0)
    {
        return json_decode(
            $this->postAndGetResult('api/search/pc', [
                's'      => $word,
                'offset' => $offset,
                'limit'  => $limit,
                'type'   => $type,
            ]),
            true);
    }


    function music_get($id)
    {
//    'http://music.163.com/weapi/song/enhance/player/url?csrf_token=';
        $data = $this->createParam($id);
        return json_decode($this->postAndGetResult('weapi/song/enhance/player/url', $data), true);

        /*
         *  {"data":[{"id":413812378,"url":"http://m10.music.126.net/20170930150020/5a60f4e62d8da953894c00a57f083203/ymusic/2a0c/718e/fecc/d2407d8228490343a94dc008463d3aab.mp3","br":128000,"size":1778042,"md5":"d2407d8228490343a94dc008463d3aab","code":200,"expi":1200,"type":"mp3","gain":-2.0E-4,"fee":0,"uf":null,"payed":0,"flag":0,"canExtend":false}],"code":200}
         */

    }


    function createParam($id = '', $br = 128000)
    {
//    echo $text    = json_encode($text);
//    $pubKey  = 010001;
//    $modulus = '00e0b509f6259df8642dbc35662901477df22677ec152b5ff68ace615bb7b725152b3ab17a876aea8a5aa76d2e417629ec4ee341f56135fccf695280104e0312ecbda92557c93870114af6c9d05c4f7f0c3685b7a46bee255932575cce10b424d813cfe4875d3e82047b97ddef52741d546b8e289dc6935b3ece0462db0a22b8e7';

        $text    = [
            'ids'        => '[' . $id . ']',
            'br'         => $br,
            'csrf_token' => ''
        ];
        $text    = json_encode($text);
        $nonce   = '0CoJUm6Qyw8W8jud';
        $secKey  = 'FFFFFFFFFFFFFFFF';
        $encText = $this->AES_encrypt(
            $this->AES_encrypt($text, $nonce),
            $secKey);
//    $encSecKey = RSA_encrypt($secKey, $pubKey, $modulus);  不需要计算rsa加密，在密钥$secKey固定的情况此值不会变化
        $encSecKey = '257348aecb5e556c066de214e531faadd1c55d814f9be95fd06d6bff9f4c7a41f831f6394d5a3fd2e3881736d94a02ca919d952872e7d0a50ebfa1769a7a62d512f5f1ca21aec60bc3819a9c3ffca5eca9a0dba6d6f7249b06f5965ecfff3695b54e1c28f3f624750ed39e7de08fc8493242e26dbc4484a01c76f739e135637c';
        return [
            'params'    => $encText,
            'encSecKey' => $encSecKey,
        ];
    }

    function AES_encrypt($text, $key, $iv = '0102030405060708')
    {
        $pad       = 16 - strlen($text) % 16;
        $text      = $text . str_repeat(chr($pad), $pad);
        $encryptor = openssl_encrypt($text, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($encryptor);
    }

    function get_music_info($music_id)
    {
        $url = "http://music.163.com/api/song/detail/?id=" . $music_id . "&ids=%5B" . $music_id . "%5D";
        return $this->curl_get($url);
    }

    function get_artist_album($artist_id, $limit)
    {
        $url = "http://music.163.com/api/artist/albums/" . $artist_id . "?limit=" . $limit;
        return $this->curl_get($url);
    }

    function get_album_info($album_id)
    {
        $url = "http://music.163.com/api/album/" . $album_id;
        return $this->curl_get($url);
    }

    function get_playlist_info($playlist_id)
    {
        $url = "http://music.163.com/api/playlist/detail?id=" . $playlist_id;
        return $this->curl_get($url);
    }

    function get_music_lyric($music_id)
    {
        $url = "http://music.163.com/api/song/lyric?os=pc&id=" . $music_id . "&lv=-1&kv=-1&tv=-1";
        return $this->curl_get($url);
    }

    function get_mv_info()
    {
        $url = "http://music.163.com/api/mv/detail?id=319104&type=mp4";
        return $this->curl_get($url);
    }


}

function outPut($r = 0, $data = [], $extra = '')
{
    echo json_encode(['r' => $r, 'data' => $data, 'ex' => $extra]);
    exit;
}

$word = $_GET['word'] ? $_GET['word'] : 'acg';

$m     = new Music_163();
$count = $m->music_search($word, 1, 1)['result']['songCount'];
if (!$count) {
    outPut(0, '换一个关键字吧，没搜到');
}
$page        = 20;
$ex          = [];
$ex['count'] = $count;
$p           = 0;
if ($count > $page) {
    $p     = ceil($count / $page) - 1;
    $p     = ceil(rand(0, $p));
    $count = $page;
}
$ex['p'] = $p;
$list    = $m->music_search($word, 1, $count, $p);
$url     = function () use ($list, $count, $m, &$url) {
    $rand = (int)rand(0, $count - 1);
    $song = $list['result']['songs'][$rand];
    $info = $m->music_get($song['id']);
    $res  = $info['data'][0]['url'];
    if ($res) {
        return $res;
    } else {
        return $url();
    }
};
if ($_GET['ajax']) {
    outPut(1, $url(), $ex);
}

?>

<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport"
          content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <meta name="full-screen" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>
    <title>随机音乐播放demo</title>
</head>
<body>
<div class="container-fluid" style="height: 100vh">
    <div class="row" style="margin-top: 1vh; background: #bdbdbd;padding: 1vh">
        <div class="col-xs-12 col-sm-6 col-md-8">
            音乐资源来源于<a href="http://music.163.com/" target="_blank">网易云</a>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-8">
            本程序仅用于学习交流,若有任何侵犯合法权益行为请联系 qq:623975749 删除程序
        </div>
        <div class="col-xs-12 col-sm-6 col-md-8">
            本程序已开源于 <a href="https://github.com/LanFD/music_163" target="_blank">https://github.com/LanFD/music_163</a>
        </div>
    </div>
    <div class="form-group center-block" style="margin-top: 4vh">
        <div class="input-group">
            <div class="input-group-addon">关键字：</div>
            <input class="form-control" type="text" id="text" value="" placeholder="写入关键字">
        </div>
        <div style="margin-top: 1vh">
            <button id="playButton" type="button" class="btn btn-default">
                <span class="glyphicon glyphicon-play"></span>
            </button>
            <button onclick="getAnother()" type="button" class="btn btn-default">
                <span class="glyphicon glyphicon-step-forward"></span>
            </button>

            <button style="float: right" onclick="down()" type="button" class="btn btn-default">
                <span class=" glyphicon glyphicon-arrow-down"></span>
            </button>

        </div>

        <input class="btn btn-primary" type="submit" id="mobile" onclick="mplay()" style="display: none"
               value="手机端若未自动播放请点此">
        <div style="margin-top: 4vh">
            <canvas id="c" style="z-index: -1; width: 100%;background: transparent"></canvas>
        </div>
    </div>
    <audio id="audio" src=""></audio>
</div>
</body>
</html>
<script>
    let audio = $("#audio")[0];
    function log(x)
    {
        console.log(x);
    }
    function down()
    {
        let src = audio.src;
        window.open(src);
    }

    function playOrPause(autoplay = 0)
    {
        if (autoplay) {
            audio.pause();
        }
        if (audio.paused) {
            audio.play();
            $('#playButton').find('span').attr("class", "glyphicon glyphicon-play");
        } else {
            audio.pause();
            $('#playButton').find('span').attr("class", "glyphicon glyphicon-pause");
        }
    }

    function autoPlay(n = 0)
    {
        if (n > 10) {
            //获取资源失败
            getAnother();
            return;
        }
        if (audio.readyState) {
            playOrPause(1);
        } else {
            n++;
            setTimeout(() =>
            {
                autoPlay(n);
        }, 500);
        }
    }

    function mplay()
    {
        autoPlay();
        if (audio.readyState) {
            $('#mobile').hide(2000);
        }
    }

    function getAnother(ini)
    {
        let w = $('#text').val();
        if (ini) {
            w = ini;
            $('#text').val(w);
        }
        if (w) {
            $.ajax({
                    url:      '?ajax=1&word=' + w,
                    dataType: 'json',
                    success:  (x) =>
                {
                    if (x.r) {
                audio.src = x.data;
                autoPlay(0);
            } else {
                alert(x.data)
            }


        }
        }
        );
        } else {
            alert('请写入关键字');
        }
    }


    function randStr()
    {
        let arr = [
            '战舰世界',
            '高达',
            'Touhou',
            '初音',
            'acg',
            '钢琴',
            '小提琴'
        ];
        let l   = arr.length;
        let r   = Math.ceil(Math.random() * l);
        return arr[r - 1];
    }

    $(() =>
    {
        getAnother(randStr());
    if (typeof window.orientation != 'undefined') {
        setTimeout(() =>
        {
            if (audio.paused) {
            $('#mobile').show(500);
        }
    }, 1200)

    }
    $('#playButton').click(() =>
    {
        playOrPause();
    });

    audio.loop    = false;
    audio.onended = () =>
    {
        getAnother();
    };

    document.body.addEventListener('keypress', (e) =>
    {
        let keyCode = e.keyCode || e.which;
    switch (keyCode) {
        case 13:
            getAnother();
            break;
    }
    return false;
    });


    });


</script>
