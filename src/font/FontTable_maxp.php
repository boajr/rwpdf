<?php

namespace Boajr\PDF\font;


class FontTable_maxp extends FontTable
{
    #[FontPropertyType(FontPropertyType::Version16Dot16)] public ?float $version = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $numGlyphs = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxPoints = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxContours = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxCompositePoints = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxCompositeContours = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxZones = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxTwilightPoints = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxStorage = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxFunctionDefs = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxInstructionDefs = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxStackElements = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxSizeOfInstructions = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxComponentElements = null;
    #[FontPropertyType(FontPropertyType::uint16)]         public ?int $maxComponentDepth = null;


    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);
        $this->parse();
    }
}
