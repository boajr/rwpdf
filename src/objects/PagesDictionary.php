<?php

namespace Boajr\PDF\Objects;

use Boajr\PDF\Parser\IPDFObject;
use Boajr\PDF\Parser\PDFObjectDictionary;
use Boajr\PDF\PDF;
use Boajr\PDF\PDFException;
use ReflectionMethod;


/**
 * Classe che rappresenta un page tree node come specificato a pagina 103 del file ISO_32000-2-2020_sponsored.pdf
 */
class PagesDictionary extends BaseDictionary
{
    public function __construct(
        PDF $pdf,
        PDFObjectDictionary|PagesDictionary|null $src_or_parent,
        ResourcesDictionary|array|null $resources_or_pages = null,
        ?Rectangle $mediaBox = null,
        ?Rectangle $cropBox = null,
        int $rotate = 0
    ) {
        $this->pdf = $pdf;

        $this->addEntry('Type', Entry::NAME, true, true, 1000);
        $this->addEntry('Parent', [Entry::DICTIONARY => PagesDictionary::class], false, false, 1000);
        $this->addEntry('Kids', [Entry::ARRAY => PagesDictionary_KidsArray::class], true, true, 1000);
        $this->addEntry('Count', Entry::INTEGER, true, true, 1000);

        // oggetti ereditabili, ma che andrebbero eliminati e copiati in ogni pagina
        $this->addEntry('Resources', [Entry::DICTIONARY => ResourcesDictionary::class], false, true, 1000); // potrebbe anche non essere in linea
        $this->addEntry('MediaBox', Entry::RECTANGLE, false, true, 1000);
        $this->addEntry('CropBox', Entry::RECTANGLE, false, true, 1000);
        $this->addEntry('Rotate', Entry::INTEGER, false, true, 1000);

        if ($src_or_parent instanceof PDFObjectDictionary) {
            $src_or_parent->SetLinkedObject($this);
            $this->constructFromParser($src_or_parent, $resources_or_pages, $mediaBox, $cropBox, $rotate);
        } else {
            // imposto Type e Parent
            $this->Type = 'Pages';
            if ($src_or_parent)
                $this->Parent = $src_or_parent;
            $this->constructFromPDF($resources_or_pages, $mediaBox);
        }
    }

    /**
     * funzione chiamata quando il pdf viene creato da un'importazione. Non uso il setData standard perché faccio in
     * modo di passare le risorse, i box e la rotazione già ai figli.
     */
    private function constructFromParser(PDFObjectDictionary $src, ?ResourcesDictionary $resources, ?Rectangle $mediaBox, ?Rectangle $cropBox, int $rotate): void
    {
        $val = $this->getResources($src, 'Resources');
        if ($val)
            $resources = $val;

        $val = $this->getRectangle($src, 'MediaBox');
        if ($val)
            $mediaBox = $val;

        $val = $this->getRectangle($src, 'CropBox');
        if ($val)
            $cropBox = $val;

        $val = $this->getInteger($src, 'Rotate');
        if ($val !== null)
            $rotate = $val;

        $kids = $this->getArray($src, 'Kids');
        if (!$kids)
            return;

        $pushPage = new ReflectionMethod($this->pdf, 'pushPage');
        foreach ($kids as $kid) {
            if ($kid->GetFinalType() !== IPDFObject::TYPE_DICTIONARY)
                throw new PDFException("Unable to set value in Kids array in " . $this->dictionaryName() . ': wrong type.');
            $kid = $kid->GetFinalValue();

            if ($kid->hasLinkedObject())
                throw new PDFException("Pages tree has cyclic references.");

            $type = $kid['Type'];
            if ($type && $type->GetFinalType() === IPDFObject::TYPE_NAME) {
                $type = $type->GetFinalValue();
                if ($type === 'Pages') {
                    new PagesDictionary($this->pdf, $kid, $resources, $mediaBox, $cropBox, $rotate);
                    continue;
                }
                if ($type === 'Page') {
                    $pushPage->invoke($this->pdf, new PageDictionary($this->pdf, $kid, $rotate, $resources, $mediaBox, $cropBox));
                    continue;
                }
            }
            throw new PDFException("Unable to set value for entry 'Type' in Kids array in " . $this->dictionaryName() . ': wrong type.');
        }
    }

