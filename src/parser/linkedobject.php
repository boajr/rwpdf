<?php

namespace Boajr\PDF\Parser;

use Boajr\PDF\Objects\IBaseObject;


trait LinkedObject
{
    private $linkedObject = null;

    public function HasLinkedObject(): bool
    {
        return $this->linkedObject != null;
    }

    public function GetLinkedObject(): IBaseObject
    {
        return $this->linkedObject;
    }

    public function SetLinkedObject(IBaseObject $obj): void
    {
        //if ($this->linkedObject)
        //    throw new PDFParserException('Linked object already set... possible cyclic reference');
        $this->linkedObject = $obj;
    }
}
