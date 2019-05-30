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