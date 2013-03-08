<?php
/**
 * PHP > 5.2.3
 *
 * @author Milan Matějček
 * @since 2009-06-22 - version 0.5
 * @version 1.8.3
 */
class Cnb extends NControl
{
    /**
     * number of version
     * @var string
     */
    static private $version =false;

		private $tempdir;

    /**
     * key from array $this->rating
     */
    const RATE      ='rate';
    const NUM_FORMAT='format';
    const DECIMAL   ='decimal';
    const DEC_POINT ='decpoint';
    const THOUSANDS ='thousands';
    const SYMBOL    ='symbol';
    const CODE      =false;
    const COUNTRY   =false;//only czech
    const NAME      =false;//only czech
    const FROM1     =false;
    const TO        =false;

    /**
     * url where download rating
     * @var const
     */
    const CNB_LIST  ='http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt';
    const CNB_LIST2 ='http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_ostatnich_men/kurzy.txt';
    /**
     * @deprecated only for develop
     * const CNB_LIST  ='denni_kurz.txt';
     * const CNB_LIST2 ='kurzy.txt';
     */

    /**
     * include czech rating !important
     * @var const
     */
    const CNB_CZK   ='Česká Republika|Koruna česká|1|CZK|1';

    /**
     * @var const delimiter in self::CNB_CZK
     */
    const PIPE      ='|';

    /**
     * name of cache file and name of static class
     * @var const string
     */
    const RATE_CLASS ='ExchangeRate';

    /**
     * explicit formating number for rating, use UPPERCASE key in array
     * @var array
     */
    public static $defineMoney   =array(
                                    'CZK'=>array('1 Kč', 0, ',', ' '),
                                    'EUR'=>array('1€', 2, ',', '.'),
                                    'USD'=>array('$1', 2, '.', ','),
                                    'GBP'=>array('£1', 2, '.', ''),
                                    'PLN'=>'1 Zł',///load default formating
                                    );

    /**
     * load both list of currency
     * @var bool - false download only Cnb::CNB_LIST
     */
    protected $loadBoth = true;

    /**
     * default number format
     * @var array
     */
    protected $defaultFormat =array(2, ',', ' ');

    /**
     * corection for rating, up or down [%], for czech eshop is recommended 1.03 is different beetwen buy and middle rate
     * @example 5% = 1.05, -5% = 0.95
     * @var number
     */
    protected $correction    =1;

    /**
     * define case size of code, 0-lowercase, 1-uppercase, another int-Firstletter
     * @var int
     */
    protected $fontSize  =2;

    /**
     * time for reload cache [s]
     * @var int
     */
    protected $refresh   =86400;

    /**
     * namespace session in Nette
     * @var string
     */
    protected $sessionName='ExchangeRate';

    /**
     * letter corection by str_replace
     * @var array
     */
    protected $rFound   =array(1, ' ');
    protected $rChange  =array('', "\xc2\xa0");

    /**
     * default money on web
     * @var string
     */
    protected $defMoney   ='CZK';

    /**
     * show actual money for web and is first in array $this->rating
     * @var string
     */
    protected $webMoney;

    /**
     * class for history
     * @var string
     */
    protected $actualClass;

    /**
     * saved letter size
     * @var function
     */
    protected $strTo;

    /**
     * vat, only prefered value [%]
     * @example 20% = 1.20
     * @var real
     */
    protected $vat  =1.2;

    /**
     * method self::format() return price with vat if set on TRUE
     * @var bool
     */
    protected $globalVat    =false;

    /**
     * last working value
     * @var array
     */
    protected $lastChange=array(null, null);

    /**
     * rating list
     * @var array
     */
    protected $rating=array();

    /**
     * make all setup
     * @param string  set output currency
     * @param boolean set global vat
     * @return void
     */
    public function __construct($tempdir, $webMoney=0, $globalVat=false)
    {
        $this->tempdir = $tempdir;
				$this->setGlobalVat($globalVat);
        $this->actualClass  =Cnb::RATE_CLASS;
        $strTo =$this->strTo =self::strTo($this->fontSize);
        $this->defMoney =$strTo($this->defMoney);
        $this->loadList();
        $this->setWebMoney($webMoney);
        if($this->defMoney != $this->webMoney)
            $this->loadRating($this->defMoney);
    }

    /**
     * is fast getter for information, name for function is same as value of constant upper
     * @example $this->getDecPoint('eur');
     * @param $name value of constant
     * @param $args currency
     * @return mixed
     */
    public function __call($name, $args)
    {
        $args   =(!empty($args))? $args[0]: false;

        $name   =strtolower($name);

        if(substr($name, 0, 3) == 'get')
        {
            $name   =substr($name, 3);
        }

        $val    =$this->getMoney($args, $name);

        if(empty($val))
        {
           throw new MemberAccessException('This is not value of constant in this class "'. $name .'"!');
        }

        return $val;
    }

