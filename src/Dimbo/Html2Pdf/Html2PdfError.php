<?php namespace Dimbo\Html2Pdf;

class Html2PdfError extends \RuntimeException
{
    function __construct($code, $message = null)
    {
        parent::__construct($message, $code);
    }
}
