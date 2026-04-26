<?php

namespace Boajr\PDF\Parser;


class PDFObjectBool implements IPDFObject
{
    use LinkedObject;

    /**
     * @var bool $value;
     */
    public $value;

    public function __construct(bool $bool)
    {
        $this->value = $bool;
    }

    public function GetType(): int
    {
        return self::TYPE_BOOL;
    }

    public function GetFinalType(): int
    {
        return self::TYPE_BOOL;
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
