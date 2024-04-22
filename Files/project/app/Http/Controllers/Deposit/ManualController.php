<?php

namespace App\Http\Controllers\Deposit;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use App\Models\Generalsetting;

class ManualController extends Controller
{
    public function store(Request $request){

        $currency = Currency::where('id',$request->currency_id)->first();
        $amountToAdd = $request->amount/$currency->value;

        $deposit = new Deposit();
        $deposit['deposit_number'] = Str::random(12);
        $deposit['user_id'] = auth()->id();
        $deposit['currency_id'] = $request->currency_id;
        $deposit['amount'] = $amountToAdd;
        $deposit['method'] = $request->method;
        $deposit['txnid'] = $request->txn_id4;
        $deposit['status'] = "pending";
        $deposit->save();


        $gs =  Generalsetting::findOrFail(1);
        $user = auth()->user();

        $to = $user->email;
        $subject = 'Deposit';
        $msg = "Dear Customer,<br> Your deposit in process.";

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
            $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
            mail($to,$subject,$msg,$headers);
        }

        return redirect()->route('user.deposit.create')->with('success','Deposit amount '.$request->amount.' ('.$request->currency_code.') successfully!');
    }
}
