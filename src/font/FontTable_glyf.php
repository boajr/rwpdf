<?php

namespace Boajr\PDF\font;

use stdClass;

class FontTable_glyf extends FontTable
{
    private FontTable_loca $loca;

    private array $glyphsHeader = [];
    //#[FontPropertyType(FontPropertyType::uint16)] public ?int $version = null;
    //#[FontPropertyType(FontPropertyType::uint16)] public ?int $numTables = null;
    //public array $encodingRecords = [];

    //#[FontPropertyType(FontPropertyType::uint16)]         public ?int $numGlyphs = null;
    //#[FontPropertyType(FontPropertyType::uint16)]         public ?int $glyphNameIndex[numGlyphs] = null;
    //#[FontPropertyType(FontPropertyType::uint8)]          public ?int $stringData[variable] = null;

    //#[FontPropertyType(FontPropertyType::uint16)]         public ?int $numGlyphs = null;
    //#[FontPropertyType(FontPropertyType::int8)]           public ?int $offset[numGlyphs] = null;

    //private ?array $unicodeCharMap = null;

    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);
        $loca = $font->readTable('loca');
        if (!$loca)
            throw new FontException('missing \'loca\' table');
        $this->loca = $loca;
    }

    public function getGlyphHeader(int $glyphId): ?stdClass
    {
        if (!array_key_exists($glyphId, $this->glyphsHeader)) {
            $off = $this->loca->getGlyphOffset($glyphId);
            if ($off) {
                $this->seek($off);
                $v = unpack('n5', $this->read(10));

                $head = new stdClass;
                $head->numberOfContours = $v[1] >= 0x8000 ? $v[1] - 0x10000 : $v[1];
                $head->xMin = $v[2] >= 0x8000 ? $v[2] - 0x10000 : $v[2];
                $head->yMin = $v[3] >= 0x8000 ? $v[3] - 0x10000 : $v[3];
                $head->xMax = $v[4] >= 0x8000 ? $v[4] - 0x10000 : $v[4];
                $head->yMax = $v[5] >= 0x8000 ? $v[5] - 0x10000 : $v[5];

                $this->glyphsHeader[$glyphId] = $head;
            } else
                $this->glyphsHeader[$glyphId] = null;
        }

        return $this->glyphsHeader[$glyphId];
    }
}
