<?php

namespace App\Http\Controllers;

use App\Models\Money;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;


class AppController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Sylvain Attoumani
     */
    public function home()
    {

        return view('welcome');
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

    public function getSymbols()
    {
        return [
            'Bitcoin' => 'BTCUSDT'
        ];
    }
    public function chartInfos(Request $request){
        $responses = [];
        foreach ($request['symbols'] as $symbol){
            $response = Http::get('https://api.binance.com/api/v3/klines', [
                'symbol' => $symbol,
                'interval' => $request['interval'],

            ]);
            $alls = [];
            foreach ($response->json() as $data) {
                $alls[] = ['open' => $data[1], 'close' => $data[4]];
            }
            $datas = $this->calculateRSIValues($alls, $request['size']);
            $responses[$symbol]['data'] = array_slice($datas, -170);
            $responses[$symbol]['last'] = array_slice($datas, -1);
        }
        return $responses;
    }

    public function thunes(){
        return Money::all();
    }
}
