<?php

namespace mywishlist\mvc;

/**
 * Class Renderer
 * Abstract class for rendering views with constants
 * @absract
 * @author Guillaume ARNOUX
 * @package mywishlist\mvc
 */
abstract class Renderer
{

    const SHOW = 0;
    const SHOW_FOR_ITEM = 1;
    const CREATE = 2;
    const EDIT = 3;
    const EDIT_ADD_ITEM = 22;
    const POT_CREATE = 23;
    const POT_PARTICIPATE = 24;
    const REQUEST_AUTH = 4;
    const PREVENT_DELETE = 41;
    const DELETE = 42;
    const LOGIN = 71;
    const LOGIN_2FA = 71_1;
    const REGISTER = 72;
    const PROFILE = 73;
    const ENABLE_2FA = 81;
    const MANAGE_2FA = 82;
    const SHOW_2FA_CODES = 83;
    const RECOVER_2FA = 84;
    const LOST_PASSWORD = 85;
    const RESET_PASSWORD = 86;

    const OTHER_MODE = 10;
    const OWNER_MODE = 100;
    const ADMIN_MODE = 1000;
}