    /**
     * return value of $this->$rating array, you can see __call()
     * @param string $code currency
     * @param string $key use value of constant upper, rate, code
     * @return mix
     */
    public function getMoney($code=false, $key=false)
    {
        $code   =$code? $this->loadCurrency($code): $this->webMoney;

        if($key === false)
        {
            return $this->rating[$code];
        }

        return $this->rating[$code][$key];
    }

    /**
     * actual currency of output for web
     * @return string
     */
    public function getWebMoney()
    {
        return $this->webMoney;
    }

    /**
     * default currency
     * @return string
     */
    public function getDefMoney()
    {
        return $this->defMoney;
    }

    /**
     * all currency for use
     * @return array
     */
    public function getAllCode($codeSort = false)
    {
        $reflection = new ReflectionClass($this->actualClass);
        $array = $reflection->getMethods(ReflectionMethod::IS_STATIC);
        array_walk($array, __CLASS__ .'::obj2array');
        if($codeSort)
        {
            sort($array);
            reset($array);
        }
        $key    =array_search('TRY1', $array);
        $array[$key]    ='TRY';
        return $array;
    }

    /**
     * currency list
     * @param string ... [arg1, arg2 OR array(arg1, arg2, ...) OR bool] -code of currency
     * @return array
     */
    public function & getRating()
    {
        $args   =func_get_args();

        if(empty($args))
        {
            $args   =array_keys(self::$defineMoney);
        }
        elseif($args[0] === true)
        {
            return $this->rating;
        }
        elseif($args[0] === false)
        {
            $args   =$this->getAllCode();
        }
        elseif(is_array($args[0]))
        {
            $args   =$args[0];
        }

        foreach((array) $args as $val)
        {
            $this->loadCurrency($val);
        }

        return $this->rating;
    }

    /**
     * date of last download cache file
     * @param string $format -for format used function date()
     * @return date|timestamp
     */
    public function getDate($format=false)
    {
        $time  =filemtime($this->getActualFile());

        if($format != false)
        {
            $time =date($format, $time);
        }

        return $time;
    }

    /**
     * value prefered vat [%]
     * @return real
     */
    public function getVat()
    {
        return ($this->vat * 100)-100;
    }

    /**
     * compare with output currency
     * @param $code code of currency, careful case sensitivity
     * @return boolean
     */
    public function isActual($code)
    {
        return ($code == $this->webMoney);
    }

    /**
     * global vat is eneble
     * @return bool
     */
    public function isVatEnable()
    {
        return $this->globalVat;
    }

    /**
     * transfer number by exchange rate
     * @param double|int|string $price number
     * @param string $from default currency
     * @param string $to output currency
     * @param int $round number round
     * @return double
     */
    public function change($price, $from=false, $to=false, $round=false)
    {
        if(is_string($price))
        {
            $price    =(double)self::stroke2point($price);
        }

        $from   =(!$from)? $this->defMoney: $this->loadCurrency($from);

        $to     =(!$to)? $this->webMoney: $this->loadCurrency($to);

        $price =$this->rating[$to][self::RATE] / $this->rating[$from][self::RATE] * $price;

        if($round !== false)
        {
            $price =round($price, $round);
        }

        return $price;
    }

    /**
     * @see Cnb::loadRating()
     * @param string $code
     * @return string
     */
    public function loadCurrency($code)
    {
        $strTo  =$this->strTo;
        return $this->loadRating($strTo($code));
    }

    /**
     * count, format price and set vat
     * @param $number int|double|string price
     * @param $from string|bool TRUE currency doesn't counting, FALSE set actual
     * @param $to string output currency, FALSE set actual
     * @param $vat bool|real use vat, but get vat by method $this->formatVat(), look at to globatVat upper
     * @return nuber string
     */
    public function format($number, $from=false, $to=false, $vat=false)
    {
        if($to != false)
        {
            $old    =$this->webMoney;
            $to     =$this->loadCurrency($to);
            $this->webMoney =$to;
        }


        if($from !== true)
        {
            $number =$this->change($number, $from, $to);
        }

        $getVat =false;
        if($vat === true)
        {
            $getVat =true;
            $vat    =$this->vat;
        }
        elseif($vat === false)
        {
            $vat    =$this->vat;
        }
        else
        {
            $vat    =(double)$vat;
        }

        $withVat    =$number * $vat;

        if($this->globalVat || $getVat)
        {
            $number =$withVat;
        }

        $number =$this->numberFormating($number, $this->webMoney);

        if($to != false)
        {
            $this->webMoney =$old;
        }
        else
        {
            $to =$this->webMoney;
        }

        $this->lastChange   =array($withVat, $to);
        return $number;
    }

