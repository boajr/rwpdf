<?php

namespace Boajr\PDF\Parser;


class PDFObjectNull implements IPDFObject
{
    use LinkedObject;

    public function __construct() {}

    public function GetType(): int
    {
        return self::TYPE_NULL;
    }

    public function GetFinalType(): int
    {
        return self::TYPE_NULL;
    }

    public function GetValue(): mixed
    {
        return null;
    }

    public function GetFinalValue(): mixed
    {
        return null;
    }

    public function GetReferencedObject(): IPDFObject
    {
        return $this;
    }
}
