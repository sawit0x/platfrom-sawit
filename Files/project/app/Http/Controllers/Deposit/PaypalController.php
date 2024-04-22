<?php

namespace App\Http\Controllers\Deposit;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Classes\GeniusMailer;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Models\Transaction as AppTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Omnipay\Omnipay;

class PaypalController extends Controller
{
    private $_api_context;
    public $gateway;

    public function __construct()
    {
        $data = PaymentGateway::whereKeyword('paypal')->first();
        $paydata = $data->convertAutoData();
        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId($paydata['client_id']);
        $this->gateway->setSecret($paydata['client_secret']);
        $this->gateway->setTestMode(true);
       
    }

    public function store(Request $request){

        $support = ['USD','EUR'];
        if(!in_array($request->currency_code,$support)){
            return redirect()->back()->with('warning','Please Select USD Or EUR Currency For Paypal.');
        }

        $settings = Generalsetting::findOrFail(1);
        $deposit = new Deposit();
        $cancel_url = route('deposit.paypal.cancel');
        $notify_url = route('deposit.paypal.notify');

        $item_name = $settings->title." Deposit";
        $item_number = Str::random(12);
        $item_amount = $request->amount;

       

        $currency = Currency::whereId($request->currency_id)->first();
        $amountToAdd = $request->amount/$currency->value;

        $deposit['user_id'] = auth()->user()->id;
        $deposit['currency_id'] = $request->currency_id;
        $deposit['amount'] = $amountToAdd ;
        $deposit['method'] = $request->method;
        $deposit['deposit_number'] = $item_number;
        $deposit['status'] = "pending";
        $deposit->save();

        

        try {
            $response = $this->gateway->purchase(array(
                'amount' => $item_amount,
                'currency' => $request->currency_code,
                'returnUrl' => $notify_url,
                'cancelUrl' => $cancel_url,
            ))->send();

            if ($response->isRedirect()) {

               

                
                Session::put('deposit_data',$request->all());
                Session::put('deposit_number',$item_number);

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
        $user = auth()->user();
        $deposit_data = Session::get('deposit_data');

        $response = $transaction->send();
        $deposit_number = Session::get('deposit_number');
        $payment_id = Session::get('paypal_payment_id');


        if ($response->isSuccessful()) {
           

                $deposit = Deposit::where('deposit_number',$deposit_number)->where('status','pending')->first();
                $data['txnid'] = $response->getData()['transactions'][0]['related_resources'][0]['sale']['id'];
                $data['status'] = "complete";
                $deposit->update($data);

                $gs =  Generalsetting::findOrFail(1);


                if($gs->is_smtp == 1)
                {
                    $data = [
                        'to' => $user->email,
                        'type' => "Deposit",
                        'cname' => $user->name,
                        'oamount' => $deposit->amount,
                        'aname' => "",
                        'aemail' => "",
                        'wtitle' => "",
                    ];

                    $mailer = new GeniusMailer();
                    $mailer->sendAutoMail($data);
                }
                else
                {
                    $to = $user->email;
                    $subject = " You have deposited successfully.";
                    $msg = "Hello ".$user->name."!\nYou have invested successfully.\nThank you.";
                    $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                    mail($to,$subject,$msg,$headers);
                }

                $user->balance += $deposit->amount;
                $user->save();

                $trans = new AppTransaction();
                $trans->email = $user->email;
                $trans->amount = $deposit->amount;
                $trans->type = "Deposit";
                $trans->profit = "plus";
                $trans->txnid = $deposit->deposit_number;
                $trans->user_id = $user->id;
                $trans->save();

                Session::forget('deposit_data');
                Session::forget('paypal_payment_id');
                Session::forget('deposit_number');

                return redirect()->route('user.deposit.create')->with('success','Deposit amount '.$deposit->amount.' (USD) successfully!');
        }

    }
}