    /**
     * before call this method MUST call method format()
     * formating price only with vat
     * @return string
     */
    public function formatVat()
    {
        return $this->numberFormating($this->lastChange[0], $this->lastChange[1]);
    }

    /**
     * delete cache file
     * @return bool
     */
    public function deleteTemp()
    {
        $new    =$this->getActualFile();
        return self::rename($new, $this->getOldFile($new));
    }

    /**
     * actual cache file
     * @return string
     */
    protected function getActualFile()
    {
        return $this->getTemp() . DIRECTORY_SEPARATOR . $this->actualClass . '.php';
    }

    /**
     * path to backup cache file
     * @return string
     */
    protected function getOldFile($actualFile)
    {
        return $actualFile .'.txt';
    }

    /**
     * path of temp
     * @return string
     */
    protected function getTemp()
    {
        $this->tempdir;
    }

    /**
     * setup session for global vat
     * @param bool $vat true|false|null
     * @return void
     */
    protected function setGlobalVat($vat)
    {
        $this->globalVat    =$vat;
    }

    /**
     * create symbol of currency
     * @param string $string
     * @return string
     */
    protected function setSymbol($string)
    {
        return str_replace($this->rFound, '', $string);
    }

    /**
     * setup currency for web and session
     * @param string $code
     * @return void
     */
    protected function setWebMoney($code=false)
    {
        if($code)
        {
            $code   =$this->loadRating($code);
            $_SESSION[$this->sessionName]  =$code;
        }
        else
        {
            $code   =(isset($_SESSION[$this->sessionName]))? $_SESSION[$this->sessionName]: $this->defMoney;
        }

        $this->webMoney =$code;
        if($this->webMoney != $this->defMoney)
            $this->loadRating($this->webMoney);
    }

    /**
     * without session
     */
//    protected function setWebMoney($code=false)
//    {
//        $this->webMoney =($code == false)? $this->defMoney: $this->loadRating($code);
//    }

    /**
     * environment TRUE is production
     * @return bool
     */
    protected function isProduction()
    {
        return true;
    }

    /**
     * download new currency
     * @return void
     */
    protected function loadList()
    {
        $new    =$this->getActualFile();
        $old    =$this->getOldFile($new);

        if((!file_exists( $new ) || (time() - @filemtime( $new ) > $this->refresh)))
        {
            $cnb2   =true;
            if(ini_get('allow_url_fopen'))
            {
                $cnb    =@file_get_contents(Cnb::CNB_LIST);
                if($this->loadBoth)
                    $cnb2   =@file_get_contents(Cnb::CNB_LIST2);
            }
            elseif(extension_loaded('curl'))
            {
                $curl   =new CUrl(Cnb::CNB_LIST);
                $cnb    =$curl->getResult();
                if($this->loadBoth)
                {
                    $curl   =new CUrl(Cnb::CNB_LIST2);
                    $cnb2   =$curl->getResult();
                }
            }
            else
            {
                throw new RuntimeException('This library need allow_url_fopen -enable or curl extension');
            }

            if( $cnb !== false && $cnb2 !== false )
            {
                self::rename($new, $old);
                $this->createCacheFile(self::stroke2point($cnb . $cnb2), $new);
            }
            elseif(($exis = file_exists($new)) || file_exists($old) )
            {
                if(!$exis)
                {
                    self::rename($old, $new);
                }

                touch($new);
            }
            else
            {
                throw new LogicException('You must connect to internet. It can\'t download rating list');
            }
        }
        if(!class_exists($this->actualClass))
            require_once $new;
    }

    /**
     * setup number formating for later use
     * @param string $code -UPPERCASE code
     * @return array
     */
    protected function createFormat($code)
    {
        $strTo      =$this->strTo;
        $codeStr    =$strTo($code);
        $numFormat  =array('1 '. $codeStr);

        if(!isset(self::$defineMoney[ $code ]))
        {//neexistuje vubec nic
            $numFormat =array_merge($numFormat, $this->defaultFormat);
        }
        elseif(is_array(self::$defineMoney[ $code ]))
        {//formatovani existuje
            $numFormat  =self::$defineMoney[ $code ];
        }
        else
        {
            $numFormat[0] =self::$defineMoney[ $code ];
            $numFormat    =array_merge($numFormat, $this->defaultFormat);
        }
        $numFormat[4]   =$codeStr;
        return $numFormat;
    }

