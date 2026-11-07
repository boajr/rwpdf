<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectArray;


class EncodingDictionary_DifferencesArray extends FullArray
{
    public function __construct(InternalState $internalState, PDFObjectArray|array|null $src = null)
    {
        $this->internalState = $internalState;

        $this->setElementType([Entry::INTEGER, Entry::NAME], true);

        if ($src instanceof PDFObjectArray) {
            $src->SetLinkedObject($this);
            foreach ($src as $elem) {
                $this->addElement($elem);
            }
        } else if(is_array($src)) {
            foreach ($src as $elem) {
                $this->addElement($elem);
            }
        }
    }
}
