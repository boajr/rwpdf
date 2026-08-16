<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectArray;


class Stream_DecodeParamsArray extends FullArray
{
    public function __construct(InternalState $internalState, ?PDFObjectArray $src = null)
    {
        $this->internalState = $internalState;

        $this->setElementType([Entry::DICTIONARY => GenericDictionary::class], true);

        if ($src) {
            $src->SetLinkedObject($this);
            foreach ($src as $elem) {
                $this->addElement($elem);
            }
        }
    }
}
