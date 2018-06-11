<?php

class Music_163
{


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
//        $request .= "Cookie: " . "appver=1.5.0.75771;\n";
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

    /*
     * type=1             单曲

type=10           专辑

type=100         歌手

type=1000      歌单

type=1002      用户

type=1004       MV

type=1006      歌词

type=1009      主播电台
     */

    function music_search($word = '战舰世界', $offset = 0, $limit = 30, $type = 1)
    {
        //http://music.163.com/weapi/cloudsearch/get/web?csrf_token=
        $p = [
            's'      => $word,
            'type'   => $type,
            'offset' => $offset,
            'total'  => 'true',
            'limit'  => $limit,
        ];
        $p = json_encode($p);
        return json_decode(
            $this->postAndGetResult('weapi/cloudsearch/get/web',
                $this->createParam($p)
            ),
            true);
    }

    /*
     * 获取歌单歌曲信息
     */
    function music_list($id = 2181436358)
    {
        return json_decode(file_get_contents('http://music.163.com/api/playlist/detail?id=' . $id), true);
    }


    function music_get($id, $br = 128000)
    {
//    'http://music.163.com/weapi/song/enhance/player/url?csrf_token=';
//        {"ids":"[413812377]","br":128000,"csrf_token":""}
        $p    = [
            'ids' => '[' . $id . ']',
            'br'  => $br,
        ];
        $p    = json_encode($p);
        $data = $this->createParam($p);
        return json_decode($this->postAndGetResult('weapi/song/enhance/player/url?csrf_token=', $data), true);

        /*
         *  {"data":[{"id":413812378,"url":"http://m10.music.126.net/20170930150020/5a60f4e62d8da953894c00a57f083203/ymusic/2a0c/718e/fecc/d2407d8228490343a94dc008463d3aab.mp3","br":128000,"size":1778042,"md5":"d2407d8228490343a94dc008463d3aab","code":200,"expi":1200,"type":"mp3","gain":-2.0E-4,"fee":0,"uf":null,"payed":0,"flag":0,"canExtend":false}],"code":200}
         */

    }


