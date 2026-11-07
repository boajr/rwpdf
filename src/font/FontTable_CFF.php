<?php

namespace Boajr\PDF\font;


class FontTable_CFF extends FontTable
{
    #[FontPropertyType(FontPropertyType::uint8)] public ?int $major = null;
    #[FontPropertyType(FontPropertyType::uint8)] public ?int $minor = null;
    #[FontPropertyType(FontPropertyType::uint8)] public ?int $hdrSize = null;
    #[FontPropertyType(FontPropertyType::uint8)] public ?int $offSize = null;

    private CFFIndex $nameIndex;
    private CFFIndex_TopDict $topDictIndex;

    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);

        $this->seek(0);
        $this->parseProperty('major');

        if ($this->major != 1)
            throw new FontException('Vertsione di struttura CFF non supportata');

        $this->parseProperty('minor');
        $this->parseProperty('hdrSize');
        $this->parseProperty('offSize');

        echo 'Major: ' . $this->major . PHP_EOL;
        echo 'Minor: ' . $this->minor . PHP_EOL;
        echo 'hdrSize: ' . $this->hdrSize . PHP_EOL;
        echo 'offSize: ' . $this->offSize . PHP_EOL;

        //$buf = $this->read(64);
        //for ($i = 0; $i < 64; ++$i)
        //    echo ord($buf[$i]) . ' ';
        //echo PHP_EOL;

        $this->nameIndex = new CFFIndex($this, $this->hdrSize);

        $off = $this->hdrSize + $this->nameIndex->size();
        //echo $off . PHP_EOL;
        $this->topDictIndex = new CFFIndex_TopDict($this, $off);

        $this->topDictIndex->getTopDict(0);
        //$this->seek($off + 7);
        //$buf = $this->read(66);
        //for ($i = 0; $i < 66; ++$i)
        //    echo ord($buf[$i]) . ' ';
        //echo PHP_EOL;


        }
}
