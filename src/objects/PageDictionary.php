<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\IPDFObject;
use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;
use Boajr\PDF\PDFException;
use ReflectionProperty;

//class PageGroup_CSArray extends BaseArray
//{
//    public function __construct(?PDFObjectArray $src = null)
//    {
//        $this->setElementType([Entry::STREAM => 'Stream'], false);
//
//        if ($src) {
//            $src->SetLinkedObject($this);
//            foreach ($src as $elem) {
//                $this->addElement($elem);
//            }
//        }
//    }
//}

/**
 * Classe che rappresenta un page group come specificato a pagina 421 del pdf
 * 2.0
 */
//class Page_GroupDictionary extends BaseDictionary
//{
//    public function __construct(?PDFObjectDictionary $src = null)
//    {
//        $this->addEntry('S', Entry::NAME, true, true, 1000);
//        $this->addEntry('CS', Entry::NAME /*[Entry::NAME, Entry::ARRAY => 'PageGroup_CSArray']*/, false, true, 1000);
//        $this->addEntry('I', Entry::BOOLEAN, false, true, 1000);
//        $this->addEntry('K', Entry::BOOLEAN, false, true, 1000);
//
//        if ($src) {
//            $src->SetLinkedObject($this);
//            $this->setData($src);
//        }
//    }
//}

/**
 * Classe che rappresenta un page object come specificato a pagina 104 del file ISO_32000-2-2020_sponsored.pdf
 */
class PageDictionary extends BaseDictionary implements IResourcesWriter
{
    use ResourcesWriter;

    /**
     * Contents stream che posso scrivere, i contents stream che leggo non li modifico, al massimo cambio i riferimenti
     * alle risorse, questo per evitare di fare casini se uno stream è contenuto in più pagine 
     */
    private ?ContentsStream $writableContents = null;

    /**
     * Collegamento alla classe internal state del pdf
     */
    private ?InternalState $internalState = null;



    public function __construct(
        PDF $pdf,
        PDFObjectDictionary|array|null $src_or_size,
        int $rotate = 0,
        ?ResourcesDictionary $resources = null,
        ?Rectangle $mediaBox = null,
        ?Rectangle $cropBox = null
    ) {
        $this->pdf = $pdf;

        $this->addEntry('Type', Entry::NAME, true, true, 1000);
        $this->addEntry('Parent', [Entry::DICTIONARY => PagesDictionary::class], true, false, 1000);
        //$this->addEntry('LastModified', Entry::DATE, false, true, 1003);
        $this->addEntry('Resources', [Entry::DICTIONARY => ResourcesDictionary::class], false, true, 1000); // potrebbe anche non essere in linea
        $this->addEntry('MediaBox', Entry::RECTANGLE, false, true, 1000);
        $this->addEntry('CropBox', Entry::RECTANGLE, false, true, 1000);
        //$this->addEntry('BleedBox', Entry::RECTANGLE, false, true, 1003);
        //$this->addEntry('TrimBox', Entry::RECTANGLE, false, true, 1003);
        //$this->addEntry('ArtBox', Entry::RECTANGLE, false, true, 1003);
        //$this->addEntry('BoxColorInfo', [Entry::DICTIONARY => '???'], true, false, 1004);
        $this->addEntry('Contents', [Entry::STREAM => ContentsStream::class, Entry::ARRAY => ContentsArray::class], false, [Entry::STREAM => false, Entry::ARRAY => true], 1000);
        $this->addEntry('Rotate', Entry::INTEGER, false, true, 1000);
        //$this->addEntry('Group', [Entry::DICTIONARY => 'GroupXObjectDictionary'], false, true, 1004);
        //$this->addEntry('Thumb', [Entry::STREAM => '???'], false, false, 1000);
        //$this->addEntry('B', [Entry::DICTIONARY => '???'], false, false, 1001);
        //$this->addEntry('Dur', [Entry::DICTIONARY => '???'], false, false, 1001);
        //$this->addEntry('Trans', [Entry::DICTIONARY => '???'], false, false, 1001);
        //$this->addEntry('Annots', [Entry::DICTIONARY => '???'], false, false, 1000);
        //$this->addEntry('AA', [Entry::DICTIONARY => '???'], false, false, 1002);
        //$this->addEntry('Metadata', [Entry::DICTIONARY => '???'], false, false, 1004);
        //$this->addEntry('PieceInfo', [Entry::DICTIONARY => '???'], false, false, 1003);
        //$this->addEntry('StructParents', Entry::INTEGER, false, true, 1003);
        //$this->addEntry('ID', [Entry::DICTIONARY => '???'], false, false, 1003);
        //$this->addEntry('PZ', [Entry::DICTIONARY => '???'], false, false, 1003);
        //$this->addEntry('SeparationInfo', [Entry::DICTIONARY => '???'], false, false, 1003);
        //$this->addEntry('Tabs', [Entry::DICTIONARY => '???'], false, false, 1005);
        //$this->addEntry('TemplateInstantiated', [Entry::DICTIONARY => '???'], false, false, 1005);
        //$this->addEntry('PresSteps', [Entry::DICTIONARY => '???'], false, false, 1005);
        $this->addEntry('UserUnit', [Entry::NUMBER], false, true, 1006);
        //$this->addEntry('VP', [Entry::DICTIONARY => '???'], false, false, 1006);
        //$this->addEntry('AF', [Entry::DICTIONARY => '???'], false, false, 2000);
        //$this->addEntry('OutputIntents', [Entry::DICTIONARY => '???'], false, false, 2000);
        //$this->addEntry('DPart', [Entry::DICTIONARY => '???'], false, false, 2000);

        $this->resourcesDict = new ResourcesDictionary($pdf, $this);

        if ($src_or_size instanceof PDFObjectDictionary) {
            $src_or_size->SetLinkedObject($this);
            $this->constructFromParser($src_or_size, $resources, $mediaBox, $cropBox, $rotate);
        } else {
            $this->Type = 'Page';
            $this->Resources = $this->resourcesDict;
            $this->MediaBox = new Rectangle($pdf, 0, 0, $src_or_size[0], $src_or_size[1]);
            $this->Rotate = $rotate;
            $this->graphicsState = new GraphicsState();
        }
    }

