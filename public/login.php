<?php
include_once "../app/bootstrap.php";

$filimo = new Filimo();

if(DB::Get("FILIMO_USER"))
{
    $filimo->setUserName(DB::Get("FILIMO_USER"));

    if(isset($_GET['logout']))
    {
        if($filimo->logout())
        {
            header("Location: /login.php");
            die();
        }
    }


    if($filimo->isLogin())
    {
        header("Location: /");
        die();
    }
}


$theme["page_title"] = "Login";
$theme["alert"] = "";
$theme["username"] = "";
$theme["disable_username"] = "";
$theme["code"] = "Password";
$theme["guid"] = "";
$theme["temp_id"] = "";

if(isset($_POST['username']) && isset($_POST['code']))
{
    if($_POST['username'] == '')
    {
        $theme["alert"] = getAlert("Error!", "Username or Code is empty.");
    }
    else
    {
        $username = strip_tags($_POST['username']);
        $code = strip_tags($_POST['code']);
        $theme["username"] = $username;

        try
        {
            $filimo->setUserName($username)->setPassword($code);
            if(isset($_POST['guid']) && !empty($_POST['guid']))
            {
                $guid = strip_tags($_POST['guid']);
                $temp_id = strip_tags($_POST['temp_id']);
                $theme["guid"] = $guid;
                $theme["temp_id"] = $temp_id;

                $filimo->loginOTP($code, $guid,$temp_id);
            }
            else
            {
                $filimo->login();
            }
            if($filimo->isLogin())
            {
                DB::Set("FILIMO_USER", $username);
                DB::Set("FILIMO_AUTH_DATA", $filimo->getAuthData());
                header("Location: /");
                die();
            }
        }
        catch (OTPLoginException $e)
        {
            $theme["guid"] = $e->guid;
            $theme["temp_id"] = $e->temp_id;
            $theme["code"] = "OTP code";
            $theme["disable_username"] = " disabled ";
            $theme["alert"] = getAlert('OTP Login',$e->getMessage(), 'info');
        }
        catch (Exception $e)
        {
            $theme["alert"] = getAlert("Error!", $e->getMessage());
        }
    }
}


ThemeEngine::render('login', $theme);