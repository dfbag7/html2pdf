<?php namespace Dimbo\Html2Pdf;

use Illuminate\Support\Facades\Facade;

class Html2PdfFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'html2pdf';
    }
}
