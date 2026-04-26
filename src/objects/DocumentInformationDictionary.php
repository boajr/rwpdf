<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;


/**
 * Classe che rappresenta un document information dictionary come specificato a pagina 716 del file 
 * ISO_32000-2-2020_sponsored.pdf
 */
class DocumentInformationDictionary extends BaseDictionary
{
    public function __construct(PDF $pdf, ?PDFObjectDictionary $src = null)
    {
        $this->pdf = $pdf;

        $this->addEntry('Title', Entry::TEXT_STRING, false, true, 1001, 2000);
        $this->addEntry('Author', Entry::TEXT_STRING, false, true, 1000, 2000);
        $this->addEntry('Subject', Entry::TEXT_STRING, false, true, 1001, 2000);
        $this->addEntry('Keywords', Entry::TEXT_STRING, false, true, 1001, 2000);
        $this->addEntry('Creator', Entry::TEXT_STRING, false, true, 1000, 2000);
        $this->addEntry('Producer', Entry::TEXT_STRING, false, true, 1000, 2000);
        $this->addEntry('CreationDate', Entry::DATE, false, true, 1000);
        $this->addEntry('ModDate', Entry::DATE, false, true, 1001);
        $this->addEntry('Trapped', Entry::NAME, false, true, 1003, 2000);

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setData($src);
        }
    }
}
