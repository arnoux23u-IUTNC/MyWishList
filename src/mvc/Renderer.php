<?php

namespace mywishlist\mvc;

abstract class Renderer{

    const SHOW = 0;
    const CREATE = 1;
    const EDIT = 2;
    const EDIT_ADD_ITEM = 22;
    const REQUEST_AUTH = 3;
    const PREVENT_DELETE = 41;
    const DELETE = 42;
    const LOGIN = 71;
    const LOGIN_2FA = 71_1;
    const REGISTER = 72;
    const PROFILE = 73;
    const ENABLE_2FA = 81;
    const MANAGE_2FA = 82;
    const SHOW_2FA_CODES = 83;
}