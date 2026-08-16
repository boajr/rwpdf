<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectDictionary;


class ResourcesDictionary_XObjectDictionary extends GenericDictionary
{
    public function __construct(InternalState $internalState, ?PDFObjectDictionary $src = null)
    {
        $this->internalState = $internalState;

        $this->setElementType([Entry::STREAM => XObjectStream::class], false);

        if ($src) {
            $src->SetLinkedObject($this);
            $this->setData($src);
        }
    }
}
