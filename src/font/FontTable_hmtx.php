<?php

namespace Boajr\PDF\font;


class FontTable_hmtx extends FontTable
{
    private array $glyphs = [];

    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);

        $hhea = $font->readTable('hhea');
        $numberOfHMetrics = $hhea ? $hhea->numberOfHMetrics : 0;
        $maxp = $font->readTable('maxp');
        $numGlyphs = $maxp ? $maxp->numGlyphs : 0;

        $this->seek(0);
        $advanceWidth = 0;
        for ($i = 0; $i < $numberOfHMetrics; ++$i) {
            //$advanceWidth = $this->readUFWORD();
            //$this->glyphs[$i] = array('w' => $advanceWidth, 'lsb' => $this->ReadFWORD());
            $next = (object)unpack('nw/nlsb', $this->read(4));
            if ($next->lsb > 0x8000)
                $next->lsb -= 0x10000;
            $advanceWidth = $next->w;
            $this->glyphs[$i] = $next;
        }
        for (; $i < $numGlyphs; ++$i) {
            //$this->glyphs[$i] = array('w' => $advanceWidth, 'lsb' => $this->ReadFWORD());
            $next = (object)unpack('nlsb', $this->read(2));
            if ($next->lsb > 0x8000)
                $next->lsb -= 0x10000;
            $next->w = $advanceWidth;
            $this->glyphs[$i] = $next;
        }
    }

    public function getGlyphWidth(int $index): ?int
    {
        if ($index < 0 || $index > count($this->glyphs))
            return null;
        return $this->glyphs[$index]->w;
    }
}