    function createParam($text)
    {
//    echo $text    = json_encode($text);
//    $pubKey  = 010001;
//    $modulus = '00e0b509f6259df8642dbc35662901477df22677ec152b5ff68ace615bb7b725152b3ab17a876aea8a5aa76d2e417629ec4ee341f56135fccf695280104e0312ecbda92557c93870114af6c9d05c4f7f0c3685b7a46bee255932575cce10b424d813cfe4875d3e82047b97ddef52741d546b8e289dc6935b3ece0462db0a22b8e7';

//        $text    = [
//            'ids'        => '[' . $id . ']',
//            'br'         => $br,
//            'csrf_token' => ''
//        ];
//        $text    = json_encode($text);
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

    function outPut($r = 0, $data = [], $extra = '')
    {
        echo json_encode(['r' => $r, 'data' => $data, 'ex' => $extra]);
        exit;
    }

}

//$_GET['ajax']= 1;
if ($_GET['ajax']) {
    $cacheToServer = $_GET['doCache'] ? 1 : 0; //是否存储到自己的空间以解决跨域

    $m = new Music_163();
//    $r = $m->music_list();

    if (isset($_GET['song_id'])) {
        $id = $_GET['song_id'];
    } else {

        $type = isset($_GET['type']) ? $_GET['type'] : 1000;
        //单曲和歌单区分
        $song = '';
        $info = $m->music_search(
            $word = $_GET['word'] ? $_GET['word'] : '战舰世界',
            $offset = 0,
            $limit = 30,
            $type
        );


        switch ($type) {
            case 1:
                //单曲
                $count = $info['result']['songCount'];
                if ($count > 0) {
                    $play = rand(0, $count);
                    $p    = 0;
                    if ($play > $limit) {
                        $p    = floor($play / $limit);
                        $play = $play - $limit * $p;
                    }
                    if ($p > 0) {
                        $info = $m->music_search(
                            $word,
                            $p,
                            $limit,
                            $type
                        );
                    }
//        print_r($info);exit;
                    $song = $info['result']['songs'][$play];

                }
                break;
            case 1000:
                //歌单
                if ($info['code'] == 200) {
                    $count = $info['result']['playlistCount'];
                    if ($count > 0) {
                        $play = rand(0, $count);
                        $p    = 0;
                        if ($play > $limit) {
                            $p    = floor($play / $limit);
                            $play = $play - $limit * $p;
                        }
                        if ($p > 0) {
                            $info = $m->music_search(
                                $word,
                                $p,
                                $limit,
                                $type
                            );
                        }

                        $list = $info['result']['playlists'][$play];
                        if ($list) {
                            $songList = $m->music_list($list['id']);
                            if ($songList['code'] == 200) {
                                $play = rand(0, $songList['result']['trackCount']);
                                $song = $songList['result']['tracks'];
                            }
                        }

                    }
                }
                break;
        }
    }


    function getMusic($try = 1)
    {
        global $m;
        global $cacheToServer;
        global $songList;
        global $song;
        global $play;
        global $id;
        $try++;
        $id = $id ? $id : $song[$play]['id'];
        if ($try > 9) {
            $m->outPut(0, '搜索失败');
        }
        if ($try % 3 == 0) {
            if(!$song){
                $m->outPut(0, '搜索失败');
            }
            $play = rand(0, count($song));
            $id   = 0;
            getMusic($try);
        }
        $url = $m->music_get($id);
        $url = $url['data'][0]['url'];
        if (!$url) {
            getMusic($try);
        }
        $ex = [
            'cors' => $cacheToServer,
            'name' => $song['name'] ? $song['name'] : $song[$play]['name'],
            'al'   => [
                'name' => $song['al']['name'],
                'pic'  => $song['al']['picUrl']
            ]

        ];

        //@todo
        if ($cacheToServer) {
            $qiniuUrl = 'https://www.lanfd.top/flashchat/qiniu.php?';
            $param    = [
                'ajax' => 1,
                'url'  => $url,
                'key'  => isset($song['name']) ? $song['name'] : '' . $song['id'],
            ];
            $qiniuUrl = $qiniuUrl . http_build_query($param);
            $res      = json_decode(file_get_contents($qiniuUrl), true);
        } else {
            $res = ['r' => 1, 'data' => $url];
        }

        if ($res['r']) {
            if (isset($songList)) {
                $ex['song_list'] = $songList;
            }
            $m->outPut(1, $res['data'], $ex);
        }
    }

    getMusic();

    exit;
}

?>


<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport"
          content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <meta name="full-screen" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="referrer" content="no-referrer"/>
    <link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <script src="http://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
    <title>随机音乐播放demo</title>
    <style>
        .cube {
            -webkit-transform: translateZ(0);
            -moz-transform: translateZ(0);
            -ms-transform: translateZ(0);
            -o-transform: translateZ(0);
            transform: translateZ(0);
            /* Other transform properties here */
        }

        .song_list {
            display: none;
        }
    </style>
</head>
<body>
<div class="container-fluid" style="height: 100vh">
    <div class="cube"></div>
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
            <div class="input-group-addon">搜索模式：</div>
            <select id="type" name="s" class="form-control" style="z-index:  0">
                <option value="1000" selected="selected">歌单</option>
                <option value="1">单曲</option>
            </select>
            <div class="input-group-addon">关键字：</div>
            <input style="z-index: 0" class="form-control" type="text" id="text" value="" placeholder="写入关键字">
        </div>
        <div style="margin-top: 1vh; width:  100%; text-align: center">
            <form class="form-inline">
                <div style="float: left" class="form-group">
                    <button id="playButton" onclick="playOrPause()" type="button" class="btn btn-default">
                        <span class="glyphicon glyphicon-play"></span>
                    </button>
                </div>
                <div class="form-group" style="float: left; margin-left: 2%">
                    <button onclick="getAnother()" type="button" class="btn btn-default">
                        <span class="glyphicon glyphicon-step-forward"></span>
                    </button>


