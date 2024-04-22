<?php

namespace App\Http\Controllers\Api\Deposit;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use App\Models\Generalsetting;
use App\Models\User;

class ManualController extends Controller
{
    public function store(Request $request){

        $deposit = Deposit::findOrFail($request->deposit_id);
        $deposit->method = $request->method;
        $deposit->txnid = $request->txn_id4;
        $deposit->status = 'pending';
        $deposit->update();


        $gs =  Generalsetting::findOrFail(1);
        $user = User::findOrFail($deposit->user_id);

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
            $mailer->sendPhpMailer($data);
        }
        else
        {
            $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
            mail($to,$subject,$msg,$headers);
        }

        return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('success','Deposit amount '.$request->amount.' ('.$request->currency_code.') successfully!');
    }
}
