<?php

namespace Boajr\PDF\Objects;


/**
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
// protected InternalState $internalState;


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
    private ?array $fontDictionary = null;




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


    public function getResourcesHash(bool $force = false): string
    {
        if (!$force && $this->hash)
            return $this->hash;

        $ctx = hash_init('crc32');

        // aggiunge il valore del procSet
        hash_update($ctx, $this->getProcSet());

        // aggiunge tutti gli id dei font (ordinati in modo crescente)...
        if ($this->fontDictionary) {
            $a = [];
            foreach ($this->fontDictionary as $f)
                $a[] = $f->getPostScriptName();
            sort($a);
            foreach ($a as $k)
                hash_update($ctx, $k);
        }




        $this->hash = hash_final($ctx);
        return $this->hash;
    }

    public function isResourcesInLine(): bool
    {
        return count($this->internalState->getIResourcesWriters($this->getResourcesHash())) <= 1;
    }

    public function appendResources(array &$objs, bool $inline): int
    {
        $writers = $this->internalState->getIResourcesWriters($this->getResourcesHash());

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
            if ($this->fontDictionary) {
                // il font dictionary in una risorsa è sempre inline quindi devo aggiungere solo i font (che non sono inline)
                foreach ($this->fontDictionary as $f) {
                    $v = $f->appendObject($objs, false);
                    if ($ver < $v)
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
        //echo 'TODO: ricreare l\'ExtGState dictionary';

        // aggiungo il ColorSpace dictionary (min ver )
        //echo 'TODO: ricreare il ColorSpace dictionary';

        // aggiungo il Pattern dictionary (min ver )
        //echo 'TODO: ricreare il ColorSpace dictionary';

        // aggiungo il Shading dictionary (min ver )
        //echo 'TODO: ricreare il Pattern dictionary';

        // aggiungo l'XObject dictionary (min ver )
        //echo 'TODO: ricreare l\'XObject dictionary';

        // aggiungo il Font dictionary (min ver 1.0)
        if ($this->fontDictionary) {
            $out .= '/Font<<';
            foreach ($this->fontDictionary as $f)
                $out .= $f->getNameAndObjectReference();
            $out .= '>>';
        }

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

    public function addFont(FontDictionary|InternalFont $font): InternalFont
    {
        echo '***** addFont' . PHP_EOL;
        // aggiunge il font alle risorse
        if (!$this->fontDictionary)
            $this->fontDictionary = [];

        if ($font instanceof FontDictionary)
            $font = $font->toInternalFont();

        foreach ($this->fontDictionary as $f) {
            if ($font->isSameFont($f))
                return $f;
        }

        $this->fontDictionary[] = $font;
        return $font;
    }
}
