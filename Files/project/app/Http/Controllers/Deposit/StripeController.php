<?php

namespace App\Http\Controllers\Deposit;

use App\Classes\GeniusMailer;
use Cartalyst\Stripe\Laravel\Facades\Stripe;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Stripe\Error\Card;
use Carbon\Carbon;
use Input;
use Redirect;
use URL;
use Validator;
use Config;

class StripeController extends Controller
{
    public function __construct()
    {
        $data = PaymentGateway::whereKeyword('Stripe')->first();
        $paydata = $data->convertAutoData();

        \Stripe\Stripe::setApiKey($paydata['secret']);
    }

    public function store(Request $request){
        $settings = Generalsetting::findOrFail(1);
        
        $item_name = $settings->title." Deposit";
        $item_number = Str::random(4).time();
        $item_amount = $request->amount;
        
        $support = ['USD'];
        if(!in_array($request->currency_code,$support)){
            return redirect()->back()->with('warning','Please Select USD Currency For Stripe.');
        }
        $user = auth()->user();
        $gs = Generalsetting::findOrFail(1);
        Session::put('request',$request->all());

        $session = \Stripe\Checkout\Session::create([
            "line_items" => [
                [
                    "quantity" => 1,
                    "price_data" => [
                        "currency" => $request->currency_code,
                        "unit_amount" =>$item_amount*100,
                        "product_data" => [
                            "name" => $gs->title . 'Deposit'
                        ]
                    ]
                ]
                ],
            'mode' => 'payment',
            "locale" => "auto",
            'success_url' => route('user.deposit.success', [], true) . "?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => route('checkout.paypal.cancel', [], true),
          ]);
          return redirect($session->url);

        }

        public function success(Request $request)
        {
            $deposit = new Deposit();
            $user= Auth::user();
            $gs= Generalsetting::first();
            $item_number = Str::random(4).time();
            
            $item_amount = $request['amount'];
            $sessionId = $request->get('session_id');
           
            try{
                $session = \Stripe\Checkout\Session::retrieve($sessionId);

        
                if (!$session) {
                    throw new NotFoundHttpException;
                }
                $request = Session::get('request');
                if ($session->payment_status == 'paid'  && $session->status=='complete') {
                    $currency = Currency::where('id',$request['currency_id'])->first();
                    $amountToAdd = $request['amount']/$currency->value;

                    $deposit['deposit_number'] = Str::random(12);
                    $deposit['user_id'] = auth()->id();
                    $deposit['currency_id'] = $request['currency_id'];
                    $deposit['amount'] = $amountToAdd;
                    $deposit['method'] = $request['method'];
                    $deposit['txnid'] = $session->payment_intent;
                    $deposit['charge_id'] = $sessionId;
                    $deposit['status'] = "complete";
                    $deposit->save();

                    $gs =  Generalsetting::findOrFail(1);
        
                    $user = auth()->user();
                    $user->balance += $amountToAdd;
                    $user->save();
        
                    $trans = new Transaction();
                    $trans->email = $user->email;
                    $trans->amount = $amountToAdd;
                    $trans->type = "Deposit";
                    $trans->profit = "plus";
                    $trans->txnid = $deposit->deposit_number;
                    $trans->user_id = $user->id;
                    $trans->save();


                    if($gs->is_smtp == 1)
                    {
                        $data = [
                            'to' => $user->email,
                            'type' => "Deposit",
                            'cname' => $user->name,
                            'oamount' => $item_amount,
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

                    return redirect()->route('user.deposit.create')->with('success','Deposit amount '.$request['amount'].' (USD) successfully!');
                }
                
            }catch (Exception $e){
                return back()->with('unsuccess', $e->getMessage());
            }
        
        return back()->with('unsuccess', 'Please Enter Valid Credit Card Informations.');
    }
}
