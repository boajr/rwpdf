<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectArray;


class PagesDictionary_KidsArray extends FullArray
{
    public function __construct(InternalState $internalState, ?PDFObjectArray $src = null)
    {
        $this->internalState = $internalState;

        $this->setElementType([Entry::DICTIONARY => [PageDictionary::class, PagesDictionary::class]], false);

        if ($src) {
            $src->SetLinkedObject($this);
            foreach ($src as $elem) {
                $this->addElement($elem);
            }
        }
    }
}
