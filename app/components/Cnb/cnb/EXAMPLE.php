<?php
function p()
{
    $arg    =func_get_args();
    show($arg);
}

function show($arg)
{
    if($GLOBALS['print'])
    {
        echo "<hr />";
        foreach($arg as $val)
        {
            if(class_exists('Debug'))
            {
                Debug::dump($val);
            }
            else
            {
                NDebug::dump($val);
            }

            echo "<hr />";
        }
    }
}

function s(&$param)
{
    if(!isset($param))
        $param  =NULL;
}
s($_GET['m']);
s($_GET['n']);

/*
$cnbH   =new CnbHistory($_GET['m'], 30, 12, 2000);

p('format', $cnbH->format(50), $cnbH->format(50, 'usd'), $cnbH->format(50, 'usd', 'eur'), $cnbH->format(50, 'eur', 'usd', false));
*/


$cnb    =new Cnb($_GET['m'], $_GET['n']);

p('format', $cnb->format(50), $cnb->format(2.38538237679, 'usd'), $cnb->format(50, 'usd', 'eur'), $cnb->format(50, true, 'usd'));
p('change', $cnb->change(50), $cnb->change(2.38538237679, 'usd'), $cnb->change(50, 'usd', 'eur'), $cnb->change(50, 'try', 'usd', 3));
p('loadRating', $cnb->loadCurrency('eur'), $cnb->loadCurrency('php'));
p('getMoney', $cnb->getMoney('eur', Cnb::RATE), $cnb->getMoney('try'));
p('getWebMoney', $cnb->getWebMoney());
p('getDefMoney', $cnb->getDefMoney());
p('getDate', $cnb->getDate(), $cnb->getDate('Y-m-d'));
p('formatVat', $cnb->format(100, false, false, 1.1), $cnb->formatVat(), $cnb->formatVat(), $cnb->format(100), $cnb->formatVat(), $cnb->formatVat());
p('getVat', $cnb->getVat());
//p('getSymbol', $cnb->getSymbol(), $cnb->getSymbol('eur'));
p('getRating', $cnb->getRating(true), $cnb->getRating('jpy'), $cnb->getRating());
