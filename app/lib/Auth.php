<?php

class Auth
{
    public function __invoke()
    {
        $this->check();
    }

    public function check()
    {
        $filimo = Filimo::getDefault();

        if ($filimo->isLogin() == false)
        {
            Route::redirect(Route::link("login"));
        }
    }
}