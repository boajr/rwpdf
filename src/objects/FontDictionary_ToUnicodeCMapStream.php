<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;


/**
 * Classe che rappresenta un ToUnicode stream come specificato a pagina 338 del file ISO_32000-2-2020_sponsored.pdf
 */
class FontDictionary_ToUnicodeCMapStream extends Stream
{
    public function __construct(PDF $pdf, ?PDFObjectDictionary $src = null)
    {
        $this->pdf = $pdf;

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setStreamData($src, true);
        }
    }
}
