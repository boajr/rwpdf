<?php

namespace Boajr\PDF\font;


class CFFDict_TopDict extends CFFDict
{
    protected function operator(int $op1, int $op2, array $params)
    {
        if ($op1 == 12) {
            print 'operator 12 ' . $op2 . ': ' . json_encode($params) . PHP_EOL;
        } else {
            switch ($op1) {
                case 0: // SID
                    print 'version: ' . $params[0] . PHP_EOL;
                    break;
                case 1: // SID
                    print 'Notice: ' . $params[0] . PHP_EOL;
                    break;
                case 2: // SID
                    print 'FullName: ' . $params[0] . PHP_EOL;
                    break;
                case 3: // SID
                    print 'FamilyName: ' . $params[0] . PHP_EOL;
                    break;
                case 4: // SID
                    print 'Weight: ' . $params[0] . PHP_EOL;
                    break;
                case 5: // array
                    print 'FontBBox: ' . json_encode($params) . PHP_EOL;
                    break;
                case 13: // number
                    print 'UniqueID: ' . $params[0] . PHP_EOL;
                    break;
                case 14: // array
                    print 'XUID: ' . json_encode($params) . PHP_EOL;
                    break;
                case 15: // number
                    print 'charset: ' . $params[0] . PHP_EOL;
                    break;
                case 16: // number
                    print 'Encoding: ' . $params[0] . PHP_EOL;
                    break;
                case 17: // number
                    print 'CharStrings: ' . $params[0] . PHP_EOL;
                    break;
                case 18: // number number
                    print 'Private: ' . $params[0] . ' ' . $params[1] . PHP_EOL;
                    break;
                default:
                    print 'operator ' . $op1 . ': ' . json_encode($params) . PHP_EOL;
                    break;
            }
        }
    }
}