    /**
     * funzione chiamata quando si crea il PagesDictionary per generare il PDF finale. Fa in modo di avere nel nodo i 
     * box e la rotazione più usati dai nodi figli.
     */
    private function constructFromPDF(?array $pages, ?Rectangle $mediaBox): void
    {
        // creo degli array per memorizzare quali rettangoli e quali rotazioni ho nei figli del nodo
        $mb = [];
        $cb = [];
        $r = [];

        // memorizzo l'"hash" del media box passato alla struttura
        $mbRect = $mediaBox ? $mediaBox->toJson() : '';

        // processo tutti gli elementi dell'array pages
        $pushResources = new ReflectionMethod($this->pdf, 'pushResources');
        $this->Kids = new PagesDictionary_KidsArray($this->pdf);
        $this->Count = 0;
        foreach ($pages as $kid) {
            if ($kid instanceof PageDictionary) {
                // se l'elemento è un page dictionary lo aggiungo all'albero
                $this->Kids[] = $kid;
                $kid->Parent = $this;
                ++$this->Count;

                // aggiungo anche le risorse all'array globale
                $pushResources->invoke($this->pdf, $kid);
            } else if (is_array($kid)) {
                // se l'elemento è un array, creo un pages dictionary figlio
                $kid = new PagesDictionary($this->pdf, $this, $kid, $mediaBox);

                // se il dictionary appena creato non ha pagine, lo ignoro
                if ($kid->Count <= 0)
                    continue;

                // aggiungo il dictionary all'alberatura e aggiorno il numero di pagine totale
                $this->Kids[] = $kid;
                $this->Count += $kid->Count;
            } else {
                continue;
            }

            // aggiungo agli array che contano i tipi di rettangoli e le rotazioni, gli "hash" del nuovo oggetto
            if (!isset($kid->MediaBox)) {
                $kid->MediaBox = $mediaBox;
                $hash = $mbRect;
            } else {
                $hash = $kid->MediaBox->toJson();
            }
            if (array_key_exists($hash, $mb))
                $mb[$hash][] = $kid;
            else
                $mb[$hash] = [$kid];

            $hash = isset($kid->CropBox) ? $kid->CropBox->toJson() : '';
            if (array_key_exists($hash, $cb))
                $cb[$hash][] = $kid;
            else
                $cb[$hash] = [$kid];

            $hash = isset($kid->Rotate) && $kid->Rotate ? $kid->Rotate : 0;
            if (array_key_exists($hash, $r))
                $r[$hash][] = $kid;
            else
                $r[$hash] = [$kid];
        }

        // potrebbe essere che l'array passato non contenga pagine
        if (!$this->Count)
            return;

        // verifica qual è il media box più usato e lo mette come default
        $def = $this->arrayWithMoreValues($mb, $mbRect);
        if ($def)
            $this->MediaBox = new Rectangle($this->pdf, ...json_decode($def, true));
        else
            unset($this->MediaBox);
        foreach ($mb[$def] as $kid) {
            unset($kid->MediaBox);
        }

        // verifica qual è il crop box più usato e lo mette come default
        $def = $this->arrayWithMoreValues($cb, '');
        if ($def)
            $this->CropBox = new Rectangle($this->pdf, ...json_decode($def, true));
        else
            unset($this->CropBox);
        foreach ($cb[$def] as $kid) {
            unset($kid->CropBox);
        }

        // verifica qual è il rotate più usato e lo mette come default
        $def = array_key_exists(0, $r) ? 0 : $this->arrayWithMoreValues($r, 0);
        if ($def)
            $this->Rotate = $def;
        else
            unset($this->Rotate);
        foreach ($r[$def] as $kid) {
            unset($kid->Rotate);
        }
    }

    /**
     * determina quel è l'array che ha più elementi
     */
    private function arrayWithMoreValues(array &$a, string|int $def): mixed
    {
        if (array_key_exists($def, $a)) {
            $max = count($a[$def]);
            $ret = $def;
        } else {
            $max = -1;
            $ret = null;
        }
        foreach ($a as $k => &$v) {
            $c = count($v);
            if ($max < $c) {
                $max = $c;
                $ret = $k;
            }
        }
        return $ret;
    }
}