                    <label class="radio-inline song_list">
                        <input checked type="radio" name="play" value="0"> 继续播放歌单
                    </label>
                    <label class="radio-inline song_list">
                        <input type="radio" name="play" value="1"> 搜索新歌单
                    </label>


                </div>


                <div class="song_list" style="margin: 0 auto; display: none;">
                    <label for="">歌单信息: </label>
                    <span id="songListInfo" style="color: #d95c37">waiting for data</span>
                </div>

                <div style="margin: 0 auto; display: inline-block;">
                    <label for="">当前播放: </label>
                    <span id="songInfo" style="color: darkcyan">waiting for data</span>
                </div>


                <button style="float: right" onclick="down()" type="button" class="btn btn-default">
                    <span class=" glyphicon glyphicon-arrow-down"></span>
                </button>


            </form>
        </div>

        <input class="btn btn-primary" type="submit" id="mobile" style="display: none"
               value="手机端若未自动播放请点播放键">
        <div style="margin-top: 4vh;width: 100%;height: 50vh">
            <canvas id="canvas"></canvas>
        </div>
    </div>
    <audio id="audio" src=""></audio>
</div>

<div id="mask"
     style="z-index:99;top:0;height: 100vh;width: 100%;position: absolute;background: black;opacity: .7;font-size: 200%;text-align: center;color: white;padding-top: 20vh">
    downloading ~~please wait~~~
</div>

</body>
</html>


<script>
    class audioCvs {
        constructor()
        {
            this.file        = '';
            this.confirmStop = 1;//停止播放
            this.ctldo       = 0;      //自动播放控制
            this.netError    = 0; //请求失败

        }

        static ini()
        {
            return new this()
        }


        loadSound(url, cb = '')
        {
            hideShowMask(0);
            if (this.audioContext) {
                this.audioContext.close();
                this.audioBufferSourceNode = '';
                this.currentTime           = 0;
                this.confirmStop           = 1;
                this.RAF ? cancelAnimationFrame(this.RAF) : '';
            }
            this.audioContext = new AudioContext();
            this.data         = url;

            let request = new XMLHttpRequest();//建立一个请求
            request.open('GET', url, true); //配置好请求类型，文件路径等
            request.responseType = 'arraybuffer'; //配置数据返回类型
            // 一旦获取完成，对音频进行进一步操作，比如解码
            request.onload  = () =>
            {
                hideShowMask(1);
                this.file = request.response;
                this.loadAudioFile();
            };
            request.onerror = () =>
            {
                this.netError = 1;
                return 0
            };

//            request.onreadystatechange = ()=>{
//                log('ready:'+request.readyState + '; status:' + request.status);
//            };

            request.send();
            if (cb) {
                cb(this.netError);
            }
        }


        loadAudioFile()
        {
            this.audioContext.decodeAudioData(this.file, (buffer) =>
            {
                this.getStart(buffer);
            });
        }


        getStart(buffer, startTime = 0)
        {
            this.confirmStop           = 0;
            this.audioBufferSourceNode = this.audioContext.createBufferSource();
            let analyser               = this.audioContext.createAnalyser();
            //connect the source to the analyser
            this.audioBufferSourceNode.connect(analyser);
            //connect the analyser to the destination(the speaker), or we won't hear the sound
            analyser.connect(this.audioContext.destination);
            //then assign the buffer to the buffer source node
            this.audioBufferSourceNode.buffer = buffer;
            //play the source
            if (startTime) {
                this.audioBufferSourceNode.start(startTime, startTime);
            } else {
                this.audioBufferSourceNode.start(0);
            }

            this.audioBufferSourceNode.onended = () =>
            {
                // log(this.ctldo);
                // log(parseInt(this.audioBufferSourceNode.buffer.duration) <= parseInt(this.audioContext.currentTime));
                if (!this.ctldo || parseInt(this.audioBufferSourceNode.buffer.duration) <= parseInt(this.audioContext.currentTime)) {
                    this.audioBufferSourceNode = '';
                    this.confirmStop           = 1;
                    cancelAnimationFrame(this.RAF);
                    this.ctldo = 0;
                    getAnother();
                }

            };
            let c                              = this.canvasFunc();
            this.widthExpand();
            this.canvasImg(analyser, c);

        }

