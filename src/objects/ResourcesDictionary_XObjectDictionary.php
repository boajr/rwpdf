<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;


class ResourcesDictionary_XObjectDictionary extends GenericDictionary
{
    public function __construct(PDF $pdf, ?PDFObjectDictionary $src = null)
    {
        $this->pdf = $pdf;

        $this->setElementType([Entry::STREAM => XObjectStream::class], false);

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setData($src);
        }
    }
}
