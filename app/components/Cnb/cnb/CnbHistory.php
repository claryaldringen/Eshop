<?php

/**
 * stahovani histori avsak funguje i normalne, jen stahuje jen jeden kurzovni listek
 * @author Milan
 *
 */
class CnbHistory extends Cnb
{
    /**
     * parametr date funguje pouze pro Cnb::CNB_LIST
     * @var string
     */
    const CNB_DATE   ='?date=';

    /**
     *
     * @var date for download
     */
    private $time;

    /**
     * funkce je urcena pro precteni kurzovniho listku a
     * je potreba dodrzet $exchangeRate pro nacitani z php souboru
     *
     * @param string $webMoney -v jake mene se maji zobrazit castky
     * @param bool
     * @param date format DD.MM.YYYY
     * @return void
     */
    public function __construct($webMoney=0, $globalVat=false, $date=null)
    {
        $this->time = $date;
        parent::__construct($webMoney, $globalVat);
        if($this->time !== null)
            $this->actualClass  =Cnb::RATE_CLASS . str_replace('.', '', $this->time);
    }

    /**
     *
     * @return void
     */
    protected function loadList()
    {
        if($this->time === null)
            return parent::loadList();

        $new    =$this->getActualFile();

        if( !file_exists($new) )
        {
            $url    =Cnb::CNB_LIST . CnbHistory::CNB_DATE . $this->time;

            if(ini_get('allow_url_fopen'))
            {
                $cnb    =@file_get_contents($url);
            }
            elseif(extension_loaded('curl'))
            {
                $curl   =new CUrl($url);
                $cnb    =$curl->getResult();
            }
            else
            {
                throw new RuntimeException('This library need allow_url_fopen -enable or curl extension');
            }

            if( $cnb !== false )
            {
                $this->createCacheFile(self::stroke2point($cnb . $cnb2), $new);
            }
            else
            {
                throw new LogicException('You must connect to internet. It cant download rating list');
            }
        }

        if(!class_exists($this->actualClass))
            require_once $new;
    }
}
