<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\PDFObjectArray;


class ContentsStream_TextArray extends FullArray
{
    public function __construct(InternalState $internalState, ?PDFObjectArray $src = null, ?IResourcesWriter $resourcesWriter = null, ?GraphicsState $graphicsState = null, ?ResourcesDictionary $resources = null)
    {
        $this->internalState = $internalState;

        $this->setElementType([Entry::TEXT_STRING, Entry::NUMBER], true);

        if ($src) {
            $src->SetLinkedObject($this);
            foreach ($src as $elem) {
                $this->addElement($elem);
            }
        }
    }
}
