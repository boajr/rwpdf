<?php

namespace Boajr\PDF\Parser;

use Boajr\PDF\Objects\IBaseObject;


interface IPDFObject
{
    const
        TYPE_ARRAY = 0,
        TYPE_BOOL = 1,
        TYPE_DICTIONARY = 2,
        TYPE_FLOAT = 3,
        TYPE_INT = 4,
        TYPE_NAME = 5,
        TYPE_NULL = 6,
        TYPE_REFERENCE = 7,
        TYPE_STREAM = 8,
        TYPE_STRING = 9;

    /**
     * funzione che restituisce il tipo di dato contenuto nell'oggetto 
     */
    public function GetType(): int;

    /**
     * funzione che restituisce il tipo di dato una volta risolti tutti gli oggetti indiretti
     */
    public function GetFinalType(): int;

    /**
     * funzione che restituisce il valore contenuto nell'oggetto
     */
    public function GetValue(): mixed;

    /**
     * funzione che restituisce il valore una volta risolti tutti gli oggetti indiretti
     */
    public function GetFinalValue(): mixed;

    /**
     * funzione che restituisce l'oggetto referenziato
     */
    public function GetReferencedObject(): IPDFObject;

    /**
     * funzione che gestiscono l'oggetto pdf collegato a questa struttura
     */
    public function HasLinkedObject(): bool;
    public function GetLinkedObject(): IBaseObject;
    public function SetLinkedObject(IBaseObject $obj);
}
