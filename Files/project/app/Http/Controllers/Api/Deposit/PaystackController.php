<?php

namespace App\Http\Controllers\Api\Deposit;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaystackController extends Controller
{
    public function store(Request $request){
        $gs =  Generalsetting::findOrFail(1);

        if($request->currency_code != "NGN")
        {
            return redirect()->back()->with('unsuccess','Please Select NGN Currency For Paystack.');
        }

        $deposit = Deposit::findOrFail($request->deposit_id);
        $deposit->method = $request->method;
        $deposit->txnid = $request->paystack_txn;
        $deposit['status'] = "complete";
        $deposit->update();

        $user = User::findOrFail($deposit->user_id);
        $user->income += $deposit->amount;
        $user->save();

        if($gs->is_smtp == 1)
        {
            $data = [
                'to' => $user->email,
                'type' => "Deposit",
                'cname' => $user->name,
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

        return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('success','Deposit amount ('.$request->amount.') successfully!');
    }
}
