<?php

namespace Boajr\PDF\Objects;

use ReflectionMethod;


/**
 * @mixin BaseDictionary
 * @property \Boajr\PDF\PDF $pdf 
 * 
 * @mixin IResourcesWriter
 * @method   int getProcSet()
 * 
 * 
 * 
 * @property mixed $Resources
 * 
 * 
 */
trait ResourcesWriter
{
    /**
     * possibili valori che possono essere inseriti nel ProcSet
     */
    private const procSetNames = ['/PDF', '/Text', '/ImageB', '/ImageC', '/ImageI'];



    /**
     * I valori di default che possono essere cambiati durante la scrittura della pagina
     */
    public ?GraphicsState $graphicsState = null;

    /**
     * array che memorizza i font usati dai contents stream
     */
    private ?ResourcesDictionary_FontDictionary $FontDictionary = null;




    /**
     * hash delle risorse utilizzate
     */
    private ?string $hash = null;

    /**
     * numero dell'oggetto delle risorse
     */
    private int $resourcesNumber = 0;

    /**
     * offset dell'oggetto delle risorse
     */
    private int $resourcesOffset = 0;


    /**
     * caching di un po' di metodi
     */
    private ?ReflectionMethod $getIResourcesWritersMethod = null;
    private ?ReflectionMethod $pushSingleResourceMethod = null;


    public function getResourcesHash(bool $force = false): string
    {
        if (!$force && $this->hash)
            return $this->hash;

        $ctx = hash_init('crc32');

        // aggiunge il valore del procSet
        hash_update($ctx, $this->getProcSet());

        // aggiunge tutti gli id dei font (ordinati in modo crescente)...
        $a = $this->FontDictionary->getDictionaryKeys();
        sort($a);
        foreach ($a as $k)
            hash_update($ctx, $k);





        $this->hash = hash_final($ctx);
        return $this->hash;
    }

    private function getWriters(): array
    {
        if (!$this->getIResourcesWritersMethod) {
            $this->getIResourcesWritersMethod = new ReflectionMethod($this->pdf, 'getIResourcesWriters');
        }
        return $this->getIResourcesWritersMethod->Invoke($this->pdf, $this->getResourcesHash());
    }

    public function isResourcesInLine(): bool
    {
        return count($this->getWriters()) <= 1;
    }

    public function appendResources(array &$objs, bool $inline): int
    {
        $writers = $this->getWriters();

        // solo il primo writers dell'elenco ricrea le risorse, gli altri lo richiamano
        if ($writers[0] !== $this)
            return $writers[0]->appendResources($objs, $inline);

        // la versione minima di questa struttura è la 1.0
        $ver = 1000;

        if ($inline || !in_array($this->Resources, $objs)) {
            if (!$inline) {
                $objs[] = $this->Resources;
                $this->resourcesNumber = count($objs);
            }

            // aggiungo l'ExtGState dictionary (min ver )

            // aggiungo il ColorSpace dictionary (min ver )

            // aggiungo il Pattern dictionary (min ver )

            // aggiungo il Shading dictionary (min ver )

            // aggiungo l'XObject dictionary (min ver )

            // aggiungo il Font dictionary (min ver 1.0)
            if ($this->FontDictionary) {
                $v = $this->FontDictionary->appendObject($objs, true);
                if ($ver < $v) {
                    $ver = $v;
                }
            }

            // il ProcSet è un array inline di nomi, con versione minima 1.0 quindi non ho oggetti da aggiungere

            // aggiungo il Properties dictionary (min ver )

            //foreach ($this->entryList as $entry) {
            //    $v = $entry->appendObject($objs);
            //    if ($ver < $v) {
            //        $ver = $v;
            //    }
            //}
        }
        return $ver;
    }







    public function getInlineResourcesObject(int $ver): string
    {
        $out = '<<';
        //foreach ($this->entryList as $key => $entry) {
        //    if (!$entry->isOutputable($ver)) {
        //        continue;
        //    }
        //
        //    $out .= '/' . $key;
        //    if ($entry->startWithSeparator()/* || strpos(" \t\r\n\f()<>[]/%", $out[strlen($out) - 1]) !== false*/) {
        //        $out .= $entry->getOutput($ver);
        //        continue;
        //    }
        //    $out .= ' ' . $entry->getOutput($ver);
        //}


        // aggiungo l'ExtGState dictionary (min ver )
        //print 'TODO: ricreare l\'ExtGState dictionary';

        // aggiungo il ColorSpace dictionary (min ver )
        //print 'TODO: ricreare il ColorSpace dictionary';

        // aggiungo il Pattern dictionary (min ver )
        //print 'TODO: ricreare il ColorSpace dictionary';

        // aggiungo il Shading dictionary (min ver )
        //print 'TODO: ricreare il Pattern dictionary';

        // aggiungo l'XObject dictionary (min ver )
        //print 'TODO: ricreare l\'XObject dictionary';

        // aggiungo il Font dictionary (min ver 1.0)
        if ($this->FontDictionary)
            $out .= '/Font' . $this->FontDictionary->getInlineObject($ver);

        // scrivo ProcSet se ho almeno una voce e se la versione è inferiore alla 2.0
        $procSet = $this->getProcSet();
        if ($procSet && $ver < 2000) {
            $out .= '/ProcSet[';
            for ($i = 0; $i < 5; ++$i)
                if ($procSet & (1 << $i))
                    $out .= static::procSetNames[$i];
            $out .= ']';
        }

        // aggiungo il Properties dictionary (min ver )







        return $out . '>>';
    }

    public function getResourcesPDFObject(int $ver, int $offset, int $objNumber): string
    {
        $this->resourcesOffset = $offset;
        return $this->resourcesNumber . " 0 obj\r\n" . $this->getInlineResourcesObject($ver) . "\r\nendobj\r\n";
    }

    public function getResourcesObjectReference(): string
    {
        return $this->resourcesNumber . ' 0 R';
    }

    public function getResourcesObjectOffset(int $objNumber): int
    {
        return $this->resourcesOffset;
    }

    /**
     * Qui inizia la possibilità di aggiungere risorse all'oggetto
     */
    private function pushSingleResource(string $type, object $obj): array
    {
        if (!$this->pushSingleResourceMethod)
            $this->pushSingleResourceMethod = new ReflectionMethod($this->pdf, 'pushSingleResource');
        return $this->pushSingleResourceMethod->Invoke($this->pdf, $type, $obj);
    }

    public function addFont(FontDictionary $font): string
    {
        // chiede al PDF se il font è già presente
        [$idx, $font] = $this->pushSingleResource('font', $font);

        // aggiunge il font alle risorse
        if (!$this->FontDictionary)
            $this->FontDictionary = new ResourcesDictionary_FontDictionary($this->pdf);

        $name = 'F' . $idx;
        $this->FontDictionary->$name = $font;
        return $name;
    }
}
