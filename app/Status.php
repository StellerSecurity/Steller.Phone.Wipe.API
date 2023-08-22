<?php

namespace App;

enum Status: int
{
    case ACTIVE = 1;

    case WIPED = 2;

    case WIPING = 3;

}
