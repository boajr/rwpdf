<?php

namespace Boajr\PDF\Parser;


class DataStream
{
    /**
     * @var ?resource $file - pointer to file to import
     */
    private $file = null;

    /**
     * @var ?string $memory - memory block with data to parse
     */
    private $memory = null;

    /**
     * @var float $version - PDF version
     */
    private $version = 0;

    /**
     * @var int $offset - position in file of % character (PDF begin)
     */
    private $offset = 0;

    /**
     * @var int $end_of_file - end position of last %%EOF marker
     */
    private $end_of_file = 0;

    /**
     * @var int $pos - pointer to last read byte in $memory or in $read_data
     */
    private $pos = 0;

    /**
     * @var string $read_data - buffer for file reading
     */
    private $read_data = null;

    /**
     * @var int $read_offset - offset of first byte of $read_data
     */
    private $read_offset = 0;

    /**
     * @var int $read_length - size of read_data
     */
    private $read_length = 0;

    /**
     * @var ?DataStream $parent
     */
    private $parent = null;

    /**
     * @var array $xref - xref table
     */
    private $xref = [];

    /**
     * @var int $operator_offset - offset of starting byte of last operator search
     */
    private $operator_offset = 0;


    public function __construct(string $file_or_data, int $end_of_file = 0, ?DataStream $parent = null)
    {
        // Se passo un end_of_file, vuol dire che creo l'oggetto per leggere i dati da uno stream object e quindi non
        // devo cercare header ed altro
        if ($end_of_file) {
            $this->memory = $file_or_data;
            $this->end_of_file = $end_of_file;
            $this->parent = $parent;
            return;
        }

        // Secondo le specifiche della versione 2.0 l'header deve essere nella forma:
        //
        //    %PDF-M.m
        //
        // ma, ho trovato un pdf che inizia così:
        //
        //    %!PS−Adobe−N.n PDF−M.m
        //
        // L'header inoltre può essere in una posizione qualsiasi del file, e quindi devo memorizzarne la posizione
        // perché tutti gli offset vanno calcolati a partire dal carattere % e non dall'inizio del file

        $regExp = '/^(?:.|\r|\n)*?(%)(?:!PS−Adobe−(?:[1-9][0-9]*[.][0-9]+) ){0,1}PDF-([1-9][0-9]*[.][0-9]+)(?:\s|\r|\n)/';
        if (preg_match($regExp, $file_or_data, $matches, PREG_OFFSET_CAPTURE)) {
            $this->memory = $file_or_data;
            $this->version = floatval($matches[2][0]);
            $this->offset = $matches[1][1];

            // cerco la fine del file
            if (!preg_match_all('/(?:\r|\n)%%EOF/', $this->memory, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                throw new PDFParserException('unable to find %%EOF marker');
            }
            $matches = end($matches);
            $this->end_of_file = $matches[0][1] + strlen($matches[0][0]);
        } else {
            $this->file = @fopen($file_or_data, 'rb');
            if (!$this->file) {
                throw new PDFParserException('unable to open input file');
            }

            // leggo il file 1024 byte per volta finché non trovo un match.
            $buf = '';
            do {
                // ad ogni lettura tengo solo gli ultimi 128 byte, visto che la stringa della versione dovrebbe essere
                // lunga al massimo 18 caratteri più la lunghezza dei numeri della versione, non dovrei perdermi dei
                // dati utili
                if ($buf) {
                    $len = strlen($buf);
                    if ($len >= 128) {
                        $len -= 128;
                        $this->offset += $len;
                        $buf = substr($buf, $len);
                    }
                }

                // leggo 1024 byte dal file
                $tmp = @fread($this->file, 1024);
                if ($tmp === false) {
                    throw new PDFParserException('unable to read from file');
                }
                if ($tmp === '') {
                    throw new PDFParserException('unable to find pdf header in file');
                }

                $buf .= $tmp;
            } while (!preg_match($regExp, $buf, $matches, PREG_OFFSET_CAPTURE));

            // se arrivo qui vuol dire che ho trovato l'header
            $this->version = floatval($matches[2][0]);
            $this->offset += $matches[1][1];

            // calcolo la lunghezza del file
            if (@fseek($this->file, 0, SEEK_END) === -1) {
                throw new PDFParserException('unable to read from file');
            }

            $end = @ftell($this->file);
            if ($end === false) {
                throw new PDFParserException('unable to read from file');
            }

            // cerco l'ultima occorrenza di %%EOF
            $this->end_of_file = false;
            do {
                $end -= 1018; // rileggo sempre gli ultimi 6 caratteri
                if ($end < $this->offset) {
                    $end = $this->offset;
                }

                if (@fseek($this->file, $end, SEEK_SET) === -1) {
                    throw new PDFParserException('unable to read from file');
                }

                $tmp = @fread($this->file, 1024);
                if ($tmp === false) {
                    throw new PDFParserException('unable to read from file');
                }

                if (preg_match_all('/(?:\r|\n)%%EOF/', $tmp, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                    $matches = end($matches);
                    $this->end_of_file = $end + $matches[0][1] + strlen($matches[0][0]);
                }
            } while ($this->end_of_file === false && $end > $this->offset);

            if ($this->end_of_file === false) {
                throw new PDFParserException('unable to find %%EOF marker');
            }
        }
    }

    public function __destruct()
    {
        if ($this->file) {
            @fclose($this->file);
        }
    }

    /**
     * Get the PDF version parsed from header.
     */
    public function GetPDFVersion(): float
    {
        return $this->version;
    }

    /**
     * Get the size of trimmered PDF.
     */
    public function GetFileLength(): int
    {
        return $this->end_of_file - $this->offset;
    }

    /**
     * Read $length byte of data from the source, if $offset is negative, read from source end, otherwise begin from
     * source start.
     */
    public function ReadData(int $offset, ?int $length = null): string
    {
        if ($offset < 0) {
            // sottraggo alla lunghezza del file l'offset richiesto
            $offset += $this->end_of_file;

            // non posso leggere i dati che precedono l'header
            if ($offset < $this->offset) {
                $offset = $this->offset;
            }
        } else {
            // aggiungo all'offset richiesto quello dell'header
            $offset += $this->offset;

            // non posso leggere i dati oltre la fine del file
            if ($offset > $this->end_of_file) {
                $offset = $this->end_of_file;
            }
        }

        // verifico di non leggere dati oltre la fine del file
        if ($length === null || $length > $this->end_of_file - $offset) {
            $length = $this->end_of_file - $offset;
        }

        $this->pos = $offset;

        if ($this->memory) {
            if ($length === 0) {
                return '';
            }

            return substr($this->memory, $offset, $length);
        }

        if (@fseek($this->file, $offset, SEEK_SET) === -1) {
            throw new PDFParserException('unable to read from file');
        }

        if ($length === 0) {
            $this->read_data = '';
            $this->read_offset = $offset;
            $this->read_length = 0;
            return '';
        }

        $this->read_data = @fread($this->file, $length);
        if ($this->read_data === false) {
            throw new PDFParserException('unable to read from file');
        }

        $this->read_offset = $offset;
        $this->read_length = strlen($this->read_data);
        return $this->read_data;
    }

    /**
     * Read the next row from file, if $offset is negative, read from source end, otherwise begin from source start.
     *
     * @return string|bool the row string or false if eof is reached
     */
    public function ReadNextRow(?int $offset = null): string|bool
    {
        return $this->prossimo_token("\r\n", "\r\n", $offset);
    }

    /**
     * Read and parse the next object from file, if $offset is negative, read from source end, otherwise begin from 
     * source start.
     *
     * @return IPDFObject the object readed
     */
    public function ReadObject(?int $offset = null, int|false $objNum = false, int|false $genNum = false): IPDFObject
    {
        $row = $this->ReadNextRow($offset);
        if ($row === false) {
            throw new PDFParserEndOfFileException('unexpected end of file', $this->operator_offset);
        }

        if (!preg_match('/^([0-9]+) ([0-9]+) (obj)/', $row, $matches, PREG_OFFSET_CAPTURE)) {
            throw new PDFParserException('invalid object header');
        }

        if ($objNum !== false && $objNum !== intval($matches[1][0])) {
            throw new PDFParserException('invalid object number');
        }

        if ($genNum !== false && $genNum !== intval($matches[2][0])) {
            throw new PDFParserException('invalid generation number');
        }

        $this->pos -= strlen($row) - $matches[3][1] - 3;
        return $this->ReadObjectData();
    }

    /**
     * Read and parse the next object from file, if $offset is negative, read from source end, otherwise begin from 
     * source start.
     *
     * @return IPDFObject the object readed
     */
    public function ReadObjectData(string $end = 'endobj', ?int $offset = null): IPDFObject
    {
        // un oggetto può essere o un elemento base, o un array, o un dizionario seguito o meno da uno stream leggo
        // quindi il primo elemento
        $obj = $this->prossimo_oggetto($offset);
        if ($obj === false) {
            throw new PDFParserEndOfFileException('unexpected end of file', $this->operator_offset);
        }

        // se la funzione ritorna un token, se è quello di chiusura ritorno l'oggetto null (anche se non sarebbe
        // corretto), altrimenti sollevo un'eccezione
        if (is_string($obj)) {
            if (strcmp($obj, $end) === 0) {
                return new PDFObjectNull();
            }
            throw new PDFParserException('unknown token in object: ' . $obj);
        }

        // ora che ho in memoria l'oggetto verifico che il token successivo sia quello di chiusura
        $token = $this->prossimo_oggetto();
        if ($token === false) {
            throw new PDFParserEndOfFileException('unexpected end of file', $this->operator_offset);
        }

        // se è il token di chiusura restituisco l'oggetto prelevato
        if (is_string($token) && strcmp($token, $end) === 0) {
            return $obj;
        }

        // altrimenti sollevo un'eccezione
        throw new PDFParserException('invalid object: too much element in base struct');
    }

    /**
     * Read next operators in contents stream
     * 
     * @return ?PDFOperator
     */
    public function ReadOperator(): ?PDFOperator
    {
        $this->operator_offset = $this->read_offset + $this->pos;
        $params = [];
        while ($next = $this->prossimo_oggetto()) {
            if ($next instanceof IPDFObject) {
                $params[] = $next;
                continue;
            }
            return new PDFOperator($next, $params);
        }

        if (count($params))
            throw new PDFParserEndOfFileException('unexpected end of file', $this->operator_offset);

        return null;
    }

    /**
     * Legge al massimo 1024 bytes dal file.
     * 
     * @return string i dati letti
     */
    private function leggi_da_file(int $length): string
    {
        if ($length <= 0)
            return '';

        $buf = @fread($this->file, $length < 1024 ? $length : 1024);
        if ($buf === false) {
            throw new PDFParserException('unable to read from file');
        }

        return $buf;
    }

    /**
     * Read the next token from file, if $offset is negative, read from source end, otherwise begin from source start.
     *
     * @return string|bool the token string or false if eof is reached
     */
    private function prossimo_token(string $startSep, string $endSep, ?int $offset = null): string|bool
    {
        // normalizzo l'offset
        if ($offset === null) {
            // leggo partendo dall'offset precedente
            $offset = $this->read_offset + $this->pos;
        } else if ($offset < 0) {
            // sottraggo alla lunghezza del file l'offset richiesto
            $offset += $this->end_of_file;
        } else {
            // aggiungo all'offset richiesto quello dell'header
            $offset += $this->offset;
        }

        // non posso leggere i dati che precedono l'header
        if ($offset < $this->offset) {
            $offset = $this->offset;
        }

        if ($this->memory) {
            // scarto tutti gli a capo
            $offset += strspn($this->memory, $startSep, $offset, $this->end_of_file - $offset);

            // memorizzo l'inizio della stringa
            $this->pos = $offset;

            // se ho finito la memoria restituisco false
            if ($offset >= $this->end_of_file) {
                return false;
            }

            // mi sposto al carattere successivo, perché il primo carattere non è mai un separatore
            ++$this->pos;

            // scorro la stringa fino al primo a capo
            $this->pos += strcspn($this->memory, $endSep, $this->pos, $this->end_of_file - $this->pos);
            if ($this->pos <= $offset)
                return false;

            return substr($this->memory, $offset, $this->pos - $offset);
        }

        // se l'offset ricade dentro ai dati letti mi posiziono nel buffer, altrimenti leggo un nuovo buffer
        if ($offset >= $this->read_offset && $offset < $this->read_offset + $this->read_length) {
            $offset -= $this->read_offset;
        } else {
            if (@fseek($this->file, $offset, SEEK_SET) === -1) {
                throw new PDFParserException('unable to read from file');
            }
            $this->read_data = $this->leggi_da_file($this->end_of_file - $offset);
            $this->read_offset = $offset;
            $this->read_length = strlen($this->read_data);
            $offset = 0;
        }

        // scarto tutti gli a capo
        do {
            $offset += strspn($this->read_data, $startSep, $offset, $this->read_length - $offset);

            if ($offset >= $this->read_length) {
                // butto via quanto letto finora e leggo altri 1024 byte
                $this->read_data = $this->leggi_da_file($this->end_of_file - ($this->read_offset + $offset));
                $this->read_offset += $offset;
                $this->read_length = strlen($this->read_data);
                $offset = 0;
            }
        } while ($offset < $this->read_length && strpos($startSep, $this->read_data[$offset]) !== false);

        // memorizzo l'inizio della stringa
        $this->pos = $offset;

        // se ho finito la memoria restituisco false
        if ($offset >= $this->read_length) {
            return false;
        }

        // mi sposto al carattere successivo, perché il primo carattere non è mai un separatore
        ++$this->pos;

        do {
            // scorro la stringa fino al primo a capo
            $this->pos += strcspn($this->read_data, $endSep, $this->pos, $this->read_length - $this->pos);

            if ($this->pos >= $this->read_length) {
                // tengo in memoria solo quello che posso restituire
                if ($offset) {
                    $this->read_data = substr($this->read_data, $offset);
                    $this->read_offset += $offset;
                    $this->read_length -= $offset;
                    $this->pos -= $offset;
                    $offset = 0;
                }

                // concateno altri 1024 byte a quanto letto finora
                $tmp = $this->leggi_da_file($this->end_of_file - ($this->read_offset + $this->pos));
                $this->read_data .= $tmp;
                $this->read_length += strlen($tmp);
            }
        } while ($this->pos < $this->read_length && strpos($endSep, $this->read_data[$this->pos]) === false);

        if ($this->pos <= $offset)
            return false;

        return substr($this->read_data, $offset, $this->pos - $offset);
    }

    /**
     * Read next byte from memory
     * 
     * @return string|bool the next byte or false if eof
     */
    private function prossimo_byte(): string|bool
    {
        $offset = $this->read_offset + $this->pos;
        if ($offset >= $this->end_of_file) {
            return false;
        }

        if ($this->memory) {
            return $this->memory[$this->pos++];
        }

        if ($this->pos >= $this->read_length) {
            // tengo in memoria solo quello che posso restituire
            //if ($offset) {
            //    $this->read_data = substr($this->read_data, $offset);
            //    $this->read_offset += $offset;
            //    $this->read_length -= $offset;
            //    $this->pos -= $offset;
            //    $offset = 0;
            //}

            // concateno altri 1024 byte a quanto letto finora
            $tmp = $this->leggi_da_file($this->end_of_file - ($this->read_offset + $this->pos));
            $this->read_data .= $tmp;
            $this->read_length += strlen($tmp);
        }

        return $this->pos >= $this->read_length ? false : $this->read_data[$this->pos++];
    }

    private static function hex_to_num(string $ch): int|bool
    {
        if ($ch >= '0' && $ch <= '9') {
            return ord($ch) - 48;
        }
        if ($ch >= 'A' && $ch <= 'F') {
            return ord($ch) - 55;
        }
        if ($ch >= 'a' && $ch <= 'f') {
            return ord($ch) - 87;
        }
        return false;
    }

    private function prossimo_token_no_commento(?int $offset = null): string|bool
    {
        $buf = $this->prossimo_token(" \t\r\n\f", " \t\r\n\f()<>[]/%", $offset);
        if ($buf === false) {
            return false;
        }

        // elimino tutti i commenti
        while ($buf[0] === '%') {
            // se ho finito il file non ci sono più token dopo il commento
            if ($this->read_offset + $this->pos >= $this->end_of_file) {
                return false;
            }

            // ho sempre il carattere successivo in read_data perché la prossimo_token ne ha già verificato l'esistenza
            $ascii = ord($this->memory ? $this->memory[$this->pos] : $this->read_data[$this->pos]);

            // se il token non finisce in un fine riga, leggo dal buffer fino al fine riga
            if ($ascii !== 13 && $ascii !== 10) {
                $buf = $this->prossimo_token("\r\n", "\r\n");
                if ($buf === false) {
                    return false;
                }
            }

            // leggo il prossimo token
            $buf = $this->prossimo_token(" \t\r\n\f", " \t\r\n\f()<>[]/%");
            if ($buf === false) {
                return false;
            }
        }

        return $buf;
    }

    public function prossimo_oggetto(?int $offset = null): IPDFObject|string|bool
    {
        $buf = $this->prossimo_token_no_commento($offset);
        if ($buf === false) {
            return false;
        }

        // verifico se il token è l'apertura di un dizionario
        if ($buf === '<' && ($this->memory ? $this->memory[$this->pos] : $this->read_data[$this->pos]) === '<') {
            ++$this->pos;
            return $this->leggi_dictionary();
        }

        // il token ha sempre lunghezza di almeno un carattere
        if ($buf[0] === '<') {
            // mi assicuro di avere in memoria il testo fino al primo maggiore
            if (($this->memory ? $this->memory[$this->pos] : $this->read_data[$this->pos]) !== '>') {
                $tmp = $this->prossimo_token('', '>');
                if ($tmp === false) {
                    throw new PDFParserException('invalid hexadecimal string object');
                }
                $buf .= $tmp;
            }

            $len = strlen($buf);
            $out = '';
            $primo = true;
            $ascii = 0;
            for ($i = 1; $i < $len; ++$i) {
                $ch = $buf[$i];
                if (strpos(" \t\r\n\f", $ch) !== false)
                    continue;

                $v = static::hex_to_num($ch);
                if ($v === false) {
                    throw new PDFParserException('invalid hexadecimal string object');
                }

                if ($primo) {
                    $ascii = $v * 16;
                    $primo = false;
                } else {
                    $out .= chr($ascii + $v);
                    $primo = true;
                    $ascii = 0;
                }
            }

            if (!$primo)
                $out .= chr($ascii);

            ++$this->pos;
            return new PDFObjectString($out);
        }

        if ($buf[0] === '(') {
            // mi assicuro di avere in memoria il testo fino alla prima parentesi chiusa
            if (($this->memory ? $this->memory[$this->pos] : $this->read_data[$this->pos]) !== ')') {
                // leggo fino alla prima parentesi chiusa
                $tmp = $this->prossimo_token('', ')');
                if ($tmp === false) {
                    throw new PDFParserException('invalid hexadecimal string object');
                }
                $buf .= $tmp;
            }

            // processo il buffer dal secondo carattere dato che il primo è una parentesi aperta
            $parentesi = 1;
            $i = 1;
            $out = '';
            while ($parentesi > 0) {
                // aggiungo al buffer una parentesi chiusa, visto che la funzione prossimo_token non la restituisce
                $buf .= ')';
                $len = strlen($buf);

                // scorro tutto il buffer
                while ($i < $len) {
                    // leggo e consumo il carattere
                    $ch = $buf[$i++];

                    // se è una sequenza di escape la processo
                    if ($ch === "\\") {
                        // leggo, ma non consumo il carattere successivo
                        $ch = $buf[$i];

                        // verifico se è l'inizio di un numero ottale, di al massimo 3 numeri
                        if ($ch >= '0' && $ch <= '9') {
                            // mi sposto al carattere successivo e lo leggo
                            $nch = $buf[++$i];

                            // verifico se è la seconda cifra
                            if ($nch >= '0' && $nch <= '9') {
                                // concateno le prime due cifre
                                $ch .= $nch;

                                // mi sposto al carattere successivo e lo leggo
                                $nch = $buf[++$i];

                                // verifico se è la terza cifra
                                if ($nch >= '0' && $nch <= '9') {
                                    // concateno le tre cifre e consumo il carattere
                                    $ch .= $nch;
                                    ++$i;
                                }
                            }

                            // converto il valore ottale in un carattere
                            $out .= chr(octdec($ch));
                            continue;
                        }

                        // se non è un numero verifico se è una sequenza conosciuta
                        switch ($ch) {
                            case 'n':
                                $out .= "\n";
                                ++$i;
                                break;
                            case 'r':
                                $out .= "\r";
                                ++$i;
                                break;
                            case 't':
                                $out .= "\t";
                                ++$i;
                                break;
                            case 'b':
                                $out .= "\010";
                                ++$i;
                                break;
                            case 'f':
                                $out .= "\f";
                                ++$i;
                                break;
                            case '(':
                                $out .= '(';
                                ++$i;
                                break;
                            case ')':
                                $out .= ')';
                                ++$i;
                                break;
                            case "\\":
                                $out .= "\\";
                                ++$i;
                                break;
                            case "\r":
                                // verifico se è un a capo nella forma \r\n, in questo caso consumo 2 caratteri
                                if ($buf[++$i] === "\n") {
                                    ++$i;
                                }
                                break;
                            case "\n":
                                ++$i;
                                break;
                        }

                        // qui ho consumato il backslash e gli eventuali caratterio della sequenza di escape, passo a
                        // verificare il prossimo carattere
                        continue;
                    }

                    // se arrivo qui, vuol dire che non ho una sequenza di escape. Verifico se ho un acapo nella forma
                    // \r o \r\n
                    if ($ch === "\r") {
                        if ($buf[$i] === "\n") {
                            ++$i;
                        }

                        // trasforma l'acapo in un \n
                        $out .= "\n";
                        continue;
                    }

                    if ($ch === '(') {
                        // se ho una parentesi aperta incremento il conteggio delle parentesi
                        ++$parentesi;
                    } else if ($ch === ')') {
                        // se ho una parentesi chiusa decremento il conteggio delle parentesi
                        --$parentesi;
                        if ($parentesi === 0) {
                            if ($i < $len) {
                                throw new PDFParserException('invalid literal string object (impossible)');
                            }
                            break;
                        }
                    }

                    // copio il carattere nella stringa di uscita
                    $out .= $ch;
                }

                // se non ho terminato la stringa leggo fino alla prossima parentesi chiusa
                if ($parentesi > 0) {
                    $buf = $this->prossimo_token('', ')');
                    if ($buf === false) {
                        throw new PDFParserException('invalid literal string object');
                    }
                    $i = 1;
                }
            }

            ++$this->pos;
            return new PDFObjectString($out);
        }

        if ($buf[0] === '[') {
            $this->pos -= (strlen($buf) - 1);
            return $this->leggi_array();
        }

        if ($buf[0] === '/') {
            $len = strlen($buf);
            $out = '';
            for ($i = 1; $i < $len; ++$i) {
                if ($buf[$i] === '#') {
                    if ($i >= $len - 2) {
                        throw new PDFParserException('invalid name string object');
                    }
                    $out .= chr(static::hex_to_num($buf[$i + 1]) << 4 | static::hex_to_num($buf[$i + 2]));
                    continue;
                }

                $out .= $buf[$i];
            }

            return new PDFObjectName($out);
        }

        if (is_numeric($buf)) {
            if (is_float($buf + 0)) {
                return new PDFObjectNumber(floatval($buf));
            } else {
                // per verificare se è un riferimento ad oggetto, verifico che i prossimo byte contengano uno spazio, 
                // da 1 a 5 cifre, uno spazio e un separatore

                // memorizzo l'int appena letto
                $int = intval($buf);

                // memorizzo l'offset attuale
                $offset = $this->read_offset + $this->pos;

                // verifico che il prossimo byte sia uno spazio
                if ($this->prossimo_byte() !== ' ') {
                    $this->pos = $offset - $this->read_offset;
                    return new PDFObjectNumber($int);
                }

                $int2 = 0;
                $almeno_uno = false;
                while (1) {
                    $ch = $this->prossimo_byte();
                    if ($ch === ' ') {
                        break;
                    }

                    if ($ch < '0' || $ch > '9') {
                        $this->pos = $offset - $this->read_offset;
                        return new PDFObjectNumber($int);
                    }

                    $almeno_uno = true;
                    $int2 = $int2 * 10 + ord($ch) - 48;
                }

                if (!$almeno_uno || $this->prossimo_byte() !== 'R' || strpos(" \t\r\n\f()<>[]/%", $this->prossimo_byte()) === false) {
                    $this->pos = $offset - $this->read_offset;
                    return new PDFObjectNumber($int);
                }

                --$this->pos;
                return new PDFObjectReference($this->parent ?: $this, $int, $int2);
            }
        }

        if ($buf === 'true') {
            return new PDFObjectBool(true);
        }

        if ($buf === 'false') {
            return new PDFObjectBool(false);
        }

        if ($buf === 'null') {
            return new PDFObjectNull();
        }

        return $buf;
    }

    private function leggi_array(): PDFObjectArray
    {
        $array = new PDFObjectArray();
        while (1) {
            $token = $this->prossimo_oggetto();
            if ($token === false) {
                throw new PDFParserEndOfFileException('unexpected end of file', $this->operator_offset);
            }

            // se la funzione ritorna un token, se è quello di chiusura ritorno l'array altrimenti sollevo un'eccezione
            if (is_string($token)) {
                if ($token[0] == ']') {
                    $this->pos -= (strlen($token) - 1);
                    return $array;
                }
                throw new PDFParserException('unknown token in array: ' . $token);
            }

            // aggiungo l'oggetto all'array
            $array[] = $token;
        }
    }

    private function leggi_dictionary(): PDFObjectDictionary
    {
        $dict = new PDFObjectDictionary();
        while (1) {
            $key = $this->prossimo_oggetto();
            if ($key === false) {
                throw new PDFParserEndOfFileException('unexpected end of file', $this->operator_offset);
            }

            // se la funzione ritorna un token, se è quello di chiusura ritorno il dictionary altrimenti sollevo
            // un'eccezione
            if (is_string($key)) {
                if ($key === '>' && ($this->memory ? $this->memory[$this->pos] : $this->read_data[$this->pos]) === '>') {
                    ++$this->pos;

                    // controllo se ho un token successivo e se è 'stream'
                    $stream = $this->prossimo_token_no_commento();
                    if ($stream === 'stream') {
                        $sep = $this->prossimo_byte();
                        if ($sep === "\r") {
                            $sep = $this->prossimo_byte();
                        }
                        if ($sep !== "\n") {
                            throw new PDFParserException('invalid stream');
                        }

                        // aggiungo lo stream al dizionario
                        $offset = $this->read_offset + $this->pos - $this->offset;
                        $dict->AddStream($this, $offset);

                        $length = $dict['Length'];
                        if (!$length || $length->GetFinalType() !== IPDFObject::TYPE_INT) {
                            throw new PDFParserException('invalid stream');
                        }

                        // verifico che lo stream finisca correttamente
                        $sep = $this->prossimo_token(" \t\r\n\f", " \t\r\n\f()<>[]/%", $offset + $length->GetFinalValue());
                        if ($sep !== 'endstream') {
                            throw new PDFParserException('invalid stream');
                        }
                    } else {
                        $this->pos -= strlen($stream);
                    }

                    return $dict;
                }
                throw new PDFParserException('unknown token in dictionary: ' . $key);
            }

            if ($key->GetType() !== IPDFObject::TYPE_NAME) {
                throw new PDFParserException('dictionary field name isn\'t a name object');
            }

            // leggo l'oggetto vero e proprio
            $obj = $this->prossimo_oggetto();
            if ($obj === false) {
                throw new PDFParserEndOfFileException('unexpected end of file', $this->operator_offset);
            }

            // se la funzione ritorna un token sollevo un'eccezione
            if (is_string($obj)) {
                throw new PDFParserException('invalid dictionary object');
            }

            // salvo l'entry nel dizionario
            $dict[$key->GetValue()] = $obj;
        }
    }





    public function AddXRefEntry(int $objNum, int $type, int $offset, int $genNum): void
    {
        if (isset($this->xref[$objNum])) {
            return;
        }

        switch ($type) {
            case 0: // oggetto non usato
                $this->xref[$objNum] = [
                    'type' => 0
                ];
                break;

            case 1: // oggetto indipendente
                $this->xref[$objNum] = [
                    'type' => 1,
                    'offset' => $offset,
                    'genNum' => $genNum,
                    'object' => null
                ];
                break;

            case 2: // oggetto compresso inserito in uno stream
                $this->xref[$objNum] = [
                    'type' => 2,
                    'container' => $offset,
                    'obj_index' => $genNum,
                    'object' => null
                ];
                break;
        }
    }

    public function ReadObjectByXRef(int $objNum, int $genNum): IPDFObject
    {
        if (!isset($this->xref[$objNum]) || $this->xref[$objNum]['type'] === 0) {
            return new PDFObjectNull();
        }

        if (!$this->xref[$objNum]['object']) {
            switch ($this->xref[$objNum]['type']) {
                case 1:
                    if ($genNum !== $this->xref[$objNum]['genNum']) {
                        throw new PDFParserException('Wrong generation number');
                    }
                    $this->xref[$objNum]['object'] = $this->ReadObject($this->xref[$objNum]['offset'], $objNum, $this->xref[$objNum]['genNum']);
                    break;

                case 2:
                    /**
                     * @var PDFObjectDictionary $c
                     */
                    $c = $this->ReadObjectByXRef($this->xref[$objNum]['container'], $this->xref[$this->xref[$objNum]['container']]['genNum']);
                    $this->xref[$objNum]['object'] = $c->ReadObject($this->xref[$objNum]['obj_index'], $objNum);
                    break;

                default:
                    throw new \Exception('TODO: Tipo di oggetto ancora da gestire: ' . $this->xref[$objNum]['type']);
            }
        }

        return $this->xref[$objNum]['object'];
    }
}
