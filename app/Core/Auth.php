<?php


namespace App\Core;


trait Auth
{
    public function checkAuth()
    {
        return isset($_SESSION['username']);
    }
    public function signIn(string $username)
    {
        $_SESSION['username'] = $username;
    }
    public function signOut()
    {
        unset($_SESSION['username']) ;
    }
}