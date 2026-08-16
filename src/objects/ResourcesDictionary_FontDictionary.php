<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;


class ResourcesDictionary_FontDictionary extends GenericDictionary
{
    public function __construct(InternalState $internalState, ?PDFObjectDictionary $src = null)
    {
        $this->internalState = $internalState;

        $this->setElementType([Entry::DICTIONARY => FontDictionary::class], false);

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setData($src);
        }
    }
}
