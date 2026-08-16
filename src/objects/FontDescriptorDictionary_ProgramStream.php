<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\font\Font;
use Boajr\PDF\Parser\PDFObjectDictionary;


/**
 * Classe che rappresenta un font stream come specificato a pagina 313 (SubType Type1) e 317 (SubType Type3) del file 
 * ISO_32000-2-2020_sponsored.pdf
 */
class FontDescriptorDictionary_ProgramStream extends Stream
{
    private ?Font $font_obj = null;

    public function __construct(InternalState $internalState, ?PDFObjectDictionary $src = null)
    {
        $this->internalState = $internalState;

        $this->addEntry('Length1', Entry::INTEGER, false, true, 1000);
        //$this->addEntry('Length2', Entry::INTEGER, false, true, 1000);
        //$this->addEntry('Length3', Entry::INTEGER, false, true, 1000);
        $this->addEntry('Subtype', Entry::NAME, false, true, 1002);

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setStreamData($src, true);
        }
    }

    public function appendObject(array &$objs, bool $inline): int
    {
        $ver = parent::appendObject($objs, $inline);

        print 'TODO: la versione del pdf dipende dal tipo di font che viene incluso.' . PHP_EOL;

        return $ver;
    }
}
