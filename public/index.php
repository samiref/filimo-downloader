<?php
include_once "../app/bootstrap.php";

/***
 *
 */
Route::add("/", function (){
    $theme['page_title'] = "Filimo Downloader";
    $theme['user'] = DB::Get("FILIMO_USER");
    $theme['action_add_new'] = Route::link("show_movie_info");
    $theme['table_content'] = "";

    //DB::Append("alerts", getAlert("ERROR", "For Test"));
    ProcessDownloadList($add_new = false);

    $download_queue = DB::Get("download_queue");

    if(is_array($download_queue))
    {
        foreach ($download_queue as $item)
        {
            $item_html = ThemeEngine::render("movie_list_item", $item, true);
            if($item['status'] == 'download')
            {
                $theme['table_content'] = $item_html . $theme['table_content'];
            }
            else
            {
                $theme['table_content'] .= $item_html;
            }
        }
    }

    $theme['alert'] = DB::Cut("alerts");
    ThemeEngine::render('index', $theme);
})->setName("index")->setBefore(new Auth());

/***
 *
 */
Route::post("/show/filimo", function(){

    $theme['page_title'] = "Filimo Downloader";
    $theme['user'] = DB::Get("FILIMO_USER");
    $theme['show_info'] = "";

    $filimo = Filimo::getDefault();

    if(isset($_POST['movie_url']))
    {
        if(empty($_POST['movie_url']))
        {
            $theme['alert'] = getAlert("Error", "Please input Movie Id or URL.");
        }
        else
        {
            if(strpos($_POST['movie_url'], "/m/"))
            {
                try
                {
                    $movie_list = $filimo->getMoviesList($_POST['movie_url']);
                    if(count($movie_list) == 0)
                    {
                        throw new Exception("Not Found any movie id");
                    }
                }
                catch (Exception $e)
                {
                    DB::Append("alerts" , getAlert("Error", $e->getMessage()));
                    Route::redirect("/");
                }

                $multi_form['items'] = "";
                $limit = 5;

                $is_ok = array();
                foreach ($movie_list as $movieId)
                {
                    if(isset($is_ok[$movieId]))
                        continue;

                    $is_ok[$movieId] = true;

                    //if(!$limit--)
                    //   break;

                    try
                    {
                        $videoInfo = $filimo->getVideoInfo($movieId);
                        $info['id'] = $videoInfo->getId();

                        $info['poster'] = $videoInfo->getCover();
                        $info['title'] = $videoInfo->getTitle();
                        $info['select'] = "";
                        foreach ($videoInfo->getQualities() as $key => $quality)
                            $info['select'] .= "<option value=\"{$key}\">{$quality['quality']}</option>\n";

                        $info['data'] = base64_encode(serialize($videoInfo));

                        $multi_form['items'] .= ThemeEngine::render("multi_form_item", $info, true);
                    }
                    catch (Exception $e)
                    {}
                }

                $multi_form['multi_form_action'] = Route::link("multi_add_new");
                $theme['show_info'] = ThemeEngine::render("multi_form", $multi_form, true);
            }
            else
            {
                try
                {

                    $videoInfo = $filimo->getVideoInfo($_POST['movie_url']);
                    $info['id'] = $videoInfo->getId();

                    $info['poster'] = $videoInfo->getCover();
                    $info['title'] = $videoInfo->getTitle();
                    $info['select'] = "";
                    foreach ($videoInfo->getQualities() as $key => $quality)
                        $info['select'] .= "<option value=\"{$key}\">{$quality['quality']}</option>\n";

                    $info['data'] = base64_encode(serialize($videoInfo));

                    $info['single_form_action'] = Route::link("add_new");
                    $theme['show_info'] = ThemeEngine::render("info_form", $info, true);
                } catch (Exception $e)
                {
                    DB::Append("alerts" , getAlert("Error", $e->getMessage()));
                    Route::redirect("/");
                }
            }
        }
    }

    $theme['alert'] = DB::Cut("alerts");
    ThemeEngine::render('add_new_movie', $theme);
})->setName("show_movie_info")->setBefore(new Auth());


/***
 *
 */
Route::post("/add/multi/filimo", function(){
    $count = 0;
    foreach ($_POST['movie'] as $movie_id => $val)
    {
        $data    = $_POST['data'][$movie_id];
        $name    = $_POST['name'][$movie_id];
        $quality = $_POST['quality'][$movie_id];

        add_movie_to_queue($data, $name, $quality);
        $count++;
    }

    DB::Append("alerts", getAlert("{$count} Movie added.","", "info"));
    Route::redirect("/");
})->setName("multi_add_new")->setBefore(new Auth());


/***
 *
 */
