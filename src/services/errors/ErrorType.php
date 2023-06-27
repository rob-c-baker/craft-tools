<?php

namespace alanrogers\tools\services\errors;

enum ErrorType : string
{
    case BACKEND = 'BACKEND';
    case FRONTEND = 'FRONTEND';
}