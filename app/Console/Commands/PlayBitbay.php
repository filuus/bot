<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Phpml\Classification\MLPClassifier;
use Phpml\NeuralNetwork\ActivationFunction\PReLU;
use Phpml\NeuralNetwork\ActivationFunction\Sigmoid;
use Phpml\NeuralNetwork\Layer;
use Phpml\NeuralNetwork\Node\Neuron;

class PlayBitbay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'play:bitbay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private $pubKey;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private $privKey;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->pubKey = config('app.bitbay_public_key');
        $this->privKey = config('app.bitbay_private_key');
    }

    /**
     * @return int
     * @throws \Phpml\Exception\InvalidArgumentException
     */
    public function handle()
    {
        $mlp = new MLPClassifier(30, [10], [-1, 0, 1]);

        for ($i = 0; $i < 100; $i++) {
            $quantity = (string)($i * 5);
            $result = $this->getSamples(Carbon::now('Europe/Warsaw')->sub($quantity . 'minutes'));
            $mlp->partialTrain(
                $samples = [$result['samples']],
                $targets = [$result['target']]
            );
            $this->info($i);
        }

        $signal = $mlp->predict([$this->getData()])[0];

        $this->play($signal);

        return 0;
    }

    /**
     * @param $data
     * @return string
     */
    public function GetUUID(string $data): string
    {
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * @param $method
     * @param null $params
     * @param string $type
     * @return bool|string
     * @throws Exception
     */
    public function callApi($method, $params = null, $type = 'GET')
    {
        $post = null;
        if (($type == 'GET') && is_array($params)):
            $method = $method . '?query=' . urlencode(json_encode($params));
        elseif (($type == 'POST' || $type == 'PUT' || $type == 'DELETE') && is_array($params) && (count($params) > 0)):
            $post = json_encode($params);
        endif;
        $time = time();
        $sign = hash_hmac("sha512", $this->pubKey . $time . $post, $this->privKey);
        $headers = array(
            'API-Key: ' . $this->pubKey,
            'API-Hash: ' . $sign,
            'operation-id: ' . $this->GetUUID(random_bytes(16)),
            'Request-Timestamp: ' . $time,
            'Content-Type: application/json'
        );
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, 'https://api.bitbay.net/rest/' . $method);
        if ($type == 'POST' || $type == 'PUT' || $type == 'DELETE') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        return curl_exec($curl);
    }

    /**
     * @param Carbon $date
     * @return array
     * @throws Exception
     */
    public function getSamples(Carbon $date): array
    {
        $before = clone $date;
        $before = $before->sub('5 hour');
        $response = $this->callApi('trading/candle/history/ETH-PLN/300?from=' . $before->timestamp . '000' . '&to=' . $date->timestamp . '000');

        $data = json_decode($response);
        $thirtyElements = array_slice($data->items, 0, 30);
        $rateSamples = array_map(function ($el) {
            return $el[1]->c - $el[1]->o;
        }, $thirtyElements);

        $first = end($thirtyElements);
        $last = end($data->items);
        $target = (($last[1]->h + $last[1]->l) / 2) - (($first[1]->h + $first[1]->l) / 2);

        if ($target > 30) {
            $target = 1;
        } else if ($target < -30) {
            $target = -1;
        } else {
            $target = 0;
        }

        return [
            'samples' => $rateSamples,
            'target' => $target
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getData(): array
    {
        $now = Carbon::now();
        $before = Carbon::now()->sub('5 hour');
        $response = $this->callApi('trading/candle/history/ETH-PLN/300?from=' . $before->timestamp . '000' . '&to=' . $now->timestamp . '000');

        $data = json_decode($response);
        $thirtyElements = array_slice($data->items, -30);

        return array_map(function ($el) {
            return $el[1]->c - $el[1]->o;
        }, $thirtyElements);
    }

    public function getSaldo()
    {
        $balances = $this->callApi('balances/BITBAY/balance');
        $ticker = $this->callApi('trading/ticker/ETH-PLN');
        $myCurrency = array_filter(
            json_decode($balances)->balances,
            function ($e) {
                return $e->currency == 'ETH' || $e->currency == 'PLN';
            }
        );

        $rate = json_decode($ticker)->ticker->rate;

        return array_map(function ($element) use ($rate) {
            return [
                'name' => $element->name,
                'available' => $element->availableFunds,
                'value' => $element->name !== 'PLN' ? $element->availableFunds * $rate : $element->availableFunds
            ];
        }, $myCurrency);
    }

    public function haveFounds()
    {
        $balances = $this->getSaldo();
        $plnValue = $balances['27']['value'];
        $ethValue = $balances['20']['value'];

        if ($ethValue <= $plnValue) {
            return true;
        } else {
            return false;
        }
    }

    public function play($signal)
    {
        $this->info($signal);
        $balances = $this->getSaldo();
        $pln = $balances['27'];
        $eth = $balances['20'];
        $haveFounds = $this->haveFounds();
        switch ($signal) {
            case 1:
                if ($haveFounds) {
                    $params = array(
                        "offerType" => "BUY",
                        "amount" => null,
                        "price" => 0.9 * $pln['available'],
                        "rate" => null,
                        "postOnly" => false,
                        "mode" => "market",
                        "fillOrKill" => false
                    );
                    $response = $this->callApi('trading/offer/ETH-PLN', $params, 'POST');
                    $this->info($response);
                } else {
                    $this->info('You haven\'t founds');
                }
                break;
            case -1:
                if (!$haveFounds) {
                    $params = array(
                        "offerType" => "SELL",
                        "amount" => 0.9 * $eth['available'],
                        "price" => null,
                        "rate" => null,
                        "postOnly" => false,
                        "mode" => "market",
                        "fillOrKill" => false
                    );
                    $response = $this->callApi('trading/offer/ETH-PLN', $params, 'POST');
                    $this->info($response);

                } else {
                    $this->info('You haven\'t ETH');
                }
                break;
            case 0:
                $this->info('You don\' have signal');
                break;
        }
    }
}