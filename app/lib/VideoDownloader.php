<?php
include_once "VideoInfo.php";
include_once "State.php";

abstract class VideoDownloader
{
    public static $temp_dir;
    public static $download_dir;
    private $userName;
    private $password;
    private $fullName;
    private $cookieFilePath;
    private $moviesPath;
    private $proxy;
    private $baseUrl;
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36';
    private $defaultCookie;

    private $lastRequestHeaders;
    private $lastResponseHeaders;
    private $lastResponseBody;

    private $curl;


    public abstract function login();
    public abstract function isLogin();

    /***
     * @param $videoId
     * @return VideoInfo
     */
    public abstract function getVideoInfo($videoId);

    /**
     * @return self
     */
    protected function initCurl()
    {
        $this->curl = new Curl($this->getCookieFilePath() . DIRECTORY_SEPARATOR . $this->getCookieFileName());
        $this->curl->setUserAgent($this->getUserAgent());
        return $this;
    }

    /**
     * @return Curl
     */
    protected function getCurl()
    {
        if(!$this->curl)
        {
            $this->initCurl();
        }

        return $this->curl;
    }

    /**
     * @param mixed $password
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @param mixed $userName
     * @return self
     */
    public function setUserName($userName)
    {
        $username = str_replace(array("/","\\") , array("",""), $userName);
        $this->userName = $userName;
        return $this;
    }

    /**
     * @param mixed $cookieFilePath
     * @return self
     */
    public function setCookieFilePath($cookieFilePath)
    {
        $this->cookieFilePath = $cookieFilePath;
        if(file_exists($this->getCookieFilePath()) == false)
            mkdir($this->getCookieFilePath(), 0777, true);

        return $this;
    }

    /**
     * @param mixed $moviesPath
     * @return self
     */
    public function setMoviesPath($moviesPath)
    {
        $this->moviesPath = $moviesPath;

        if(file_exists($this->moviesPath) == false)
            mkdir($this->moviesPath, 0777, true);
        return $this;
    }

    /**
     * @param mixed $baseUrl
     * @return self
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }


    /**
     * @param mixed $defaultCookie
     * @return self
     */
    public function setDefaultCookie($defaultCookie)
    {
        $this->defaultCookie = $defaultCookie;
        return $this;
    }

    /**
     * @param mixed $fullName
     * @return self
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
        return $this;
    }

    /**
     * @param mixed $proxy
     * @return self
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * @param string $userAgent
     * @return self
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
        return $this;
    }



    /**
     * @return mixed
     *
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return mixed
     */
    public function getCookieFilePath()
    {
        if($this->cookieFilePath == false)
        {
            if(self::$temp_dir != false)
                $this->cookieFilePath = rtrim(self::$temp_dir, '\\/') . DIRECTORY_SEPARATOR;
            else
                $this->cookieFilePath = __DIR__ . DIRECTORY_SEPARATOR;
        }
        return $this->cookieFilePath;
    }


    /**
     * @return mixed
     */
    public function getMoviesPath()
    {
        if($this->moviesPath == false)
            $this->setMoviesPath(self::$download_dir);
        return $this->moviesPath;
    }

    /**
     * @return mixed
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @return mixed
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @return mixed
     */
    public function getDefaultCookie()
    {
        return $this->defaultCookie;
    }


    /**
     * @return mixed
     */
    public function getLastRequestHeaders()
    {
        return $this->lastRequestHeaders;
    }

    /**
     * @return mixed
     */
    public function getLastResponseHeaders()
    {
        return $this->lastResponseHeaders;
    }

    /**
     * @return mixed
     */
    public function getLastResponseBody()
    {
        return $this->lastResponseBody;
    }

    function multiSort($mdarray, $mdkey, $sort = SORT_ASC)
    {
        foreach ($mdarray as $key => $row) {
            // replace 0 with the field's index/key
            $dates[$key] = $row[$mdkey];
        }
        array_multisort($dates, $sort, $mdarray);
        return $mdarray;
    }

    public function getFFmpegCommand(VideoInfo $videoInfo)
    {
        $resolutionID = $videoInfo->getSelectedQuality();
        if($resolutionID == false)
            throw new Exception("Not Selected resulotion id in VideoInfo.");

        $qualities = $videoInfo->getQualities();

        $video_file = $this->getMoviesPath() . DIRECTORY_SEPARATOR . $videoInfo->getFileName() . '.mp4';

        $cmd_proxy = '';
        if ($this->getProxy())
        {
            $cmd_proxy = '-http_proxy http://' . $this->getProxy();
        }

        $ffmpegUserAgentCommand = '';
        if($this->getUserAgent())
        {
            $ffmpegUserAgentCommand = ' -user_agent "' . $this->getUserAgent() . '" ';
        }

        $video_m3u = $qualities[$resolutionID]['url'];

        $command = 'ffmpeg ' . $ffmpegUserAgentCommand . ' ' . $cmd_proxy . ' -i "' . $video_m3u . '" -c copy -y "' . $video_file . '"';

        return $command;
    }

