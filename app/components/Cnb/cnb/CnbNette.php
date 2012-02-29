<?php
/**
 *
 * @author Hakuna
 * @since 6.6.2010 - version 1.5.2
 * trida je urcena primo pro Nette
 * @example
 * 1) registrace nekde v presenteru
 * CnbNette::register($this->template, 'dph');
 * 2) v sablone lze pak uz volat jen
{!$a=10|currency} //10 Kč
<br/>
{!$a|vat} //12 Kč - cena s dph, defaultně 20%
<br/>
{!$currency} // proměnná pro url = curr
<br/>
{!$vat} // proměnná pro url = dph
//další možnosti formátovaní
{!$a|currency:eur:usd} //převede eura na dolary a zformatuje: $14.71
{!$a|currency:eur:usd:1.1} //to samé jen se zvedne cena o 10% třeba daň :) "kamarád stát"

//vstupní parametry helperu currency jsou stejné jako pro metodu format()
 */

class CnbNette extends NonObject
{

    static private $objCnb;
		static private $template;
    /**
     * name of helpers in template
     * @var string
     */
    static public $helperCurrency = 'currency';
    static public $helperVat = 'vat';

    /**
     * name class for instence
     * @var string
     */
    static public $nameClass = 'NCnb';

    /**
     * register Cnb to Nette
     * @param string|bool $vat param name of url for vat, TRUE - all price with VAT, FALSE - display VAT with helper vat
     * @param string $currency param name of url for currency
     */
    static public function register(NFileTemplate $template, $vat=false, $currency='curr')
    {
        self::$template = $template;
				$obj = self::getTemplate();
        $request =NEnvironment::getHttpRequest();
        $obj->registerHelper(self::$helperCurrency, __CLASS__ .'::currency');
        $obj->currency  =$currency;

        $dphNotBool = !is_bool($vat);
        if($dphNotBool)
        {
            $obj->vat       =$vat;
            $vat    =$request->getQuery($vat);
        }
        elseif($vat === false)
            $obj->registerHelper(self::$helperVat, __CLASS__ .'::vat');

        self::$objCnb   =new self::$nameClass($request->getQuery($currency), $vat);
        $obj->useRate = self::$objCnb->getRating();

        if($dphNotBool)
            $obj->globalVat = self::$objCnb->isVatEnable();
    }

    /**
     * @return Template
     */
    static protected function getTemplate()
    {
			return self::$template;
    }

    /**
     * @return Cnb
     */
    public static function getObj()
    {
        return self::$objCnb;
    }

//-------------helpers for template---------------------------------------------

    public static function currency($number, $from=false, $to=false, $vat=false)
    {
        return self::$objCnb->format($number, $from, $to, $vat);
    }

    public static function vat()
    {
        return self::$objCnb->formatVat();
    }
}