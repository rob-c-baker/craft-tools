<?php

namespace alanrogers\tools\services\es;

enum IndexType: string
{
    case SECTION = 'section';
    case SAYT = 'sayt';
    case ALL = 'all';
}