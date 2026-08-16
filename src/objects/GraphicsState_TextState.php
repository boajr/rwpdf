<?php

namespace Boajr\PDF\Objects;


/**
 * Classe che contiene i parametri del testo delle singole pagine e dei singoli oggetti.
 */
class GraphicsState_TextState
{
    public float $Tc = 0;            // Character spacing (unscaled text space units)
    public float $Tw = 0;            // Word spacing (unscaled text space units)
    public float $Th = 100;          // Horizontal scaling (float)
    public float $Tl = 0;            // Leading (unscaled text space units)
    public ?InternalFont $Tf = null; // Text font (name of a font resource) - è un InternalFont da cui poi estraggo il nome
    public float $Tfs = 0;           // Text font size (float)
    public int $Tmode = 0;           // Text rendering mode (integer)
    public float $Trise = 0;         // Text rise (unscaled text space units)
    public bool $Tk = true;          // Text knockout (PDF 1.4)

    public EncodingDictionary|string|null      $encoding = null;
    public ?FontDictionary_ToUnicodeCMapStream $toUnicode = null;
}
