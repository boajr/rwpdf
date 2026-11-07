<?php

namespace Boajr\PDF\font;


class CFFIndex_TopDict extends CFFIndex
{
    public function getTopDict(int $idx) {
        $a = new CFFDict_TopDict($this->getRawData($idx));

    }
}
