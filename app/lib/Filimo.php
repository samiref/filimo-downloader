<?php
require_once "Curl.php";
require_once "OTPLoginException.php";
require_once "VideoDownloader.php";

class Filimo extends VideoDownloader
{
    private $isLogin = false;
    private $authData;

    private static $defaultUser;

    public static function getDefault()
    {
        if(self::$defaultUser == null)
        {
            self::$defaultUser = new Filimo();

            if (DB::Get("FILIMO_USER"))
            {
                self::$defaultUser->setUserName(DB::Get("FILIMO_USER"));
            }
        }

        return self::$defaultUser;
    }

    public function getAuthData()
    {
        return $this->authData;
    }

    public function isLogin()
    {
        if($this->isLogin == true)
        {
            return true;
        }

        $this->getCurl()->get("https://www.filimo.com/signin");

        $location = $this->getCurl()->getResponseHeader("location");

        if($location != null)
        {
            $this->isLogin = true;
            return true;
        }

        return false;
    }

    public function logout()
    {
        $res = $this->getCurl()->get("https://www.filimo.com/authentication/authentication/signout");
        if($res->getHttpStatus() == 302)
        {
            $this->isLogin = false;
            return true;
        }
        return false;
    }

    public function login()
    {
        if(empty($this->getUserName()))
            throw new Exception("UserName Is Empty.");

        $this->getCurl()->get("https://www.filimo.com/signin");

        $location = $this->getCurl()->getResponseHeader("location");

        if($location != null)
        {
            $this->isLogin = true;
            return true;
        }

        $body = $this->getCurl()->getResponse();

        $guid = $this->getMatch('/guid\: "([^"]+)"/i', $body, 1);

        if($guid == false)
            throw new Exception("Error in get GUID");


        $temp_id = $this->getLoginTempId($guid);

        if(empty($this->getPassword()))
        {
            $step_1 = $this->getLoginSendOTP($guid, $temp_id);
        }
        else
        {
            $step_1 = $this->getLoginStep1($guid, $temp_id);

            if (isset($step_1['data']["attributes"]["code_pass_type"]))
            {
                $loginType = $step_1['data']["attributes"]["code_pass_type"];
                $temp_id = $step_1['data']["attributes"]["temp_id"];

                if ($loginType == 'pass')
                {
                    $step_2 = $this->getLoginStep2($guid, $temp_id, $this->getPassword(), 'pass');
                    $this->authData = $step_2['data']["attributes"];
                    $this->isLogin = true;
                    return true;
                } else if ($loginType == 'otp')
                {
                    throw new OTPLoginException("Need OTP", $guid, $temp_id);
                }
            }
        }
        return false;
    }

    public function loginOTP($code, $guid, $temp_id)
    {
        $step_2 = $this->getLoginStep2($guid, $temp_id, $code, 'otp');
        $this->authData = $step_2['data']["attributes"];
        return true;
    }

    private function getLoginStep1($guid, $temp_id)
    {
        $res = $this->getCurl()->post("https://www.filimo.com/api/fa/v1/user/Authenticate/signin_step1",
            array(
                "account" => $this->getUserName(),
                "guid" => $guid,
                "temp_id" => $temp_id
            ),true);

        if($res->getHttpStatus() == 200)
        {
            $auth = json_decode($res->getResponse(), true);
            return $auth;
        }

        throw new Exception("Error in Login Step One");

        /*
         * With Pass
         {
            "data": {
                "attributes": {
                    "code_pass_type": "pass",
                    "id": 1,
                    "temp_id": "638562",
                    "type": "signIn"
                },
                "id": 1,
                "type": "authenticate"
            }
        }
         *
         * With OTP
         {
            "data": {
                "attributes": {
                    "code_length": 6,
                    "code_pass_type": "otp",
                    "id": 1,
                    "mobile_valid": "000000",
                    "notif_text": "کد فرستاده شده برای<span dir=\"auto\"> ( 00000 ) </span>را وارد کنید",
                    "temp_id": "100955",
                    "type": "signIn"
                },
                "id": 1,
                "type": "authenticate"
            }
        }
         */
    }private function getLoginSendOTP($guid, $temp_id)
    {
        $res = $this->getCurl()->post("https://www.filimo.com/api/fa/v1/user/Authenticate/signin_step1",
            array(
                "account" => $this->getUserName(),
                "codepass_type" => "otp",
                "guid" => $guid,
                "temp_id" => $temp_id
            ),true);


        if($res->getHttpStatus() == 200)
        {
            $auth = json_decode($res->getResponse(), true);

            if(isset($auth['data']["attributes"]["code_pass_type"]))
            {
                $loginType = $auth['data']["attributes"]["code_pass_type"];
                $temp_id = $auth['data']["attributes"]["temp_id"];
                $message = $auth['data']["attributes"]["notif_text"];

                if($loginType == 'otp')
                    throw new OTPLoginException( $message, $guid, $temp_id);
            }
        }

        throw new Exception("Error in Login Step One (OTP)");
    }

