<?php

namespace App\Application\Exceptions\Auth;

use Exception;

class RegistrationConflict extends Exception
{
    /** @var int */
    protected $code = 409;

    /** @var string */
    protected $message = "Пользователь с таким email уже зарегистрирован";
}
