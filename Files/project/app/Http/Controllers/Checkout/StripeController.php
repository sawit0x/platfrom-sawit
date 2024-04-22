<?php

namespace App\Http\Controllers\Checkout;

use Cartalyst\Stripe\Laravel\Facades\Stripe;
use App\Repositories\OrderRepository;
use App\Http\Controllers\Controller;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Transaction;
use App\Models\Referral;
use App\Models\User;
use Carbon\Carbon;
use Validator;
use Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class StripeController extends Controller
{
    public $orderRepositorty;
    public  $allusers = [];

    public function __construct(OrderRepository $orderRepositorty)
    {
        $data = PaymentGateway::whereKeyword('Stripe')->first();
        $paydata = $data->convertAutoData();

        \Stripe\Stripe::setApiKey($paydata['secret']);

        $this->orderRepositorty = $orderRepositorty;
    }

    public function store(Request $request){

        $gs = Generalsetting::findOrFail(1);
        $item_name = $gs->title." Invest";
       
        $item_amount = $request->amount;

        $support = ['USD'];
        if(!in_array($request->currency_code,$support)){
            return redirect()->back()->with('warning','Please Select USD Currency For Stripe.');
        }

        Session::put('request',$request->all());


            $session = \Stripe\Checkout\Session::create([
                "line_items" => [
                    [
                        "quantity" => 1,
                        "price_data" => [
                            "currency" => $request->currency_code,
                            "unit_amount" =>$item_amount*100,
                            "product_data" => [
                                "name" => $gs->title . 'Invest'
                            ]
                        ]
                    ]
                    ],
                'mode' => 'payment',
                "locale" => "auto",
                'success_url' => route('checkout.success', [], true) . "?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => route('checkout.paypal.cancel', [], true),
              ]);
              return redirect($session->url);

            
            
        
       
    }


    public function success(Request $request)
    {

        $user= Auth::user();
        $gs= Generalsetting::first();
        
        $sessionId = $request->get('session_id');

        try{
            
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

        
            if (!$session) {
                throw new NotFoundHttpException;
            }
            $item_number = Str::random(4).time();
            $request = Session::get('request');

            if ($session->payment_status == 'paid'  && $session->status=='complete') {
                
                $addionalData = ['item_number'=>$item_number,'txnid'=>$session->payment_intent,'charge_id'=> $sessionId ];
                $this->orderRepositorty->order($request,'running',$addionalData);

                return redirect()->route('user.invest.history')->with('message','Invest successfully complete.');
            }

        }catch (Exception $e){
            return back()->with('unsuccess', $e->getMessage());
        }

    }
}
