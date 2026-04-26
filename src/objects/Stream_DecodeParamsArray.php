<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectArray;
use Boajr\PDF\PDF;


class Stream_DecodeParamsArray extends FullArray
{
    public function __construct(PDF $pdf, ?PDFObjectArray $src = null)
    {
        $this->pdf = $pdf;

        $this->setElementType([Entry::DICTIONARY => GenericDictionary::class], true);

        if ($src) {
            $src->SetLinkedObject($this);
            foreach ($src as $elem) {
                $this->addElement($elem);
            }
        }
    }
}