        canvasFunc()
        {
            if (this.canvasIns) {
                return this.canvasIns;
            } else {
                return this.canvasIns = Draw.ini('canvas')
            }
        }

        stopPlay()
        {
            this.confirmStop = 1;
            this.ctldo       = 1;
            this.currentTime = (this.audioContext.currentTime - 1) < 0 ? 0 : this.audioContext.currentTime - 1;
            if (this.audioBufferSourceNode) {
                this.audioBufferSourceNode.stop(this.audioContext.currentTime + .1);
            }

        }

        startPlay()
        {
            if (this.confirmStop) {
                this.ctldo       = 0;
                this.confirmStop = 0;
                this.getStart(this.audioBufferSourceNode.buffer, this.currentTime);
            } else {
                this.stopPlay();
            }

        }

        minusOrPlus(i, cb)
        {
            // return
            //i <  1? this.canvasCenterY :
            return cb(
                i % 2 === 0 ? 1 : 0
            )
        }


        widthExpand()
        {
            let minW            = 2;
            this.canvasCenterX  = this.canvasIns.width / 2;
            this.canvasCenterY  = this.canvasIns.height / 2;
            this.canvasX_maxNum = Math.floor(this.canvasIns.width / minW);
            this.canvasX_num    = Math.floor(this.canvasX_maxNum / 2);
            this.canvasX_exW    = Math.ceil(1024 / this.canvasX_num);
            this.canvasX_num    = Math.floor(1024 / this.canvasX_exW);
            this.canvasX_ex     = .5 * this.canvasIns.width / this.canvasX_num;
            this.canvasY_min    = this.canvasIns.height * .5
        }

        canvasImg(analyser, d)
        {
            let array = new Uint8Array(analyser.frequencyBinCount);
            analyser.getByteFrequencyData(array);
            let arr = [];
            let i;
            let ii  = 0;
            for (i = 0; i < 1024; i += this.canvasX_exW) {


                arr[ii + this.canvasX_num] = [
                    ii * this.canvasX_ex + this.canvasCenterX,
                    this.minusOrPlus(ii, (x) =>
                    {
                        return x ? this.canvasY_min - array[i] * .6 : this.canvasY_min + array[i] * .6;
                    })
                ];
                arr[this.canvasX_num - ii] = [
                    -arr[ii + this.canvasX_num][0] + 2 * this.canvasCenterX,
                    arr[ii + this.canvasX_num][1]
                ];
                ii++;
            }
            if (!arr[0]) {
                arr[0] = [0, this.canvasCenterY];
            }
//            log(arr);
//            return;

//            setTimeout(this.canvasImg(analyser), 1000/60);
            this.RAF = requestAnimationFrame(
                () =>
                {
                    this.canvasImg(analyser, d);
                    d.dataIn(arr);
                    d.loopAni();
                }
            );
        }
    }
    class Draw {
        constructor(tag)
        {
            this.container = document.getElementById(tag);
            let p          = this.container.parentNode;
            this.container.setAttribute('width', p.offsetWidth);
            this.container.setAttribute('height', p.offsetHeight);
            this.ctx        = this.container.getContext('2d');
            this.width      = this.container.width;
            this.height     = this.container.height;
            this.center     = {w: this.width / 2, h: this.height / 2};
            this.r          = (this.center.w >= this.center.h ? this.center.h : this.center.w) / 1.2;
            this.startAngle = 0;
            this.CAF        = '';
            this.ZHEN       = 0;
        }

        dataIn(x)
        {
            this.data = x
        }

        loopAni()
        {
            let t = this;
            let p = t.data;
            t.ctx.clearRect(0, 0, this.width, this.height);
            t.drawBezier(p);
//            t.CAF = requestAnimationFrame(() => t.loopAni());
        }

        static ini(x)
        {

            return new this(x);
        }