    /**
     * create cache file
     * @param string $cnb
     * @param $file name of cache file
     * @return void
     */
    protected function createCacheFile($cnb, $file)
    {
        $cnb    = explode("\n", $cnb);
        unset($cnb[1]);
        $info   = explode(' #', $cnb[0]);
        $cnb[0] = Cnb::CNB_CZK;
        $obj    = new ReflectionClass($this);
        $property = array($obj->getConstant('COUNTRY'), $obj->getConstant('NAME'),
                    $obj->getConstant('FROM1'), $obj->getConstant('CODE'), $obj->getConstant('TO'),);

        $list  ='<?php class '. $this->actualClass .' extends NonObject {static public $date=\''. $info[0] .'\';static public $id='. (int)$info[1] .';';

        $processed = array();

        foreach($cnb as $value)
        {
            $row    =explode(self::PIPE, $value);

            if( !isset($row[4]) || !is_numeric($row[4]) || ($row[4] = (double)$row[4]) <= 0 )
                continue;
            else
                $row[2] =(double)$row[2];

            if ( isset( $processed[$this->correctTry($row[3])] )) continue;   // TH FIX
            $processed[$this->correctTry($row[3])] = TRUE;                    // TH FIX

            $numFormat  =$this->createFormat($row[3]);
            $correction =$row[4]/$row[2];

            if($numFormat[4] != $this->defMoney)
                $correction /=$this->correction;

            $list   .='public static function '. $this->correctTry($row[3]) .'(){return array(';
            $list   .=self::getElement2Cache(self::RATE, 1/$correction);
            $list   .=self::getElement2Cache(self::NUM_FORMAT, $numFormat[0]);
            $list   .=self::getElement2Cache(self::DECIMAL, $numFormat[1]);
            $list   .=self::getElement2Cache(self::DEC_POINT, $numFormat[2]);
            $list   .=self::getElement2Cache(self::THOUSANDS, $numFormat[3]);
            $list   .=self::getElement2Cache(self::SYMBOL, $this->setSymbol($numFormat[0]));

            //$row[3] = $numFormat[4];
            foreach($property as $key => $val)
            {
                if($val !== false)
                    $list   .=self::getElement2Cache($val, $row[$key]);
            }

            $list   .=');}';
        }
        file_put_contents(dirname(__FILE__) . $file, $list.'}');
    }

    /**
     * formating number
     * @param real $number
     * @return string
     */
    protected function numberFormating($number, $to)
    {
        $this->rChange[0] = number_format($number,
            $this->rating[$to][self::DECIMAL],
            $this->rating[$to][self::DEC_POINT],
            $this->rating[$to][self::THOUSANDS]);
        return str_replace($this->rFound, $this->rChange, $this->rating[$to][ self::NUM_FORMAT ]);
    }

    /**
     * verify whether the currency code and load this
     * @param string $code
     * @return string
     */
    protected function loadRating($code, $add=true)
    {
        if(!isset($this->rating[$code]))
        {
            $array  =@call_user_func(array($this->actualClass, $this->correctTry(strtoupper($code))));
            if(!is_array($array))
            {
                if($this->isProduction())
                    return $this->defMoney;

                throw new OutOfRangeException('This currency "'. $code .'" does not exist!');
            }

            if($add === true)
                $this->rating[$code]    =$array;
            else
                return array($code=>$array);
        }

        return $code;
    }

    /**
     * letter size
     * @param int $case
     * @return function|string
     * @deprecated
     */
    static protected function strTo($case=0)
    {
        switch($case)
        {
            case 0:
                return 'strtolower';

            case 1:
                return 'strtoupper';

            default:
                return create_function('$input', 'return strtoupper($input[0]) . strtolower(substr($input, 1));');
        }
    }

    /**
     * correction for Turkish currency because try is reserved word in php
     * for load Turkish currency use its code 'try'
     * @param $string currency UPPERCASE
     * @return string
     */
    private function correctTry($string)
    {
        return ($string == 'TRY')? $string . '1': $string;
    }

    /**
     * version of this class
     * @return string
     */
    static public function getVersion()
    {
        if(self::$version === false)
        {
            $rc = new ReflectionClass(__CLASS__);
            $found = array();
            preg_match('~@version (.*)~', $rc->getDocComment(), $array);
            self::$version = $array[1];
        }
        return self::$version;
    }

    /**
     * safe rename file
     * @param string $new
     * @param string $old
     * @return bool
     */
    static protected function rename($new, $old)
    {
        if(file_exists($new))
        {
            if(file_exists($old))
                unlink($old);
            return rename($new, $old);
        }
        return null;
    }

    /**
     * replace stroke to point
     * @param string $string
     * @return string
     */
    static protected function stroke2point($string)
    {
        return str_replace(',', '.', $string);
    }

    /**
     *
     * @param string|boolean $const
     * @param mixed $val
     * @return string
     */
    static private function getElement2Cache($const, $val)
    {
        $val = is_numeric($val)? (double)$val: "'$val'";
        return "'$const'=>$val,";
    }

    /**
     * use for array_walk
     * @param ReflectionMethod $item
     * @param int $key
     * @return void
     */
    static private function obj2array(ReflectionMethod &$item, $key)
    {
        $item = $item->name;
    }
}