Route::post("/add/filimo", function(){
    if(isset($_POST['action']) && $_POST['action'] == 'download')
    {
        $data    = $_POST['data'];
        $name    = $_POST['name'];
        $quality = $_POST['quality'];

        add_movie_to_queue($data, $name, $quality);
    }

    DB::Append("alerts", getAlert("{$name} added.","", "info"));
    Route::redirect("/");

})->setName("add_new")->setBefore(new Auth());

/***
 *
 */
Route::get("/remove/{id}", function($id){
    $remove_id = $id;

    $download_queue = DB::Get("download_queue");

    if(is_array($download_queue))
    {
        foreach ($download_queue as $key => $item)
        {
            if($item['id'] === $remove_id)
            {
                unset($download_queue[$key]);
                break;
            }
        }
    }

    DB::Set("download_queue", $download_queue);

    Route::back();
})->setName("remove_item")->setBefore(new Auth());


/***
 *
 */
Route::get("/process-queue", function(){
    ProcessDownloadList(isset($_GET['add']));

    if(isset($_GET['echo']))
    {
        $theme['table_content'] = "";
        $download_queue = DB::Get("download_queue");

        if(is_array($download_queue))
        {
            foreach ($download_queue as $item)
            {
                $item_html = ThemeEngine::render("movie_list_item", $item, true);
                if($item['status'] == 'download')
                {
                    $theme['table_content'] = $item_html . $theme['table_content'];
                }
                else
                {
                    $theme['table_content'] .= $item_html;
                }
            }
        }

        echo $theme['table_content'];
    }
})->setBefore(new Auth());


/***
 *
 */
Route::get("/login", function (){
    $filimo = Filimo::getDefault();
    if($filimo->isLogin())
    {
        Route::redirect(Route::link("index"));
    }

    $theme["page_title"] = "Login";

    $theme["username"] = "";
    $theme["disable_username"] = "";
    $theme["code"] = "Password";
    $theme["guid"] = "";
    $theme["temp_id"] = "";
    $theme["login_form_action"] = Route::link("login");

    $theme["alert"] = DB::Cut("alerts");
    ThemeEngine::render('login', $theme);
})->setName("login");

/***
 *
 */
Route::post("/login", function (){
    $filimo = Filimo::getDefault();
    if($filimo->isLogin())
    {
        Route::redirect(Route::link("login"));
    }

    if($_POST['username'] == '')
    {
        DB::Append("alerts", getAlert("Error!", "Username is empty."));
        Route::redirect(Route::link("login"));
    }


    $theme["page_title"] = "Login";
    $theme["alert"] = "";
    $theme["username"] = "";
    $theme["disable_username"] = "";
    $theme["code"] = "Password";
    $theme["guid"] = "";
    $theme["temp_id"] = "";
    $theme["login_form_action"] = Route::link("login");

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
            Route::redirect(Route::link("index"));
        }
    }
    catch (OTPLoginException $e)
    {
        $theme["guid"] = $e->guid;
        $theme["temp_id"] = $e->temp_id;
        $theme["code"] = "OTP code";
        $theme["disable_username"] = " disabled ";
        DB::Append("alerts", getAlert('OTP Login',$e->getMessage(), 'info'));
    }
    catch (Exception $e)
    {
        $theme["alert"] = getAlert("Error!", $e->getMessage());
    }

    ThemeEngine::render('login', $theme);
})->setName("login_process");


/***
 *
 */
Route::get("/logout", function (){
    $filimo = Filimo::getDefault();
    $filimo->logout();
    Route::redirect(Route::link("login"));
})->setName("logout");

Route::exec();
die();


$theme['page_title'] = "Filimo Downloader";
$theme['user'] = DB::Get("FILIMO_USER");
$theme['alert'] = "";
$theme['movie_form'] = "";
$theme['show_info'] = "";

$show_movie_form = false;


if(isset($_POST['action']) && $_POST['action'] == 'download')
{
    $quality = $_POST['quality'];
    $data    = $_POST['data'];
    $name    = $_POST['name'];

    $videoInfo = unserialize(base64_decode($data));


    $videoInfo->setSelectedQuality($quality);

    if($name != $videoInfo->getId())
    {
        $videoInfo->setFileName($name);
    }

    $download_queue = DB::Get("download_queue");
    $download_queue[] =
                    array(
                        'type' => "FILIMO",
                        'id'   => $videoInfo->getId(),
                        'name' => $videoInfo->getFileName(),
                        'quality' => $quality,
                        'poster' => $videoInfo->getCover(),
                        'title'  => $videoInfo->getTitle(),
                        'progress'  => 0,
                        'status' => "new",
                    );
    DB::Set("download_queue", $download_queue);

    header("Location: /");
    die();
}
else
{
    $show_movie_form = true;
}