    private function getLoginStep2($guid, $temp_id,$code,$type)
    {
        $res = $this->getCurl()->post("https://www.filimo.com/api/fa/v1/user/Authenticate/signin_step2",
            array(
                "account" => $this->getUserName(),
                "code" => $code,
                "codepass_type" => $type,
                "guid" => $guid,
                "temp_id" => $temp_id
            ),true);

        if($res->getHttpStatus() == 200)
        {
            $auth2 = json_decode($res->getResponse(), true);
            return $auth2;
        }

        if($res->getHttpStatus() == 401)
        {
            $auth2 = json_decode($res->getResponse(), true);
            $error = $auth2['errors'][0]['type_info'];
            throw new Exception("Error in Login Step Two -> {$error}");
        }

        if($res->getHttpStatus() == 403)
        {
            $auth2 = json_decode($res->getResponse(), true);
            $error = $auth2['errors'][0]['detail'];
            // if need login with Password
            if($auth2['errors'][0]['type_info'] == 'force_mobile_signin')
            {
                $step_1 = $this->getLoginSendOTP($guid, $temp_id);
            }

            throw new Exception("Error in Login Step Two -> {$error}");
        }

        throw new Exception("Error in Login Step Two(With Password)");
    }

    private function getLoginTempId($guid)
    {
        $res = $this->getCurl()->post("https://www.filimo.com/api/fa/v1/user/Authenticate/auth",
            array('guid' => $guid),true);

        if($res->getHttpStatus() == 200)
        {
            $auth = json_decode($res->getResponse(), true);
            if(isset($auth['data']['attributes']['temp_id']))
                return $auth['data']['attributes']['temp_id'];
        }

        throw new Exception("Error in get TEMP_ID");
    }

    private function getMatch($pattern, $body, $group = 0)
    {
        preg_match($pattern, $body, $matches);

        return isset($matches[$group]) ? $matches[$group] : false;
    }

    public function getVideoInfo($videoId)
    {
        if($this->isLogin() == false)
            throw new Exception("Must be Login First.");

        $videoId = str_replace("/m/", "/w/" , $videoId);
        if(strpos($videoId, 'filimo.com') == false)
            $url = 'https://www.filimo.com/w/' . $videoId;
        else
            $url = $videoId;

        $res = $this->getCurl()->get($url);
        $pattern = "/var player_data \= ([^\n]+)/";

        $player_data = $this->getMatch($pattern, $res->getResponse(), 1);

        if($player_data == false)
            throw new Exception("Error In Get Player Data", 100);

        $player_data = json_decode(trim($player_data,';') , true);


        $player_data['subtitle'] = isset($player_data['tracks'][0]['src'])
                                ? $player_data['tracks'][0]['src']
                                : false;

        $src_m3u8 = $player_data["multiSRC"][0][0]["src"];
        if(isset($player_data["multiSRC"]))
        {
            foreach ($player_data["multiSRC"] as $multiS)
            {
                if ($multiS[0]['type'] == 'application/vnd.apple.mpegurl')
                {
                    $src_m3u8 = $multiS[0]['src'];
                    break;
                }
            }
        }

        if (!$src_m3u8)
            throw new Exception("Movie SRC not found");
        $player_data['src_m3u8'] = $src_m3u8;


        $res = $this->getCurl()->get($src_m3u8);
        if($res->getHttpStatus() != 200)
            throw new Exception("Error in get m3u8 file");

        $m3u8_content = $res->getResponse();
        preg_match_all('/#((?:[0-9])+(?:p|k)+)\n(?:.*)BANDWIDTH=(.*),RESOLUTION=(.*)\n(.*)/', $m3u8_content, $matches);

        $qualities = array();
        foreach ($matches[1] as $key => $value) {
            $qualities[] = array(
                'quality' => $matches[1][$key],
                'bandwidth' => $matches[2][$key],
                'resolution' => $matches[3][$key],
                'url' => $matches[4][$key],
            );
        }

        $qualities = $this->multiSort($qualities, 'bandwidth', SORT_DESC);
        $qualities = array_combine(range(1, count($qualities)), array_values($qualities));

        $videoData = new VideoInfo();
        $videoData->setId($player_data["uuid"])
            ->setFileName($player_data["uuid"])
            ->setTitle($player_data["info"]["text"])
            ->setCover($player_data["poster"])
            ->setSubtitle($player_data['subtitle'])
            ->setQualities($qualities)
            ->setSelectedQuality(1);

        return $videoData;
    }

    public function getMoviesList($movie_url)
    {
        if($this->isLogin() == false)
            throw new Exception("Must be Login First.");

        $res = $this->getCurl()->get($movie_url);
        if($res->getHttpStatus() != 200)
            throw new Exception("Error in Open {$movie_url}");

        preg_match_all("#filimo.com/w/([a-zA-Z0-9]+)#", $res->getResponse(), $matches);
        return $matches[1];
    }
}