<?php

namespace Boajr\PDF\Parser;

use Boajr\PDF\PDFException;


class PDFParserException extends PDFException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
