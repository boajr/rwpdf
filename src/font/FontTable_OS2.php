<?php

namespace Boajr\PDF\font;


class FontTable_OS2 extends FontTable
{
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $version = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $xAvgCharWidth = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $usWeightClass = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $usWidthClass = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $fsType = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $ySubscriptXSize = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $ySubscriptYSize = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $ySubscriptXOffset = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $ySubscriptYOffset = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $ySuperscriptXSize = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $ySuperscriptYSize = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $ySuperscriptXOffset = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $ySuperscriptYOffset = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $yStrikeoutSize = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $yStrikeoutPosition = null;
    #[FontPropertyType(FontPropertyType::int16)]     public ?int $sFamilyClass = null;
    #[FontPropertyType(FontPropertyType::uint8, 10)] public ?array $panose = null;
    #[FontPropertyType(FontPropertyType::uint32)]    public ?int $ulUnicodeRange1 = null;
    #[FontPropertyType(FontPropertyType::uint32)]    public ?int $ulUnicodeRange2 = null;
    #[FontPropertyType(FontPropertyType::uint32)]    public ?int $ulUnicodeRange3 = null;
    #[FontPropertyType(FontPropertyType::uint32)]    public ?int $ulUnicodeRange4 = null;
    #[FontPropertyType(FontPropertyType::Tag)]       public ?string $achVendID = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $fsSelection = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $usFirstCharIndex = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $usLastCharIndex = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $sTypoAscender = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $sTypoDescender = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $sTypoLineGap = null;
    #[FontPropertyType(FontPropertyType::UFWORD)]    public ?int $usWinAscent = null;
    #[FontPropertyType(FontPropertyType::UFWORD)]    public ?int $usWinDescent = null;
    #[FontPropertyType(FontPropertyType::uint32)]    public ?int $ulCodePageRange1 = null;
    #[FontPropertyType(FontPropertyType::uint32)]    public ?int $ulCodePageRange2 = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $sxHeight = null;
    #[FontPropertyType(FontPropertyType::FWORD)]     public ?int $sCapHeight = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $usDefaultChar = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $usBreakChar = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $usMaxContext = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $usLowerOpticalPointSize = null;
    #[FontPropertyType(FontPropertyType::uint16)]    public ?int $usUpperOpticalPointSize = null;


    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);
        $this->parse();
    }
}
