<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectArray;


class FontDictionary_WidthsArray extends BaseArray
{
    public function __construct(InternalState $internalState, ?PDFObjectArray $src = null)
    {
        $this->internalState = $internalState;

        $this->setElementType(Entry::NUMBER, true);

        if ($src) {
            $src->SetLinkedObject($this);
            foreach ($src as $elem) {
                $this->addElement($elem);
            }
        }
    }
}
