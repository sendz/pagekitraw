<?php

namespace Pagekit\Component\Auth;

interface UserInterface
{
    /**
     * Retrieves the unique indentifier
     *
     * @return string Id
     */
    public function getId();

    /**
     * Retrieves the username
     *
     * @return string Username
     */
    public function getUsername();

    /**
     * Retrieves the password
     *
     * @return string Password
     */
    public function getPassword();
}
