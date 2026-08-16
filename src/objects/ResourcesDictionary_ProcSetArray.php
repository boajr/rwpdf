<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectArray;


class ResourcesDictionary_ProcSetArray extends FullArray
{
    public function __construct(InternalState $internalState, ?PDFObjectArray $src = null)
    {
        $this->internalState = $internalState;

        $this->setElementType(Entry::NAME, true);

        if ($src) {
            $src->SetLinkedObject($this);
            foreach ($src as $elem) {
                $this->addElement($elem);
            }
        }
    }
}
