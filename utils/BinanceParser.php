<?php
namespace app\utils;

use app\models\Api\Bittrex;
use Yii;

class BinanceParser extends ExchangeParser
{
    public static function parsePricesForLag($prices)
    {
        foreach ($prices as $marketPrice) {
            if (strpos($marketPrice['symbol'], 'BTC', -3)){
                $market = str_replace('BTC', '', $marketPrice['symbol']);
                $fullMarket = 'BTC-'.$market;
                $marketPrices[$fullMarket] = $marketPrice['price'];
            }
        }

        return $marketPrices;
    }

    public static function parsePrices(array $prices): array
    {
        $marketPrices = [];
        foreach ($prices as $marketPrice) {
            if (strpos($marketPrice['symbol'], 'BTC', -3) || strpos($marketPrice['symbol'], 'USDT', -4)){
                $marketPrices[$marketPrice['symbol']] = $marketPrice['price'];
            }
        }

        return $marketPrices;
    }

    public static function sliceTicker(array $tickerData, array $markets)
    {
        $slicedTickers = [];
        foreach ($tickerData as $ticker) {
            if (strpos($ticker['symbol'], 'BTC', -3)){
                $market = str_replace('BTC', '', $ticker['symbol']);
                $fullMarket = 'BTC-'.$market;
                $ticker['bittrexName'] = $fullMarket;
                if (in_array($fullMarket, $markets)) {
                    $slicedTickers[$fullMarket] = $ticker;
                }
            }
        }

        return $slicedTickers;
    }

    public static function formatTickerToMarketList(array $tickerData): array
    {
        $slicedTickers = [];
        foreach ($tickerData as $ticker) {
            if (strpos($ticker['symbol'], 'USDT', -4) || strpos($ticker['symbol'], 'BTC', -3)){
                $slicedTickers[$ticker['symbol']] = $ticker['symbol'];
            }
        }

        asort($slicedTickers);
        return $slicedTickers;
    }

    public static function parseTicker(array $tickerData)
    {
        $ticker = [];

        $timestamp = self::safe_integer($tickerData['closeTime']);
        $datetime = date("Y-m-d H:i:s");

        $ticker['exchange'] = 'BINANCE';
//        $ticker['symbol'] = $tickerData['symbol'];
        $ticker['symbol'] = $tickerData['bittrexName'];
        $ticker['timestamp'] = $timestamp;
        $ticker['datetime'] = $datetime;
        $ticker['high'] = self::safe_float($tickerData['highPrice']);
        $ticker['low'] = self::safe_float($tickerData['lowPrice']);
        $ticker['bid'] = self::safe_float($tickerData['bidPrice']);
        $ticker['ask'] = self::safe_float($tickerData['askPrice']);
        $ticker['vwap'] = self::safe_float($tickerData['weightedAvgPrice']);
        $ticker['open'] = self::safe_float($tickerData['openPrice']);
        $ticker['close'] = self::safe_float($tickerData['prevClosePrice']);
        $ticker['first'] = null;
        $ticker['last'] = self::safe_float($tickerData['lastPrice']);
        $ticker['change'] = self::safe_float($tickerData['priceChangePercent']);
        $ticker['percentage'] = null;
        $ticker['average'] = null;
        $ticker['basevolume'] = self::safe_float($tickerData['volume']);
        $ticker['quotevolume'] = self::safe_float($tickerData['quoteVolume']);
        $ticker['created_at'] = $datetime;
        $ticker['updated_at'] = $datetime;

        return $ticker;

    }

    public static function parseTickerForPendingOrder(array $ticker): array
    {
        $parsedTicker['Last'] = $ticker['lastPrice'];
        $parsedTicker['Ask'] = $ticker['askPrice'];
        $parsedTicker['Bid'] = $ticker['bidPrice'];

        return $parsedTicker;
    }

