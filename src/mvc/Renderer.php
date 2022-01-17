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
    const SHOW_FOR_MENU = 2;
    const CREATE = 3;
    const EDIT = 4;
    const EDIT_ADD_ITEM = 22;
    const POT_CREATE = 23;
    const POT_PARTICIPATE = 24;
    const RESERVATION_FORM = 25;
    const REQUEST_AUTH = 26;
    const PREVENT_DELETE = 41;
    const DELETE = 42;
    const LOGIN = 71;
    const LOGIN_2FA = 72;
    const REGISTER = 73;
    const PROFILE = 74;
    const ENABLE_2FA = 81;
    const MANAGE_2FA = 82;
    const SHOW_2FA_CODES = 83;
    const RECOVER_2FA = 84;
    const LOST_PASSWORD = 85;
    const RESET_PASSWORD = 86;
    const DELETE_ACCOUNT_CONFIRM = 87;

    const OTHER_MODE = 10;
    const OWNER_MODE = 100;
    const ADMIN_MODE = 1000;
}