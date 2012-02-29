<?php
/**
 * This is library use for Nette with Nette features
 * @author Milan Matějček
 * @since 2010-06-10
 *
 */

//vyberte si zda budete vyuzivat historii ci nikoliv
//You choose history or without history?
class NCnb extends Cnb/*History*/
{
    /**
     * time for Nette session [s]
     * @var int
     */
    protected $netteExpiration   =1209600;

    /**
     * (non-PHPdoc)
     * @see cnb/Cnb#getTemp()
     */
    protected function getTemp()
    {
        return NEnvironment::getVariable('tempDir');
    }

    /**
     * (non-PHPdoc)
     * @see cnb/Cnb#isProduction()
     */
    protected function isProduction()
    {
        return NEnvironment::isProduction();
    }

    /**
     * (non-PHPdoc)
     * @see cnb/Cnb#setWebMoney($code)
     */
    protected function setWebMoney($code=false)
    {
        $session    =NEnvironment::getSession($this->sessionName);

        $session->setExpiration($this->netteExpiration, 'webMoney');

        if($code)
        {
            $code   =$this->loadCurrency($code);
        }
        else
        {
            $code   =isset($session->webMoney)? $session->webMoney: $this->defMoney;
            $this->loadRating($code);
        }
        $session->webMoney = $this->webMoney = $code;
    }

    /**
     * (non-PHPdoc)
     * @see cnb/Cnb#setGlobalVat($vat)
     */
    protected function setGlobalVat($vat)
    {
        $session    =NEnvironment::getSession($this->sessionName);

        if($vat === null)
        {
            if(isset($session->vat))
            {
               $this->globalVat =$session->vat;
            }
            else
            {
                $session->vat   =$this->globalVat;
            }
        }
        else
        {
            $this->globalVat = $session->vat = (bool)$vat;
        }


        $session->setExpiration($this->netteExpiration, 'vat');
    }

    /**
     * (non-PHPdoc)
     * @see cnb/Cnb#numberFormating($number, $to)

    protected function numberFormating($number, $to)
    {
        $this->rChange[0] = '<span>'. number_format($number,
            $this->rating[$to][self::DECIMAL],
            $this->rating[$to][self::DEC_POINT],
            $this->rating[$to][self::THOUSANDS]) .'</span>';
        return str_replace($this->rFound, $this->rChange, $this->rating[$to][ self::NUM_FORMAT ]);
    }*/

}