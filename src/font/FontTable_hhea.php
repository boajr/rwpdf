<?php

namespace Boajr\PDF\font;


class FontTable_hhea extends FontTable
{
    #[FontPropertyType(FontPropertyType::uint16)]       public ?int $majorVersion = null;
    #[FontPropertyType(FontPropertyType::uint16)]       public ?int $minorVersion = null;
    #[FontPropertyType(FontPropertyType::FWORD)]        public ?int $ascender = null;
    #[FontPropertyType(FontPropertyType::FWORD)]        public ?int $descender = null;
    #[FontPropertyType(FontPropertyType::FWORD)]        public ?int $lineGap = null;
    #[FontPropertyType(FontPropertyType::UFWORD)]       public ?int $advanceWidthMax = null;
    #[FontPropertyType(FontPropertyType::FWORD)]        public ?int $minLeftSideBearing = null;
    #[FontPropertyType(FontPropertyType::FWORD)]        public ?int $minRightSideBearing = null;
    #[FontPropertyType(FontPropertyType::FWORD)]        public ?int $xMaxExtent = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $caretSlopeRise = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $caretSlopeRun = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $caretOffset = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $reserved1 = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $reserved2 = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $reserved3 = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $reserved4 = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $metricDataFormat = null;
    #[FontPropertyType(FontPropertyType::uint16)]       public ?int $numberOfHMetrics = null;


    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);
        $this->parse();
    }
}
