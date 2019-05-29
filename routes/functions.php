<?php 

error_reporting(0);

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Slim\Views\Twig;
use Intervention\Image\ImageManager;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use Tuupola\Base62;
use App\User;
use App\Email;
use App\Machine;
use App\Formula;
use App\Quote;
use App\QuoteFormula;
use App\QuoteItem;

function convert_machine($ml,$oz,$pulse,$fraction){
    $y1 = (float) $ml / $oz;
    $y = (int) $y1;
    $p1  = $y1 - $y;
    $p2 = $p1 * $pulse;
    $p = (int) $p2;
    $f1 = (float) $p2 - $p;
    $f3 = floor($f1/$fraction)*$fraction;
    return (object)[
        'y' => $y,
        'p' => $p,
        'f' => $f3
    ];
}

function convert_g($ml,$p1,$density,$oz,$pulse,$fraction){
    $y = (float) $ml * $oz;
    $p2 = (float) $ml / $p1; 
    $p = (float) $p2 * $pulse;
    $f = (float) $p2 * $fraction;

    return (float) ($y + $p + $f) * $density;
}

function get_price_rounded($price){
    switch(getenv('APP_PRICE_ROUND')){
        case 'up':
            return ceil($price);
            break;

        case 'down':
            return floor($price);
            break;

        default:
            return round($price,2);
    }
}

function stringInsert($str,$pos,$insertstr){
    if (!is_array($pos))
        $pos=array($pos);

    $offset=-1;
    foreach($pos as $p){
        $offset++;
        $str = substr($str, 0, $p+$offset) . $insertstr . substr($str, $p+$offset);
    }
    return $str;
}

function generate_uuid($id){
    $str = md5(uniqid($id, true));
    $str = stringInsert($str,8,'-');
    $str = stringInsert($str,13,'-');
    $str = stringInsert($str,17,'-');
    return $str;
}