    /**
     * funzione chiamata quando il pdf viene creato da un'importazione. Non uso il setData standard perché faccio in 
     * modo di passare le risorse, i box e la rotazione già ai figli.
     */
    private function constructFromParser(PDFObjectDictionary $src, ?ResourcesDictionary $resources, ?Rectangle $mediaBox, ?Rectangle $cropBox, int $rotate): void
    {
        // legge tutti i campi della pagina, tranne i Contents
        foreach ($src as $k => $v) {
            if ($k === 'Contents')
                continue;

            if (!isset($this->entryList[$k])) {
                if (static::$NoUnexpectedEntryError) {
                    print "Unexpected entry '$k' in " . $this->dictionaryName() . '.' . PHP_EOL;
                    continue;
                }
                throw new PDFException("Unexpected entry '$k' in " . $this->dictionaryName() . '.');
            }

            try {
                $this->entryList[$k]->setValue($v);
            } catch (EntrySetValueException $ex) {
                throw new PDFException("Unable to set value for entry '$k' in " . $this->dictionaryName() . ': ' . $ex->getMessage());
            }
        }

        // aggiungo i valori passati come parametri
        if ($this->Resources)
            $resources = $this->Resources;
        $this->Resources = $this->resourcesDict;

        if (!$this->MediaBox)
            $this->MediaBox = $mediaBox;

        if (!$this->CropBox)
            $this->CropBox = $cropBox;

        if (!$this->Rotate)
            $this->Rotate = $rotate;

        // faccio il parser della pagina
        $this->graphicsState = new GraphicsState();

        if (!$src['Contents'])
            return;

        $val = $src['Contents']->GetReferencedObject();
        $type = $val->GetFinalType();
        if ($type === IPDFObject::TYPE_NULL)
            return;

        if ($type === IPDFObject::TYPE_STREAM) {
            $this->Contents = new ContentsStream($this->pdf, $val->GetFinalValue(), $this, $this->graphicsState, $resources);
            if ($this->Contents->needContents)
                throw new PDFException('Invalid content stream data');
            return;
        }

        if ($type === IPDFObject::TYPE_ARRAY) {
            $c = new ContentsArray($this->pdf, $val->GetFinalValue(), $this, $this->graphicsState, $resources);
            $this->Contents = count($c) == 1 ? $c[0] : $c;
            return;
        }

        throw new PDFException("Unable to set value for entry 'Contents' in " . $this->dictionaryName() . ': wrong type.');
    }

    /**
     * funzione chiamata per calcolare il ProcSet da scrivere basato sul contenuto reale della pagina
     */
    public function getProcSet(): int
    {
        if ($this->Contents)
            return $this->Contents->getProcSet();
        return 0;
    }

    /**
     * funzione che restituisce l'unico stream scrivibile della pagina, se non esiste lo crea
     */
    private function getWritableContents(): ContentsStream
    {
        if (!$this->writableContents) {
            $this->writableContents = new ContentsStream($this->pdf, null, $this, $this->graphicsState, $this->Resources);

            if (!$this->Contents)
                $this->Contents = $this->writableContents;
            else {
                if ($this->Contents instanceof ContentsStream) {
                    $c = new ContentsArray($this->pdf);
                    $c[] = $this->Contents;
                    $this->Contents = $c;
                }
                $this->Contents[] = $this->writableContents;
            }
        }

        return $this->writableContents;
    }

    /**
     * funzione che rstituisce l'internalState del pdf
     */
    private function getInternalState(): InternalState
    {
        if (!$this->internalState) {
            $this->internalState = new ReflectionProperty($this->pdf, 'internalState')->getValue($this->pdf);
        }
        return $this->internalState;
    }

