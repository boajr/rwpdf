## A php class for create/modify/merge pdf

Il progetto è nato come un estensione di [FPDF](https://www.fpdf.org/), ma poi ho trovato che dal sito [PDF Association](https://pdfa.org/sponsored-standards/) è possibile scaricare lo standard 2.0 del PDF e allora ho iniziato a scrivere una libreria da zero, l'idea è quella di implementare tutti i metodi pubblici di FPDF, e poi le mie stensioni (non necessariamente in quest'ordine).

È un reinventare l'acqua calda? Non lo so, ma almeno in questo modo sto capendo un po' meglio come funzionano i PDF.

## Installation

Require this package in your composer.json and update composer. This will download the package.

```
composer config repositories.boajr-rwpdf vcs https://github.com/boajr/rwpdf
composer require boajr/rwpdf
```

## Using

## Configuration

## License

This library is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
