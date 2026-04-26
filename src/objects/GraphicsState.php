<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\PDFException;


/**
 * Classe che contiene i parametri grafici delle singole pagine e dei singoli oggetti.
 */
class GraphicsState
{
    /**
     * Parametri specifici dei PDF processor come riportato a pagina 156 del file ISO_32000-2-2020_sponsored.pdf
     */
    public array $CTM = [];
    public $clipping_path = null;
    public string|array $color_space = 'DeviceGray';
    public string|array $color_space_stroking = 'DeviceGray';
    public $color = [0];
    public $color_stroking = [0];
    public $text_state = [
        'Tc' => 0,    // Character spacing (unscaled text space units)
        'Tw' => 0,    // Word spacing (unscaled text space units)
        'Th' => 100,  // Horizontal scaling (float)
        'Tl' => 0,    // Leading (unscaled text space units)
        'Tf' => '',   // Text font (name of a font resource)
        'Tfs' => 0,   // Text font size (float)
        'Tmode' => 0, // Text rendering mode (integer)
        'Trise' => 0, // Text rise (unscaled text space units)
        'Tk' => true, // Text knockout (PDF 1.4)
    ];
    public float $line_width = 1.0;
    public int $line_cap = 0;  // butt caps
    public int $line_join = 0; // mitered joins
    public float $miter_limit = 10.0;
    public array $dash_pattern = [[], 0];
    public string $rendering_intent = 'RelativeColorimetric';
    public bool $stroke_adjustment = false;
    public string|array $blend_mode = 'Normal';
    public string|object $soft_mask = 'None';
    public float $alpha_constant = 1.0;
    public float $alpha_constant_stroking = 1.0;
    public bool $alpha_source = false;
    public string $black_point_compensation = 'Default';

    //public bool $overprint = false;
    //public float $overprint_mode = false;
    //public float $black_generation

    /**
     * serve per gestire gli operatori BX e EX, non va salvato nello stack
     */
    public bool $throwIfUnknown = false;

    public bool $textObject = false;

    private $stack = [];

    public function __construct() {}





    /**
     * come specificato a pagina 307 del file ISO_32000-2-2020_sponsored.pdf, bisogna fare in modo che q..Q e BT..BE 
     * non si accavallino
     */
    public function save()
    {
        //$stack[] = $this->
        //foreach()
    }

    public function restore() {}

    public function startTextObject()
    {
        if ($this->textObject)
            throw new PDFException('invalid operator: text objects cannot be nested');

        $this->textObject = true;
    }

    public function endTextObject()
    {
        if (!$this->textObject)
            throw new PDFException('invalid operator: no text object has been created');

        $this->textObject = false;
    }
}
