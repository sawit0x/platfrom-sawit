<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Generalsetting;
use PHPMailer\PHPMailer\PHPMailer;
use App\Models\Invest;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class ManualController extends Controller
{
    public $gs;

    public function __construct(OrderRepository $orderRepositorty)
    {
        $this->gs = Generalsetting::findOrFail(1);
        $this->orderRepositorty = $orderRepositorty;
    }

    public function store(Request $request){
        $invest_id = $request->invest_id;
        $invest = Invest::findOrFail($invest_id);
        $this->orderRepositorty->apiOrder($request,$invest);

        $user = User::whereId($invest->user_id)->first();
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
        return redirect()->back()->with('message','Invest successfully complete.');
    }
}
