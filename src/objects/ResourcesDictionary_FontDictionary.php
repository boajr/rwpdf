<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;


class ResourcesDictionary_FontDictionary extends GenericDictionary
{
    public function __construct(PDF $pdf, ?PDFObjectDictionary $src = null)
    {
        $this->pdf = $pdf;

        $this->setElementType([Entry::DICTIONARY => FontDictionary::class], false);

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setData($src);
        }
    }
}
