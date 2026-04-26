<?php

namespace Boajr\PDF\Objects;

//use Boajr\PDF\Parser\PDFObjectDictionary;


/**
 * Parametri di default da usare nelle varie pagine
 */
class InternalState
{
    /**
     * Draw Color
     * Colore usato per disegnare le linee. Viene convertito nello stroking color nelle operazione di grafica
     */
    public array $drawColor = [0];

    /**
     * Fill Color
     * Colore usato per riempire le figure. Viene convertito nel filling color nelle operazioni di grafica
     */
    public array $fillColor = [0];

    /**
     * Text Color
     * Colore usato per i testi. Viene convertito nel filling color quando si scrive testo e nello stroking color se 
     * serve anche sottolinearlo
     */
    public array $textColor = [0];










    /**
     * Line Width (8.4.3.2)
     * spessore della linea da disegnare
     */
    public float $lineWidth = 1.0;

    /**
     * Line cap styles (8.4.3.3)
     * Specifica le forma delle terminazioni delle linee non chiuse, sia nei percorsi aperti, sia nei segmenti delle 
     * linee tratteggiate
     */
    public int $lineCap = 0;

    /**
     * Line join styles (8.4.3.4)
     * Specifica come viene disegnata la congiunzione tra due linee consecutive
     */
    public int $lineJoin = 0;

    /**
     * Miter limit (8.4.3.5)
     * distanza massima in cui la congiunzione miter si trasforma in bevel
     */
    public float $miterLimit = 10.0;
}
