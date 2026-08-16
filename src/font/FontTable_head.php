<?php

namespace Boajr\PDF\font;


class FontTable_head extends FontTable
{
    #[FontPropertyType(FontPropertyType::uint16)]       public ?int $majorVersion = null;
    #[FontPropertyType(FontPropertyType::uint16)]       public ?int $minorVersion = null;
    #[FontPropertyType(FontPropertyType::Fixed)]        public ?float $fontRevision = null;
    #[FontPropertyType(FontPropertyType::uint32)]       public ?int $checksumAdjustment = null;
    #[FontPropertyType(FontPropertyType::uint32)]       public ?int $magicNumber = null;
    #[FontPropertyType(FontPropertyType::uint16)]       public ?int $flags = null;
    #[FontPropertyType(FontPropertyType::uint16)]       public ?int $unitsPerEm = null;
    #[FontPropertyType(FontPropertyType::LONGDATETIME)] public ?int $created = null;
    #[FontPropertyType(FontPropertyType::LONGDATETIME)] public ?int $modified = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $xMin = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $yMin = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $xMax = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $yMax = null;
    #[FontPropertyType(FontPropertyType::uint16)]       public ?int $macStyle = null;
    #[FontPropertyType(FontPropertyType::uint16)]       public ?int $lowestRecPPEM = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $fontDirectionHint = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $indexToLocFormat = null;
    #[FontPropertyType(FontPropertyType::int16)]        public ?int $glyphDataFormat = null;


    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);
        $this->parse();

        //print 'Length: ' . $length . PHP_EOL;

        //print 'magic number: ' . dechex($this->magicNumber) . PHP_EOL;
        //print 'flags: ' . str_pad(decbin($this->flags), 16, '0', STR_PAD_LEFT) . PHP_EOL;
        //print 'unitsPerEm: ' . $this->unitsPerEm . PHP_EOL;
        //print 'created: ' . date('Y-m-d H:i:s', $this->created) . PHP_EOL;
        //print 'modified: ' . date('Y-m-d H:i:s', $this->modified) . PHP_EOL;
        //print 'xMin-xMax: [' . $this->xMin . ', ' . $this->xMax . ']' . PHP_EOL;
        //print 'yMin-yMax: [' . $this->yMin . ', ' . $this->yMax . ']' . PHP_EOL;
        //print 'macStyle: ' . str_pad(decbin($this->macStyle), 16, '0', STR_PAD_LEFT) . PHP_EOL;

        //print 'lowestRecPPEM: ' . $this->lowestRecPPEM . PHP_EOL;
        //print 'fontDirectionHint: ' . $this->fontDirectionHint . PHP_EOL;
        //print 'indexToLocFormat: ' . $this->indexToLocFormat . PHP_EOL;
        //print 'glyphDataFormat: ' . $this->glyphDataFormat . PHP_EOL . PHP_EOL;
    }
}
