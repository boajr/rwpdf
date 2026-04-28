<?php

namespace Boajr\PDF\Parser;


class PDFParserEndOfFileException extends PDFParserException
{
    /**
     * l'offset da dove ripartire per la ricerca di operatori 
     * 
     * @var int $operator_offset
     */
    protected $operator_offset;

    public function __construct(string $message, int $operator_offset = 0)
    {
        parent::__construct($message);
        $this->operator_offset = $operator_offset;
    }

    public function getOperatorOffset(): int
    {
        return $this->operator_offset;
    }
}
