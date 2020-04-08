<?


use \Bitrix\Main\Config\Option;
use Bitrix\Main\Context,
    Bitrix\Main\Loader,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale\Order,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem;


class OrderIsPayed
{
    public static $MODULE_ID = 'dmbgeo.orderispayed';


    public static function Agent()
    {
        if (!Loader::includeModule("sale") && !Loader::includeModule("catalog")) die();
        $sites = self::getSites();
        global $DB;
        foreach ($sites as $site) {
            if (self::getOption("STATUS", $site['LID']) == "N") {
                continue;
            }
            
            $orderStatuses = explode(',', self::getOption("ORDER_FILTER_STATUS", $site['LID']));
            $orderDeliveries = explode(',', self::getOption("ORDER_FILTER_DELIVERY", $site['LID']));
            $orderPaies = explode(',', self::getOption("ORDER_FILTER_PAY", $site['LID']));
           

            $arFilter = array();
            if (count($orderStatuses) > 0 && reset($orderStatuses)!=='N') {
                $arFilter['STATUS_ID'] = $orderStatuses;
            }
            if (count($orderDeliveries) > 0 && reset($orderDeliveries)!=='N') {
                $arFilter['DELIVERY_ID'] = $orderDeliveries;
            }
            if (count($orderPaies) > 0 && reset($orderPaies)!=='N') {
                $arFilter['PAY_SYSTEM_ID'] = $orderPaies;
            }

            $arFilter["<DATE_INSERT"] = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", $site['LID'])), mktime(date("G"), date("i")-IntVal(self::getOption("ORDER_TIME", $site['LID'])), date('s'), date("n"), date('j'), date("Y")));
            $arFilter[">DATE_INSERT"] = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL", $site['LID'])), mktime(date("G"), date("i"), date('s'), date("n"), date('j')-1, date("Y")));
            
            $rsSales = CSaleOrder::GetList(array('DATE_INSERT'=>'DESC'), $arFilter);
            while ($order = $rsSales->Fetch()) {
                $ORDER_ID=$order['ID'];
                if($order['PAYED']=="Y"){
                    \CSaleOrder::StatusOrder($ORDER_ID, self::getOption("ORDER_STATUS_Y", $site['LID']));
                }
                if($order['PAYED']=="N"){
                    \CSaleOrder::StatusOrder($ORDER_ID, self::getOption("ORDER_STATUS_N", $site['LID']));
                    \CSaleOrder::CancelOrder($ORDER_ID, "Y",  self::getOption("ORDER_CANCEL_MESSAGE", $site['LID']));
                }
                
            }
        }


        return '\OrderIsPayed::Agent();';
    }

    public static function getSites()
    {
        $SITES = array();
        $rsSites = \CSite::GetList($by = "sort", $order = "desc");
        while ($arSite = $rsSites->Fetch()) {
            $SITES[] = $arSite;
        }
        return $SITES;
    }




    public static function getOption($PARAM, $SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_' . $PARAM . '_' . $SITE_ID, "N");
    }
}
