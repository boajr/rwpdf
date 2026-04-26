<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectArray;
use Boajr\PDF\PDF;


class ResourcesDictionary_ProcSetArray extends FullArray
{
    public function __construct(PDF $pdf, ?PDFObjectArray $src = null)
    {
        $this->pdf = $pdf;

        $this->setElementType(Entry::NAME, true);

        if ($src) {
            $src->SetLinkedObject($this);
            foreach ($src as $elem) {
                $this->addElement($elem);
            }
        }
    }
}