        drawCircle()
        {
            let ctx      = this.ctx;
            let gradient = ctx.createLinearGradient(0, 0, this.width, this.height);
            gradient.addColorStop("0", randColor());
            gradient.addColorStop("0.25", randColor());
            gradient.addColorStop("0.5", randColor());
            gradient.addColorStop("0.75", randColor());
            gradient.addColorStop("1", randColor());
            ctx.beginPath();
            ctx.rotate(Math.PI / 9000);
            ctx.arc(this.center.w, this.center.h, this.r, this.startAngle * 1.5, -this.startAngle, false);
            ctx.strokeStyle = gradient;
            ctx.stroke();
            this.startAngle += Math.PI / 60;
            this.r = this.r / 1.004;
        }

        getCtrlPoint(p1, p2, p3, p4)
        {
            //求p2至p3的控制点
            /*
             pi(xi,yi);i = [0,1,2....,n]
             pi->pi+1;
             控制点为a1,b1;
             a1(xi + a(xi+1 - xi-1), yi + a(yi+1 - yi-1))
             b1(xi+1 - a(xi+2 - xi), yi+1 - a(yi+2 - yi))
             */
            let a  = .25;
            let b  = .25;
            let a1 = [p2[0] + a * (p3[0] - p1[0]), p2[1] + a * (p3[1] - p1[1])];
            let b1 = [p3[0] - b * (p4[0] - p2[0]), p3[1] - b * (p4[1] - p2[1])];
            return [a1, b1];
        }

        drawPoint(x, y, color = 'black')
        {
            let t   = this;
            let ctx = t.ctx;
            ctx.save();
            ctx.arc(x, y, 1, 0, Math.PI * 2, false);
            ctx.strokeStyle = color;
            ctx.stroke();
            ctx.restore();
        }

        //绘制贝塞尔曲线
        drawBezier(point)
        {
            let t        = this;
            let ctx      = t.ctx;
            let l        = point.length - 1;
            let gradient = ctx.createLinearGradient(0, 0, this.width, this.height);
            gradient.addColorStop("0", "#f8ec3e");
            gradient.addColorStop("0.25", "#af1027");
            gradient.addColorStop("0.5", "#2480a5");
            gradient.addColorStop("0.75", "#b310bd");
            gradient.addColorStop("1", "black");
            if (l < 1) {
                alert('至少需要2个点绘制曲线');
                return false;
            }
            ctx.beginPath();

            ctx.moveTo(point[0][0], point[0][1]);
            for (let i = 0; i < l; i++) {
                let c = t.getCtrlPoint(
                    point[i - 1] ? point[i - 1] : point[i],
                    point[i],
                    point[i + 1],
                    point[i + 2] ? point[i + 2] : point[i + 1]
                );
                ctx.bezierCurveTo(c[0][0], c[0][1], c[1][0], c[1][1], point[i + 1][0], point[i + 1][1]);
            }
            ctx.strokeStyle = gradient;
            ctx.stroke();
        }
    }


    /*
     无法跨域时使用audio
     */
    let audioC    = audioCvs.ini();
    let audio     = $("#audio")[0];
    let canCross  = 1;//默认可跨域
    audio.loop    = false;
    audio.onended = () =>
    {
        getAnother();
    };

    /*
     1000歌单， 1单曲
     */
    let type   = 1000;
    let sl;
    let urlAdd = '';

    $("#type").change(function ()
    {
        if ($(this).val() == 1) {
            $('.song_list').hide();
        } else {
            if ($('#songListInfo').html != '') {
                $('.song_list').show();
            }

        }
    });


    function hideShowMask(h = 0)
    {
        let m = $('#mask');
        h ? m.hide(1000) : m.show(0);
    }

