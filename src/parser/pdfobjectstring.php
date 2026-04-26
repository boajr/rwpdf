<?php

namespace Boajr\PDF\Parser;


class PDFObjectString implements IPDFObject
{
    use LinkedObject;

    /**
     * @var string $value;
     */
    public $value;

    public function __construct(string $str)
    {
        $this->value = $str;
    }

    public function GetType(): int
    {
        return self::TYPE_STRING;
    }

    public function GetFinalType(): int
    {
        return self::TYPE_STRING;
    }

    public function GetValue(): mixed
    {
        return $this->value;
    }

    public function GetFinalValue(): mixed
    {
        return $this->value;
    }

    public function GetReferencedObject(): IPDFObject
    {
        return $this;
    }
}
