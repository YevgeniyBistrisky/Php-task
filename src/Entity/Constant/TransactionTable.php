<?php

namespace App\Entity\Constant;

class TransactionTable
{
    public const TABLE_NAME = 'transactions';

    public const COLUMN_ID = 'id';
    public const COLUMN_AMOUNT = 'amount';
    public const COLUMN_EXTERNAL_ID = 'external_id';
    public const COLUMN_WALLET_ID = 'wallet_id';
    public const COLUMN_OPERATION_TYPE = 'operation_type';
    public const COLUMN_CREATED_AT = 'created_at';
    public const COLUMN_UPDATED_AT = 'updated_at';
}
