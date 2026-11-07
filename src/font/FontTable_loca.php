<?php

namespace Boajr\PDF\font;


class FontTable_loca extends FontTable
{
    private array $delta = [];

    public function __construct(Font $font, int $offset, int $length)
    {
        parent::__construct($font, $offset, $length);
        //$this->parse();

        $maxp = $font->readTable('maxp');
        if (!$maxp)
            throw new FontException('missing \'maxp\' table');

        $head = $font->readTable('head');
        if (!$head)
            throw new FontException('missing \'head\' table');

        $da_leggere = $maxp->numGlyphs + 1;

        $this->seek(0);
        if ($head->indexToLocFormat == 0) {
            $this->delta = array_values(unpack('n' . $da_leggere, $this->read(2 * $da_leggere)));
        } else {
            $this->delta = array_values(unpack('N' . $da_leggere, $this->read(4 * $da_leggere)));
        }
    }

    public function getGlyphOffset(int $glyphId): int|false
    {
        if ($glyphId < 0 || $glyphId >= count($this->delta) || $this->delta[$glyphId] == $this->delta[$glyphId + 1])
            return false;

        return $this->delta[$glyphId];
    }

    public function getGlyphOffsetAndSize(int $glyphId): array|false
    {
        if ($glyphId < 0 || $glyphId <= count($this->delta) || $this->delta[$glyphId] == $this->delta[$glyphId + 1])
            return false;

        return [$this->delta[$glyphId], $this->delta[$glyphId + 1] - $this->delta[$glyphId]];
    }
}
