<?php

namespace App\Constant;

enum OperationType: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
