<?php

namespace Boajr\PDF\Parser;


class PDFOperator
{
    /**
     * @var string $operator;
     */
    public $operator = null;

    /**
     * @var array<IPDFObject> $parameters;
     */
    public $parameters = null;

    public function __construct(string $operator, ?array $parameters)
    {
        $this->operator = $operator;
        $this->parameters = $parameters;
    }
}