    public function downloadCover(VideoInfo $videoInfo)
    {
        $cover_file = $this->getMoviesPath() . DIRECTORY_SEPARATOR . $videoInfo->getFileName() . '.jpg';
        if ($videoInfo->getCover())
        {
            $res = $this->getCurl()->get($videoInfo->getCover());
            file_put_contents($cover_file, $res->getResponse());
        }
    }

    public function downloadSubtitle(VideoInfo $videoInfo)
    {
        $subtitle_file = $this->getMoviesPath() . DIRECTORY_SEPARATOR . $videoInfo->getFileName() . '.srt';
        if ($videoInfo->getSubtitle())
        {
            $res = $this->getCurl()->get($videoInfo->getSubtitle());
            file_put_contents($subtitle_file, $res->getResponse());
        }
    }

    public function writeInfo(VideoInfo $videoInfo)
    {
        $info_file = $this->getMoviesPath() . DIRECTORY_SEPARATOR . $videoInfo->getFileName() . '.info';
        file_put_contents($info_file, serialize($videoInfo));
    }

    public function execConsoleCommand($ffmpegCommand, VideoInfo $videoInfo)
    {
        $log_file = $this->getMoviesPath() . DIRECTORY_SEPARATOR . $videoInfo->getFileName() . '.log';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
        {
            $command = 'start /B ' . $ffmpegCommand . '<nul >nul 2>"' . $log_file . '"';

            pclose(popen($command, 'r'));
        }
        else
        {
            $command = $ffmpegCommand . ' </dev/null >/dev/null 2>"' . $log_file . '" &';
            shell_exec($command);
        }

        return $log_file;
    }

    public function getState(VideoInfo $videoInfo)
    {
        $filename = $videoInfo->getFileName();
        $dirname = $this->getMoviesPath();

        $filename_log = $dirname . DIRECTORY_SEPARATOR . $filename . '.log';
        $filename_video = $dirname . DIRECTORY_SEPARATOR . $filename . '.mp4';

        $modified_date = date('Y-m-d H:i:s', filemtime($filename_log));
        if (is_file($filename_video)) {
            $filesize = filesize($filename_video);
        } else {
            $filesize = 0;
        }


        $state = $this->getStateFromLog($filename_log);

        $state->setDownloadedSize($filesize);
        $state->setLastModified($modified_date);

        return $state;

    }

    public function getStateFromLog($log_file)
    {
        $content = @file_get_contents($log_file);

        //get duration of source
        preg_match("/Duration: (.*?), start:/", $content, $matches);

        $rawDuration = isset($matches[1]) ? $matches[1] : "00:00:00.00";

        //rawDuration is in 00:00:00.00 format. This converts it to seconds.
        $ar = array_reverse(explode(":", $rawDuration));
        $duration = floatval($ar[0]);
        if (!empty($ar[1])) $duration += intval($ar[1]) * 60;
        if (!empty($ar[2])) $duration += intval($ar[2]) * 60 * 60;

        //get the time in the file that is already encoded
        preg_match_all("/time=(.*?) bitrate/", $content, $matches);

        $rawTime = array_pop($matches);

        //this is needed if there is more than one match
        if (is_array($rawTime)) {
            $rawTime = array_pop($rawTime);
        }

        //rawTime is in 00:00:00.00 format. This converts it to seconds.
        $ar = array_reverse(explode(":", $rawTime));
        $time = floatval($ar[0]);
        if (!empty($ar[1])) $time += intval($ar[1]) * 60;
        if (!empty($ar[2])) $time += intval($ar[2]) * 60 * 60;

        //calculate the progress
        if($duration == 0)
            $progress = 0;
        else
            $progress = round(($time / $duration) * 100, 2);


        preg_match_all("/ speed=(.*?)x/", $content, $matches);
        $last_speed = array_pop($matches);
        $last_speed = array_pop($last_speed);

        $state = new State();
        $state->setDuration($rawDuration);
        $state->setTime($rawTime);
        $state->setProgress($progress);
        $state->setSpeed($last_speed);

        return $state;
    }

    private function getCookieFileName()
    {
        return get_class($this) . '_' . $this->userName . '_' . 'cookie.txt';
    }

}