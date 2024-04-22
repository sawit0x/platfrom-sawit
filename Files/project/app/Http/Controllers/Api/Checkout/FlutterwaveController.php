<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Session;

class FlutterwaveController extends Controller
{
    public $public_key;
    private $secret_key;
    public $orderRepositorty;

    public function __construct(OrderRepository $orderRepositorty)
    {
        $data = PaymentGateway::whereKeyword('flutterwave')->first();
        $paydata = $data->convertAutoData();
        $this->public_key = $paydata['public_key'];
        $this->secret_key = $paydata['secret_key'];
        $this->orderRepositorty = $orderRepositorty;
    }

    public function store(Request $request) {
        $invest = Invest::findOrFail($request->invest_id);
        $user = User::findOrFail($invest->user_id);
        $item_amount = $request->amount;

        $curl = curl_init();

        $customer_email =  $user->email;
        $amount = $item_amount;
        $currency = $request->currency_code;
        $txref = $invest->transaction_no;
        $PBFPubKey = $this->public_key;
        $redirect_url = route('api.checkout.flutter.notify');
        $payment_plan = "";

        Session::put('method',$request->method);
        Session::put('invest_id',$request->invest_id);

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
              'amount' => $amount,
              'customer_email' => $customer_email,
              'currency' => $currency,
              'txref' => $txref,
              'PBFPubKey' => $PBFPubKey,
              'redirect_url' => $redirect_url,
              'payment_plan' => $payment_plan
            ]),
            CURLOPT_HTTPHEADER => [
              "content-type: application/json",
              "cache-control: no-cache"
            ],
          ));

          $response = curl_exec($curl);
          $err = curl_error($curl);

          if($err){
            die('Curl returned error: ' . $err);
          }

          $transaction = json_decode($response);

          if(!$transaction->data && !$transaction->data->link){
            print_r('API returned error: ' . $transaction->message);
          }

          return redirect($transaction->data->link);
     }


     public function notify(Request $request) {
        $input = $request->all();

        $method = Session::get('method');
        $invest_id = Session::get('invest_id');
        $invest = Invest::findOrFail($invest_id);

        if($request->cancelled == "true"){
            return redirect()->route('api.user.invest.checkout',$invest->id)->with('success',__('Payment Cancelled!'));
        }

        if (isset($input['txref'])) {
            $ref = $input['txref'];
            $query = array(
                "SECKEY" => $this->secret_key,
                "txref" => $ref
            );


            $data_string = json_encode($query);

            $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $response = curl_exec($ch);
            curl_close($ch);
            $resp = json_decode($response, true);

            if ($resp['status'] == "success") {

              $paymentStatus = $resp['data']['status'];
              $chargeResponsecode = $resp['data']['chargecode'];

              if (($chargeResponsecode == "00" || $chargeResponsecode == "0") && ($paymentStatus == "successful")) {

                $addionalData = ['status'=>'running','method'=>$method];

                $this->orderRepositorty->apiOrder($request,$invest,$addionalData);

                return redirect()->route('api.user.invest.checkout',$invest->id)->with('message','Invest successfully complete.');
              }
              else {
                return redirect()->route('api.user.invest.checkout',$invest->id)->with('warning','Something went wrong!');
              }

            }
        }
        else {
            return redirect()->route('api.user.invest.checkout',$invest->id)->with('warning','Something went wrong!');
        }
     }
}
