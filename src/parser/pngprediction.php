<?php

namespace Boajr\PDF\Parser;


class PNGPrediction
{
    private static function filterPaeth(int $a, int $b, int $c): int
    {
        $p = $a + $b - $c;
        $pa = $p > $a ? $p - $a : $a - $p;
        $pb = $p > $b ? $p - $b : $b - $p;
        $pc = $p > $c ? $p - $c : $c - $p;
        if ($pa <= $pb && $pa <= $pc)
            return $a;
        if ($pb <= $pc)
            return $b;
        return $c;
    }

    public static function decode(string $stream, int $bytePerRow, int $bitPerSample): string
    {
        if (strlen($stream) % $bytePerRow !== 0) {
            throw new PDFParserException('Stream length isn\'t a multiple of row length');
        }

        $rows = array_chunk(unpack('C*', $stream), $bytePerRow);
        $num_rows = count($rows);
        $delta = intval((7 + $bitPerSample) / 8);

        $lastRow = null;
        $stream = '';
        for ($i = 0; $i < $num_rows; ++$i) {
            $row = &$rows[$i];
            switch ($row[0]) {
                case 0:
                    break;

                case 1:
                    for ($j = $delta + 1; $j < $bytePerRow; ++$j) {
                        $row[$j] = ($row[$j] + $row[$j - $delta]) & 0xff;
                    }
                    break;

                case 2:
                    if ($i === 0)
                        break;

                    for ($j = 1; $j < $bytePerRow; ++$j) {
                        $row[$j] = ($row[$j] + $lastRow[$j]) & 0xff;;
                    }
                    break;

                case 3:
                    if ($i == 0) {
                        for ($j = $delta + 1; $j < $bytePerRow; ++$j)
                            $row[$j] = ($row[$j] + intval($row[$j - $delta] / 2)) & 0xff;
                        break;
                    }

                    for ($j = 1; $j <= $delta; ++$j) {
                        $row[$j] = ($row[$j] + intval($lastRow[$j] / 2)) & 0xff;
                    }
                    for (; $j < $bytePerRow; ++$j) {
                        $row[$j] = ($row[$j] + intval(($row[$j - $delta] + $lastRow[$j]) / 2)) & 0xff;
                    }
                    break;

                case 4:
                    if ($i == 0) {
                        for ($j = $delta + 1; $j < $bytePerRow; ++$j) {
                            $row[$j] = ($row[$j] + $row[$j - $delta]) & 0xff;
                        }
                        break;
                    }

                    for ($j = 1; $j <= $delta; ++$j) {
                        $row[$j] = ($row[$j] + $lastRow[$j]) & 0xff;
                    }
                    for (; $j < $bytePerRow; ++$j) {
                        $row[$j] = ($row[$j] + static::FilterPaeth($row[$j - $delta], $lastRow[$j], $lastRow[$j - $delta])) & 0xff;
                    }
                    break;

                default:
                    throw new PDFParserException('Unknown PNG Predictor (' . $row[0] . ')');
            }

            // salvo l'ultima riga per il prossimo passaggio
            $lastRow = &$row;

            // ricostruisco lo stream decodificato
            for ($j = 1; $j < $bytePerRow; ++$j) {
                $stream .= chr($row[$j]);
            }
        }

        $rows = null;

        return $stream;
    }
}