    public static function getBinanceSummary(array $binanceBalance, array $currentPrices): array
    {
        $binanceSum = 0;
        $binanceSumUSDT = 0;

        foreach ($binanceBalance['balances'] as $asset) {
            if ($asset['free'] + $asset['locked'] > 0) {
                if ($asset['asset'] != 'BTC' && $asset['asset'] != 'USDT' && $asset['asset'] != 'BUSD') {
                    $binanceSummary[$asset['asset']]['Currency'] = $asset['asset'];
                    $binanceSummary[$asset['asset']]['Balance'] = $asset['free'] + $asset['locked'];
                    $binanceSummary[$asset['asset']]['Price'] = $currentPrices['Binance'][$asset['asset'] . 'BTC'];
                    $binanceSummary[$asset['asset']]['PriceUSDT'] = $currentPrices['Binance'][$asset['asset'] . 'USDT'];
                    $binanceSummary[$asset['asset']]['Value'] = $binanceSummary[$asset['asset']]['Balance'] * $binanceSummary[$asset['asset']]['Price'];
                    $binanceSummary[$asset['asset']]['ValueUSDT'] = $binanceSummary[$asset['asset']]['Balance'] * $binanceSummary[$asset['asset']]['PriceUSDT'];
                    $binanceSum += $binanceSummary[$asset['asset']]['Value'];
                    $binanceSumUSDT += $binanceSummary[$asset['asset']]['ValueUSDT'];
                }
                if ($asset['asset'] == 'BTC') {
                    $binanceSummary['BTC']['Currency'] ='BTC';
                    $binanceSummary['BTC']['Balance'] = $asset['free'] + $asset['locked'];
                    $binanceSummary['BTC']['Price'] = 1;
                    $binanceSummary['BTC']['PriceUSDT'] = (float)$currentPrices['Bittrex']['USD-BTC'];
                    $binanceSummary['BTC']['Value'] = $binanceSummary['BTC']['Balance'];
                    $binanceSummary['BTC']['ValueUSDT'] =  $binanceSummary['BTC']['Balance'] * $binanceSummary['BTC']['PriceUSDT'];
                    $binanceSum += $binanceSummary[$asset['asset']]['Value'];
                    $binanceSumUSDT += $binanceSummary[$asset['asset']]['ValueUSDT'];
                }
                if ($asset['asset'] == 'USDT') {
                    $binanceSummary['USDT']['Currency'] ='USDT';
                    $binanceSummary['USDT']['Balance'] = $asset['free'] + $asset['locked'];
                    $binanceSummary['USDT']['Price'] = $currentPrices['Bittrex']['BTC-TUSD'];
                    $binanceSummary['USDT']['PriceUSDT'] = 1;
                    $binanceSummary['USDT']['Value'] = $binanceSummary['USDT']['Balance'] * $binanceSummary['USDT']['Price'];
                    $binanceSummary['USDT']['ValueUSDT'] = $binanceSummary['USDT']['Balance'] * $binanceSummary['USDT']['PriceUSDT'];
                    $binanceSum += $binanceSummary[$asset['asset']]['Value'];
                    $binanceSumUSDT += $binanceSummary[$asset['asset']]['ValueUSDT'];
                }
                if ($asset['asset'] == 'BUSD') {
                    $binanceSummary['BUSD']['Currency'] ='BUSD';
                    $binanceSummary['BUSD']['Balance'] = $asset['free'] + $asset['locked'];
                    $binanceSummary['BUSD']['Price'] = $currentPrices['Bittrex']['BTC-TUSD'];
                    $binanceSummary['BUSD']['PriceUSDT'] = 1;
                    $binanceSummary['BUSD']['Value'] = $binanceSummary[$asset['asset']]['Balance'] * $binanceSummary[$asset['asset']]['Price'];
                    $binanceSummary['BUSD']['ValueUSDT'] = $binanceSummary['BUSD']['Balance'] * $binanceSummary['BUSD']['PriceUSDT'];
                    $binanceSum += $binanceSummary[$asset['asset']]['Value'];
                    $binanceSumUSDT += $binanceSummary[$asset['asset']]['ValueUSDT'];
                }
            }
        }

        return [
            'binanceSummary' => $binanceSummary,
            'binanceSumValue' => $binanceSum,
            'binanceSumValueUSDT' => $binanceSumUSDT
        ];
    }
}