function send_quote_pdf($email,$guid,$subject="Untitled",$html="Text is unset",$user=NULL,$template="template",$debug=0){

    global $container; 
    
    $view = new \Slim\Views\Twig( __DIR__ . '/../' . getenv('APP_PUBLIC') . 'templates', [
        'cache' => false
    ]);

    $view->addExtension(new Twig_Extension_StringLoader());
    $html = $view->fetch("emails/{$template}.html",[
        'html' => $html,
        'app_url' => getenv('APP_URL'),
        'api_url' => $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost(),
        'user' => $user,    
        'guid' => $guid
    ]);

    //if($_SERVER['REMOTE_ADDR'] == '127.0.0.1') return ['status' => 'success'];

    $attachment = \refocus2pdf($guid,'S');

    //Create a new PHPMailer instance
    $mail = new \PHPMailer;
    $mail->IsSMTP(); 
    $mail->SMTPDebug = $debug?:getenv('MAIL_SMTP_DEBUG');
    $mail->SMTPAuth = getenv('MAIL_SMTP_AUTH');
    $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE');
    $mail->Host = getenv('MAIL_SMTP_HOST');
    $mail->Port = getenv('MAIL_SMTP_PORT');
    $mail->CharSet = "UTF-8";
    $mail->IsHTML(true);
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );    
    $mail->Username = getenv('MAIL_SMTP_ACCOUNT');
    $mail->Password = getenv('MAIL_SMTP_PASSWORD');
    $mail->setFrom(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->addReplyTo(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = $html;
    $mail->addAddress($email, $subject);

    //$mail->addAttachment('images/phpmailer_mini.png');
    $mail->AddStringAttachment($attachment, "{$guid}.pdf", 'base64', 'application/pdf');// attachment
    $data = [];

    //send the message, check for errors
    if ( ! $mail->send()) {
        $data['success'] = false;
        $data['message'] = $mail->ErrorInfo;
    } else {
        $data['success'] = true;
    }

    return $data;
}

/* sends email to an account.. */

function send_email($subject,$recipient,$template,$data,$debug = 0){

    global $container; 

    $view = new \Slim\Views\Twig( __DIR__ . '/../' . getenv('APP_PUBLIC') . 'templates', [
        'cache' => false
    ]);

    $code = strtolower(Base62::encode(random_bytes(16)));

    while($container["spot"]->mapper("App\Email")->first(["code" => $code])){
        $code = strtolower(Base62::encode(random_bytes(16)));
    }

    $data['code'] = $code;
    $data['recipient'] = $recipient;
    $data['app_url'] = getenv('APP_URL');
    $data['api_url'] = $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost();

    $html = $view->fetch("emails/{$template}",$data);
    $full_name = $recipient->first_name . ' ' . $recipient->last_name;

    if( strpos($subject,getenv('APP_TITLE')) === false) {
        $subject = getenv('APP_TITLE') . " " . $subject;
    }

    $body = [
        'code' => $code,
        'subject' => $subject,
        'user_id' => $recipient->id,
        'email' => $recipient->email,
        'full_name' => $full_name,
        'content' => $html
    ];

    $email = new Email($body);
    $container["spot"]->mapper("App\Email")->save($email);


    //if($_SERVER['REMOTE_ADDR'] == '127.0.0.1') return ['status' => 'success'];

    //Create a new PHPMailer instance
    $mail = new \PHPMailer;
    $mail->IsSMTP(); 
    $mail->SMTPDebug = $debug?:getenv('MAIL_SMTP_DEBUG');
    $mail->SMTPAuth = getenv('MAIL_SMTP_AUTH');
    $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE');
    $mail->Host = getenv('MAIL_SMTP_HOST');
    $mail->Port = getenv('MAIL_SMTP_PORT');
    $mail->CharSet = "UTF-8";
    $mail->IsHTML(true);
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );    
    $mail->Username = getenv('MAIL_SMTP_ACCOUNT');
    $mail->Password = getenv('MAIL_SMTP_PASSWORD');
    $mail->setFrom(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->addReplyTo(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = \html2text($html);
    $mail->addAddress($recipient->email, $full_name);

    //$mail->addAttachment('images/phpmailer_mini.png');
    $data = [];


    //send the message, check for errors
    if ( ! $mail->send()) {
        $data['status'] =  "error";
        $data['message'] = $mail->ErrorInfo;
    } else {
        $data['status'] = "success";
    }

    return $data;
}

/* sends email with predefined title and content to an account.. */

function send_email_template($template,$recipient,$data,$debug = 0){

    global $container; 

    $view = new \Slim\Views\Twig( __DIR__ . '/../' . getenv('APP_PUBLIC') . 'templates', [
        'cache' => false
    ]);
    
    $view->addExtension(new Twig_Extension_StringLoader());
    $code = strtolower(Base62::encode(random_bytes(16)));

    while($container["spot"]->mapper("App\Email")->first(["code" => $code])){
        $code = strtolower(Base62::encode(random_bytes(16)));
    }

    $subject = getenv($template . '_TITLE');
    $html = getenv($template . '_HTML');
    $data['code'] = $code;
    $data['html'] = $html;
    $data['recipient'] = $recipient;
    $data['app_url'] = getenv('APP_URL');
    $data['api_url'] = $container->request->getUri()->getScheme() . '://' . $container->request->getUri()->getHost();

    $html = $view->fetch('emails/template.html',$data);
    $full_name = $recipient->first_name . ' ' . $recipient->last_name;

    if( strpos($subject,getenv('APP_TITLE')) === false) {
        $subject = getenv('APP_TITLE') . " " . $subject;
    }

    $body = [
        'code' => $code,
        'subject' => $subject,
        'user_id' => $recipient->id,
        'email' => $recipient->email,
        'full_name' => $full_name,
        'content' => $html
    ];

    $email = new Email($body);
    $container["spot"]->mapper("App\Email")->save($email);

    //if($_SERVER['REMOTE_ADDR'] == '127.0.0.1') return ['status' => 'success'];

    //Create a new PHPMailer instance
    $mail = new \PHPMailer;
    $mail->IsSMTP(); 
    $mail->SMTPDebug = $debug?:getenv('MAIL_SMTP_DEBUG');
    $mail->SMTPAuth = getenv('MAIL_SMTP_AUTH');
    $mail->SMTPSecure = getenv('MAIL_SMTP_SECURE');
    $mail->Host = getenv('MAIL_SMTP_HOST');
    $mail->Port = getenv('MAIL_SMTP_PORT');
    $mail->CharSet = "UTF-8";
    $mail->IsHTML(true);
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );    
    $mail->Username = getenv('MAIL_SMTP_ACCOUNT');
    $mail->Password = getenv('MAIL_SMTP_PASSWORD');
    $mail->setFrom(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->addReplyTo(getenv('MAIL_SMTP_ACCOUNT'), getenv('MAIL_FROM_NAME'));
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = \html2text($html);
    $mail->addAddress($recipient->email, $full_name);

    //$mail->addAttachment('images/phpmailer_mini.png');
    $data = [];


    //send the message, check for errors
    if ( ! $mail->send()) {
        $data['status'] =  "error";
        $data['message'] = $mail->ErrorInfo;
    } else {
        $data['status'] = "success";
    }

    return $data;
}

function quote2pdf($uuid,$uid,$output=null,$name=null){

    global $container;    

    $quote = $container["spot"]->mapper("App\Quote")->first([
        'uuid' => $uuid
    ]);

    if(!$quote){
        return false;
    }

    $user = $container["spot"]->mapper("App\User")->first([
        'id' => $uid
    ]);

    if(!defined('FPDF_FONTPATH')){
        define('FPDF_FONTPATH',getenv('FPDF_FONTPATH'));
    }

    $items = $container["spot"]->mapper("App\QuoteItem")->where([            
        'quote_id' => $quote->id
    ]);
                
    $qit = sprintf("%'.08d", $quote->id);

    $datenow = date('j-n-y H:i',time());

    $imgpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'img/';
    $uploadpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'uploads/';

    $logo  = '../public_html/img/Presupuestos-02.png';

    if($user->logo_url && file_exists($uploadpath . basename($user->logo_url))) {
        $logo = '../public_html/uploads/' . basename($user->logo_url);
    }

    // create pdf canvas
    $pdf = new FPDF();
    $pdf->AddFont('Weber-Regular','','Weber-Regular.php');
    $pdf->SetFont('Weber-Regular','',16);
    $pdf->AddPage();
    $pdf->SetLineWidth(0.50);
    $pdf->Line(105,24,105,65);
    $pdf->Line(10,65,200,65);           
    $pdf->Line(10,83,200,83);   
    $pdf->Cell(20,6,'#'.$qit,0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,$datenow,0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Cliente: '.utf8_decode($quote->customer),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Tel.: '.$quote->phone,0,0,'L');
    $pdf->Ln();    
    $pdf->Cell(20,6,'Email: '.$quote->email,0,0,'L');

    foreach($items as $i => $item){
        $pdf->SetXY(140,5 + ($i+1)*6);
        $pdf->Cell(60,6,implode(' ',[utf8_decode($item->title),"x".$item->qty. ' .....',number_format($item->amount, 2, ',', '.')."ARS"]),0,0,'R');
    }

    $pdf->SetXY(140,40);
    $pdf->Cell(60,6,'SUBTOTAL: '.number_format($quote->subtotal, 2, ',', '.')."ARS",0,0,'R');
    $pdf->SetXY(140,50);
    $pdf->Cell(60,15,'TOTAL: '.number_format($quote->total, 2, ',', '.')."ARS",1,1,'C');
    $pdf->Ln(6);
    $pdf->Cell(20,5,'Firma: ',0,0,'L');
    $pdf->Cell(70);
    $pdf->Cell(20,5,utf8_decode('Aclaración: '),0,0,'L');
    $pdf->Ln();
    $pdf->SetLineWidth(0,1);
    $pdf->Line(10,137,200,137);
    
    //recibo inferior

    $pdf->Ln(20);
    $pdf->Image($logo,10,null,190);

    $pdf->Ln(20);
    $pdf->SetLineWidth(0.50);
    $pdf->Line(105,160,105,206);        
    $pdf->Line(10,206,200,206);         
    $pdf->Line(10,224,200,224);
    $pdf->Cell(20,6,'#'.$qit,0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,$datenow,0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Cliente: '.utf8_decode($quote->customer),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Tel.: '.$quote->phone,0,0,'L');
    $pdf->Ln();    
    $pdf->Cell(20,6,'Email: '.$quote->email,0,0,'L');

    foreach($items as $i => $item){
        $pdf->SetXY(140,140 + ($i+1)*6);
        $pdf->Cell(60,6,implode(' ',[utf8_decode($item->title),"x".$item->qty.' .....',number_format($item->amount, 2, ',', '.')."ARS"]),0,0,'R');
    }

    $pdf->SetXY(140,181);
    $pdf->Cell(60,6,'SUBTOTAL: '.number_format($quote->subtotal, 2, ',', '.')."ARS",0,0,'R');
    $pdf->SetXY(140,191);
    $pdf->Cell(60,15,'TOTAL: '.number_format($quote->total, 2, ',', '.')."ARS",1,1,'C');
    $pdf->Ln(6);
    $pdf->Cell(20,5,'Firma: ',0,0,'L');
    $pdf->Cell(70);
    $pdf->Cell(20,5,utf8_decode('Aclaración: '),0,0,'L');
    $pdf->Ln();
    $pdf->SetLineWidth(0,1);
    $pdf->Line(10,137,200,137);

    return $pdf->Output($output, $name);
}

function color2pdf($uuid,$mid,$uid,$output=null,$name=null){

    global $container;    

    $quote = $container["spot"]->mapper("App\Quote")->first([
        'uuid' => $uuid
    ]);

    if(!$quote){
        return false;
    }

    if(!defined('FPDF_FONTPATH')){
        define('FPDF_FONTPATH',getenv('FPDF_FONTPATH'));
    }

    $datenow = date('j-n-y H:i',time());

    $color = $container["spot"]->mapper("App\Color")->first([
        'user_id' => [1,$uid],
        'id' => $quote->color_id
    ]);

    $machine = $container["spot"]->mapper("App\Machine")->first([
        'id' => $mid
    ]);     

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($machine, new Machine);
    $mac = $fractal->createData($resource)->toArray()['data'];

    /**/
    $auto = strstr(strtolower($machine->type->title),"auto");
    $manual = strstr(strtolower($machine->type->title),"manual");
    $colorants = [];

    $formulas = $container["spot"]->mapper("App\QuoteFormula")->where([            
        'quote_id' => $quote->id
    ]);

    foreach ($formulas as $formula) {

        $ml = (float) $formula->amount / $formula->density;

        if($auto) {
            $colorants[]= [
                'title' => utf8_decode(implode(' ',[$formula->description,$formula->code])),
                'qty' => implode(' ',[number_format($formula->amount * $quote->kg / $quote->texture->colorant_unit,2).'g',number_format($ml * $quote->kg / $quote->texture->colorant_unit,2).'ml'])
            ];
        } elseif ($manual) {
            $values = \convert_machine(number_format($ml * $quote->kg / $quote->texture->colorant_unit,2),$machine->ounce->ml,$machine->pulse->quantity,$machine->fraction->quantity);
            $colorants[]= [
                'title' => utf8_decode(implode(' ',[$formula->description,$formula->code])),
                'qty' => implode(' ',[$values->y.'Y',$values->p.'P',$values->f.'F'])
            ];
        }
    }


    // create pdf canvas
    $pdf = new FPDF();
    $pdf->AddFont('Weber-Regular','','Weber-Regular.php');
    $pdf->SetFont('Weber-Regular','',16);

    $imgpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'img/';
    $uploadpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'uploads/';

    $logo  = '../public_html/img/Presupuestos-02.png';

    if($user->logo_url && file_exists($uploadpath . basename($user->logo_url))) {
        $logo = '../public_html/uploads/' . basename($user->logo_url);
    }

    $pdf->AddPage();
    $pdf->Ln(20);
    $pdf->Image('../public_html/img/Presupuestos-02.png',10,null,190);
    $pdf->Ln(20);
    $pdf->Cell(20,6,$datenow,0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Cliente: '.utf8_decode($quote->customer),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Tel.: '.$quote->phone,0,0,'L');
    $pdf->Ln();    
    $pdf->Cell(20,6,'Email: '.$quote->email,0,0,'L');
    $pdf->Ln();    
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Cell(20,6,'Color: '.utf8_decode($quote->colors),0,0,'L');

    if(strlen(trim($quote->comments))){
        $pdf->Ln();    
        $pdf->Cell(20,6,'Observaciones: '.utf8_decode($quote->comments),0,0,'L');
    }

    foreach($colorants as $i => $item){
        $pdf->SetXY(140,140 + ($i+1)*6);
        $pdf->Cell(60,6,implode(' ',[$item['title'].' .....',$item['qty']]),0,0,'R');
    }

    return $pdf->Output($output, $name);
}

function color3pdf($uuid,$macid,$uid,$output=null,$name=null){

    global $container;    

    $quote = $container["spot"]->mapper("App\Quote")->first([
        'uuid' => $uuid
    ]);

    if(!$quote){
        return false;
    }

    if(!defined('FPDF_FONTPATH')){
        define('FPDF_FONTPATH',getenv('FPDF_FONTPATH'));
    }

    $datenow = date('j-n-y H:i',time());

    $color = $container["spot"]->mapper("App\Color")->first([
        'user_id' => [1,$uid],
        'id' => $quote->color_id
    ]);

    $machine = $container["spot"]->mapper("App\Machine")->first([
        'id' => $macid
    ]);     

    $user = $container["spot"]->mapper("App\User")->first([
        'id' => $uid
    ]);

    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($machine, new Machine);
    $mac = $fractal->createData($resource)->toArray()['data'];

    /**/
    $auto = strstr(strtolower($machine->type->title),"auto");
    $manual = strstr(strtolower($machine->type->title),"manual");
    $ths = $auto ? ['COLORANTES','G','ML'] : ['COLORANTES','Y','PULSOS','FRACCIÓN'];
    $colorants = [];

    $formulas = $container["spot"]->mapper("App\QuoteFormula")->where([            
        'quote_id' => $quote->id
    ]);

    foreach ($formulas as $formula) {

        $ml = (float) $formula->amount / $formula->density;
        if($auto) {
            $colorants[]= (object)[
                'code' => $formula->code,
                'values' => (object)[
                    'g' => number_format($formula->amount * $quote->kg / $quote->texture->colorant_unit,2),
                    'ml' => number_format($ml * $quote->kg / $quote->texture->colorant_unit,2)
                ]
            ];
        } elseif ($manual) {
            $values = \convert_machine(number_format($ml * $quote->kg / $quote->texture->colorant_unit,2),$machine->ounce->ml,$machine->pulse->quantity,$machine->fraction->quantity);
            $colorants[]= (object)[
                'code' => $formula->code,
                'values' => (object) $values
            ];
        }
    }

    // clear if necessary

    foreach($colorants as $i => $colorant){
        $hasanyvalue = 0;
        foreach ($colorant->values as $amount) {
            if($amount > 0){
                $hasanyvalue = 1;
            }
        }

        if(!$hasanyvalue){
            unset($colorants[$i]);
        }
    }

    $colorants = array_values($colorants);

    // create pdf canvas
    $pdf = new FPDF();
    $pdf->AddFont('Weber-Medium','','Weber-Medium.php');
    $pdf->AddFont('Weber-Regular','','Weber-Regular.php');
    $pdf->SetFont('Weber-Regular','',16);

    $imgpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'img/';
    $uploadpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'uploads/';

    $logo  = '../public_html/img/Presupuestos-02.png';

    if($user->logo_url && file_exists($uploadpath . basename($user->logo_url))) {
        $logo = '../public_html/uploads/' . basename($user->logo_url);
    }

    $pdf->AddPage();
    $pdf->Ln(20);
    $pdf->Image($logo,10,null,190);
    $pdf->Ln(20);
    $pdf->Cell(20,6,$datenow,0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Cliente: '.utf8_decode($quote->customer),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Tel.: '.$quote->phone,0,0,'L');
    $pdf->Ln();    
    $pdf->Cell(20,6,'Email: '.$quote->email,0,0,'L');
    $pdf->Ln();    
    //$pdf->SetFont('Weber-Medium','',16);
    $pdf->Cell(20,6,'Textura: '.utf8_decode($quote->textures),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Color: '.utf8_decode($quote->colors),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Envase: '.utf8_decode($quote->packs),0,0,'L');
    $pdf->Ln();
    $pdf->Cell(20,6,'Baldes: '.utf8_decode($quote->qty),0,0,'L');

    if(strlen(trim($quote->comments))){
        $pdf->Ln();    
        $pdf->MultiCell(160,6,'Observaciones: '.utf8_decode($quote->comments));
    }   

    $pdf->SetFont('Weber-Medium','',15);
    $cellw = 48;
    $celly = 150;
    foreach($ths as $i => $th){
        $x = 10 + ($i*$cellw);
        $y = $celly;
        $pdf->SetXY($x,$y);
        $pdf->Image('../fpdf/img/TH.png',$x,null,$cellw);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetXY($x,$y);
        $pdf->Cell($cellw,10,utf8_decode(strtoupper($th)),0,0,'C');
    }

    foreach($colorants as $j => $item){

        $x = 10;
        $y = $celly + 10 + ($j*10);
        $pdf->SetXY($x,$y);
        $pdf->Image('../fpdf/img/' . $item->code . '.png',$x,null,$cellw);

        if(in_array($item->code,['AXX','KX'])){
            $pdf->SetTextColor(0,0,0);    
        } else {
            $pdf->SetTextColor(255,255,255);
        }

        $pdf->SetXY($x,$y);
        $pdf->Cell($cellw,10,$item->code,0,0,'C');

        foreach ($item->values as $k => $value) {
            $x+= ($k+1)*$cellw;
            $pdf->SetXY($x,$y);
            $pdf->Image('../fpdf/img/TD.png',$x,null,$cellw);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetXY($x,$y);
            $pdf->Cell($cellw,10,$value,0,0,'C');
        }
    }


    return $pdf->Output($output, $name);
}


function formula3pdf($colid,$macid,$uid,$output=null,$name=null){

    global $container;    

    $color = $container["spot"]->mapper("App\Color")->first([
        'user_id' => [1,$uid],
        'id' => $colid
    ]);

    if(!$color){
        return false;
    }

    if(!defined('FPDF_FONTPATH')){
        define('FPDF_FONTPATH',getenv('FPDF_FONTPATH'));
    }

    $datenow = date('j-n-y H:i',time());

    $machine = $container["spot"]->mapper("App\Machine")->first([
        'id' => $macid
    ]);     

    $user = $container["spot"]->mapper("App\User")->first([
        'id' => $uid
    ]);


    $fractal = new Manager();
    $fractal->setSerializer(new DataArraySerializer);
    $resource = new Item($machine, new Machine);
    $mac = $fractal->createData($resource)->toArray()['data'];

    /**/
    $auto = strstr(strtolower($machine->type->title),"auto");
    $manual = strstr(strtolower($machine->type->title),"manual");
    $ths = $auto ? ['COLORANTES','G','ML'] : ['COLORANTES','Y','PULSOS','FRACCIÓN'];
    $colorants = [];

    $formulas = $container["spot"]->mapper("App\Formula")
        ->where(['color_id' => $colid])
        ->where(['unit' => 'g']);

    foreach ($formulas as $formula) {

        $ml = (float) $formula->amount / $formula->colorant->density;
        if($auto) {
            $colorants[]= (object)[
                'code' => $formula->colorant->code,
                'values' => (object)[
                    'g' => number_format($formula->amount,2),
                    'ml' => number_format($ml,2)
                ]
            ];
        } elseif ($manual) {
            $values = \convert_machine($ml,$machine->ounce->ml,$machine->pulse->quantity,$machine->fraction->quantity);
            $colorants[]= (object)[
                'code' => $formula->colorant->code,
                'values' => (object) $values
            ];
        }
    }

    // clear if necessary

    foreach($colorants as $i => $colorant){
        $hasanyvalue = 0;
        foreach ($colorant->values as $amount) {
            if($amount > 0){
                $hasanyvalue = 1;
            }
        }

        if(!$hasanyvalue){
            unset($colorants[$i]);
        }
    }

    $colorants = array_values($colorants);

    // create pdf canvas
    $pdf = new FPDF();
    $pdf->AddFont('Weber-Medium','','Weber-Medium.php');
    $pdf->AddFont('Weber-Regular','','Weber-Regular.php');
    $pdf->SetFont('Weber-Regular','',16);

    $imgpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'img/';
    $uploadpath = __DIR__ . '/../' . getenv('APP_PUBLIC') . 'uploads/';

    $logo  = '../public_html/img/Presupuestos-02.png';

    if($user->logo_url && file_exists($uploadpath . basename($user->logo_url))) {
        $logo = '../public_html/uploads/' . basename($user->logo_url);
    }

    $pdf->AddPage();
    $pdf->Ln(20);
    $pdf->Image($logo,10,null,190);
    $pdf->Ln(20);
    $pdf->Cell(20,6,$datenow,0,0,'L');

    $pdf->Ln();
    $pdf->Cell(20,6,utf8_decode('Fórmula propia: ') . utf8_decode($color->title),0,0,'L');

    $pdf->SetFont('Weber-Medium','',15);
    $cellw = 48;
    $celly = 150;

    foreach($ths as $i => $th){
        $x = 10 + ($i*$cellw);
        $y = $celly;
        $pdf->SetXY($x,$y);
        $pdf->Image('../fpdf/img/TH.png',$x,null,$cellw);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetXY($x,$y);
        $pdf->Cell($cellw,10,utf8_decode(strtoupper($th)),0,0,'C');
    }

    foreach($colorants as $j => $item){

        $x = 10;
        $y = $celly + 10 + ($j*10);
        $pdf->SetXY($x,$y);
        $pdf->Image('../fpdf/img/' . $item->code . '.png',$x,null,$cellw);

        if(in_array($item->code,['AXX','KX'])){
            $pdf->SetTextColor(0,0,0);    
        } else {
            $pdf->SetTextColor(255,255,255);
        }

        $pdf->SetXY($x,$y);
        $pdf->Cell($cellw,10,$item->code,0,0,'C');

        foreach ($item->values as $k => $value) {
            $x+= ($k+1)*$cellw;
            $pdf->SetXY($x,$y);
            $pdf->Image('../fpdf/img/TD.png',$x,null,$cellw);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetXY($x,$y);
            $pdf->Cell($cellw,10,$value,0,0,'C');
        }
    }


    return $pdf->Output($output, $name);
}

function log2file($path, $data, $mode="a"){
   $fh = fopen($path, $mode) or die($path);
   fwrite($fh,$data . "\n");
   fclose($fh);
   chmod($path, 0777);
}

function login_redirect($data){
    \log2file( __DIR__ . "/../logs/ecma-" . date('Y-m-d') . ".log",json_encode($data)); 
    return "<script>location.href = '" . \login_redirect_url($data) . "';</script>";
}

function login_redirect_url($data){
    return getenv('APP_URL') . "/opener?token=" . json_encode($data) . "&url=" . getenv('APP_REDIRECT_AFTER_LOGIN');
}

function strToHex($string){
    $hex = '';
    for ($i=0; $i<strlen($string); $i++){
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0'.$hexCode, -2);
    }
    return strToUpper($hex);
}

function hexToStr($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}

function set_username($intended){

    global $container; 

    if($intended == ""){
        $intended = strtolower(Base62::encode(random_bytes(8)));
    }

    $j=0;
    $username = $intended;

    while($container["spot"]->mapper("App\User")->first(["username" => \slugify($username)])){
        $j++;
        $username = $intended . $j;
    }

    return \slugify($username);
}

function slugify($text){

    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return strtolower(Base62::encode(random_bytes(8)));
    }

    return $text;
}

function set_token($user){

    global $container;

    $now = new DateTime();
    $future = new DateTime("now +" . getenv('APP_JWT_EXPIRATION'));
    $jti = Base62::encode(random_bytes(16));

    $payload = [
        "uid" => $user->id,
        "rid" => $user->role_id,
        "iat" => $now->getTimeStamp(),
        "exp" => $future->getTimeStamp(),
        "jti" => $jti
    ];

    return JWT::encode($payload, getenv("APP_JWT_SECRET"), "HS256");
}

function register_if_not_exists($email){

    global $container;

    if(!strlen($email)) return false;

    $user = $container["spot"]->mapper("App\User")->first([
        "email" => $email
    ]);

    $fakenames = ['Fresh','Hot','Flamming','Bumpy'];
    $fakesurenames = ['Feeling','Splendorous','Jackets'];

    if(!$user){
        $password = strtolower(Base62::encode(random_bytes(10)));
        $emaildata['readable_password'] = $password;
        $emaildata['email_encoded'] = Base62::encode($email);
        $hash = sha1($password . getenv('APP_HASH_SALT'));
        $user = new User([
            "email" => $email,
            "password" => $hash,
            "first_name" => "User"
        ]);

        /*
        \log2file( __DIR__ . "/../logs/password-" . date('Y-m-d') . ".log",json_encode([
            'hash' => $hash,
            'salt' => getenv('APP_HASH_SALT'),
            'password' => $password
        ])); */

        $container["spot"]->mapper("App\User")->save($user);

        \send_email("Welcome to " . getenv('APP_TITLE'),$user,'welcome.html',$emaildata);
    }

    return $user;
}

function html2text($Document) {
    $Rules = array ('@<style[^>]*?>.*?</style>@si',
                    '@<script[^>]*?>.*?</script>@si',
                    '@<[\/\!]*?[^<>]*?>@si',
                    '@([\r\n])[\s]+@',
                    '@&(quot|#34);@i',
                    '@&(amp|#38);@i',
                    '@&(lt|#60);@i',
                    '@&(gt|#62);@i',
                    '@&(nbsp|#160);@i',
                    '@&(iexcl|#161);@i',
                    '@&(cent|#162);@i',
                    '@&(pound|#163);@i',
                    '@&(copy|#169);@i',
                    '@&(reg|#174);@i',
                    '@&#(d+);@e'
             );
    $Replace = array ('',
                      '',
                      '',
                      '',
                      '',
                      '&',
                      '<',
                      '>',
                      ' ',
                      chr(161),
                      chr(162),
                      chr(163),
                      chr(169),
                      chr(174),
                      'chr()'
                );
  return preg_replace($Rules, $Replace, $Document);
}

function human_timespan_short($time){

    $str = "";
    $diff = time() - $time; // to get the time since that moment
    $diff = ($diff<1)? $diff*-1 : $diff;

    $Y = date('Y', $time);
    $n = date('n', $time);
    $w = date('w', $time);
    $wdays = ['dom','lun','mar','mié','jue','sáb'];

    if($diff < 86400){
        $str = date('H:i',$time); 
    } elseif($diff < 604800){
        $str = $wdays[$w];
    } elseif($Y <> date('Y')){
        $str = date('j/n/y',$time);  
    } elseif($n <> date('n')){
        $str = date('j/n',$time); 
    } else {
        $str = date('j',$time);  
    }

    return $str;
}

function human_timespan($time){

    $time = time() - $time; // to get the time since that moment
    $time = ($time<1)? $time*-1 : $time;
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'min',
        1 => 'sec'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.($numberOfUnits>1)?'s':'';
    }
}