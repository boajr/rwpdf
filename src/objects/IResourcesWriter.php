<?php

namespace Boajr\PDF\Objects;

use stdClass;


interface IResourcesWriter
{
    /**
     * funzione chiamata per ottenere un hash che identifica le risorse utilizzate
     */
    public function getResourcesHash(bool $force = false): string;

    /**
     * funzione chiamata per determinare se le risorse vanno scritte inline o come riferimento
     */
    public function isResourcesInLine(): bool;

    /**
     * funzione chiamata per aggiungere la risorsa e tutti i sotto elementi all'elenco degli oggetti globali
     */
    public function appendResources(array &$objs, bool $inline): int;

    /**
     * funzione chiamata per scrivere la risorsa inline
     */
    public function getInlineResourcesObject(int $ver): string;

    /**
     * funzione chiamata per scrivere la risorsa come oggetto
     */
    public function getResourcesPDFObject(int $ver, int $offset, int $objNumber): string;

    /**
     * funzione chiamata per scrivere il riferimento all'oggetto della risorsa
     */
    public function getResourcesObjectReference(): string;

    /**
     * funzione chiamata per ottenere l'offset dell'oggetto della risorsa
     */
    public function getResourcesObjectOffset(int $objNumber): int;



    /**
     * funzioni per aggiungere risorse all'oggetto
     */
    public function addFont(FontDictionary $font): string;

    /**
     * funzione che restituisce il procSet da inserire nella risorsa
     */
    public function getProcSet(): int;
}
