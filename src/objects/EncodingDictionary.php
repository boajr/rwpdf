<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;


/**
 * Classe che rappresenta un encoding dictionary come specificato a pagina 323 del file
 * ISO_32000-2-2020_sponsored.pdf
 */
class EncodingDictionary extends BaseDictionary
{
    public function __construct(InternalState $internalState, ?PDFObjectDictionary $src = null)
    {
        $this->internalState = $internalState;

        $this->addEntry('Type', Entry::NAME, false, true, 1000);
        $this->addEntry('BaseEncoding', Entry::NAME, false, true, 1000);
        $this->addEntry('Differences', Entry::ARRAY, false, true, 1000);

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setData($src);
        //} else {
        //    $this->Type = 'Encoding';
        }
    }
}
