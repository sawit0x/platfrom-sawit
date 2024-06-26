<?php

namespace App\Http\Controllers\Checkout;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\ReferralBonus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Str;

class ManualController extends Controller
{
    public $gs;
    public  $allusers = [];
    public $referral_ids = [];

    public function __construct()
    {
        $this->gs = Generalsetting::findOrFail(1);
    }

    public function store(Request $request){

        $item_name = $this->gs->title." Invest";
        $item_number = Str::random(4).time();
        $item_amount = $request->amount;

        $order = new Invest();

        $plan = Plan::whereId($request->plan_id)->first();
        $order['transaction_no'] = $item_number;

        $order['user_id'] = $request->user_id;
        $order['plan_id'] = $plan->id;
        $order['currency_id'] = $request->currency_id;
        $order['method'] = $request->method;

        if($request->currency_id){
            $currencyValue = Currency::where('id',$request->currency_id)->first();
            $order['amount'] = $request->amount/$currencyValue->value;
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

        $order['payment_status'] = "pending";
        $order['status'] = 0;

        $order['profit_time'] = Carbon::now()->addHours($plan->schedule_hour);
        $order['txnid'] = $request->txn_id4;
        $order->save();

        $user = User::whereId($order->user_id)->first();
        $this->gs = Generalsetting::findOrFail(1);

        $to = $user->email;
        $subject = 'Invest';
        $msg = "Dear Customer,<br> Your invest in process.";

        if($this->gs->is_smtp == 1)
        {

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = $this->gs->smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $this->gs->smtp_user;
                $mail->Password   = $this->gs->smtp_pass;
                if ($this->gs->smtp_encryption == 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                $mail->Port       = $this->gs->smtp_port;
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($this->gs->from_email, $this->gs->from_name);
                $mail->addAddress($user->email, $user->name);
                $mail->addReplyTo($this->gs->from_email, $this->gs->from_name);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $msg;
                $mail->send();
            } catch (Exception $e) {

            }
        }
        else
        {
            $headers = "From: ".$this->gs->from_name."<".$this->gs->from_email.">";
            mail($to,$subject,$msg,$headers);
        }
        return redirect()->route('user.invest.history')->with('message','Invest successfully complete.');
    }

}