    /**
     * funzione che posiziona all'interno del CropBox la coordinata X
     */
    private function adjX(float $val): float
    {
        $rect = $this->CropBox ?: $this->MediaBox;
        return ($rect ? $rect->left : 0) + $val / ($this->UserUnit ?: 1.0);
    }

    /**
     * funzione che posiziona all'interno del CropBox la coordinata Y
     */
    private function adjY(float $val): float
    {
        $rect = $this->CropBox ?: $this->MediaBox;
        return ($rect ? $rect->bottom - $rect->top : 0) - $val / ($this->UserUnit ?: 1.0);
    }

    /**
     * funzione che corregge le misure
     */
    private function adjSz(float $val): float
    {
        return $val / ($this->UserUnit ?: 1.0);
    }

    /**
     * funzione che verifica se devo modificare il graphicState prima di fare un riempiemento
     */
    private function writeGSForFill(ContentsStream $c): void
    {
        $is = $this->getInternalState();
        $gs = $this->graphicsState;

        // seleziono il colore da utilizzare per il riempimento
        $count = count($is->fillColor);
        if (
            !is_string($gs->color_space) ||
            !in_array($gs->color_space, ['DeviceGray', 'DeviceRGB']) ||
            count($gs->color) != $count ||
            $gs->color !== $is->fillColor
        ) {
            if ($count == 3)
                $c->AppendOperators('rg', $is->fillColor[0], $is->fillColor[1], $is->fillColor[2]);
            else if ($count == 1)
                $c->AppendOperators('g', $is->fillColor[0]);
        }
    }

    /**
     * funzione che inserisce nell'internalState i valori per utilizzare un riempimento
     */
    private function writeGSForStroke(ContentsStream $c): void
    {
        $is = $this->getInternalState();
        $gs = $this->graphicsState;

        // seleziono il colore da utilizzare per la grafica
        $count = count($is->drawColor);
        if (
            !is_string($gs->color_space_stroking) ||
            !in_array($gs->color_space_stroking, ['DeviceGray', 'DeviceRGB']) ||
            count($gs->color_stroking) != $count ||
            $gs->color_stroking !== $is->drawColor
        ) {
            if ($count == 3)
                $c->AppendOperators('RG', $is->drawColor[0], $is->drawColor[1], $is->drawColor[2]);
            else if ($count == 1)
                $c->AppendOperators('G', $is->drawColor[0]);
        }

        // seleziono lo spessore della linea
        if ($gs->line_width != $is->lineWidth)
            $c->AppendOperators('w', $is->lineWidth);

        // seleziono i butt caps
        if ($gs->line_cap != $is->lineCap)
            $c->AppendOperators('J', $is->lineCap);

        // seleziono i mitered joins e il mitter limit
        if ($gs->line_join != $is->lineJoin)
            $c->AppendOperators('j', $is->lineJoin);

        // seleziono i mitered joins
        if ($is->lineJoin == 0 && $gs->miter_limit != $is->miterLimit)
            $c->AppendOperators('M', $is->miterLimit);


        //public array $dash_pattern = [[], 0];


        //print_r($is);
        //print_r($gs);
    }



    public function SetPageSize(array $size): void
    {
        $userUnit = $this->UserUnit ?: 1.0;

        if (isset($this->MediaBox)) {
            $this->MediaBox->left = 0;
            $this->MediaBox->top = 0;
            $this->MediaBox->right = $size[0] / $userUnit;
            $this->MediaBox->bottom = $size[1] / $userUnit;
        } else {
            $this->MediaBox = new Rectangle($this->pdf, 0, 0, $size[0] / $userUnit, $size[1] / $userUnit);
        }
        $this->CropBox = null;
    }

    public function MoveTo(float $x, float $y): void
    {
        $c = $this->getWritableContents();
        $c->AppendOperators('m', $this->adjX($x), $this->adjY($y));
    }

    public function LineTo(float $x, float $y): void
    {
        $c = $this->getWritableContents();
        $c->AppendOperators('l', $this->adjX($x), $this->adjY($y));
    }

    public function Rect(float $x, float $y, float $w, float $h): void
    {
        $c = $this->getWritableContents();
        $c->AppendOperators('re', $this->adjX($x), $this->adjY($y), $this->adjSz($w), -$this->adjSz($h));
    }

    public function Fill(bool $evenOddRule = false): void
    {
        $c = $this->getWritableContents();
        $this->writeGSForFill($c);
        $c->AppendOperators($evenOddRule ? 'f*' : 'f');
    }

    public function FillAndStroke(bool $closePath = false, bool $evenOddRule = false): void
    {
        $c = $this->getWritableContents();
        $this->writeGSForFill($c);
        $this->writeGSForStroke($c);
        $c->AppendOperators(($closePath ? 'b' : 'B') . ($evenOddRule ? '*' : ''));
    }

    public function Stroke(bool $closePath = false): void
    {
        $c = $this->getWritableContents();
        $this->writeGSForStroke($c);
        $c->AppendOperators($closePath ? 's' : 'S');
    }
}
