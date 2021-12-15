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

}