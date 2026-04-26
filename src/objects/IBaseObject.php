<?php

namespace Boajr\PDF\Objects;


interface IBaseObject
{
    /**
     * funzione chiamata per aggiungere alla lista $objs tutti gli oggetti collegati alla struttura. restituisce la 
     * versione minima del PDF che verrà creato
     */
    public function appendObject(array &$objs, bool $inline): int;

    /**
     * funzione che restituisce la stringa da inserire nel pdf per memorizzare l'oggetto come campo di un altro oggetto
     */
    public function getInlineObject(int $ver): string;

    /**
     * funzione ch restituisce la stringa da inserire nel pdf per memorizzare l'oggetto come blocco principale
     */
    public function getPDFObject(int $ver, int $offset, int $objNumber): string;

    /**
     * funzione che restituisce il riferimento all'oggetto da inserire nel pdf
     */
    public function getObjectReference(): string;

    /**
     * funzione che restituisce il primo byte dell'oggetto all'interno del pdf
     */
    public function getObjectOffset(int $objNumber): int;
}
