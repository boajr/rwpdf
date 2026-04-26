<?php

namespace Boajr\PDF\Parser;


class PDFObjectNumber implements IPDFObject
{
    use LinkedObject;

    /**
     * @var int|float $value;
     */
    public $value;

    public function __construct(int|float $num)
    {
        $this->value = $num;
    }

    public function GetType(): int
    {
        return is_float($this->value) ? self::TYPE_FLOAT : self::TYPE_INT;
    }

    public function GetFinalType(): int
    {
        return is_float($this->value) ? self::TYPE_FLOAT : self::TYPE_INT;
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
