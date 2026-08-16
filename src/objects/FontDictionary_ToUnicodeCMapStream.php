<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;


/**
 * Classe che rappresenta un ToUnicode stream come specificato a pagina 338 del file ISO_32000-2-2020_sponsored.pdf
 */
class FontDictionary_ToUnicodeCMapStream extends Stream
{
    public function __construct(InternalState $internalState, ?PDFObjectDictionary $src = null)
    {
        $this->internalState = $internalState;

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setStreamData($src, true);
        }
    }
}
