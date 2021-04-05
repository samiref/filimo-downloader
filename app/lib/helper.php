<?php

function getAlert($title, $message, $status = '')
{
    return '<div class="alert '.$status.'">
                <span class="closebtn" onclick="this.parentElement.style.display=\'none\';">&times;</span>
                <strong>'.$title.'</strong> '.$message.'
            </div>';
}

function ProcessDownloadList($add_new = true)
{
    $download_queue = DB::Get("download_queue");
    $new_key = false;
    $in_process_key = false;
    if(is_array($download_queue))
    {
        foreach ($download_queue as $key => $item)
        {
            if($new_key === false && $item['status'] == 'new')
                $new_key = $key;

            if($in_process_key == false && $item['status'] == 'download')
            {
                if($item['progress'] >= 100)
                {
                    $download_queue[$key]['status'] = "finished";
                    unlink($download_queue[$key]['log_file']);
                }
                else
                {
                    $in_process_key = $key;
                }
            }
        }
    }

    if($in_process_key !== false)
    {
        $video = &$download_queue[$in_process_key];
        $log_file = $video['log_file'];

        $filimo = new Filimo();
        $state = $filimo->getStateFromLog($log_file);

        $video['progress'] = $state->getProgress();
        $video['duration'] = $state->getDuration();
    }
    else if($add_new && $new_key !== false)
    {
        $video = &$download_queue[$new_key];
        $filimo = new Filimo();

        if (DB::Get("FILIMO_USER"))
        {
            $filimo->setUserName(DB::Get("FILIMO_USER"));
            if($filimo->isLogin())
            {
                $videoInfo = $filimo->getVideoInfo($video['id']);
                $videoInfo->setSelectedQuality($video['quality']);
                $videoInfo->setFileName($video['name']);

                $filimo->downloadCover($videoInfo);
                $filimo->downloadSubtitle($videoInfo);
                $command = $filimo->getFFmpegCommand($videoInfo);
                $log_file = $filimo->execConsoleCommand($command, $videoInfo);

                $video['log_file'] = $log_file;
                $video['status'] = 'download';
            }
        }
    }

    DB::Set("download_queue", $download_queue);

}

function add_movie_to_queue($data, $name, $quality){
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
}