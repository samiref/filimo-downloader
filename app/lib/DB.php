<?php


class DB
{
    public static $db_file;
    private static $db_content;

    public static function Get($key)
    {
        self::OpenDB();

        if(isset(self::$db_content[$key]))
            return self::$db_content[$key];

        return false;
    }

    public static function Push($key, $value)
    {
        $item = self::Get($key);
        if(is_array($item))
            $item[] = $value;
        else
            $item = array($value);

        self::Set($key, $item);
    }

    public static function Cut($key)
    {
        $item = self::Get($key);
        self::Del($key);
        return $item;
    }

    public static function Append($key, $value)
    {
        $item = self::Get($key);
        if(is_string($item))
            $item .= $value;
        else
            $item = $value;

        self::Set($key, $item);
    }

    private static function OpenDB()
    {
        if(is_array(self::$db_content) == false)
        {
            if(!self::$db_file)
                throw new Exception("Db File Not Set");
            if(!file_exists(self::$db_file))
            {
                self::$db_content = array();
                self::Write();
            }

            $db_content = file_get_contents(self::$db_file);
            self::$db_content = unserialize($db_content);
        }
    }

    public static function Set($key, $value)
    {
        self::OpenDB();
        self::$db_content[$key] = $value;
        self::Write();
        return true;
    }

    public static function Del($key)
    {
        self::OpenDB();
        if(isset(self::$db_content[$key]))
        {
            unset(self::$db_content[$key]);
            self::Write();
        }
    }

    public static function Write()
    {
        file_put_contents(self::$db_file, serialize(self::$db_content));
    }
}