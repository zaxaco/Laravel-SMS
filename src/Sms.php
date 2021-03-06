<?php
namespace abdullahobaid\Sms;

use App\Http\Controllers\Controller;

class SMS extends Controller
{
    protected static $gateway;

    public static function run($gatewayName = false)
    {
        if (!$gatewayName)
            $gatewayName = config('sms.default');
        static::$gateway = config("sms.gateways")[$gatewayName];
    }

    public static function SendRequest($type, $url, $parameters)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        if ($type == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        } elseif ($type == 'get') {
            curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($parameters));
        } else {
            dd('method not acceptable!');
        }
        $result = curl_exec($ch);
        return $result;
    }

    public static function Balance($gatewayName = false)
    {
        static::run($gatewayName);
        $gateway = static::$gateway;
        $parameters[$gateway['userParameter']] = $gateway['parameters'][$gateway['userParameter']];
        $parameters[$gateway['passwordParameter']] = $gateway['parameters'][$gateway['passwordParameter']];
        $request = static::SendRequest($gateway['method'], $gateway['links']['getCredit'], $parameters);
        return $request;
    }
    public static function SendPattern($numbers, $pattern_code, $input_data, $from = false, $gatewayName = false)
    {
        static::run($gatewayName);
        $gateway = static::$gateway;
        $numbers = self::format_numbers($numbers);
        $parameters[$gateway['userParameter']] = $gateway['parameters'][$gateway['userParameter']];
        $parameters[$gateway['passwordParameter']] = $gateway['parameters'][$gateway['passwordParameter']];
        $parameters[$gateway['senderParameter']] = $gateway['parameters'][$gateway['senderParameter']];
        $parameters[$gateway['recipientsParameter']] = json_encode($numbers);
        $parameters['input_data'] = json_encode($input_data);
        $parameters['pattern_code'] = $pattern_code;

        $response = static::SendRequest($gateway['method'], $gateway['links']['sendPattern'], $parameters);
        return $response;
    }

    public static function Send($numbers, $message, $dateTime = false, $senderName = false, $gatewayName = false)
    {
        static::run($gatewayName);
        $gateway = static::$gateway;
        $parameters = $gateway['parameters'];
        $parameters[$gateway['recipientsParameter']] = $numbers;
        $parameters[$gateway['messageParameter']] = $message;
        if ($senderName)
            $parameters[$gateway['senderParameter']] = $senderName;
        if ($dateTime) {
            $dateTimeObject = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
            if (isset($gateway['dateTimeParameter'])) {
                $parameters[$gateway['dateTimeParameter']] = $dateTimeObject->format($gateway['dateTimeFormat']);
            } else {
                $parameters[$gateway['dateParameter']] = $dateTimeObject->format($gateway['dateFormat']);
                $parameters[$gateway['timeParameter']] = $dateTimeObject->format($gateway['timeFormat']);
            }
        }
        $request = static::SendRequest($gateway['method'], $gateway['links']['send'], $parameters);
        return $request;
    }
    
    public static function SendBulk($numbers, $message, $dateTime = false, $senderName = false, $gatewayName = false)
    {
        static::run($gatewayName);
        $gateway = static::$gateway;
        $parameters = $gateway['parameters'];
        $parameters[$gateway['recipientsParameter']] = $numbers;
        $parameters[$gateway['messageParameter']] = $message;
        if ($senderName)
            $parameters[$gateway['senderParameter']] = $senderName;
        if ($dateTime) {
            $dateTimeObject = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
            if (isset($gateway['dateTimeParameter'])) {
                $parameters[$gateway['dateTimeParameter']] = $dateTimeObject->format($gateway['dateTimeFormat']);
            } else {
                $parameters[$gateway['dateParameter']] = $dateTimeObject->format($gateway['dateFormat']);
                $parameters[$gateway['timeParameter']] = $dateTimeObject->format($gateway['timeFormat']);
            }
        }
        $request = static::SendRequest($gateway['method'], $gateway['links']['sendBulk'], $parameters);
        return $request;
    }
    /* format numbers:(array or string) and return numbers:(array or string)
    ** edited
    */
    public static function format_numbers($numbers, $separator = false)
    {
        if (!is_array($numbers))
            return array(self::format_number($numbers));
        $numbers_array = array();
        foreach ($numbers as $number) {
            $n = self::format_number($number);
            array_push($numbers_array, $n);
        }
        if( $separator ) {
            return implode($separator, $numbers_array);
        } else {
            return $numbers_array;
        }
    }
    /* format a number:string and return number:string
    ** not edited
    */
    public static function format_number($number)
    {
        if (strlen($number) == 10 && starts_with($number, '05'))
            return preg_replace('/^0/', '966', $number);
        elseif (starts_with($number, '00'))
            return preg_replace('/^00/', '', $number);
        elseif (starts_with($number, '+'))
            return preg_replace('/^+/', '', $number);
        return $number;
    }

    public static function count_messages($text)
    {
        $length = mb_strlen($text);
        if ($length <= 70)
            return 1;
        else
            return ceil($length / 67);
    }

}
