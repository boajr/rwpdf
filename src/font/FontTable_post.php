<?php

namespace Boajr\PDF\font;


class FontTable_post extends FontTable
{
    #[FontPropertyType(FontPropertyType::Version16Dot16)] public ?float $version = null;
    #[FontPropertyType(FontPropertyType::Fixed)]          public ?float $italicAngle = null;
    #[FontPropertyType(FontPropertyType::FWORD)]          public ?int $underlinePosition = null;
    #[FontPropertyType(FontPropertyType::FWORD)]          public ?int $underlineThickness = null;
    #[FontPropertyType(FontPropertyType::uint32)]         public ?int $isFixedPitch = null;
    #[FontPropertyType(FontPropertyType::uint32)]         public ?int $minMemType42 = null;
    #[FontPropertyType(FontPropertyType::uint32)]         public ?int $maxMemType42 = null;
    #[FontPropertyType(FontPropertyType::uint32)]         public ?int $minMemType1 = null;
    #[FontPropertyType(FontPropertyType::uint32)]         public ?int $maxMemType1 = null;

    //#[FontPropertyType(FontPropertyType::uint16)]         public ?int $numGlyphs = null;
    //#[FontPropertyType(FontPropertyType::uint16)]         public ?int $glyphNameIndex[numGlyphs] = null;
    //#[FontPropertyType(FontPropertyType::uint8)]          public ?int $stringData[variable] = null;

    //#[FontPropertyType(FontPropertyType::uint16)]         public ?int $numGlyphs = null;
    //#[FontPropertyType(FontPropertyType::int8)]           public ?int $offset[numGlyphs] = null;

    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);

        $this->seek(0);
        $this->parseProperty('version');
        $this->parseProperty('italicAngle');
        $this->parseProperty('underlinePosition');
        $this->parseProperty('underlineThickness');
        $this->parseProperty('isFixedPitch');
        $this->parseProperty('minMemType42');
        $this->parseProperty('maxMemType42');
        $this->parseProperty('minMemType1');
        $this->parseProperty('maxMemType1');

        switch ($this->version) {
            case 2:
                break;

            case 2.5:
                break;
        }
    }
}
