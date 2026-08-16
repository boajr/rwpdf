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
    public array $color = [0];
    public array $color_stroking = [0];
    public GraphicsState_TextState $text_state;
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

    private bool $textObject = false;
    private array $textMatrix = [];
    private array $textLineMatrix = [];

    private array $stack = [];

    /**
     * Serve per gestire il posizionamento del testo
     */
    public float $xText = 0.0;
    public float $yText = 0.0;


    /**
     * Inizializzo il text_state in creazione
     */
    public function __construct()
    {
        $this->text_state = new GraphicsState_TextState;
    }

    /**
     * come specificato a pagina 307 del file ISO_32000-2-2020_sponsored.pdf, bisogna fare in modo che q..Q e BT..BE 
     * non si accavallino
     */
    public function save()
    {
        $this->stack['q'] = 'BOH';
        //foreach()
    }

    public function restore()
    {
        if (array_key_last($this->stack) != 'q')
            throw new PDFException('invalid operator: graphic state can be restored only when text objects begined after state save are ended');

        $stato = array_pop($this->stack);
    }

    public function startTextObject()
    {
        if ($this->textObject)
            throw new PDFException('invalid operator: text objects cannot be nested');

        $this->textObject = true;
        $this->stack['BT'] = true;

        $this->textLineMatrix = [1, 0, 0, 1, 0, 0];
        $this->textMatrix = $this->textLineMatrix;
    }

    public function endTextObject()
    {
        if (!$this->textObject)
            throw new PDFException('invalid operator: no text object has been created');

        if (array_key_last($this->stack) != 'BT')
            throw new PDFException('invalid operator: text object can be ended only when all states saved after text object begin are restored');

        array_pop($this->stack);
        $this->textObject = false;
    }

    public function setLineTextMatrix(float $a, float $b, float $c, float $d, float $e, float $f)
    {
        $this->textLineMatrix = [$a, $b, $c, $d, $e, $f];
        $this->textMatrix = $this->textLineMatrix;
    }

    private function mulMatrix(array &$m, float $tx, float $ty)
    {
        $m[4] = $tx * $m[0] + $ty * $m[2] + $m[4];
        $m[5] = $tx * $m[1] + $ty * $m[3] + $m[5];
    }

    public function alterLineTextMatrix(float $tx, float $ty)
    {
        $this->mulMatrix($this->textLineMatrix, $tx, $ty);
        $this->textMatrix = $this->textLineMatrix;
    }

    public function addUsedChars(ContentsStream_TextArray|string $text)
    {
        if (!$this->text_state->Tf)
            throw new PDFException('choose a text font before call addUsedChars');

        if ($text instanceof ContentsStream_TextArray) {
            foreach ($text as $t) {
                if (is_string($t))
                    $this->text_state->Tf->addUsedChars($t, $this->text_state->encoding);
            }
        } else
            $this->text_state->Tf->addUsedChars($text, $this->text_state->encoding);
    }

    public function processText(string $text): array
    {
        if (!$this->text_state->Tf)
            throw new PDFException('choose a text font before call processText');

        [$t, $w] = $this->text_state->Tf->processText($text);
        $w *= $this->text_state->Tfs;
        $this->mulMatrix($this->textMatrix, $w, 0);
        return [$t, $w];
    }

    public function getDecorationPosition(string $textDecoration): array
    {
        if (!$this->text_state->Tf)
            throw new PDFException('choose a text font before call getDecorationPosition');

        $m = $this->text_state->Tf->getDecorationMeasures($textDecoration);
        $pos = $this->textMatrix;
        // uso il meno perché mi devo sposta più in basso di metà dello spessore della linea
        $this->mulMatrix($pos, 0, ($m[0] - $m[1] / 2) * $this->text_state->Tfs);
        return [$pos[4], $pos[5]];
    }

    public function getDecorationWidth(string $textDecoration): float
    {
        if (!$this->text_state->Tf)
            throw new PDFException('choose a text font before call getDecorationWidth');

        $m = $this->text_state->Tf->getDecorationMeasures($textDecoration);
        return $m[1] * $this->text_state->Tfs;
    }
}
