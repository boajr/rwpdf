<?php

namespace Boajr\PDF\font;

use Attribute;


#[Attribute(Attribute::TARGET_PROPERTY)]
class FontPropertyType
{
    public const uint8          = 1;
    public const int8           = 2;
    public const uint16         = 3;
    public const int16          = 4;
    public const uint24         = 5;
    public const uint32         = 6;
    public const int32          = 7;
    public const Fixed          = 8;
    public const FWORD          = 4;
    public const UFWORD         = 3;
    public const F2DOT14        = 9;
    public const LONGDATETIME   = 10;
    public const Tag            = 11;
    public const Offset8        = 1;
    public const Offset16       = 3;
    public const Offset24       = 5;
    public const Offset32       = 6;
    public const Version16Dot16 = 12;


    public function __construct(private int $type, private int $repeat = 0)
    {
        if ($this->type < 1 || $this->type > 12)
            throw new FontException('Unknown property type');
    }

    private function getSingleSize(): int
    {
        switch ($this->type) {
            case 1: // uint8 Offset8
            case 2: // int8
                return 1;

            case 3: // uint16 UFWORD Offset16
            case 4: // int16 FWORD
            case 9: // F2DOT14
                return 2;

            case 5: // uint24 Offset24
                return 3;

            case 10: // LONGDATETIME
                return 8;
        }

        //case 6: // uint32 Offset32
        //case 7: // int32
        //case 8: // Fixed
        //case 11: // Tag
        //case 12: // Version16Dot16
        return 4;
    }

    public function getSize(): int
    {
        return ($this->repeat > 0 ? $this->repeat : 1) * $this->getSingleSize();
    }

    public function unpack(string $buf): mixed
    {
        $size = $this->getSize();
        if ($size != strlen($buf))
            throw new FontException('Packed value has an unexpected size');

        $singleSize = $this->getSingleSize();
        if ($this->repeat > 0) {
            $ret = [];
            for ($i = 0; $i < $size; $i += $singleSize)
                $ret[] = $this->unpackSingle(substr($buf, $i, $singleSize), $singleSize);

            return $ret;
        }
        return $this->unpackSingle($buf, $singleSize);
    }

    private function unpackSingle(string $buf, int $size): mixed
    {
        switch ($this->type) {
            case 1: // uint8 Offset8
                return ord($buf[0]);

            //case 2: // int8
            //    $v = ord($buf[0]);
            //    return $v >= 0x80 ? $v - 0x100 : $v;

            case 3: // uint16 UFWORD Offset16
                return (ord($buf[0]) << 8) + ord($buf[1]);

            case 4: // int16 FWORD
                $v = (ord($buf[0]) << 8) + ord($buf[1]);
                return $v >= 0x8000 ? $v - 0x10000 : $v;

            //case 5: // uint24 Offset24
            //    return (ord($buf[0]) << 16) + (ord($buf[1]) << 8) + ord($buf[2]);

            case 6: // uint32 Offset32
                return (ord($buf[0]) << 24) + (ord($buf[1]) << 16) + (ord($buf[2]) << 8) + ord($buf[3]);

            //case 7: // int32
            //    $v = (ord($buf[0]) << 24) + (ord($buf[1]) << 16) + (ord($buf[2]) << 8) + ord($buf[3]);
            //    return $v >= 0x80000000 ? $v - 0x100000000 : $v;

            case 8: // Fixed
            case 12: // Version16Dot16
                $int = (ord($buf[0]) << 8) + ord($buf[1]);
                if ($int >= 0x8000)
                    $int -= 0x8000;
                $dec = (ord($buf[2]) << 8) + ord($buf[3]);
                return round($int + $dec / 0x10000, 4);

            //case 9: // F2DOT14

            case 10: // LONGDATETIME: nei sistemi a 32 bit funziona fino al 19 gennaio 1938
                return @(ord($buf[0]) << 56) + @(ord($buf[1]) << 48) + @(ord($buf[2]) << 40) + @(ord($buf[3]) << 32)
                    + (ord($buf[4]) << 24) + (ord($buf[5]) << 16) + (ord($buf[6]) << 8) + ord($buf[7]) - 2082844800;

            case 11: // Tag
                return $buf;

            default:
                throw new FontException('Non ancora implementato ' . $this->type);
        }
    }
}
