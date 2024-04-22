<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Omnipay\Omnipay;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\Repositories\OrderRepository;
use Illuminate\Support\Str;

class PaypalController extends Controller
{
    private $_api_context;
    public $orderRepositorty;
    public $gateway;

    public function __construct(OrderRepository $orderRepositorty)
    {
        $data = Paymentgateway::whereKeyword('paypal')->first();
        $paydata = $data->convertAutoData();
        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId($paydata['client_id']);
        $this->gateway->setSecret($paydata['client_secret']);
        $this->gateway->setTestMode(true);
        $this->orderRepositorty = $orderRepositorty;
    }

    public function store(Request $request){

        $cancel_url = route('checkout.paypal.cancel');
        $notify_url = route('checkout.paypal.notify');

        $gs = Generalsetting::findOrFail(1);
        $item_name = $gs->title." Invest";
        $item_number = Str::random(4).time();
        $item_amount = $request->amount;

        $support = ['USD','EUR'];
        if(!in_array($request->currency_code,$support)){
            return redirect()->back()->with('warning','Please Select USD Or EUR Currency For Paypal.');
        }

        $addionalData = ['item_number'=>$item_number];
        $this->orderRepositorty->order($request,'pending',$addionalData);

        $currency = Currency::whereId($request->currency_id)->first();
        $amountToAdd = $request->amount/$currency->value;

        try {
            $response = $this->gateway->purchase(array(
                'amount' => $amountToAdd,
                'currency' => $request->currency_code,
                'returnUrl' => $notify_url,
                'cancelUrl' => $cancel_url,
            ))->send();

            if ($response->isRedirect()) {

                $gs = Generalsetting::findOrFail(1);

                $item_name = $gs->title." Subscription";
               
                $addionalData = ['subscription_number'=>$item_number];
               
                Session::put('paypal_data',$request->all());
                Session::put('order_number',$item_number);
               
            

                if ($response->redirect()) {
                    /** redirect to paypal **/
                    return redirect($response->redirect());

                }
            } else {
                return redirect()->back()->with('unsuccess', $response->getMessage());

            }
        } catch (\Throwable$th) {

            return redirect()->back()->with('unsuccess', $response->getMessage());
        }

    }

    public function notify(Request $request)
    {

        $responseData = $request->all();
        if (empty($responseData['PayerID']) || empty($responseData['token']))  {
            return redirect()->back()->with('error', 'Payment Failed'); 
        } 

        $transaction = $this->gateway->completePurchase(array(
            'payer_id' => $responseData['PayerID'],
            'transactionReference' => $responseData['paymentId'],
        ));
        $response = $transaction->send();

       
        $request = Session::get('paypal_data');



        $trx = Session::get('order_number');

     

        if ($response->isSuccessful()) {
         

            $order = Invest::where('transaction_no',$trx)->where('payment_status','pending')->first();
            
            $data['txnid'] = $response->getData()['transactions'][0]['related_resources'][0]['sale']['id'];
            $data['payment_status'] = "completed";
            $data['status'] = 1;
            $order->update($data);

            $this->orderRepositorty->callAfterOrder($request,$order);


            Session::forget('paypal_data');
            Session::forget('paypal_payment_id');
            Session::forget('order_number');

            return redirect()->route('user.invest.history')->with('message','Invest successfully complete.');
        }
        else {
            return redirect()->back()->with('error', __('Payment failed'));
        }

    }

    public function cancel(){
        return redirect()->route('user.invest.checkout')->with('warning','Something went wrong!');
    }
}
