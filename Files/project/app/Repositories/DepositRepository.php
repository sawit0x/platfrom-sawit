<?php
namespace App\Repositories;

use App\Classes\GeniusMailer;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Referral;
use App\Models\ReferralBonus;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class DepositRepository
{
    public $gs;
    public  $allusers = [];
    public $referral_ids = [];

    public function __construct()
    {
        $this->gs = Generalsetting::findOrFail(1);
    }

    public function deposit($request,$status,$addionalData){
        $currency = Currency::whereId($request->currency_id)->first();
        $amountToAdd = $request->amount/$currency->value;

        $deposit = new Deposit();
        $deposit['user_id'] = auth()->user()->id;
        $deposit['currency_id'] = $request->currency_id;
        $deposit['amount'] = $amountToAdd ;
        $deposit['method'] = $request->method;

        if(isset($addionalData['item_number'])){
            $deposit['deposit_number'] = $addionalData['item_number'];
        }

        if(isset($addionalData['charge_id'])){
            $deposit['charge_id'] = $addionalData['charge_id'];
        }

        if($status == 'complete'){
            $deposit['status'] = "complete";
        }else{
            $deposit['status'] = "pending";
        }

        $deposit->save();

        if($status == 'complete'){
            $this->callAfterOrder($request,$deposit);
        }
    }

    public function OrderFromSession($request,$status,$addionalData){
        $input = Session::get('input_data');
        $order = new Invest();

        $plan = Plan::whereId($input['plan_id'])->first();

        $order['transaction_no'] = $addionalData['txnid'];
        $order['user_id'] = $input['user_id'];
        $order['plan_id'] = $plan->id;
        $order['currency_id'] = $input['currency_id'];
        $order['method'] = $input['method'];

        if($input['currency_id']){
            $currencyValue = Currency::where('id',$input['currency_id'])->first();
            $order['amount'] = $input['amount']/$currencyValue->value;
            $profitAmount = ($request->amount * $plan->profit_percentage)/100;
            $order['profit_amount'] = $profitAmount/$currencyValue->value;
        }

        if($plan->lifetime_return){
            $order['lifetime_return'] = 1;
        }

        if($plan->captial_return){
            $order['capital_back'] = 1;
            $order['profit_repeat'] = 0;
        }

        $order['status'] = 1;
        $order['payment_status'] = "completed";
        $order['profit_time'] = Carbon::now()->addHours($plan->schedule_hour);
        $order->save();

        if($status == 'complete'){
            $this->callAfterOrder($request,$order);
        }
    }

    public function callAfterOrder($request,$deposit){
        $this->createTransaction($deposit);
        $this->sendMail($deposit);
    }


    public function createTransaction($deposit){
        $user = User::whereId($deposit->user_id)->first();
        
        $trans = new Transaction();
        $trans->email = $user->email;
        $trans->amount = $deposit->amount;
        $trans->type = "Deposit";
        $trans->profit = "plus";
        $trans->txnid = $deposit->deposit_number;
        $trans->user_id = $user->id;
        $trans->save();
    }


    public function sendMail($deposit){
        $user = User::whereId($deposit->user_id)->first();

        if($this->gs->is_smtp == 1)
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
            $headers = "From: ".$this->gs->from_name."<".$this->gs->from_email.">";
            mail($to,$subject,$msg,$headers);
        }
    }

}
