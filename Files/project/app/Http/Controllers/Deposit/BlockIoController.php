<?php

namespace App\Http\Controllers\Deposit;

use App\Classes\BlockIO;
use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlockIoController extends Controller
{
    public function blockioInvest()
    {
        return view('user.deposit.blockio');
    }

    public function blockiocallback(Request $request)
    {

        $notifyID = $request['notification_id'];
        $amountRec = $request['data']['amount_received'];

        $deposit = Deposit::where('notify_id',$notifyID)->where('status','pending')->first();

            if ($deposit != NULL){

                $data['txnid'] =  $request['data']['txid'];
                $data['status'] = "complete";
                $deposit->update($data);

                $user = auth()->user();

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

                $trans = new Transaction();
                $trans->email = $user->email;
                $trans->amount = $deposit->amount;
                $trans->type = "Deposit";
                $trans->profit = "plus";
                $trans->txnid = $deposit->deposit_number;
                $trans->user_id = $user->id;
                $trans->save();

                dd('SUCCESS');
            }


    }

    function curlGetCall($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec ($ch);
        curl_close ($ch);

        return $data;
    }


    public function deposit(Request $request)
    {
        if($request->amount > 0){

            $methods = $request->method;
             $version = 2;
             $coin = "BTC";
             $my_api_key = '';


             if($methods == "block.io.ltc"){
                $blockinfo    = PaymentGateway::whereKeyword('block.io.ltc')->first();
                $blocksettings= $blockinfo->convertAutoData();
                $coin = "Litecoin";
                $my_api_key = $blocksettings['blockio_api_ltc'];

            }elseif($methods == "block.io.btc"){
                $blockinfo    = PaymentGateway::whereKeyword('block.io.btc')->first();
                $blocksettings= $blockinfo->convertAutoData();
                $coin = "Bitcoin";
                $my_api_key = $blocksettings['blockio_api_btc'];

            }elseif ($methods == "block.io.dgc"){
                $coin = "Dogecoin";
                $blockinfo    = PaymentGateway::whereKeyword('block.io.dgc')->first();
                $blocksettings= $blockinfo->convertAutoData();
                $my_api_key = $blocksettings['blockio_api_dgc'];

            }

            $acc = Auth::user()->id;
            $item_number = Str::random(4).time();;

            $item_amount = $request->amount;
            $currency_code = $request->currency_code;


            $secret = $blocksettings['secret_string'];

            $my_callback_url = route('deposit.blockio.notify');

            $block_io = new BlockIO($my_api_key, $secret, $version);

            $biorate = 1;

            $coin_amount = round($item_amount / $biorate, 8);


            $root_url = 'https://block.io/api/v2/';
            $addObject = $block_io->get_new_address(array());

            $address = $addObject->data->address;


            $notifyObject = $block_io->create_notification(array('type' => 'address', 'address' => $address, 'url' => $my_callback_url));
            $notifyID = $notifyObject->data->notification_id;

            $currency = Currency::where('id',$request->currency_id)->first();
            $amountToAdd = $request->amount/$currency->value;

            $deposit = new Deposit();
            $deposit['deposit_number'] = Str::random(12);
            $deposit['user_id'] = auth()->id();
            $deposit['currency_id'] = $request->currency_id;
            $deposit['amount'] = $amountToAdd;
            $deposit['method'] = $request->method;
            $deposit['coin_amount'] = $coin_amount;
            $deposit['notify_id'] = $notifyID;
            $deposit['status'] = "pending";
            $deposit->save();


            $qrcode_url = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=bitcoin:".$address."?amount=".$coin_amount."&choe=UTF-8";


            Session::put(['address' => $address,'coin' => $coin,'qrcode_url' => $qrcode_url,'amount' => $coin_amount,'currency_value' => $item_amount,'currency_sign' => $request->currency_sign,'accountnumber' => $acc]);

            return redirect()->route('blockio.deposit');

        }
        return redirect()->back()->with('error','Please enter a valid amount.')->withInput();

    }
}
