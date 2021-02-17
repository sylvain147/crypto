<?php

namespace App\Console\Commands;

use App\Models\BuyList;
use App\Models\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class buyCrypto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:buy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo ('HEre we go ');
        $symbol = 'BTCUSDT';
        $response = Http::get('https://api.binance.com/api/v3/klines', [
            'symbol' => $symbol,
            'interval' => '1m',

        ]);
        $alls = [];
        foreach ($response->json() as $data) {
            $alls[] = ['open' => $data[1], 'close' => $data[4]];
        }
        $datas = $this->calculateRSIValues($alls, 6);

        if (array_slice($datas, -1)[0] < 20) {
            $lastAction = BuyList::orderBy('id', 'DESC')->first();
            if ($lastAction==null || $lastAction->action == 'sell') {
                echo('BUY');
                $money = Money::find(1);
                $montant = $money->usdt * (10 / 100);
                if ($montant < 10) {
                    return;
                }
                $price = Http::get('https://api.binance.com/api/v3/trades', [
                    'symbol' => $symbol,
                    'limit' => 1
                ])->json()[0]['price'];
                $quantity = $montant / $price;
                $money->usdt = $money->usdt - $money->usdt * (10 / 100);
                $money->btc = $quantity;
                $money->save();
                $buy = new BuyList();
                $buy->action = 'buy';
                $buy->rsi = array_slice($datas, -1)[0];
                $buy->crypto = 'BTC';
                $buy->price = $price;
                $buy->quantity = $quantity;
                $buy->crypto_before = $money->btc;
                $buy->crypto_after = $quantity;
                $buy->save();

            }
        }
        if(array_slice($datas, -1)[0] > 80){
            $lastAction = BuyList::orderBy('id', 'DESC')->first();
            if($lastAction == null) return;
            if ($lastAction->action == 'buy') {
                echo('SELL');
                $money = Money::find(1);
                $montant = $money->btc;
                $price =    Http::get('https://api.binance.com/api/v3/trades', [
                    'symbol' => $symbol,
                    'limit' => 1
                ])->json()[0]['price'];
                $quantity =  $montant * $price;
                $money->btc = '0';
                $buy = new BuyList();
                $buy->action = 'sell';
                $buy->rsi = array_slice($datas, -1)[0];
                $buy->crypto = 'USDT';
                $buy->price = $price;
                $buy->quantity = $quantity;
                $buy->crypto_before = $money->usdt;
                $buy->crypto_after = $money->usdt + $quantity;
                $money->usdt += $quantity;
                $money->save();
                $buy->save();

            }
        }
        return 0;
    }

    public function calcSmmaUp(array $candlesticks, $n, $i, $avgUt1)
    {

        if ($avgUt1 == 0) {
            $sumUpChanges = 0;

            for ($j = 0; $j < $n; $j++) {
                $change = $candlesticks[$i - $j]['close'] - $candlesticks[$i - $j]['open'];

                if ($change > 0) {
                    $sumUpChanges += $change;
                }
            }
            return $sumUpChanges / $n;
        } else {
            $change = $candlesticks[$i]['close'] - $candlesticks[$i]['open'];
            if ($change < 0) {
                $change = 0;
            }
            return (($avgUt1 * ($n - 1)) + $change) / $n;
        }

    }

    public function calcSmmaDown(array $candlesticks, $n, $i, $avgDt1)
    {
        if ($avgDt1 == 0) {
            $sumDownChanges = 0;

            for ($j = 0; $j < $n; $j++) {
                $change = $candlesticks[$i - $j]['close'] - $candlesticks[$i - $j]['open'];

                if ($change < 0) {
                    $sumDownChanges -= $change;
                }
            }
            return $sumDownChanges / $n;
        } else {
            $change = $candlesticks[$i]['close'] - $candlesticks[$i]['open'];
            if ($change > 0) {
                $change = 0;
            }
            return (($avgDt1 * ($n - 1)) - $change) / $n;
        }

    }

    public function calculateRSIValues(array $candlesticks, int $n)
    {

        $results = [];
        $results[] = count($candlesticks);

        $ut1 = 0;
        $dt1 = 0;
        $size = count($candlesticks);
        for ($i = 0; $i < $size; $i++) {
            if ($i < ($n)) {
                continue;
            }

            $ut1 = $this->calcSmmaUp($candlesticks, $n, $i, $ut1);
            $dt1 = $this->calcSmmaDown($candlesticks, $n, $i, $dt1);

            $results[$i] = 100.0 - 100.0 / (1.0 + ($ut1 / $dt1));

        }

        return $results;
    }
}
