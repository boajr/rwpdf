<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\IPDFObject;
use Boajr\PDF\Parser\PDFObjectArray;
use Boajr\PDF\PDF;
use Boajr\PDF\PDFException;

class ContentsArray extends FullArray
{
    public function __construct(PDF $pdf, ?PDFObjectArray $src = null, ?IResourcesWriter $resourcesWriter = null, ?GraphicsState $graphicsState = null, ?ResourcesDictionary $resources = null)
    {
        $this->pdf = $pdf;

        $this->setElementType([Entry::STREAM => ContentsStream::class], false);

        if ($src) {
            //$src->SetLinkedObject($this);
            $last = null;
            foreach ($src as $elem) {
                $val = $elem->GetReferencedObject();
                $type = $val->GetFinalType();
                if ($type === IPDFObject::TYPE_NULL) {
                    continue;
                }

                if ($type !== IPDFObject::TYPE_STREAM)
                    throw new EntrySetValueException('wrong type.');

                if ($last) {
                    $last->AppendContents($val->GetFinalValue()->GetStream(true));
                } else {
                    $last = new ContentsStream($this->pdf, $val->GetFinalValue(), $resourcesWriter, $graphicsState, $resources);
                }

                if (!$last->needContents) {
                    $this[] = $last;
                    $last = null;
                }
            }

            if ($last)
                throw new PDFException('Invalid content stream data');
        }
    }

    public function getProcSet(): int
    {
        $procSet = 0;
        foreach ($this as $c)
            $procSet |= $c->getProcSet();
        return $procSet;
    }
}