    function log(x)
    {
        console.log(x);
    }
    function down()
    {
        let src = audio.src;
        window.open(src);
    }
    function playOrPause($ctrl = 0)
    {
        log('stop:' + audioC.confirmStop);
        $('#mobile').hide(500);
        let viewDo = 'play';
        switch ($ctrl) {
            case 'stop':
//                audioC.stopPlay();
                canCross ? audioC.stopPlay() : audio.pause();
                viewDo = 'pause';
                break;
            case 'play':
//                audioC.startPlay();
                canCross ? audioC.startPlay() : audio.play();
                break;
            default:
                if (canCross) {
                    if (audioC.ctldo) {
                        audioC.startPlay();
//            audio.play();
                    } else {
                        audioC.stopPlay();
                        viewDo = 'pause';
                    }
                } else {
                    if (audio.paused) {
                        audio.play();
                    } else {
                        audio.pause();
                        viewDo = 'pause';
                    }
                }
        }
        if (viewDo === 'pause') {
            $('#playButton').find('span').attr("class", "glyphicon glyphicon-pause");
        } else {
            $('#playButton').find('span').attr("class", "glyphicon glyphicon-play");
        }


    }
    // function autoPlay(n = 0)
    // {
    //     if (n > 10) {
    //         //获取资源失败
    //         getAnother();
    //         return;
    //     }
    //     if (audio.readyState) {
    //         playOrPause(1);
    //     } else {
    //         n++;
    //         setTimeout(() =>
    //         {
    //             autoPlay(n);
    //         }, 500);
    //     }
    // }
    // function mplay()
    // {
    //     autoPlay();
    //     if (audio.readyState) {
    //         $('#mobile').hide(2000);
    //     }
    // }
    function getAnother(ini)
    {
        hideShowMask(0);
        urlAdd = '';
        let w  = $('#text').val();
        if (ini) {
            w = ini;
            $('#text').val(w);
        }
        type = $('#type').val();
        if (type == 1000) {
            let p = $("input[name='play']:checked").val();
            if (p == 0) {
                //继续播放歌单
                if (sl) {
                    let l  = sl.trackCount;
                    r      = Math.ceil(Math.random() * l);
                    let id = sl.tracks[r - 1].id;
                    if (id) {
                        urlAdd = '&song_id=' + id;
                    }
                }
            }
        }
        if (w) {
            $.ajax({
                    url:      '?ajax=1&word=' + w + '&doCache=' + GetQueryString('doCache') + '&type=' + type + urlAdd,
                    dataType: 'json',
                    type:     'POST',
                    success:  (x) =>
                              {
                                  if (x.r) {

                                      if (canCross && x.ex.cors) {
                                          audioC.loadSound(x.data);
                                          audio.src = x.data;
                                      } else {
                                          hideShowMask(1);
                                          canCross  = 0;
                                          audio.src = x.data;
                                          audio.play(0);
                                      }
                                      if (urlAdd) {
                                          log(sl);
                                          x.ex                  = sl.tracks[r - 1];
                                          x.ex.song_list        = {};
                                          x.ex.song_list.result = sl;
                                      }

                                      if (x.ex.name) {
                                          $('#songInfo').html(x.ex.name);
                                      }
                                      if (x.ex.song_list) {
                                          sl = x.ex.song_list.result;

                                          let ss = x.ex.song_list.result.name + '(共:' + x.ex.song_list.result.trackCount
                                              + ' 首)';
                                          $('#songListInfo').html(ss);
                                          $('.song_list').show();
                                      } else {
                                          $('#songListInfo').html('');
                                          $('.song_list').hide();
                                      }


                                  } else {
//                                      alert(x.data + ',点击确定后将自动重新搜索(可能歌曲已下架或需付费)');
                                      setTimeout(() =>
                                      {
                                          getAnother();
                                      }, 500)


//                                      alert(x.data)
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
            '燃曲'

        ];
        let l   = arr.length;
        let r   = Math.ceil(Math.random() * l);
        return arr[r - 1];
    }


    function GetQueryString(name)
    {
        let reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        let r   = window.location.search.substr(1).match(reg);
        if (r != null)return unescape(r[2]);
        return '';
    }


    $(() =>
    {
        let k   = GetQueryString('k');
        let str = '';
        if (k) {
            str = k;
        } else {
            str = randStr();
        }
        getAnother(str);
        if (typeof window.orientation != 'undefined') {
            setTimeout(() =>
            {
                if (audio.confirmStop) {
                    $('#mobile').show(500);
                }
            }, 1200)
        }
        // $('#playButton').click(() =>
        // {
        //     playOrPause();
        // });
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



