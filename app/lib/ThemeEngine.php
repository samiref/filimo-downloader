<?php

class ThemeEngine
{
    public static $theme_dir;
    public static function render($file, $variables, $return = false)
    {
        if (substr($file, -5) !== '.html')
            $file = $file . ".html";


        if (file_exists(self::$theme_dir . $file))
        {
            $content = file_get_contents(self::$theme_dir . $file);

            $theme_body = preg_replace_callback("/\{\{(.*?)\}\}/i", function($matches) use ($variables){
                $key = trim($matches[1]);
                if(isset($variables[$key]) == false)
                {
                    throw new Exception("Template Render Error: \${$key} not passed.");
                }

                return $variables[$key];
            }, $content);

            if ($return)
                return $theme_body;

            echo $theme_body;
            return true;
        }

        throw new Exception("File {$file} not Exists.");
    }
}